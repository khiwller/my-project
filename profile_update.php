<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบว่ามีการกดปุ่มบันทึกมาจริงหรือไม่
if (isset($_POST['update_profile'])) {
    
    // 1. ตรวจสอบ Login เพื่อความปลอดภัย
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $userId = $_SESSION['user_id'];
    
    // 2. รับค่าและทำความสะอาดข้อมูล (Sanitization)
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // จัดการเบอร์โทรศัพท์: ลบทุกอย่างที่ไม่ใช่ตัวเลข (0-9)
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');

    // 3. ตรวจสอบเงื่อนไขบังคับ (Validation)
    if (empty($username)) {
        $_SESSION['error'] = "กรุณากรอกชื่อผู้ใช้งาน";
        header("Location: profile_edit.php");
        exit();
    }

    if (empty($email)) {
        $_SESSION['error'] = "กรุณากรอกอีเมล";
        header("Location: profile_edit.php");
        exit();
    }

    try {
        // --- กรณีที่ 1: มีการระบุรหัสผ่านใหม่ ---
        if (!empty($newPassword)) {
            
            // 🌟 ดักความยาวรหัสผ่านใหม่ (ต้อง 6 ตัวขึ้นไป)
            if (strlen($newPassword) < 6) {
                $_SESSION['error'] = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
                header("Location: profile_edit.php");
                exit();
            }

            // ตรวจสอบว่ารหัสผ่านตรงกันหรือไม่
            if ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = "รหัสผ่านใหม่ไม่ตรงกัน";
                header("Location: profile_edit.php");
                exit();
            }
            
            // Hash รหัสผ่านใหม่
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // อัปเดตข้อมูลรวม Username และรหัสผ่านใหม่
            $sql = "UPDATE users SET username = ?, email = ?, phone = ?, address = ?, password_hash = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $email, $phone, $address, $passwordHash, $userId]);
            
        } else {
            // --- กรณีที่ 2: อัปเดตข้อมูลทั่วไป (ไม่ได้เปลี่ยนรหัสผ่าน) ---
            $sql = "UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $email, $phone, $address, $userId]);
        }

        // 🌟 สำคัญ: อัปเดตชื่อใน Session ทันที เพื่อให้ Navbar แสดงชื่อใหม่
        $_SESSION['username'] = $username;

        $_SESSION['success'] = "บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว";
        header("Location: profile_edit.php");
        exit();

    } catch (PDOException $e) {
        // ตรวจสอบกรณี Username หรือ Email ซ้ำ (Error Code 23000)
        if ($e->getCode() == 23000) {
            $_SESSION['error'] = "ขออภัย: ชื่อผู้ใช้งานหรืออีเมลนี้มีผู้ใช้อื่นใช้งานแล้ว";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
        }
        header("Location: profile_edit.php");
        exit();
    }

} else {
    // ถ้าไม่ได้มาจากการกดปุ่มบันทึก ให้เด้งกลับหน้าแรก
    header("Location: index.php");
    exit();
}