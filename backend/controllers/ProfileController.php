<?php
/**
 * File: ProfileController.php
 * Layer: Controller
 * Module: Profile Management
 * System: University Web Applications System B
 *
 * Description:
 * Handles user profile setup and updates. Manages initial profile completion
 * (university, year, interests) and subsequent profile modifications.
 * Enforces profile completion requirement before accessing main app.
 *
 * Functions:
 * - setup workflow → handles initial profile completion
 * - updateProfile → updates university and year
 * - updateInterests → modifies user interest categories
 *
 * Security:
 * - requireLogin() enforces authentication
 * - Ban guard checks before profile operations
 * - Input validation and sanitization
 * - PDO prepared statements for all queries
 * - Transactional updates for consistency
 *
 * Used By:
 * - frontend/profile_setup.php
 * - frontend/edit_profile_setup.php
 * - frontend/edit_interests.php
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

session_start();
require_once __DIR__ . '/../middleware/BanGuard.php';
enforceFrontendUserNotBanned();

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
   $_SESSION['flash_success'] = 'profile_updated';
   redirectTo("../../frontend/profile_view.php");
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
   $_SESSION['flash_success'] = 'interests_updated';
   redirectTo("../../frontend/posts.php");
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
