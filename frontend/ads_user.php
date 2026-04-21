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

// 3. ΕΥΡΕΣΗ ΤΕΛΕΥΤΑΙΑΣ ΠΡΟΒΟΛΗΣ (για αποφυγή επανάληψης)
// ΝΕΟ (PDO):
$stmt2 = $conn->prepare("SELECT advertise_id FROM ad_views WHERE user_id = ? ORDER BY viewed_at DESC LIMIT 1");
$stmt2->execute([$user_id]);
$last_row = $stmt2->fetch(PDO::FETCH_ASSOC);
$last_ad_id = $last_row ? $last_row['advertise_id'] : 0;
$stmtLastTime = $conn->prepare("
    SELECT viewed_at 
    FROM ad_views 
    WHERE user_id = ? 
    ORDER BY viewed_at DESC 
    LIMIT 1
");
$stmtLastTime->execute([$user_id]);
$last_view_time = $stmtLastTime->fetchColumn();
// 4. ΕΠΙΛΟΓΗ ΔΙΑΦΗΜΙΣΗΣ 
// Επιλέγει μια τυχαία διαφήμιση που δεν την έχει δει ο χρήστης εντός του χρόνου cooldown της
// ΝΕΟ (PDO):
$stmt3 = $conn->prepare("
    SELECT a.*
    FROM advertisements a
    WHERE NOT EXISTS (
        SELECT 1 FROM ad_views v
        WHERE v.user_id = ?
        AND v.viewed_at > NOW() - INTERVAL a.cooldown_hours HOUR
    )
    ORDER BY RAND()
    LIMIT 1
");
$stmt3->execute([$user_id]);
$current_ad = $stmt3->fetch(PDO::FETCH_ASSOC);
$can_watch = $current_ad ? true : false;

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
    <link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/css/ads_user.css?v=<?php echo $adsCssVersion; ?>">
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
                    <h5 class="fw-bold">Περίμενε λίγο...</h5>
                    <p class="text-muted">
                        Μπορείς να ξαναδείς διαφήμιση σε 
                        <b id="timerLive">--:--</b>
                    </p>
                </div>
                <script>
                const lastViewTime = "<?php echo $last_view_time ?? ''; ?>";
                const cooldownHours = <?php echo $current_ad['cooldown_hours'] ?? 1; ?>;
                function startCountdown(lastViewTime, cooldownHours) {
                    if (!lastViewTime) return;
                    const lastTime = new Date(lastViewTime).getTime();
                    const cooldownMs = cooldownHours * 60 * 60 * 1000;
                    function updateCountdown() {
                        const now = new Date().getTime();
                        const remaining = (lastTime + cooldownMs) - now;
                        if (remaining <= 0) {
                            document.getElementById("countdownBox").innerHTML = `
                                <h5 class=\"ads-success-title\">✔ Μπορείς να δεις νέα διαφήμιση!</h5>
                                <button onclick=\"location.reload()\" class=\"btn btn-primary mt-3\">Δες διαφήμιση</button>
                            `;
                            return;
                        }
                        const minutes = Math.floor(remaining / 60000);
                        const seconds = Math.floor((remaining % 60000) / 1000);
                        document.getElementById("timerLive").innerText =
                            `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    }
                    updateCountdown();
                    setInterval(updateCountdown, 1000);
                }
                startCountdown(lastViewTime, cooldownHours);
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
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

            const cooldownHours = <?php echo $current_ad['cooldown_hours'] ?? 1; ?>;

            document.getElementById('ad_box').innerHTML = `
                <div class="text-center py-5">
                    <h5 class="ads-success-title">✔ Κέρδισες 1 token!</h5>
                    <p>Μπορείς να ξαναδείς διαφήμιση μετά από <b>${cooldownHours} ώρα${cooldownHours > 1 ? 'ς' : ''}</b>.</p>
                    <button class="btn btn-secondary mt-3" disabled>Περίμενε ${cooldownHours} ώρα${cooldownHours > 1 ? 'ς' : ''}</button>
                </div>
            `;

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