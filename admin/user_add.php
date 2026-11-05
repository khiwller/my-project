<?php
require_once '../config/db.php';
require_once 'layout/header.php';
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $points = intval($_POST['current_points']);
    $phone = trim($_POST['phone']);

    // เช็คชื่อซ้ำ
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmtCheck->execute([$username, $email]);
    if ($stmtCheck->rowCount() > 0) {
        echo "<script>alert('ชื่อผู้ใช้ หรือ อีเมลนี้มีในระบบแล้ว!'); window.history.back();</script>";
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password_hash, role, current_points, phone) VALUES (?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$username, $email, $hashedPassword, $role, $points, $phone]);

    echo "<script>alert('เพิ่มผู้ใช้ใหม่สำเร็จ!'); window.location='users.php';</script>";
    exit();
}
?>

<div class="container-fluid">
    <h2 class="mb-4">เพิ่มผู้ใช้ใหม่</h2>
    <div class="card shadow-sm" style="max-width: 600px;">
        <div class="card-body">
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">ชื่อผู้ใช้ (Username) <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">อีเมล (Email) <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">รหัสผ่าน (Password) <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">สิทธิ์การใช้งาน</label>
                        <select name="role" class="form-select">
                            <option value="user">User (ลูกค้า)</option>
                            <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">แต้มสะสมเริ่มต้น</label>
                        <input type="number" name="current_points" class="form-control" value="0" min="0">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> บันทึกข้อมูล</button>
                <a href="users.php" class="btn btn-secondary">ยกเลิก</a>
            </form>
        </div>
    </div>
</div>
