<?php
/**
 * File: profile_setup.php
 * Layer: Frontend
 * Module: User Profile Initialization
 * System: University Web Applications System B
 *
 * Description:
 * This page is displayed after the user's first successful login.
 * It allows the user to complete their basic profile information
 * required by the platform.
 *
 * The user must select:
 * - University
 * - Year of study
 * - Optional interest categories (filters)
 *
 * After submission:
 * Data is sent to ProfileController.php which updates:
 * - users.university
 * - users.year
 * - user_interest table
 *
 * Access Control:
 * - Only authenticated users can access this page
 * - Users who already completed their profile are redirected
 *
 * Security:
 * - Session-based authentication
 * - Prepared statements for database queries
 * - Output escaping using htmlspecialchars()
 *
 * Author: Pela Koniotaki
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
<style>
body {
    min-height: 100vh;
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background:
        radial-gradient(circle at top left, rgba(255, 255, 255, 0.72), transparent 32%),
        linear-gradient(135deg, #e9eef6 0%, #dbe4f0 42%, #cfd9e8 100%);
    color: #173665;
}

.setup-shell {
    min-height: 100vh;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 24px 18px 32px;
}

.setup-stack {
    width: min(430px, 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
    margin-top: clamp(18px, 6vh, 52px);
}

.setup-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    text-align: center;
}

.setup-brand-mark {
    width: 78px;
    height: 78px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.68);
    border: 1px solid rgba(255, 255, 255, 0.42);
    box-shadow: 0 16px 32px rgba(23, 54, 101, 0.08);
    backdrop-filter: blur(10px);
}

.setup-brand-mark img {
    width: 72%;
    height: 72%;
    object-fit: contain;
}

.setup-brand h1 {
    margin: 0;
    font-size: 31px;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #173665;
}

.setup-brand p {
    margin: 0;
    max-width: 320px;
    font-size: 14px;
    line-height: 1.5;
    color: #51698f;
}

.setup-card {
    width: 100%;
    padding: 24px 24px 21px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.42);
    background: rgba(255, 255, 255, 0.74);
    box-shadow: 0 18px 40px rgba(23, 54, 101, 0.10);
    backdrop-filter: blur(16px);
}

.setup-card h2 {
    margin: 0 0 8px;
    text-align: center;
    font-size: 27px;
    font-weight: 800;
    color: #173665;
}

.setup-subtitle {
    margin: 0 0 18px;
    text-align: center;
    font-size: 14px;
    line-height: 1.5;
    color: #5a6f8f;
}

.setup-alert {
    margin-bottom: 18px;
    border: 1px solid #f1c0c6;
    border-radius: 12px;
    background: #fff3f5;
    color: #a12d3d;
    padding: 12px 14px;
    font-size: 14px;
    font-weight: 600;
}

.setup-field {
    margin-bottom: 16px;
}

.setup-field label {
    display: block;
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: 700;
    color: #28405f;
}

.setup-input-wrap {
    position: relative;
}

.setup-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    width: 20px;
    height: 20px;
    transform: translateY(-50%);
    color: #4e83d8;
    pointer-events: none;
    z-index: 1;
}

.setup-input,
.setup-select-trigger {
    width: 100%;
    min-height: 44px;
    border: 1px solid #d0d7e2;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.9);
    font-size: 14px;
    color: #1e3760;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.setup-input {
    appearance: none;
    padding: 0 14px 0 44px;
}

.setup-input:focus,
.setup-select-trigger:focus,
.setup-dropdown.is-open .setup-select-trigger {
    border-color: #5d8fe0;
    box-shadow: 0 0 0 4px rgba(93, 143, 224, 0.16);
    background: #ffffff;
}

.setup-year-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.setup-year-option {
    position: relative;
}

.setup-year-option input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.setup-year-pill {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    border: 1px solid #d0d7e2;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.82);
    color: #35506f;
    font-size: 14px;
    font-weight: 600;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease;
}

.setup-year-option input:checked + .setup-year-pill {
    border-color: #214f95;
    background: linear-gradient(180deg, rgba(33, 79, 149, 0.12) 0%, rgba(23, 54, 101, 0.04) 100%);
    color: #173665;
    box-shadow: 0 0 0 4px rgba(93, 143, 224, 0.12);
}

.setup-dropdown {
    position: relative;
}

.setup-select-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 0 14px 0 44px;
    cursor: pointer;
    text-align: left;
}

.setup-select-trigger::after {
    content: "";
    width: 10px;
    height: 10px;
    border-right: 2px solid #5f78a2;
    border-bottom: 2px solid #5f78a2;
    transform: rotate(45deg) translateY(-2px);
    flex-shrink: 0;
    transition: transform 0.2s ease;
}

.setup-dropdown.is-open .setup-select-trigger::after {
    transform: rotate(-135deg) translateY(-1px);
}

.setup-select-text {
    color: #5b6f8d;
}

.setup-dropdown-menu {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    right: 0;
    z-index: 5;
    display: none;
    padding: 10px;
    border: 1px solid rgba(208, 215, 226, 0.95);
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 18px 34px rgba(23, 54, 101, 0.12);
    backdrop-filter: blur(14px);
}

.setup-dropdown.is-open .setup-dropdown-menu {
    display: block;
}

.setup-options {
    max-height: 220px;
    overflow-y: auto;
    display: grid;
    gap: 8px;
}

.setup-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    background: #f7f9fd;
    color: #35506f;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease;
}

.setup-option:hover {
    background: #eef4fb;
}

.setup-option input {
    accent-color: #214f95;
}

.setup-helper {
    margin-top: 8px;
    font-size: 12px;
    line-height: 1.45;
    color: #6b7e98;
}

.setup-submit {
    width: 100%;
    height: 44px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(180deg, #214f95 0%, #173665 100%);
    color: #ffffff;
    font-size: 14px;
    font-weight: 700;
    box-shadow: 0 12px 24px rgba(23, 54, 101, 0.16);
    transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
}

.setup-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 28px rgba(23, 54, 101, 0.20);
    filter: brightness(1.03);
}

@media (max-width: 520px) {
    .setup-card {
        padding: 22px 18px 19px;
    }

    .setup-brand h1 {
        font-size: 28px;
    }

    .setup-year-grid {
        grid-template-columns: 1fr;
    }
}
</style>

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
