<?php
session_start();
require_once 'config/db.php';

if (isset($_POST['confirm_order'])) {
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $userId = $_SESSION['user_id'];
    $orderType = $_POST['order_type']; 
    $pointsToUse = isset($_POST['points_to_use']) ? intval($_POST['points_to_use']) : 0;

    // รับค่าจัดส่งและพิกัดจากฟอร์ม
    $shippingPhone = $_POST['shipping_phone'] ?? null;
    $shippingAddressDetail = $_POST['shipping_address_detail'] ?? null;
    $shippingSubdistrict = $_POST['shipping_subdistrict'] ?? null;
    $shippingDistrict = $_POST['shipping_district'] ?? null;
    $shippingProvince = $_POST['shipping_province'] ?? null;
    $deliveryFee = isset($_POST['delivery_fee']) ? floatval($_POST['delivery_fee']) : 0;
    $deliveryDistance = isset($_POST['delivery_distance']) ? floatval($_POST['delivery_distance']) : 0;
    $deliveryLat = $_POST['delivery_lat'] ?? null;
    $deliveryLng = $_POST['delivery_lng'] ?? null;

    if (empty($_SESSION['cart'])) {
        header("Location: index.php");
        exit();
    }

    // คำนวณราคาสินค้า
    $stmtAll = $pdo->query("SELECT * FROM products");
    $productsDB = [];
    foreach ($stmtAll->fetchAll() as $p) {
        $productsDB[$p['id']] = $p;
    }

    $productTotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        if (!isset($productsDB[$item['product_id']])) continue;
        $thisPrice = $productsDB[$item['product_id']]['price'];
        if (!empty($item['addons'])) {
            foreach ($item['addons'] as $addonId) {
                if (isset($productsDB[$addonId])) {
                    $thisPrice += $productsDB[$addonId]['price'];
                }
            }
        }
        $productTotal += $thisPrice;
    }

    // ตรวจสอบแต้ม
    $stmtUser = $pdo->prepare("SELECT current_points FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $realUserPoints = $stmtUser->fetchColumn();

    if ($orderType == 'donation') {
        $pointsToUse = 0; 
        $deliveryFee = 0; // บริจาคไม่มีค่าจัดส่ง
    }

    $totalAmountBeforeDiscount = $productTotal + $deliveryFee;

    if ($pointsToUse > $realUserPoints) $pointsToUse = $realUserPoints;
    if ($pointsToUse > $totalAmountBeforeDiscount) $pointsToUse = $totalAmountBeforeDiscount;

    $discountAmount = $pointsToUse;
    $netTotal = $totalAmountBeforeDiscount - $discountAmount; // ยอดสุทธิ

    // อัปโหลดสลิป
    $newFileName = null;
    if ($netTotal > 0) {
        if (!isset($_FILES['slip_image']) || $_FILES['slip_image']['error'] != 0) {
            echo "<script>alert('กรุณาแนบสลิปการโอนเงิน'); window.history.back();</script>";
            exit();
        }

        $targetDir = "uploads/slips/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = basename($_FILES['slip_image']['name']);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = time() . "_" . $userId . "_" . rand(1000,9999) . "." . $fileType;
        
        if (!move_uploaded_file($_FILES['slip_image']['tmp_name'], $targetDir . $newFileName)) {
            echo "<script>alert('อัปโหลดรูปภาพล้มเหลว!'); window.history.back();</script>";
            exit();
        }
    } 

    try {
        $pdo->beginTransaction();

        $initialStatus = ($netTotal <= 0) ? 'paid' : 'pending';

        // Insert ลง Orders พร้อมข้อมูลพิกัดจัดส่ง
        $sqlOrder = "INSERT INTO orders (user_id, total_amount, delivery_fee, discount_amount, points_used, slip_image, status, order_type, shipping_phone, shipping_address_detail, shipping_subdistrict, shipping_district, shipping_province, delivery_lat, delivery_lng, delivery_distance) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtOrder = $pdo->prepare($sqlOrder);
        // สังเกตว่า $productTotal (เฉพาะค่าของ) หรือจะเก็บยอดรวมทั้งหมดก็ได้ ในที่นี้เก็บ $productTotal 
        $stmtOrder->execute([$userId, $productTotal, $deliveryFee, $discountAmount, $pointsToUse, $newFileName, $initialStatus, $orderType, $shippingPhone, $shippingAddressDetail, $shippingSubdistrict, $shippingDistrict, $shippingProvince, $deliveryLat, $deliveryLng, $deliveryDistance]);
        
        $orderId = $pdo->lastInsertId();

        // Insert Order Items
        $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
        $stmtItem = $pdo->prepare($sqlItem);

        foreach ($_SESSION['cart'] as $item) {
            if (!isset($productsDB[$item['product_id']])) continue;
            $mainPrice = $productsDB[$item['product_id']]['price'];
            $stmtItem->execute([$orderId, $item['product_id'], 1, $mainPrice]);

            if (!empty($item['addons'])) {
                foreach ($item['addons'] as $addonId) {
                    if (isset($productsDB[$addonId])) {
                        $addonPrice = $productsDB[$addonId]['price'];
                        $stmtItem->execute([$orderId, $addonId, 1, $addonPrice]);
                    }
                }
            }
        }

        // ตัดแต้ม
        if ($pointsToUse > 0) {
            $pdo->prepare("UPDATE users SET current_points = current_points - ? WHERE id = ?")->execute([$pointsToUse, $userId]);
            $pdo->prepare("INSERT INTO point_logs (user_id, order_id, points_change, description) VALUES (?, ?, ?, 'ใช้แต้มแลกส่วนลด')")->execute([$userId, $orderId, -$pointsToUse]);
            $_SESSION['points'] = $realUserPoints - $pointsToUse;
        }

        // อัปเดตข้อมูลติดต่อให้ User (สำหรับใช้ครั้งต่อไป)
        if ($orderType == 'purchase' && $shippingPhone) {
            $fullAddress = trim("$shippingAddressDetail ต.$shippingSubdistrict อ.$shippingDistrict จ.$shippingProvince");
            $pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?")->execute([$shippingPhone, $fullAddress, $userId]);
        }

        $pdo->commit();
        unset($_SESSION['cart']);

        echo "<script>alert('ยืนยันคำสั่งซื้อเรียบร้อย!'); window.location = 'history.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }

} else {
    header("Location: index.php");
    exit();
}
?>