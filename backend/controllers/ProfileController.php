<?php
/**
 * File: ProfileController.php
 * Layer: Backend Controller
 * Module: User Profile Management
 * System: University Web Applications System B
 *
 * Description:
 * Handles profile initialization after first login.
 * The controller receives data from profile_setup.php
 * and updates the user profile.
 *
 * Responsibilities:
 * - Validate incoming form data
 * - Update university & year in users table
 * - Insert selected interest categories
 * - Ensure database consistency with transactions
 *
 * Security:
 * - Requires authenticated session
 * - Uses PDO prepared statements
 * - Prevents invalid direct access
 *
 * Tables Used:
 * - users
 * - user_interest
 *
 * Author: Pela Koniotaki
 */

session_start();

/* =========================
   ACCESS CONTROL
   Ensure user is logged in
========================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: ../../frontend/login.php");
    exit;

}

/* =========================
   LOAD MODULE
========================= */

require_once "../modules/ProfileModule.php";


/* =========================
   READ FORM DATA
========================= */

$userId = $_SESSION['user_id'];

$university = $_POST['university'] ?? null;
$year = $_POST['year'] ?? null;

/* categories are optional */
$categories = $_POST['categories'] ?? [];


/* =========================
   BASIC VALIDATION
========================= */

if (!$university || !$year) {

    header("Location: ../../frontend/profile_setup.php?error=missing_fields");
    exit;

}


/* =========================
   INITIALIZE MODULE
========================= */

$profile = new ProfileModule();


/* =========================
   SAVE PROFILE DATA
========================= */

$profile->saveProfile($userId, $university, $year);


/* =========================
   SAVE USER INTERESTS
   Only if categories selected
========================= */

if (!empty($categories)) {

    $profile->saveInterests($userId, $categories);

}


/* =========================
   REDIRECT TO MAIN PAGE
========================= */

header("Location: ../../frontend/index.php");
exit;