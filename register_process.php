<?php
session_start();
require_once 'config/db.php';

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    // 1. Validation เบื้องต้น
    if ($password !== $confirm) {
        $_SESSION['error'] = "รหัสผ่านไม่ตรงกัน";
        header("Location: register.php");
        exit();
    }

    try {
        // 2. เช็คว่ามี user นี้หรือยัง
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Username หรือ Email นี้ถูกใช้งานแล้ว";
            header("Location: register.php");
            exit();
        }

        // 3. Hash Password และบันทึก
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password_hash, role, current_points) VALUES (?, ?, ?, 'user', 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $email, $hashed_password]);

        $_SESSION['success'] = "สมัครสมาชิกสำเร็จ กรุณาล็อกอิน";
        header("Location: login.php");

    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("Location: register.php");
    }
}
?>