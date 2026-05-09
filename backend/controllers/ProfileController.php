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
if ($action === 'updateInterests') {

   // Δεχόμαστε μόνο POST εδώ επίσης
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirectTo("../../edit_interests.php");
   }

   // Παίρνουμε τις επιλεγμένες κατηγορίες από τα checkboxes
   $categories = $_POST['categories'] ?? [];
   if (!is_array($categories)) {
      $categories = [];
   }

   // Καθαρίζουμε: μόνο θετικά integer category_ids περνάνε
   $normalizedCategories = [];
   foreach ($categories as $categoryId) {
      $value = (int)$categoryId;
      if ($value > 0) {
         $normalizedCategories[] = $value;
      }
   }

   // Αντικαθιστούμε όλα τα παλιά interests με τη νέα λίστα (μέσα σε transaction)
   $profile->replaceInterests((int)$userId, array_values(array_unique($normalizedCategories)));
   $_SESSION['flash_success'] = 'interests_updated';
   redirectTo("../../posts.php");
}


// --- Default περίπτωση: αρχικό setup προφίλ μετά την εγγραφή ---
// Όταν ο χρήστης μόλις εγγραφεί, συμπληρώνει university + year + interests
// μαζί στο ίδιο form και έρχονται όλα εδώ.
$university = trim($_POST['university'] ?? '');
$year = trim($_POST['year'] ?? '');
$categories = $_POST['categories'] ?? [];

// Τα βασικά (university, year) είναι υποχρεωτικά
if ($university === '' || $year === '') {
   redirectTo("../../profile_setup.php?error=missing_fields");
}

// Αποθήκευση πανεπιστημίου + έτους
$profile->saveProfile((int)$userId, $university, $year);

// Τα interests είναι προαιρετικά — τα βάζουμε μόνο αν επιλέχθηκε κάτι
if (is_array($categories) && !empty($categories)) {
   $profile->saveInterests((int)$userId, $categories);
}

// Τέλος: στέλνουμε τον χρήστη στο feed
redirectTo("../../posts.php");
