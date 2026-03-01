<?php
/**
 * File: AuthGuard.php
 * Layer: Middleware
 * Module: Access Control (RBAC)
 * System: University Web Applications System B
 *
 * Description:
 * Middleware responsible for protecting routes/pages.
 * Ensures:
 * - Only authenticated users access protected pages.
 * - Only admins access admin-only pages.
 *
 * Functions:
 * - requireLogin()
 * - requireAdmin()
 *
 * Security:
 * - Session-based access control
 * - Role verification
 * - Redirects unauthorized users
 *
 * Used By:
 * - index.php
 * - admin.php
 *
 * Author: Your Name
 * Date: 2026
 */

// Ξεκινάμε session μόνο αν δεν έχει ήδη ξεκινήσει
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Απαιτεί ο χρήστης να είναι authenticated.
 * Αν δεν υπάρχει user_id στο session τοτε redirect σε login.
 */
function requireLogin(): void
{
    if (!isset($_SESSION["user_id"])) {
        header("Location: /University-Web-Applications-System-B/frontend/login.php");
        exit;
    }
}

/**
 * Απαιτεί ο χρήστης να έχει ρόλο admin.
 * Πρώτα ελέγχει login.
 * Μετά ελέγχει role.
 */
function requireAdmin(): void
{
    // Πρώτα βεβαιωνόμαστε ότι είναι logged in
    requireLogin();

    // Αν δεν υπάρχει role ή δεν είναι admin τότε redirect
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
        header("Location: /University-Web-Applications-System-B/frontend/index.php");
        exit;
    }
}