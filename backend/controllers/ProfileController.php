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

function redirectTo(string $path): void {
   header("Location: " . $path);
   exit;
}


/* =========================
   READ FORM DATA
========================= */

$userId = $_SESSION['user_id'];

$action = $_GET['action'] ?? 'setup';


/* =========================
   BASIC VALIDATION
========================= */

$profile = new ProfileModule();

if ($action === 'updateProfile') {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirectTo("../../frontend/edit_profile_setup.php");
   }

   $university = trim($_POST['university'] ?? '');
   $year = trim($_POST['year'] ?? '');

   if ($university === '' || $year === '') {
      redirectTo("../../frontend/edit_profile_setup.php?error=missing_fields");
   }

   $profile->saveProfile((int)$userId, $university, $year);
   redirectTo("../../frontend/edit_profile_setup.php?success=1");
}

if ($action === 'updateInterests') {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirectTo("../../frontend/edit_interests.php");
   }

   $categories = $_POST['categories'] ?? [];
   if (!is_array($categories)) {
      $categories = [];
   }

   $normalizedCategories = [];
   foreach ($categories as $categoryId) {
      $value = (int)$categoryId;
      if ($value > 0) {
         $normalizedCategories[] = $value;
      }
   }

   $profile->replaceInterests((int)$userId, array_values(array_unique($normalizedCategories)));
   redirectTo("../../frontend/edit_interests.php?success=1");
}

/* =========================
   DEFAULT: INITIAL PROFILE SETUP
========================= */

$university = trim($_POST['university'] ?? '');
$year = trim($_POST['year'] ?? '');
$categories = $_POST['categories'] ?? [];

if ($university === '' || $year === '') {
   redirectTo("../../frontend/profile_setup.php?error=missing_fields");
}

$profile->saveProfile((int)$userId, $university, $year);

if (is_array($categories) && !empty($categories)) {
   $profile->saveInterests((int)$userId, $categories);
}

redirectTo("../../frontend/posts.php");