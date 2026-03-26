<?php
declare(strict_types=1);

require_once "../backend/middleware/AuthGuard.php";

requireAdmin();

header("Location: admin_dashboard.php");
exit();
