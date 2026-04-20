<?php
include('../database/db_connect.php'); 
// 1. ΔΙΑΓΡΑΦΗ ΔΙΑΦΗΜΙΣΗΣ
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM advertisements WHERE advertise_id = $id");
    header("Location: admin_ads.php");
}

// 2. ΠΡΟΣΘΗΚΗ ΝΕΑΣ ΔΙΑΦΗΜΙΣΗΣ (Import)
if (isset($_POST['add_ad'])) {
    $title = $_POST['title'];
    $url = $_POST['url'];
    $duration = $_POST['duration'];
    $cooldown = $_POST['cooldown'];

    $conn->query("INSERT INTO advertisements (title, image_url, time_duration, cooldown_hours) 
                  VALUES ('$title', '$url', $duration, $cooldown)");
    header("Location: admin_ads.php");
}

$ads = $conn->query("SELECT * FROM advertisements");
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Admin - Διαχείριση Διαφημίσεων</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f7f6; padding: 20px; }
        .admin-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .table img, .table video { border-radius: 5px; object-fit: cover; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="mb-4">📢 Διαχείριση Διαφημίσεων (Admin)</h2>

    <div class="admin-card mb-5">
        <h5>Προσθήκη Νέας Διαφήμισης </h5>
        <form method="POST" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="title" class="form-control" placeholder="Τίτλος" required>
            </div>
            <div class="col-md-5">
                <input type="text" name="url" class="form-control" placeholder="URL Φωτογραφίας ή Video" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="duration" class="form-control" placeholder="Διάρκεια (sec)" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="cooldown" class="form-control" placeholder="Cooldown (ώρες)" required>
            </div>
            <div class="col-md-2">
                <button type="submit" name="add_ad" class="btn btn-success w-100">Προσθήκη</button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5>Υπάρχουσες Διαφημίσεις</h5>
        <table class="table table-hover mt-3">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Προεπισκόπηση</th>
                    <th>Τίτλος</th>
                    <th>Διάρκεια</th>
                    <th>Cooldown</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $ads->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['advertise_id']; ?></td>
                    <td>
                        <?php 
                        $url = $row['image_url'];
                        $ext = pathinfo($url, PATHINFO_EXTENSION);
                        if(in_array($ext, ['mp4', 'webm'])): ?>
                            <video width="80" height="50" muted><source src="<?php echo $url; ?>"></video>
                        <?php else: ?>
                            <img src="<?php echo $url; ?>" width="80" height="50">
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row['title']; ?></td>
                    <td><?php echo $row['time_duration']; ?>s</td>
                    <td><?php echo $row['cooldown_hours']; ?>h</td>
                    <td>
                        <a href="?delete=<?php echo $row['advertise_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Διαγραφή;')">🗑 Διαγραφή</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>