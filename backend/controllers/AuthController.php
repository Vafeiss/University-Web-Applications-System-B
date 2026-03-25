<?php
/**
 * File: AuthController.php
 * Layer: Backend Controller
 * Module: Authentication & Referral System
 * System: University Web Applications System B
 *
 * Description:
 * This controller manages all authentication-related operations
 * of the system. It handles user registration, login, password
 * recovery, and referral code processing.
 *
 * The controller interacts with the `users` table and ensures
 * secure authentication using modern security practices.
 *
 * Core Responsibilities:
 * - User Registration
 * - User Login Authentication
 * - Referral Code Validation
 * - Token Reward Distribution
 * - Password Reset Request (Email-based)
 * - Password Reset Token Verification
 *
 * Email System:
 * - Sends password reset links using PHPMailer
 * - SMTP credentials are loaded from environment variables (.env)
 *
 * Security Measures:
 * - password_hash() for password storage
 * - password_verify() for login authentication
 * - PDO prepared statements (SQL Injection protection)
 * - Database transactions for atomic operations
 * - Cryptographically secure token generation (random_bytes)
 * - Token expiration validation
 *
 * External Libraries:
 * - PHPMailer (Email delivery via SMTP)
 * - PHP Dotenv (Environment variable management)
 *
 * Database Tables Used:
 * - users
 *
 * Author: Pela Koniotaki
 * Date: 2026
 */
require_once __DIR__ . "/../config/database.php";
$autoloadPath = __DIR__ . "/../../vendor/autoload.php";
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
use Dotenv\Dotenv;
// ευρεση .env αρχείου για mail configuration

date_default_timezone_set('Europe/Athens');

class AuthController {

    private PDO $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    private function generateUniqueReferralCode(): string {
        do {
            $referralCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

            $checkReferralCode = $this->conn->prepare(
                "SELECT user_id FROM users WHERE referral_code = :rc LIMIT 1"
            );
            $checkReferralCode->execute([":rc" => $referralCode]);
        } while ($checkReferralCode->fetch(PDO::FETCH_ASSOC));

        return $referralCode;
    }

    private function loadEnvironment(): void {
        $envPath = __DIR__ . "/../../.env";

        if (!class_exists(Dotenv::class) || !file_exists($envPath)) {
            return;
        }

        $dotenv = Dotenv::createImmutable(__DIR__ . "/../../");
        $dotenv->safeLoad();
    }

    private function getPostLoginRedirect(array $user): string {
        if (($user["role"] ?? "") === "admin") {
            return "/University-Web-Applications-System-B/frontend/admin_dashboard.php";
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

        $university = trim((string)($profile["university"] ?? ""));
        $year = trim((string)($profile["year"] ?? ""));

        if (!$profile || $university === "" || $year === "") {
            return "/University-Web-Applications-System-B/frontend/profile_setup.php";
        }

        return "/University-Web-Applications-System-B/frontend/posts.php";
    }

    /* =========================
       REGISTER
    ========================= */
    public function register(
    string $username,
    string $email,
    string $password,
    ?string $inputReferralCode = null
): array {

    $username = trim($username);
    $email = trim($email);
    $inputReferralCode = $inputReferralCode ? trim($inputReferralCode) : null;

    if ($username === "" || $email === "" || $password === "") {
        return ["ok" => false, "message" => "All fields are required."];
    }

    // ============================================
    // Check duplicates (username/email)
    // ============================================
    $check = $this->conn->prepare(
        "SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1"
    );
    $check->execute([":u" => $username, ":e" => $email]);

    if ($check->fetch()) {
        return ["ok" => false, "message" => "Username or email already exists."];
    }

    try {

        // ============================================
        // Start DB transaction (Atomic operation)
        // ============================================
        $this->conn->beginTransaction();

        // ============================================
        // If referral code provided → validate it
        // ============================================
        $referrerUserId = null;

        if (!empty($inputReferralCode)) {

            $findReferrer = $this->conn->prepare(
                "SELECT user_id FROM users WHERE referral_code = :rc LIMIT 1"
            );
            $findReferrer->execute([":rc" => $inputReferralCode]);

            $referrer = $findReferrer->fetch(PDO::FETCH_ASSOC);

            if (!$referrer) {
                $this->conn->rollBack();
                return ["ok" => false, "message" => "Invalid referral code."];
            }

            $referrerUserId = $referrer["user_id"];
        }

        // ============================================
        // Generate unique referral code for new user
        // ============================================
        $referralCode = $this->generateUniqueReferralCode();

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // ============================================
        // Token reward logic
        // ============================================
        $newUserTokens = 0;

        // ============================================
        // Insert new user
        // ============================================
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

        $newUserId = (int)$this->conn->lastInsertId();

        // ============================================
        // If referral used → reward referrer
        // ============================================
        if ($referrerUserId !== null) {
            $rewardReferrer = $this->conn->prepare(
                "UPDATE users 
                 SET token_balance = token_balance + 10 
                 WHERE user_id = :id"
            );

            $rewardReferrer->execute([":id" => $referrerUserId]);

            $recordReferrerReward = $this->conn->prepare(
                "INSERT INTO transactions (user_id, token_charge, timestamp)
                 VALUES (:id, :charge, NOW())"
            );

            $recordReferrerReward->execute([
                ":id" => $referrerUserId,
                ":charge" => 10
            ]);
        }

        // Commit transaction
        $this->conn->commit();

        return [
            "ok" => true,
            "message" => "Registration successful. You can login now."
        ];

    } catch (Throwable $e) {

        // Rollback on error
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }

        return [
            "ok" => false,
            "message" => "Registration failed. Please try again."
        ];
    }
    }
    
    /* =========================
       LOGIN
    ========================= */

    public function login(string $username, string $password): array {

        $username = trim($username);

        $stmt = $this->conn->prepare("
            SELECT user_id, username, password, role 
            FROM users 
            WHERE username = :u 
            LIMIT 1
        ");
        $stmt->execute([":u" => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user["password"])) {
            return ["ok" => false, "message" => "Invalid username or password."];
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

    /* =========================
       REQUEST PASSWORD RESET
    ========================= */
    public function requestPasswordReset(string $email): array {
    $this->loadEnvironment();

    $email = trim($email);

    if ($email === "") {
        return ["ok" => false, "message" => "Email is required."];
    }

    // =========================================
    // Check if email exists
    // =========================================
    $stmt = $this->conn->prepare(
        "SELECT user_id FROM users WHERE email = :e LIMIT 1"
    );
    $stmt->execute([":e" => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Do NOT reveal if email exists
    if (!$user) {
        return [
            "ok" => true,
            "message" => "If the email exists, you will receive a reset link shortly."
        ];
    }

    // =========================================
    // Generate secure token
    // =========================================
    $rawToken      = bin2hex(random_bytes(32));      // sent to user
    $hashedToken   = hash('sha256', $rawToken);      // stored in DB
    $expires       = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // =========================================
    // Store hashed token in database
    // =========================================
    $update = $this->conn->prepare(
        "UPDATE users 
         SET reset_token = :t,
             reset_expires = :x
         WHERE user_id = :id"
    );

    $update->execute([
        ":t"  => $hashedToken,
        ":x"  => $expires,
        ":id" => $user["user_id"]
    ]);

    // =========================================
    // Create reset link (raw token in URL)
    // =========================================
    $resetLink = "https://subeffectively-easier-kera.ngrok-free.dev/University-Web-Applications-System-B/frontend/reset_password.php?token=" . $rawToken;

    try {
        if (!class_exists(PHPMailer::class)) {
            return [
                "ok" => false,
                "message" => "Email support is not configured yet. Install Composer dependencies first."
            ];
        }

        // Load mail configuration
        $mailConfig = require __DIR__ . '/../config/mail.php';
       
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $mailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['username'];
        $mail->Password   = $mailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $mailConfig['port'];

        $mail->setFrom($mailConfig['username'], 'University System');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body    = "
            <h3>Password Reset</h3>
            <p>Click the link below to reset your password:</p>
            <a href='{$resetLink}'>{$resetLink}</a>
            <br><br>
            <small>This link expires in 1 hour.</small>
        ";

        $mail->send();

    } catch (MailException $e) {
        error_log("Mailer Error: " . $e->getMessage());
    }

    return [
        "ok" => true,
        "message" => "If the email exists, you will receive a reset link shortly."
    ];
}
    /* =========================
       RESET PASSWORD
    ========================= */
public function resetPassword(string $token, string $newPassword): array {

    if (empty($token) || empty($newPassword)) {
        return ["ok" => false, "message" => "Invalid request."];
    }

    // ==============================
    // Hash incoming token
    // ==============================
    $hashedIncomingToken = hash('sha256', $token);

    // ==============================
    // Find matching token
    // ==============================
    $stmt = $this->conn->prepare(
        "SELECT user_id, reset_expires 
         FROM users 
         WHERE reset_token = :t
         LIMIT 1"
    );

    $stmt->execute([":t" => $hashedIncomingToken]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ["ok" => false, "message" => "Invalid or expired reset link."];
    }

    // ==============================
    // Check expiration
    // ==============================
    if (strtotime($user["reset_expires"]) < time()) {
        return ["ok" => false, "message" => "Reset link has expired."];
    }

    // ==============================
    // Optional: Basic password policy
    // ==============================
    if (strlen($newPassword) < 8) {
        return ["ok" => false, "message" => "Password must be at least 8 characters long."];
    }

    // ==============================
    // Hash new password
    // ==============================
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // ==============================
    // Update password & clear token
    // ==============================
    $update = $this->conn->prepare(
        "UPDATE users
         SET password = :p,
             reset_token = NULL,
             reset_expires = NULL
         WHERE user_id = :id"
    );

    $update->execute([
        ":p"  => $hashedPassword,
        ":id" => $user["user_id"]
    ]);

    return ["ok" => true, "message" => "Password successfully reset."];
}}
