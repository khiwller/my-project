<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo 0;
    exit;
}

$user_id = $_SESSION['user_id'];

// นับข้อความที่แอดมินส่งมา (receiver_id = user_id) และยังไม่ได้อ่าน (is_read = 0)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
echo $stmt->fetchColumn();
?>