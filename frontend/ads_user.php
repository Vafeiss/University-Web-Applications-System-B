<?php
session_start();

// 1. ΣΥΝΔΕΣΗ ΜΕ ΤΗ ΒΑΣΗ
$db_path = 'C:\xampp\htdocs\University-Web-Applications-System-B\database\db_connect.php';

if (file_exists($db_path)) {
    include($db_path);
} else {
    include($_SERVER['DOCUMENT_ROOT'] . '/University-Web-Applications-System-B/database/db_connect.php');
}

if (!isset($conn)) {
    if (isset($db)) { $conn = $db; }
    elseif (isset($link)) { $conn = $link; }
}

if (!$conn) {
    die("<div style='color:red; font-weight:bold;'>Σφάλμα: Η σύνδεση απέτυχε!</div>");
}

// 2. ΠΡΟΣΩΡΙΝΟ LOGIN & DATA FETCH
$_SESSION['user_id'] = 1;
$user_id = $_SESSION['user_id'];

// Τσέκαρει τα tokens του χρήστη
$user_query = $conn->query("SELECT token_balance FROM users WHERE user_id = $user_id");
$user_data = $user_query->fetch_assoc();
$total_tokens = $user_data['token_balance'] ?? 0;

// 3. ΕΥΡΕΣΗ ΤΕΛΕΥΤΑΙΑΣ ΠΡΟΒΟΛΗΣ (για αποφυγή επανάληψης)
$last_view = $conn->query("SELECT advertise_id FROM ad_views WHERE user_id = $user_id ORDER BY viewed_at DESC LIMIT 1");
$last_ad_id = ($last_view && $row = $last_view->fetch_assoc()) ? $row['advertise_id'] : 0;

// 4. ΕΠΙΛΟΓΗ ΔΙΑΦΗΜΙΣΗΣ 
// Επιλέγει μια τυχαία διαφήμιση που δεν την έχει δει ο χρήστης εντός του χρόνου cooldown της
$next_ad_query = $conn->query("
    SELECT a.* FROM advertisements a
    LEFT JOIN ad_views v ON a.advertise_id = v.advertise_id 
        AND v.user_id = $user_id 
        AND v.viewed_at > NOW() - INTERVAL a.cooldown_hours HOUR
    WHERE v.view_id IS NULL 
    AND a.advertise_id != $last_ad_id
    ORDER BY RAND()
    LIMIT 1
");

$current_ad = $next_ad_query->fetch_assoc();
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
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; font-family: 'Segoe UI', sans-serif; }
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
                <div class="text-center py-5 my-auto">
                    <div class="display-3 mb-3">⏳</div>
                    <h5 class="fw-bold">Κάνε ένα διάλειμμα!</h5>
                    <p class="text-muted small">Δεν υπάρχουν άλλες διαθέσιμες διαφημίσεις αυτή τη στιγμή. Δοκίμασε αργότερα!</p>
                    <button onclick="location.reload()" class="btn btn-outline-primary btn-sm rounded-pill px-4">Refresh</button>
                </div>
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
    

    fetch('../backend/reward_system.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ad_id=' + id
    })
    .then(r => r.text())
    .then(data => {
        console.log("Response:", data);
        if (data.trim() === "Success") {
            location.reload();
        } else {
            alert("Καταγραφή: " + data);
            location.reload();
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