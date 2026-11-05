<?php
session_start();
require_once '../config/db.php';

if (empty($_POST['message']) || empty($_POST['user_id'])) exit;

$sender_id = 0; // แอดมินมีรหัส 0 เสมอ
$receiver_id = $_POST['user_id']; // รหัสลูกค้าที่กำลังคุยด้วย
$message = trim($_POST['message']);

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->execute([$sender_id, $receiver_id, $message]);
?>