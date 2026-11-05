<?php
session_start();
require_once '../config/db.php';
checkAdmin();

if (isset($_GET['id']) && isset($_GET['action'])) {
    $orderId = $_GET['id'];
    $action = $_GET['action'];

    try {
        $pdo->beginTransaction();

        if ($action == 'approve') {
            // 1. ดึงข้อมูล Order มาเช็ค
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if ($order['status'] !== 'pending') {
                die("รายการนี้ถูกดำเนินการไปแล้ว");
            }

            // 2. อัปเดตสถานะเป็น Paid/Completed
            $updateSql = "UPDATE orders SET status = 'paid' WHERE id = ?";
            $pdo->prepare($updateSql)->execute([$orderId]);

            // 3. *** ไฮไลท์: ถ้าเป็น Donation ให้แต้ม ***
            if ($order['order_type'] == 'donation') {
                // สูตรคำนวณ: 10 บาท ได้ 1 แต้ม (ตัวอย่าง)
                $pointsEarned = floor($order['total_amount'] / 10);

                if ($pointsEarned > 0) {
                    // เพิ่มแต้มให้ User
                    $pdo->prepare("UPDATE users SET current_points = current_points + ? WHERE id = ?")
                        ->execute([$pointsEarned, $order['user_id']]);

                    // บันทึก Log การได้แต้ม
                    $pdo->prepare("INSERT INTO point_logs (user_id, order_id, points_change, description) VALUES (?, ?, ?, 'ได้รับแต้มจากการบริจาค')")
                        ->execute([$order['user_id'], $orderId, $pointsEarned]);
                }
            }
        } elseif ($action == 'reject') {
            // เปลี่ยนสถานะเป็น cancelled
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
            // กรณี Reject ถ้ามีการใช้แต้มไปแล้ว ต้องคืนแต้มให้ User ด้วย (Logic เพิ่มเติม)
        }

        $pdo->commit();
        header("Location: orders.php?msg=success");

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>