<?php
/**
 * File: mail.php
 * Layer: Backend Configuration
 * Module: SMTP Configuration
 * System: University Web Applications System B
 *
 * Description:
 * Returns array of SMTP credentials loaded from environment variables.
 * Used by PHPMailer to send password reset and notification emails.
 * Keeps sensitive credentials outside source control via .env file.
 *
 * Configuration Keys:
 * - host → SMTP server address
 * - username → SMTP account email
 * - password → SMTP authentication password
 * - port → SMTP server port
 *
 * Security:
 * - Credentials stored in .env (excluded from version control)
 * - No hardcoded credentials in source code
 *
 * Used By:
 * - AuthController (password reset emails)
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */
return [
    'host' => $_ENV['SMTP_HOST'] ?? null,
    'username' => $_ENV['SMTP_USER'] ?? null,
    'password' => $_ENV['SMTP_PASS'] ?? null,
    'port' => $_ENV['SMTP_PORT'] ?? null
];