<?php
session_start();
require_once 'config/db.php';

// สมมติว่าตอน Login คุณเก็บ ID ของ user ไว้ใน $_SESSION['user_id'] 
// (ถ้าคุณตั้งชื่ออื่น เช่น $_SESSION['id'] ให้เปลี่ยนตรงนี้ด้วยนะครับ)
if (!isset($_SESSION['user_id']) || empty($_POST['message'])) {
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = 0; // ส่งหา Admin ให้รหัสเป็น 0 เสมอ
$message = trim($_POST['message']);

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->execute([$sender_id, $receiver_id, $message]);
?>