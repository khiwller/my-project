<?php
session_start();
require_once 'config/db.php';

// บังคับตั้งค่าโซนเวลาให้เป็นเวลาไทย
date_default_timezone_set('Asia/Bangkok');

if (!isset($_GET['token'])) {
    die("ลิงก์ไม่ถูกต้อง หรือไม่มี Token");
}

$token = $_GET['token'];

// สร้างตัวแปรเวลาปัจจุบันจาก PHP เพื่อเอาไปเทียบ
$currentTime = date("Y-m-d H:i:s");

// เช็คว่า Token ถูกต้องและยังไม่หมดอายุใช่หรือไม่
$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > ?");
$stmt->execute([$token, $currentTime]);
$user = $stmt->fetch();

if (!$user) {
    die("ลิงก์รีเซ็ทรหัสผ่านไม่ถูกต้อง หรือหมดอายุไปแล้ว กรุณาไปที่หน้าเข้าสู่ระบบเพื่อกด 'ลืมรหัสผ่าน' ใหม่อีกครั้ง");
}

// ถ้ารับค่า POST จากฟอร์ม (กดยืนยันรหัสใหม่)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_password'])) {
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT); // เข้ารหัสผ่านใหม่

    // 🌟 แก้ไขตรงนี้: เปลี่ยนคำว่า password เป็น password_hash ให้ตรงกับฐานข้อมูลของคุณ
    $stmtUpdate = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmtUpdate->execute([$newPassword, $user['id']]);

    echo "<script>alert('เปลี่ยนรหัสผ่านสำเร็จ! กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่'); window.location='login.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตั้งรหัสผ่านใหม่</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Prompt', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white p-8 shadow-xl rounded-sm border-t-4 border-[#D4AF37]">
        <h2 class="text-2xl font-bold text-center mb-6">ตั้งรหัสผ่านใหม่</h2>
        
        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-400 mb-2 uppercase">รหัสผ่านใหม่</label>
                <input type="password" name="new_password" class="w-full border p-3 text-sm focus:border-[#D4AF37]" required minlength="6" placeholder="ต้องมีความยาวอย่างน้อย 6 ตัวอักษร">
            </div>
            <button type="submit" class="w-full bg-[#121212] text-white font-bold py-3 uppercase hover:bg-[#D4AF37] transition">
                บันทึกรหัสผ่านใหม่
            </button>
        </form>
    </div>
</body>
</html>