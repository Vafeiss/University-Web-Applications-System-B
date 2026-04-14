<?php
// 1. ΣΥΝΔΕΣΗ ΜΕ ΤΗ ΒΑΣΗ
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
    <style>
        body { background: #232a4d; min-height: 100vh; display: flex; align-items: center; font-family: 'Segoe UI', sans-serif; }
        .ad-card { border-radius: 24px; border: none; background: white; overflow: hidden; min-height: 400px; display: flex; flex-direction: column; padding: 25px; }
        .media-container { width: 100%; height: 240px; background: #000; border-radius: 15px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .ad-media { width: 100%; height: 100%; object-fit: cover; border-radius: 15px; }
        .progress { height: 12px; border-radius: 20px; background-color: #eee; }
        .token-badge { background: #ffd700; color: #333; font-weight: bold; padding: 6px 15px; border-radius: 50px; }
        .wallet-badge { background: #f8f9fa; color: #667eea; font-weight: bold; padding: 6px 15px; border-radius: 50px; border: 1px solid #eee; }
        .btn-start { background: #667eea; color: white; font-weight: bold; padding: 16px; border-radius: 50px; border: none; transition: 0.3s; font-size: 1.1rem; }
        .btn-start:hover { background: #764ba2; transform: scale(1.02); }
    </style>
</head>
<body>

<a href="posts.php" class="btn btn-light position-absolute top-0 start-0 m-4 d-flex align-items-center" style="z-index:1000; box-shadow:0 2px 8px rgba(0,0,0,0.07); font-weight:bold;">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    Back to Posts
</a>

<div class="container d-flex justify-content-center">
    <div class="card ad-card shadow-lg animate__animated animate__zoomIn" style="max-width: 420px; width: 100%;">
        <div class="d-flex flex-column h-100">
            <div class="d-flex justify-content-between align-items-center mb-auto">
                <span class="token-badge shadow-sm">💰 +1 Token</span>
                <span class="wallet-badge shadow-sm">💳 Wallet: <?php echo $total_tokens; ?></span>
            </div>
            
            <?php if ($can_watch && $current_ad): ?>
                <div id="setup_box" class="text-center py-5 my-auto">
                    <p class="fw-bold mb-4" style="color: #000000; font-size: 1.2rem;">Δες διαφημίσεις για να κερδίσεις tokens...</p>
                    <button class="btn btn-start w-100 shadow-sm" onclick="startPremiumAd()">Κέρδισε Tokens 🚀</button>
                </div>

                <div id="ad_box" style="display:none;">
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
                        <div id="pgBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
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
                                <h5 style=\"color:green;\">✔ Μπορείς να δεις νέα διαφήμιση!</h5>
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
    document.getElementById('setup_box').style.display = 'none';
    document.getElementById('ad_box').style.display = 'block';
    
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
    .then(r => r.text())
    .then(data => {
        console.log("Response:", data);

        if (data.trim() === "Success") {

            const cooldownHours = <?php echo $current_ad['cooldown_hours'] ?? 1; ?>;

            document.getElementById('ad_box').innerHTML = `
                <div class="text-center py-5">
                    <h5 style="color:green;">✔ Κέρδισες 1 token!</h5>
                    <p>Μπορείς να ξαναδείς διαφήμιση μετά από <b>${cooldownHours} ώρα${cooldownHours > 1 ? 'ς' : ''}</b>.</p>
                    <button class="btn btn-secondary mt-3" disabled>Περίμενε ${cooldownHours} ώρα${cooldownHours > 1 ? 'ς' : ''}</button>
                </div>
            `;

        } else {
            document.getElementById('ad_box').innerHTML = `
                <div class="text-center py-5 text-danger">
                    <p>❌ Σφάλμα: ${data}</p>
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