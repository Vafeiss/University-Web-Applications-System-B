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

// Ξεκινάμε session για να έχουμε πρόσβαση στο user_id
session_start();

// Φορτώνουμε το BanGuard και κόβουμε αμέσως τους banned χρήστες
require_once __DIR__ . '/../middleware/BanGuard.php';
enforceFrontendUserNotBanned();


// --- Έλεγχος ότι ο χρήστης είναι logged in ---
// Αν δεν υπάρχει user_id στο session, στέλνουμε στο login
if (!isset($_SESSION['user_id'])) {

    header("Location: ../../login.php");
    exit;

}


// --- Φόρτωση του ProfileModule που κάνει τη "βρώμικη" δουλειά με τη βάση ---
require_once __DIR__ . "/../modules/ProfileModule.php";

// Μικρό helper: redirect σε path και τερματισμός του script
function redirectTo(string $path): void {
   header("Location: " . $path);
   exit;
}


// --- Διαβάζουμε ποιος είναι ο χρήστης και τι ενέργεια ζήτησε ---
$userId = $_SESSION['user_id'];

// Η δράση έρχεται ως ?action=... από το query string του URL
// (default = "setup", δηλαδή αρχικό setup προφίλ)
$action = $_GET['action'] ?? 'setup';


// Δημιουργούμε ένα instance του model (σύνδεση με DB)
$profile = new ProfileModule();


// --- Action: Ενημέρωση πανεπιστημίου / έτους από το Edit Profile ---
if ($action === 'updateProfile') {

   // Δεχόμαστε μόνο POST για να μη γίνεται update τυχαία από GET
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirectTo("../../edit_profile_setup.php");
   }

   // Καθαρίζουμε τα κενά γύρω από τα input
   $university = trim($_POST['university'] ?? '');
   $year = trim($_POST['year'] ?? '');

   // Αν λείπει κάτι, γυρίζουμε πίσω με μήνυμα λάθους
   if ($university === '' || $year === '') {
      redirectTo("../../edit_profile_setup.php?error=missing_fields");
   }

   // Αποθήκευση και redirect στο profile view με flash success
   $profile->saveProfile((int)$userId, $university, $year);
   $_SESSION['flash_success'] = 'profile_updated';
   redirectTo("../../profile_view.php");
}


// --- Action: Ενημέρωση ενδιαφερόντων από το Edit Interests ---
if ($action === 'updateIntere