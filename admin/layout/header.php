<?php 
// 1. ดึงไฟล์ตั้งค่า Database และฟังก์ชัน checkAdmin มาก่อน
require_once '../config/db.php'; 

// 🌟 2. เรียกใช้ฟังก์ชันตรวจสิทธิ์ตรงนี้เลย! (ก่อน HTML ทุกอย่าง)
checkAdmin(); 

// ตรวจสอบ Session (ถ้ายังไม่มีการ start)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 🌟 1. ระบบดึงข้อมูลเพื่อทำแจ้งเตือน (Notifications) 🌟
// ==========================================
$pending_orders = 0;
$pending_donations = 0;
$unread_chat = 0;

try {
    // 🔴 1. นับรายการสั่งซื้อ (รอตรวจสอบ)
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_type = 'purchase' AND status = 'pending'");
    $stmt1->execute();
    $pending_orders = $stmt1->fetchColumn();

    // 🔴 2. นับรายการบริจาค (รอตรวจสอบ)
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_type = 'donation' AND status = 'pending'");
    $stmt2->execute();
    $pending_donations = $stmt2->fetchColumn();

    // 🔴 3. นับแชทจากลูกค้าที่ยังไม่ได้อ่าน
    $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = 0 AND is_read = 0");
    $stmt3->execute();
    $unread_chat = $stmt3->fetchColumn();

} catch (PDOException $e) {
    // ป้องกัน Error แสดงหน้าเว็บถ้าตารางยังไม่พร้อม
    echo "<script>console.error('Notification Error: " . addslashes($e->getMessage()) . "');</script>";
}
// ==========================================
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - สมหมายหีบทองอยุธยา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* สไตล์ Sidebar */
        .sidebar { 
            height: 100vh; 
            position: sticky; 
            top: 0; 
            background-color: #1a1a1a; 
            color: white; 
            overflow-y: auto; 
        }
        .sidebar a { 
            color: #cfd2d6; 
            text-decoration: none; 
            padding: 12px 15px; 
            display: block; 
            border-radius: 8px; 
            margin-bottom: 5px; 
            transition: all 0.3s ease; 
        }
        .sidebar a:hover, .sidebar a.active { 
            background-color: #d4af37 !important; 
            color: white !important; 
            font-weight: bold;
        }
        
        /* สไตล์ Scrollbar สำหรับ Sidebar */
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: #d4af37; }
    </style>
</head>
<body class="bg-light">

<div class="d-flex min-vh-100">
    
    <div class="sidebar p-3 d-none d-md-block flex-shrink-0 shadow" style="width: 260px; z-index: 1000;">
        <h4 class="text-center py-3 fw-bold text-white">ADMIN</h4>
        <hr class="border-secondary mt-0">
        
        <div class="admin-user-section mb-4 p-3 rounded-3" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center text-dark fw-bold" style="width: 40px; height: 40px; background-color: #d4af37 !important;">
                        <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3 overflow-hidden">
                    <p class="mb-0 text-white-50" style="font-size: 0.7rem;">เข้าสู่ระบบโดย</p>
                    <p class="mb-0 fw-bold text-truncate text-white" style="font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
                </div>
            </div>
        </div>

        <ul class="list-unstyled">
            <li>
                <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i> ภาพรวมระบบ
                </a>
            </li>
            
            <li>
                <a href="users.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'user_add.php', 'user_edit.php']) ? 'active' : '' ?>">
                    <i class="bi bi-people-fill me-2"></i> จัดการผู้ใช้งาน
                </a>
            </li>

            <li>
                <a href="products.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'product-create.php', 'product-edit.php', 'manage-options.php', 'manage-sets.php']) ? 'active' : '' ?>">
                    <i class="bi bi-box-seam me-2"></i> จัดการสินค้า
                </a>
            </li>
            
            <li>
                <a href="orders.php" class="position-relative <?= in_array(basename($_SERVER['PHP_SELF']), ['orders.php', 'order_details.php']) ? 'active' : '' ?>">
                    <i class="bi bi-receipt me-2"></i> ตรวจสอบรายการสั่งซื้อ
                    <?php if($pending_orders > 0): ?>
                        <span class="position-absolute top-0 end-0 badge bg-danger rounded-pill border border-2 border-dark" 
                              style="transform: translate(-10%, 10%); font-size: 0.65rem; padding: 0.35em 0.6em;">
                            <?= $pending_orders ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li>
                <a href="donations.php" class="position-relative <?= basename($_SERVER['PHP_SELF']) == 'donations.php' ? 'active' : '' ?>">
                    <i class="bi bi-heart-fill me-2"></i> รายการบริจาค
                    <?php if($pending_donations > 0): ?>
                        <span class="position-absolute top-0 end-0 badge bg-danger rounded-pill border border-2 border-dark" 
                              style="transform: translate(-10%, 10%); font-size: 0.65rem; padding: 0.35em 0.6em;">
                            <?= $pending_donations ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li>
                <a href="manage_donation_posts.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_donation_posts.php' ? 'active' : '' ?>">
                    <i class="bi bi-images me-2"></i> ภาพกิจกรรมบริจาค
                </a>
            </li>

            <li>
                <a href="admin_chat.php" class="position-relative <?= basename($_SERVER['PHP_SELF']) == 'admin_chat.php' ? 'active' : '' ?>">
                    <i class="bi bi-chat-dots-fill me-2"></i> แชทกับลูกค้า
                    <?php if($unread_chat > 0): ?>
                        <span class="position-absolute top-0 end-0 badge bg-danger rounded-pill border border-2 border-dark" 
                              style="transform: translate(-10%, 10%); font-size: 0.65rem; padding: 0.35em 0.6em;">
                            <?= $unread_chat ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="mt-5">
                <a href="../logout.php" 
                   class="text-danger fw-bold border border-danger border-opacity-25" 
                   style="background: rgba(220,53,69,0.1);"
                   onclick="confirmLogout(event, this.href)">
                    <i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ
                </a>
            </li>
        </ul>
    </div>

    <div class="flex-grow-1 p-0 p-md-4 bg-light" style="min-width: 0; overflow-x: hidden;">

        <script>
        function confirmLogout(event, url) {
            event.preventDefault(); 
            
            Swal.fire({
                title: 'ยืนยันการออกจากระบบ?',
                text: "คุณต้องการสิ้นสุดการทำงานในส่วนผู้ดูแลระบบใช่หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545', 
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> ใช่, ออกจากระบบ',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true, 
                customClass: {
                    confirmButton: 'rounded-pill px-4 shadow-sm',
                    cancelButton: 'rounded-pill px-4 shadow-sm'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
        </script>