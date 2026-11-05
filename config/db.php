<?php
// config/db.php
$host = 'localhost';
$dbname = 'sha_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // ตั้งค่าให้แสดง Error เมื่อ Query พลาด (สำคัญมากตอน Dev)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function สำหรับเช็ค Admin (Middleware)
function checkAdmin() {
    // 🌟 แก้ไข: ตรวจสอบสถานะ session ก่อนเรียก start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../login.php");
        exit();
    }
}
?>