<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) exit;
$user_id = $_SESSION['user_id'];

// 🌟 สิ่งที่ต้องเพิ่ม: อัปเดตสถานะว่า "อ่านแล้ว" เมื่อเปิดแชทมาดู
$stmtUpdate = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0");
$stmtUpdate->execute([$user_id]);

// โค้ดส่วนล่าง (SELECT ข้อความมาแสดง) ปล่อยไว้เหมือนเดิมครับ
$stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = 0) OR (sender_id = 0 AND receiver_id = ?) ORDER BY created_at ASC");
$stmt->execute([$user_id, $user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as $msg) {
    if ($msg['sender_id'] == $user_id) {
        echo '<div class="flex justify-end mb-2">
                <div class="bg-[#D4AF37] text-white py-2 px-3 rounded-t-xl rounded-bl-xl max-w-[80%] text-sm shadow-sm break-words">' 
                . htmlspecialchars($msg['message']) . 
             '</div></div>';
    } else {
        echo '<div class="flex justify-start mb-2">
                <div class="bg-gray-100 text-gray-800 py-2 px-3 rounded-t-xl rounded-br-xl max-w-[80%] text-sm shadow-sm break-words">' 
                . htmlspecialchars($msg['message']) . 
             '</div></div>';
    }
}
?>