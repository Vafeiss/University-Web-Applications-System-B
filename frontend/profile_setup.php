<?php
/**
 * File: profile_setup.php
 * Layer: Frontend Page
 * Module: User Profile Initialization
 * System: University Web Applications System B
 *
 * Description:
 * Page displayed after the user's first successful login. Lets the user
 * complete the basic profile information required by the platform
 * (university, year of study and optional interest categories) before
 * accessing the main app.
 *
 * Features:
 * - University selection
 * - Year of study selection
 * - Optional interest categories (filters)
 * - Submit sends data to ProfileController.php which updates users.university,
 *   users.year and the user_interest table
 *
 * Security:
 * - Only authenticated users can access this page
 * - Users who already completed their profile are redirected
 * - Session-based authentication
 * - PDO prepared statements for database queries
 * - Output escaping using htmlspecialchars()
 *
 * Used By:
 * - Linked from login.php (first-time login redirect)
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

session_start();

/* =========================
   ACCESS CONTROL
========================= */

require_once "../backend/middleware/AuthGuard.php";
requireLogin();

/* =========================
   DATABASE CONNECTION
========================= */

require_once "../backend/config/database.php";

$db = new Database();
$conn = $db->connect();

/* =========================
   CHECK PROFILE COMPLETION
========================= */

$stmt = $conn->prepare("
    SELECT university, year
    FROM users
    WHERE user_id = :id
");

$stmt->execute([
    ":id" => $_SESSION["user_id"]
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* Redirect if profile already completed */

if ($user && $user["university"] !== null && $user["year"] !== null) {
    header("Location: index.php");
    exit;
}

/* =========================
   LOAD INTEREST CATEGORIES
========================= */

$stmt = $conn->query("
     SELECT MIN(category_id) AS category_id, name
     FROM categories
     GROUP BY name
     ORDER BY name ASC
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = $_GET["error"] ?? "";

$profileSetupCssVersion = filemtime(__DIR__ . '/css/profile_setup.css');

?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Complete Profile</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Custom Styles -->
<link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/assets/style.css">
<link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/css/profile_setup.css?v=<?php echo $profileSetupCssVersion; ?>">

</head>

<body>

<div class="setup-shell">
    <div class="setup-stack">
        <div class="setup-brand">
            <span class="setup-brand-mark" aria-hidden="true">
                <img src="/University-Web-Applications-System-B/frontend/imgs/unisupportlogo.png" alt="">
            </span>
            <div>
                <h1>UniSupport</h1>
                <p>Complete your profile to personalize your student workspace and start seeing the content that matters to you.</p>
            </div>
        </div>

        <div class="setup-card">
            <h2>Complete Profile</h2>
            <p class="setup-subtitle">Add your university details and choose the interests you want to follow.</p>

            <?php if ($error === "missing_fields"): ?>
                <div class="setup-alert">University and year of study are required.</div>
            <?php endif; ?>

            <form method="POST" action="../backend/controllers/ProfileController.php">
                <div class="setup-field">
                    <label for="setupUniversity">University</label>
                    <div class="setup-input-wrap">
                        <span class="setup-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 10 12 4l9 6-9 6-9-6Z"></path>
                                <path d="M7 12v5c0 .7 2.2 3 5 3s5-2.3 5-3v-5"></path>
                            </svg>
                        </span>
                        <select name="university" id="setupUniversity" class="setup-input" required>
                            <option value="">Select University</option>
                            <option value="TEPAK">TEPAK</option>
                            <option value="UCY">University of Cyprus</option>
                            <option value="CUT">Cyprus University of Technology</option>
                        </select>
                    </div>
                </div>

                <div class="setup-field">
                    <label>Year of Study</label>
                    <div class="setup-year-grid">
                        <label class="setup-year-option" for="setupYear1">
                            <input type="radio" name="year" id="setupYear1" value="1" required>
                            <span class="setup-year-pill">Year 1</span>
                        </label>
                        <label class="setup-year-option" for="setupYear2">
                            <input type="radio" name="year" id="setupYear2" value="2">
                            <span class="setup-year-pill">Year 2</span>
                        </label>
                        <label class="setup-year-option" for="setupYear3">
                            <input type="radio" name="year" id="setupYear3" value="3">
                            <span class="setup-year-pill">Year 3</span>
                        </label>
                        <label class="setup-year-option" for="setupYear4">
                            <input type="radio" name="year" id="setupYear4" value="4">
                            <span class="setup-year-pill">Year 4</span>
                        </label>
                    </div>
                </div>

                <div class="setup-field">
                    <label for="interestsTrigger">Interests (Optional)</label>
                    <div class="setup-dropdown" id="interestsDropdown">
                        <div class="setup-input-wrap">
                            <span class="setup-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 21s-6-4.35-6-10a4 4 0 0 1 7-2.65A4 4 0 0 1 18 11c0 5.65-6 10-6 10Z"></path>
                                </svg>
                            </span>
                            <button type="button" class="setup-select-trigger" id="interestsTrigger" aria-haspopup="true" aria-expanded="false">
                                <span class="setup-select-text" id="interestsLabel">Choose interests</span>
                            </button>
                        </div>

                        <div class="setup-dropdown-menu" id="interestsMenu">
                            <div class="setup-options">
                                <?php foreach ($categories as $cat): ?>
                                    <?php $categoryId = (int) $cat["category_id"]; ?>
                                    <label class="setup-option" for="cat<?= $categoryId ?>">
                                        <input
                                            class="setup-interest-checkbox"
                                            type="checkbox"
                                            name="categories[]"
                                            value="<?= $categoryId ?>"
                                            id="cat<?= $categoryId ?>"
                                        >
                                        <span><?= htmlspecialchars($cat["name"]) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="setup-helper">Open the dropdown and select one or more interests.</div>
                </div>

                <button type="submit" class="setup-submit">Save Profile</button>
            </form>
        </div>
    </div>
</div>

<script>
const dropdown = document.getElementById("interestsDropdown");
const trigger = document.getElementById("interestsTrigger");
const label = document.getElementById("interestsLabel");
const checkboxes = Array.from(document.querySelectorAll(".setup-interest-checkbox"));

function updateInterestLabel() {
    const selected = checkboxes
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.parentElement.textContent.trim());

    if (selected.length === 0) {
        label.textContent = "Choose interests";
        return;
    }

    if (selected.length <= 2) {
        label.textContent = selected.join(", ");
        return;
    }

    label.textContent = selected.length + " interests selected";
}

function setDropdownState(isOpen) {
    dropdown.classList.toggle("is-open", isOpen);
    trigger.setAttribute("aria-expanded", isOpen ? "true" : "false");
}

trigger.addEventListener("click", function () {
    setDropdownState(!dropdown.classList.contains("is-open"));
});

document.addEventListener("click", function (event) {
    if (!dropdown.contains(event.target)) {
        setDropdownState(false);
    }
});

checkboxes.forEach(function (checkbox) {
    checkbox.addEventListener("change", updateInterestLabel);
});

updateInterestLabel();
</script>

</body>

</html>
