<?php
/**
 * File: ads_user.php
 * Layer: Frontend Page
 * Module: Ads (User View)
 * System: University Web Applications System B
 *
 * Description:
 * Page where logged-in users view advertisements and earn tokens for
 * watching them. Integrates with AdsController for delivery and with
 * the token system for rewards.
 *
 * Features:
 * - Carousel / slider of active advertisements
 * - Token reward tracking per ad viewed
 * - Call-to-action link to advertiser
 * - Fallback message when no ads available
 *
 * Security:
 * - session_start() and requireLogin()
 * - PDO prepared statements (database.php)
 * - htmlspecialchars() for output escaping
 *
 * Used By:
 * - Linked from main dashboard (index.php)
 *
 * Author: Pelagia Koniotaki & Antriani Theofanous 
 * Date: 2026
 */

require_once "../backend/config/database.php";
require_once "../backend/config/app.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = (int) $_SESSION['user_id'];

$db = new Database();
$conn = $db->connect(); // PDO connection
// Τσέκαρει τα tokens του χρήστη
$stmt = $conn->prepare("SELECT token_balance FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_tokens = $user_data['token_balance'] ?? 0;

// Τελευταια προβολη + cooldown του ad που ειδε τελευταιο ο χρηστης.
$stmtLastView = $conn->prepare("
    SELECT v.viewed_at, v.advertise_id, a.cooldown_hours
    FROM ad_views v
    INNER JOIN advertisements a ON a.advertise_id = v.advertise_id
    WHERE v.user_id = ?
    ORDER BY v.viewed_at DESC
    LIMIT 1
");
$stmtLastView->execute([$user_id]);
$last_view = $stmtLastView->fetch(PDO::FETCH_ASSOC) ?: null;

$last_view_time = $last_view['viewed_at'] ?? null;
$cooldown_hours = isset($last_view['cooldown_hours']) ? (int) $last_view['cooldown_hours'] : 0;
$remaining_seconds = 0;

if ($last_view_time && $cooldown_hours > 0) {
    $lastViewedAtTs = strtotime((string) $last_view_time);
    if ($lastViewedAtTs !== false) {
        $nextAvailableTs = $lastViewedAtTs + ($cooldown_hours * 3600);
        $remaining_seconds = max(0, $nextAvailableTs - time());
    }
}

$current_ad = null;
$can_watch = false;

if ($remaining_seconds <= 0) {
    $stmt3 = $conn->query("
        SELECT *
        FROM advertisements
        ORDER BY RAND()
        LIMIT 1
    ");
    $current_ad = $stmt3 ? $stmt3->fetch(PDO::FETCH_ASSOC) : null;
    $can_watch = (bool) $current_ad;
}

$adsCssVersion = filemtime(__DIR__ . '/css/ads_user.css');

// 5. ΕΛΕΓΧΟΣ ΑΝ ΕΙΝΑΙ ΒΙΝΤΕΟ Ή ΕΙΚΟΝΑ
$is_video = false;
if ($current_ad) {
    $url = $current_ad['image_url'];
    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
    if (in_array(strtolower($ext), ['mp4', 'webm', 'ogg']) || strpos($url, 'vjs.zencdn.net') !== false) {
        $is_video = true;
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Your Tokens!</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="<?php echo app_frontend_url('css/ads_user.css'); ?>?v=<?php echo $adsCssVersion; ?>">
</head>
<body class="ads-page">

<a href="posts.php" class="btn btn-light position-absolute top-0 start-0 m-4 d-flex align-items-center ads-back-link">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="ads-back-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    Back to Posts
</a>

<div class="container d-flex justify-content-center">
    <div class="card ad-card shadow-lg animate__animated animate__zoomIn ads-card-shell">
        <div class="d-flex flex-column h-100">
            <div class="d-flex justify-content-between align-items-center mb-auto">
                <span class="token-badge shadow-sm">💰 +1 Token</span>
                <span class="wallet-badge shadow-sm">💳 Wallet: <?php echo $total_tokens; ?></span>
            </div>
            
            <?php if ($can_watch && $current_ad): ?>
                <div id="setup_box" class="text-center py-5 my-auto">
                    <p class="fw-bold mb-4 ads-setup-copy">Δες διαφημίσεις για να κερδίσεις tokens...</p>
                    <button class="btn btn-start w-100 shadow-sm" onclick="startPremiumAd()">Κέρδισε Tokens 🚀</button>
                </div>

                <div id="ad_box" hidden>
                    <div class="media-container mb-3 shadow-sm">
                        <?php if ($is_video): ?>
                            <video id="adMedia" class="ad-media" muted playsinline>
                                <source src="<?php echo $current_ad['image_url']; ?>" type="video/mp4">
                            </video>
                        <?php else: ?>
                            <img id="adMedia" src="<?php echo $current_ad['image_url']; ?>" class="ad-media" onerror="this.src='https://via.placeholder.com/500x300?text=Ad+Loading...'">
                        <?php endif; ?>
                    </div>
                    <div class="progress mb-2 shadow-sm">
                        <div id="pgBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success ads-progress-bar"></div>
                    </div>
                    <p class="small text-muted text-end">Τέλος σε: <span id="timerText" class="fw-bold text-danger"><?php echo $current_ad['time_duration']; ?></span>s</p>
                </div>
            <?php else: ?>
                <div id="countdownBox" class="text-center py-5 my-auto">
                    <div class="display-6 mb-3">⏳</div>
                    <?php if ($remaining_seconds > 0): ?>
                        <h5 class="fw-bold">Περίμενε λίγο...</h5>
                        <p class="text-muted">
                            Μπορείς να ξαναδείς διαφήμιση σε 
                            <b id="timerLive">--:--:--</b>
                        </p>
                    <?php else: ?>
                        <h5 class="fw-bold">Δεν υπάρχουν διαθέσιμες διαφημίσεις</h5>
                        <p class="text-muted">Δοκίμασε ξανά αργότερα.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const initialRemainingSeconds = <?php echo (int) $remaining_seconds; ?>;

function startCooldownCountdown(remainingSeconds) {
    const timerNode = document.getElementById("timerLive");
    const countdownBox = document.getElementById("countdownBox");

    if (!timerNode || !countdownBox || remainingSeconds <= 0) {
        return;
    }

    let secondsLeft = remainingSeconds;

    function render() {
        if (secondsLeft <= 0) {
            countdownBox.innerHTML = `
                <h5 class="ads-success-title">✔ Μπορείς να δεις νέα διαφήμιση!</h5>
                <button onclick="location.reload()" class="btn btn-primary mt-3">Δες διαφήμιση</button>
            `;
            return;
        }

        const hours = Math.floor(secondsLeft / 3600);
        const minutes = Math.floor((secondsLeft % 3600) / 60);
        const seconds = secondsLeft % 60;
        timerNode.innerText = `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        secondsLeft -= 1;
    }

    render();
    setInterval(render, 1000);
}

startCooldownCountdown(initialRemainingSeconds);

function startPremiumAd() {
    document.getElementById('setup_box').hidden = true;
    document.getElementById('ad_box').hidden = false;
    
    const media = document.getElementById('adMedia');
    const totalTime = <?php echo $current_ad['time_duration'] ?? 10; ?>;
    let currentTime = totalTime;

    if (media.tagName === 'VIDEO') { 
        media.muted = true;
        media.play().catch(e => console.log("Playback error:", e)); 
    }
    
    const interval = setInterval(() => {
        currentTime--;
        let percent = (currentTime / totalTime) * 100;
        
        if (document.getElementById('pgBar')) {
            document.getElementById('pgBar').style.width = percent + "%";
        }
        if (document.getElementById('timerText')) {
            document.getElementById('timerText').innerText = currentTime;
        }
        
        if (currentTime <= 0) {
            clearInterval(interval);
            finishAd(<?php echo $current_ad['advertise_id'] ?? 0; ?>);
        }
    }, 1000);
}

function finishAd(id) {
    document.getElementById('ad_box').innerHTML = '<div class="py-5 text-center"><div class="spinner-border text-primary mb-3"></div><p>Προσθήκη Token...</p></div>';
    

    fetch('../backend/controllers/AdsController.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ad_id=' + id
    })
    .then(async (response) => {
        const data = await response.json().catch(() => null);
        return {
            ok: response.ok,
            data
        };
    })
    .then(({ ok, data }) => {
        console.log("Response:", data);

        if (ok && data && data.ok) {

            const cooldownHours = data.cooldown_hours || <?php echo $current_ad['cooldown_hours'] ?? 1; ?>;
            const cooldownSeconds = data.remaining_seconds || (cooldownHours * 3600);

            document.getElementById('ad_box').innerHTML = `
                <div class="text-center py-5">
                    <h5 class="ads-success-title">✔ Κέρδισες 1 token!</h5>
                    <p>Μπορείς να ξαναδείς διαφήμιση μετά από <b>${cooldownHours} ώρα${cooldownHours > 1 ? 'ς' : ''}</b>.</p>
                    <p class="text-muted mb-3">Υπόλοιπο αναμονής: <b id="postRewardTimer"></b></p>
                    <button class="btn btn-secondary mt-3" disabled>Περίμενε για να ξαναδείς διαφήμιση</button>
                </div>
            `;

            const postRewardTimer = document.getElementById('postRewardTimer');
            if (postRewardTimer) {
                let secondsLeft = cooldownSeconds;
                const renderRewardTimer = () => {
                    const hours = Math.floor(secondsLeft / 3600);
                    const minutes = Math.floor((secondsLeft % 3600) / 60);
                    const seconds = secondsLeft % 60;
                    postRewardTimer.textContent = `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    if (secondsLeft > 0) {
                        secondsLeft -= 1;
                    }
                };
                renderRewardTimer();
                setInterval(renderRewardTimer, 1000);
            }

        } else {
            const errorMessage = data && data.message ? data.message : 'Κάτι πήγε στραβά.';
            document.getElementById('ad_box').innerHTML = `
                <div class="text-center py-5 text-danger">
                    <p>❌ Σφάλμα: ${errorMessage}</p>
                    <button onclick="location.reload()" class="btn btn-secondary mt-3">Δοκίμασε ξανά</button>
                </div>
            `;
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        location.reload();
    });
}
</script>
</body>
</html>
