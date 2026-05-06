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
 * - login() â†’ validates credentials and returns user with redirect
 * - register() â†’ creates new user account with unique referral code
 * - resetPassword() â†’ initiates password reset flow via email
 * - generateUniqueReferralCode() â†’ creates unique code for referral system
 * - getPostLoginRedirect() â†’ determines redirect path based on user role/profile
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
require_once __DIR__ . "/../config/app.php";
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
            return app_url("admin_dashboard.php");
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
            return app_url("profile_setup.php");
        }

        return app_url("posts.php");
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
        // Î­Î»ÎµÎ³Ï‡Î¿Ï‚ Î³Î¹Î± ÎºÎµÎ½Î¬ Ï€ÎµÎ´Î¯Î±
        if ($username === "" || $email === "" || $password === "") {
            return ["ok" => false, "message" => "Î£Ï…Î¼Ï€Î»Î®ÏÏ‰ÏƒÎµ ÏŒÎ»Î± Ï„Î± Ï€ÎµÎ´Î¯Î±."];
        }

        // ÎµÎ»ÎµÎ³Ï‡Î¿Ï‚ Î±Î½ Ï…Ï€Î±ÏÏ‡ÎµÎ¹ Î·Î´Î· Î¿ Ï‡ÏÎ·ÏƒÏ„Î·Ï‚
        $check = $this->conn->prepare(
            "SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1"
        );
        //Ï„Ï‰ÏÎ± Î´Î¯Î½Î¿Ï…Î¼Îµ Ï€ÏÎ±Î³Î¼Î±Ï„Î¹ÎºÎ­Ï‚ Ï„Î¹Î¼Î­Ï‚ ÎºÎ±Î¹ Î³Î¹Î± Ï„Î± Î´ÏÎ¿ placeholders Î³Î¹Î± ÎµÎºÏ„ÎµÎ»ÎµÏƒÎ· query
        $check->execute([":u" => $username, ":e" => $email]);
        // Î±Î½ Ï…Ï€Î¬ÏÏ‡ÎµÎ¹ Î´Î¹Ï€Î»ÏŽÏ„Î·Ï„Î± 
        if ($check->fetch()) {
            return ["ok" => false, "message" => "Î¤Î¿ username Î® Ï„Î¿ email Ï…Ï€Î¬ÏÏ‡ÎµÎ¹ Î®Î´Î·."];
        }

        try {
            // Î¾ÎµÎºÎ¹Î½Î¬Î¼Îµ transaction Î³Î¹Î± Î±ÏƒÏ†Î¬Î»ÎµÎ¹Î±
            $this->conn->beginTransaction();

            $referrerUserId = null;

            // Î±Î½ ÎµÎ´Ï‰ÏƒÎµ referral code Ï„Î¿ Ï„ÏƒÎµÎºÎ±ÏÎ¿Ï…Î¼Îµ
            if (!empty($inputReferralCode)) {
                $refStmt = $this->conn->prepare(
                    "SELECT user_id FROM users WHERE referral_code = :rc LIMIT 1"
                );
                $refStmt->execute([":rc" => $inputReferralCode]);

                $referrer = $refStmt->fetch(PDO::FETCH_ASSOC);

                if (!$referrer) {
                    $this->conn->rollBack();
                    return ["ok" => false, "message" => "Î›Î¬Î¸Î¿Ï‚ referral code."];
                }

                $referrerUserId = $referrer["user_id"];
            }

            // Ï†Ï„Î¹Î±Ï‡Î½Î¿Ï…Î¼Îµ Î¼Î¿Î½Î±Î´Î¹ÎºÎ¿ ref code Î³Î¹Î± Ï„Î¿Î½ Î½ÎµÎ¿ user
            $referralCode = $this->generateUniqueReferralCode();
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $newUserTokens = 0;

            // insert Ï„Î¿Ï… Ï‡ÏÎ·ÏƒÏ„Î·
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

            // Î±Î½ Ï‡ÏÎ·ÏƒÎ¹Î¼Î¿Ï€Î¿Î¹Î·Î¸Î·ÎºÎµ referral Î´Î¹Î½Î¿Ï…Î¼Îµ tokens ÏƒÏ„Î¿Î½ Ï€Î±Î»Î¹Î¿ user
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
                "message" => "ÎˆÏ„Î¿Î¹Î¼Î¿! ÎœÏ€Î¿ÏÎµÎ¯Ï‚ Î½Î± ÎºÎ±Î½ÎµÎ¹Ï‚ login."
            ];
        } catch (Throwable $e) {
            // Î±Î½ ÎºÎ±Ï„Î¹ Ï€Î±ÎµÎ¹ ÏƒÏ„ÏÎ±Î²Î±, rollback
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();    //Î²Î±ÏƒÎ· ÎµÏ€Î±Î½Î±Ï†Î­ÏÎµÏ„Î±Î¹ ÏƒÏ„Î·Î½ ÎºÎ±Ï„Î¬ÏƒÏ„Î±ÏƒÎ· Ï€ÏÎ¹Î½ Ï„Î·Î½ ÎµÎ³Î³ÏÎ±Ï†Î®, ÎºÎ±Î¹ Î±ÎºÏ…ÏÏŽÎ½Î¿Î½Ï„Î±Î¹ ÏŒÎ»ÎµÏ‚ Î¿Î¹ ÎµÎ½Ï„Î¿Î»Î­Ï‚ Ï€Î¿Ï… ÎµÎºÏ„ÎµÎ»Î­ÏƒÏ„Î·ÎºÎ±Î½ Î¼Î­ÏƒÎ± ÏƒÏ„Î¿ transaction
            }

            return [
                "ok" => false,
                "message" => "ÎšÎ±Ï„Î¹ Ï€Î·Î³Îµ ÏƒÏ„ÏÎ±Î²Î±, Î¾Î±Î½Î±Ï€ÏÎ¿ÏƒÏ€Î±Î¸Î·ÏƒÎµ."
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

        // TODO: Î¹ÏƒÏ‰Ï‚ Î¸ÎµÎ»ÎµÎ¹ rate limiting
        $stmt = $this->conn->prepare("
            SELECT {$selectFields}
            FROM users
            WHERE username = :u
            LIMIT 1
        ");
        $stmt->execute([":u" => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user["password"])) {
            return ["ok" => false, "message" => "Î›Î¬Î¸Î¿Ï‚ username Î® password."];
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
            return ["ok" => false, "message" => "Î§ÏÎµÎ¹Î¬Î¶Î¿Î¼Î±Î¹ Ï„Î¿ email ÏƒÎ¿Ï…."];
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

        $rawToken = bin2hex(random_bytes(32)); // Î±Ï…Ï„Î¿ Ï€Î·Î³Î±Î¹Î½ÎµÎ¹ ÏƒÏ„Î¿ email
        $hashedToken = hash("sha256", $rawToken); // Î±Ï…Ï„Î¿ ÏƒÏ„Î· Î²Î±ÏƒÎ·
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

        $resetLink = app_absolute_url("frontend/reset_password.php?token=" . rawurlencode($rawToken));

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
            $mail->Body = "ÎšÎ±Î»Ï‰Ï‚ Î·ÏÎ¸ÎµÏ‚! Î Î±Ï„Î± ÎµÎ´Ï‰ Î³Î¹Î± reset: <a href='{$resetLink}'>{$resetLink}</a><br>Î™ÏƒÏ‡Ï…ÎµÎ¹ Î³Î¹Î± 1 Ï‰ÏÎ±.";

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
            return ["ok" => false, "message" => "Î¤Î¿ link Î´ÎµÎ½ Î¹ÏƒÏ‡Ï…ÎµÎ¹ Ï€Î¹Î±."];
        }

        if (strtotime($user["reset_expires"]) < time()) {
            return ["ok" => false, "message" => "Î¤Î¿ link ÎµÎ»Î·Î¾Îµ."];
        }

        // TODO: Î½Î± Î²Î±Î»Ï‰ ÎºÎ±Î¹ ÎºÎ±Î»Ï…Ï„ÎµÏÎ· password policy (ÎºÎµÏ†Î±Î»Î±Î¹Î±, Î½Î¿Ï…Î¼ÎµÏÎ±)
        if (strlen($newPassword) < 8) {
            return ["ok" => false, "message" => "Password Ï„Î¿Ï…Î»Î¬Ï‡Î¹ÏƒÏ„Î¿Î½ 8 Ï‡Î±ÏÎ±ÎºÏ„Î·ÏÎµÏ‚."];
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

        return ["ok" => true, "message" => "Î•Ï„Î¿Î¹Î¼Î¿, Ï„Î¿ password Î±Î»Î»Î±Î¾Îµ."];
    }
}
