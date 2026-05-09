<?php
/**
 * File: AuthController.php
 * Layer: Controller
 * Module: Authentication
 * System: University Web Applications System B
 *
 * Description:
 * Handles user authentication workflows: login, registration, password reset,
 * and referral code generation. Manages session creation and user validation.
 *
 * Functions:
 * - login() → validates credentials and returns user with redirect
 * - register() → creates new user account with unique referral code
 * - resetPassword() → initiates password reset flow via email
 * - generateUniqueReferralCode() → creates unique code for referral system
 * - getPostLoginRedirect() → determines redirect path based on user role/profile
 *
 * Security:
 * - PDO prepared statements for all database queries
 * - Password hashing using standard algorithms
 * - Session regeneration on login
 * - Referral code uniqueness enforcement
 * - Email verification via PHPMailer
 *
 * Used By:
 * - login.php
 * - register.php
 * - forgot_password.php
 *
 * Author:Pelagia Koniotaki & Antriani Theofanous 
 * Date: 2026
 */

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../middleware/BanGuard.php";

$autoloadPath = __DIR__ . "/../../vendor/autoload.php";
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
use Dotenv\Dotenv;

date_default_timezone_set('Europe/Athens');

class AuthController
{
    private PDO $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    private function generateUniqueReferralCode(): string
    {
        do {
            $referralCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

            $refCheck = $this->conn->prepare(
                "SELECT user_id FROM users WHERE referral_code = :rc LIMIT 1"
            );
            $refCheck->execute([":rc" => $referralCode]);
        } while ($refCheck->fetch(PDO::FETCH_ASSOC));

        return $referralCode;
    }

    private function loadEnvironment(): void
    {
        $envPath = __DIR__ . "/../../.env";

        if (!class_exists(Dotenv::class) || !file_exists($envPath)) {
            return;
        }

        $dotenv = Dotenv::createImmutable(__DIR__ . "/../../");
        $dotenv->safeLoad();
    }

    private function getPostLoginRedirect(array $user): string
    {
        if (($user["role"] ?? "") === "admin") {
            return "/student/admin_dashboard.php";
        }

        $stmt = $this->conn->prepare(
            "SELECT university, year
             FROM users
             WHERE user_id = :id
             LIMIT 1"
        );

        $stmt->execute([
            ":id" => $user["user_id"]
        ]);

        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        $university = trim((string) ($profile["university"] ?? ""));
        $year = trim((string) ($profile["year"] ?? ""));

        if (!$profile || $university === "" || $year === "") {
            return "/student/profile_setup.php";
        }

        return "/student/posts.php";
    }

    // REGISTER
    public function register(
        string $username,
        string $email,
        string $password,
        ?string $inputReferralCode = null
    ): array {
        $username = trim($username);
        $email = trim($email);
        $inputReferralCode = $inputReferralCode ? trim($inputReferralCode) : null;
        // έλεγχος για κενά πεδία
        if ($username === "" || $email === "" || $password === "") {
            return ["ok" => false, "message" => "Συμπλήρωσε όλα τα πεδία."];
        }

        // ελεγχος αν υπαρχει ηδη ο χρηστης
        $check = $this->conn->prepare(
            "SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1"
        );
        //τωρα δίνουμε πραγματικές τιμές και για τα δύο placeholders για εκτελεση query
        $check->execute([":u" => $username, ":e" => $email]);
        // αν υπάρχει διπλώτητα 
        if ($check->fetch()) {
            return ["ok" => false, "message" => "Το username ή το email υπάρχει ήδη."];
        }

        try {
            // ξεκινάμε transaction για ασφάλεια
            $this->conn->beginTransaction();

            $referrerUserId = null;

            // αν εδωσε referral code το τσεκαρουμε
            if (!empty($inputReferralCode)) {
                $refStmt = $this->conn->prepare(
                    "SELECT user_id FROM users WHERE referral_code = :rc LIMIT 1"
                );
                $refStmt->execute([":rc" => $inputReferralCode]);

                $referrer = $refStmt->fetch(PDO::FETCH_ASSOC);

                if (!$referrer) {
                    $this->conn->rollBack();
                    return ["ok" => false, "message" => "Λάθος referral code."];
                }

                $referrerUserId = $referrer["user_id"];
            }

            // φτιαχνουμε μοναδικο ref code για τον νεο user
            $referralCode = $this->generateUniqueReferralCode();
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $newUserTokens = 0;

            // insert του χρηστη
            $stmt = $this->conn->prepare(
                "INSERT INTO users
                 (username, email, password, role, token_balance, referral_code, referred_by)
                 VALUES (:u, :e, :p, 'user', :tb, :rc, :rb)"
            );

            $stmt->execute([
                ":u" => $username,
                ":e" => $email,
                ":p" => $hashed,
                ":tb" => $newUserTokens,
                ":rc" => $referralCode,
                ":rb" => $referrerUserId
            ]);

            // αν χρησιμοποιηθηκε referral δινουμε tokens στον παλιο user
            if ($referrerUserId !== null) {
                $updateRef = $this->conn->prepare(
                    "UPDATE users
                     SET token_balance = token_balance + 10
                     WHERE user_id = :id"
                );
                $updateRef->execute([":id" => $referrerUserId]);

                $logTx = $this->conn->prepare(
                    "INSERT INTO transactions (user_id, token_charge, timestamp)
                     VALUES (:id, :charge, NOW())"
                );

                $logTx->execute([
                    ":id" => $referrerUserId,
                    ":charge" => 10
                ]);
            }

            // commit
            $this->conn->commit();

            return [
                "ok" => true,
                "message" => "Έτοιμο! Μπορείς να κανεις login."
            ];
        } catch (Throwable $e) {
            // αν κατι παει στραβα, rollback
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();    //βαση επαναφέρεται στην κατάσταση πριν την εγγραφή, και ακυρώνονται όλες οι εντολές που εκτελέστηκαν μέσα στο transaction
            }

            return [
                "ok" => false,
                "message" => "Κατι πηγε στραβα, ξαναπροσπαθησε."
            ];
        }
    }

    // LOGIN
    public function login(string $username, string $password): array
    {
        $username = trim($username);

        $selectFields = "user_id, username, password, role";
        if (hasBanColumns()) {
            $selectFields .= ", is_banned, ban_reason";
        }

        $stmt = $this->conn->prepare("
            SELECT {$selectFields}
            FROM users
            WHERE username = :u
            LIMIT 1
        ");
        $stmt->execute([":u" => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user["password"])) {
            return ["ok" => false, "message" => "Λάθος username ή password."];
        }

        if ((int) ($user["is_banned"] ?? 0) === 1) {
            return [
                "ok" => false,
                "message" => trim((string) ($user["ban_reason"] ?? "")) !== ""
                    ? (string) $user["ban_reason"]
                    : getBannedAccountMessage()
            ];
        }

        $authenticatedUser = [
            "user_id" => $user["user_id"],
            "username" => $user["username"],
            "role" => $user["role"]
        ];

        return [
            "ok" => true,
            "user" => $authenticatedUser,
            "redirect" => $this->getPostLoginRedirect($authenticatedUser)
        ];
    }

    // request password reset
    public function requestPasswordReset(string $email): array
    {
        $this->loadEnvironment();
        $email = trim($email);

        if ($email === "") {
            return ["ok" => false, "message" => "Χρειάζομαι το email σου."];
        }

        $stmt = $this->conn->prepare(
            "SELECT user_id FROM users WHERE email = :e LIMIT 1"
        );
        $stmt->execute([":e" => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [
                "ok" => true,
                "message" => "If the email exists, we sent you a link."
            ];
        }

        $rawToken = bin2hex(random_bytes(32)); // αυτο πηγαινει στο email
        $hashedToken = hash("sha256", $rawToken); // αυτο στη βαση
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $update = $this->conn->prepare(
            "UPDATE users
             SET reset_token = :t,
                 reset_expires = :x
             WHERE user_id = :id"
        );

        $update->execute([
            ":t" => $hashedToken,
            ":x" => $expires,
            ":id" => $user["user_id"]
        ]);

        $resetLink = "https://cei328.live/student/reset_password.php?token=" . $rawToken;

        try {
            if (!class_exists(PHPMailer::class)) {
                return [
                    "ok" => false,
                    "message" => "Email setup is still missing on this project."
                ];
            }

            $mailConfig = require __DIR__ . "/../config/mail.php";
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $mailConfig["host"];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig["username"];
            $mail->Password = $mailConfig["password"];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $mailConfig["port"];

            $mail->setFrom($mailConfig["username"], "University System");
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $mail->Body = "Καλως ηρθες! Πατα εδω για reset: <a href='{$resetLink}'>{$resetLink}</a><br>Ισχυει για 1 ωρα.";

            $mail->send();
        } catch (MailException $e) {
            error_log("Mailer Error: " . $e->getMessage());
        }

        return [
            "ok" => true,
            "message" => "If the email exists, we sent you a link."
        ];
    }

    // reset password
    public function resetPassword(string $token, string $newPassword): array
    {
        if (empty($token) || empty($newPassword)) {
            return ["ok" => false, "message" => "Invalid request"];
        }

        $tokenCheck = hash("sha256", $token);

        $stmt = $this->conn->prepare(
            "SELECT user_id, reset_expires
             FROM users
             WHERE reset_token = :t
             LIMIT 1"
        );

        $stmt->execute([":t" => $tokenCheck]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ["ok" => false, "message" => "Το link δεν ισχυει πια."];
        }

        if (strtotime($user["reset_expires"]) < time()) {
            return ["ok" => false, "message" => "Το link εληξε."];
        }

        if (strlen($newPassword) < 8) {
            return ["ok" => false, "message" => "Password τουλάχιστον 8 χαρακτηρες."];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $update = $this->conn->prepare(
            "UPDATE users
             SET password = :p,
                 reset_token = NULL,
                 reset_expires = NULL
             WHERE user_id = :id"
        );

        $update->execute([
            ":p" => $hashedPassword,
            ":id" => $user["user_id"]
        ]);

        return ["ok" => true, "message" => "Ετοιμο, το password αλλαξε."];
    }
}
