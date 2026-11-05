<?php
session_start();
require_once '../config/db.php'; // ตรวจสอบ path ให้ตรงกับของคุณนะครับ

// 🌟 เพิ่มคำสั่งนับข้อความที่ยังไม่อ่าน (unread_count) ซ้อนเข้าไปใน SQL
$sql = "SELECT u.id, u.username, MAX(m.created_at) as last_active,
        (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = 0 AND is_read = 0) as unread_count
        FROM users u 
        JOIN messages m ON u.id = m.sender_id OR u.id = m.receiver_id 
        WHERE u.id != 0 
        GROUP BY u.id 
        ORDER BY last_active DESC";
$stmt = $pdo->query($sql);
$chatUsers = $stmt->fetchAll();

if (count($chatUsers) == 0) {
    echo "<div class='text-muted text-center py-4 small'>ยังไม่มีลูกค้าทักมาครับ</div>";
}

foreach ($chatUsers as $u) {
    // 🌟 เช็คว่ามีข้อความใหม่ไหม ถ้ามีให้สร้าง Badge สีแดง
    $badge = '';
    if ($u['unread_count'] > 0) {
        $badge = "<span class='badge bg-danger rounded-pill ms-2 shadow-sm'>{$u['unread_count']}</span>";
    }

    echo "<div onclick='loadChat({$u['id']}, \"{$u['username']}\")' class='user-list-item p-3 border-bottom d-flex align-items-center'>
            <div class='rounded-circle d-flex align-items-center justify-content-center text-white me-3' style='width: 40px; height: 40px; background-color: #D4AF37; flex-shrink: 0;'>
                <i class='fas fa-user'></i>
            </div>
            <div class='flex-grow-1 overflow-hidden'>
                <div class='d-flex justify-content-between align-items-center'>
                    <h6 class='mb-0 fw-bold text-dark text-truncate'>{$u['username']}</h6>
                    {$badge} 
                </div>
                <small class='text-muted'>แตะเพื่อดูข้อความ</small>
            </div>
          </div>";
}
?>