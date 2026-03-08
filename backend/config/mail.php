<?php
/**
 * File: mail.php
 * Layer: Backend Configuration
 * Module: Email / SMTP Configuration
 * System: University Web Applications System B
 *
 * Description:
 * This configuration file provides the SMTP credentials used
 * by the system to send emails through PHPMailer.
 *
 * The values are loaded from environment variables (.env)
 * using the PHP Dotenv library. This approach keeps sensitive
 * information (email credentials) outside of the source code.
 *
 * Used For:
 * - Password reset email delivery
 * - Future notification emails
 * *
 * Environment Variables:
 * - SMTP_HOST   → SMTP server address
 * - SMTP_USER   → Email account used to send emails
 * - SMTP_PASS   → Application password for SMTP authentication
 * - SMTP_PORT   → SMTP server port
 *
 * Security:
 * - Credentials are stored in the .env file
 * - .env file should NOT be committed to version control
 *
 * Used By:
 * - AuthController (Password Reset Email)
 *
 * Author: pela koniotaki
 * Date: 2026
 */
return [
    'host' => $_ENV['SMTP_HOST'] ?? null,
    'username' => $_ENV['SMTP_USER'] ?? null,
    'password' => $_ENV['SMTP_PASS'] ?? null,
    'port' => $_ENV['SMTP_PORT'] ?? null
];