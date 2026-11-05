<?php
require_once '../config/db.php';
session_start();

/**
 * 🌟 1. ส่วนของ LOGIC และการประมวลผล (ต้องอยู่ก่อนการแสดงผล HTML เสมอ)
 */

// ระบบเปิด/ปิด สถานะ User (Toggle Status)
if (isset($_GET['toggle_status'])) {
    $targetId = $_GET['toggle_status'];
    
    // ตรวจสอบ: ห้ามระงับบัญชีตัวเอง
    if (isset($_SESSION['user_id']) && $targetId != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$targetId]);
    }
    
    // ทริค: เก็บค่า Query String เดิมไว้ (เช่น คำค้นหา) เพื่อให้หน้าเว็บอยู่ที่เดิมหลังกดสลับสถานะ
    $params = $_GET;
    unset($params['toggle_status']);
    $queryString = http_build_query($params);
    $redirectUrl = "users.php" . (!empty($queryString) ? "?$queryString" : "");
    
    header("Location: $redirectUrl");
    exit();
}

// ระบบค้นหาและตัวกรอง (Search & Filter)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter !== '') {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== '') {
    $sql .= " AND is_active = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY role ASC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

/**
 * 🌟 2. เรียกส่วนหัวของหน้าจอ (LAYOUT)
 */
require_once 'layout/header.php';
checkAdmin(); // ฟังก์ชันเช็คสิทธิ์ (เรียกใช้ได้แล้วเพราะโหลด header มาแล้ว)
?>

<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body { font-family: 'IBM Plex Sans Thai', sans-serif; background-color: #f4f6f9; }
    .card-custom { border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .table-custom thead th { background-color: #121212; color: #D4AF37; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; padding: 15px; }
    .badge-soft { padding: 6px 12px; border-radius: 50px; font-weight: 600; font-size: 0.75rem; }
    .badge-role-admin { background-color: #e7f5ff; color: #1971c2; }
    .badge-role-user { background-color: #f8f9fa; color: #495057; border: 1px solid #dee2e6; }
    .badge-status-active { background-color: #e6fcf5; color: #087f5b; }
    .badge-status-inactive { background-color: #fff5f5; color: #c92a2a; }
    .action-btn { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; }
</style>

<div class="container-fluid py-4 px-xl-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-people-fill text-dark me-2"></i> จัดการผู้ใช้งาน</h2>
            <p class="text-muted mb-0 small">จัดการสมาชิก กำหนดสิทธิ์ และตรวจสอบสถานะการใช้งาน</p>
        </div>
        <a href="user_add.php" class="btn btn-dark rounded-pill px-4 py-2 shadow-sm fw-bold">
            <i class="bi bi-person-plus-fill me-2"></i>เพิ่มผู้ใช้ใหม่
        </a>
    </div>

    <div class="card card-custom mb-4 shadow-sm">
        <div class="card-body p-4">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted uppercase">ค้นหาผู้ใช้งาน</label>
                    <div class="input-group border rounded-pill overflow-hidden bg-light">
                        <span class="input-group-text bg-transparent border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-transparent shadow-none" placeholder="ค้นชื่อ หรือ อีเมล..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted uppercase">สิทธิ์การใช้งาน</label>
                    <select name="role" class="form-select border rounded-pill shadow-none bg-light">
                        <option value="">ทุกสิทธิ์</option>
                        <option value="user" <?= $role_filter == 'user' ? 'selected' : '' ?>>ผู้ใช้ทั่วไป (User)</option>
                        <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>ผู้ดูแลระบบ (Admin)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted uppercase">สถานะ</label>
                    <select name="status" class="form-select border rounded-pill shadow-none bg-light">
                        <option value="">ทั้งหมด</option>
                        <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>ใช้งานปกติ</option>
                        <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>ถูกระงับ</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">กรอง</button>
                    <?php if($search || $role_filter !== '' || $status_filter !== ''): ?>
                        <a href="users.php" class="btn btn-outline-secondary rounded-pill px-3"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-custom overflow-hidden shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>ข้อมูลผู้ใช้งาน</th>
                        <th>แต้มสะสม</th>
                        <th>สิทธิ์ (Role)</th>
                        <th>สถานะ (Status)</th>
                        <th class="text-center pe-4">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php if(count($users) > 0): ?>
                        <?php $i = 1; ?>
                        <?php foreach ($users as $u): ?>
                        <tr>
                             <td class="ps-4 fw-bold text-muted">#<?= $i++ ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-circle"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($u['username']) ?></div>
                                        <div class="text-muted extra-small" style="font-size: 0.75rem;"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold text-warning"><i class="bi bi-star-fill me-1"></i><?= number_format($u['current_points']) ?></span>
                            </td>
                            <td>
                                <?php if ($u['role'] == 'admin'): ?>
                                    <span class="badge-soft badge-role-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-role-user">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="badge-soft badge-status-active"><i class="bi bi-check-circle-fill me-1"></i>ปกติ</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-status-inactive"><i class="bi bi-lock-fill me-1"></i>ระงับ</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="user_edit.php?id=<?= $u['id'] ?>" class="action-btn btn btn-light border text-primary" title="แก้ไข">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <?php 
                                            $tParams = $_GET;
                                            $tParams['toggle_status'] = $u['id'];
                                            $tUrl = "users.php?" . http_build_query($tParams);
                                        ?>
                                        <?php if ($u['is_active']): ?>
                                            <a href="<?= $tUrl ?>" class="action-btn btn btn-light border text-danger" title="ระงับบัญชี" onclick="confirmToggle(event, this.href, 'ระงับบัญชี')">
                                                <i class="bi bi-person-x-fill"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= $tUrl ?>" class="action-btn btn btn-light border text-success" title="ปลดล็อก" onclick="confirmToggle(event, this.href, 'ปลดล็อกบัญชี')">
                                                <i class="bi bi-person-check-fill"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="action-btn btn btn-light border text-muted opacity-50" disabled title="ระงับตัวเองไม่ได้">
                                            <i class="bi bi-shield-slash-fill"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-person-exclamation display-1 text-light d-block mb-3"></i>
                                <h5 class="text-muted">ไม่พบข้อมูลรายชื่อผู้ใช้งาน</h5>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmToggle(event, url, actionText) {
    event.preventDefault(); 
    Swal.fire({
        title: actionText + ' ?',
        text: `คุณต้องการ${actionText}ผู้ใช้งานรายนี้ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: (actionText === 'ระงับบัญชี' ? '#dc3545' : '#198754'),
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก',
        customClass: { confirmButton: 'rounded-pill px-4', cancelButton: 'rounded-pill px-4' }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
</script>

