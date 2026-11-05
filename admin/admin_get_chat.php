<?php
session_start();
require_once '../config/db.php';

if (!isset($_GET['user_id'])) exit;
$user_id = $_GET['user_id'];

// 🌟 1. อัปเดตสถานะว่า "แอดมินอ่านแล้ว" ทันทีที่เปิดดูแชท
// ช่วยให้ตัวเลขแจ้งเตือนสีแดงในหน้าเมนูหายไป
$stmtUpdate = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = 0 AND is_read = 0");
$stmtUpdate->execute([$user_id]);

// 🌟 2. ดึงข้อความสนทนาระหว่าง Admin (ID: 0) กับลูกค้าคนนี้
$stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = 0 AND receiver_id = ?) OR (sender_id = ? AND receiver_id = 0) ORDER BY created_at ASC");
$stmt->execute([$user_id, $user_id]);
$messages = $stmt->fetchAll();

// 🌟 3. แสดงผลฟองสบู่แชท
foreach ($messages as $msg) {
    if ($msg['sender_id'] == 0) {
        // --- ฝั่งแอดมินตอบ (ชิดขวา / สีดำ-ทอง) ---
        echo '<div class="d-flex justify-content-end mb-3">
                <div class="chat-bubble-admin shadow-sm text-break">' 
                    . htmlspecialchars($msg['message']) . 
                '</div>
              </div>';
    } else {
        // --- ฝั่งลูกค้าถามมา (ชิดซ้าย / สีขาว) ---
        echo '<div class="d-flex justify-content-start mb-3">
                <div class="chat-bubble-user shadow-sm text-break">' 
                    . htmlspecialchars($msg['message']) . 
                '</div>
              </div>';
    }
}

// 🌟 4. กรณีไม่มีข้อความเลย
if (count($messages) == 0) {
    echo '<div class="text-center text-muted mt-5 small">
            <i class="fas fa-history mb-2 d-block fa-2x opacity-25"></i>
            ยังไม่มีประวัติการสนทนากับลูกค้าท่านนี้
          </div>';
}
?>