<?php
session_start();
require_once 'config/db.php';

// ฟังก์ชันช่วยเหลือสำหรับแสดง SweetAlert2
function showSweetAlert($title, $text, $icon, $redirectUrl = null) {
    echo "
    <!DOCTYPE html>
    <html lang='th'>
    <head>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <link href='https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <style>
            body { font-family: 'IBM Plex Sans Thai', sans-serif; background-color: #ffffff; }
            .swal2-popup { border-radius: 0px !important; }
            .swal2-confirm { 
                background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%) !important; 
                color: black !important; font-weight: bold !important; border-radius: 0px !important;
                padding: 12px 35px !important; text-transform: uppercase !important; letter-spacing: 1px !important;
            }
        </style>
    </head>
    <body>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '$title',
                    text: '$text',
                    icon: '$icon',
                    confirmButtonText: 'ตกลง',
                }).then((result) => {
                    " . ($redirectUrl === 'back' ? "window.history.back();" : ($redirectUrl ? "window.location='$redirectUrl';" : "")) . "
                });
            });
        </script>
    </body>
    </html>";
}

if (isset($_POST['confirm_order'])) {
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $userId = $_SESSION['user_id'];
    $selectedKeys = $_POST['checkout_keys'] ?? []; 
    
    if (empty($selectedKeys)) {
        showSweetAlert('ไม่พบรายการ', 'กรุณาเลือกรายการสินค้าจากตะกร้าก่อนครับ', 'warning', 'cart.php');
        exit();
    }

    // 1. รับค่าจากฟอร์ม
    $orderType = $_POST['order_type']; 
    $shippingFee = isset($_POST['shipping_fee']) ? floatval($_POST['shipping_fee']) : 0;
    $discountAmount = isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0;
    $pointsToUse = intval($discountAmount); 

    $shippingPhone = $_POST['shipping_phone'] ?? null;
    $shippingAddressDetail = $_POST['shipping_address_detail'] ?? null;
    $deliveryDistance = isset($_POST['delivery_distance']) ? floatval($_POST['delivery_distance']) : 0;
    $deliveryLat = !empty($_POST['delivery_lat']) ? floatval($_POST['delivery_lat']) : null;
    $deliveryLng = !empty($_POST['delivery_lng']) ? floatval($_POST['delivery_lng']) : null;

    // 2. ดึงข้อมูลสินค้าจาก DB เพื่อคำนวณราคาที่ถูกต้อง (Security)
    $stmtAll = $pdo->query("SELECT * FROM products");
    $productsDB = [];
    foreach ($stmtAll->fetchAll() as $p) {
        $productsDB[$p['id']] = $p;
    }

    // 3. ดึงข้อมูลจริงจากตาราง cart_items
    $placeholders = implode(',', array_fill(0, count($selectedKeys), '?'));
    $stmtCart = $pdo->prepare("SELECT * FROM cart_items WHERE id IN ($placeholders) AND user_id = ?");
    $stmtCart->execute(array_merge($selectedKeys, [$userId]));
    $dbCartItems = $stmtCart->fetchAll(PDO::FETCH_ASSOC);

    if (empty($dbCartItems)) {
        showSweetAlert('ข้อผิดพลาด', 'ไม่พบข้อมูลในตะกร้าสินค้าของคุณ', 'error', 'cart.php');
        exit();
    }

    // 🌟 4. คำนวณราคาสินค้าเท่านั้น (Product Subtotal)
    $productSubtotal = 0; 
    foreach ($dbCartItems as $item) {
        $pId = $item['product_id'];
        if (!isset($productsDB[$pId])) continue;

        $productSubtotal += (floatval($productsDB[$pId]['price']) * intval($item['qty']));

        $addons = json_decode($item['addons'], true);
        if (!empty($addons)) {
            foreach ($addons as $ad) {
                $aId = is_array($ad) ? ($ad['id'] ?? 0) : (int)$ad;
                $aQty = is_array($ad) ? ($ad['qty'] ?? 1) : 1;
                if (isset($productsDB[$aId])) {
                    $productSubtotal += (floatval($productsDB[$aId]['price']) * $aQty);
                }
            }
        }
    }

    // 🌟 5. คำนวณยอดที่ต้องโอนจริง (Net Payable) เพื่อใช้ตรวจสอบสลิป
    if ($orderType == 'donation') {
        $shippingFee = 0;
        $discountAmount = 0;
        $pointsEarned = floor($productSubtotal * 0.10); 
    } else {
        $pointsEarned = 0;
    }

    $netPayable = ($productSubtotal + $shippingFee) - $discountAmount;
    if ($netPayable < 0) $netPayable = 0;

    // 6. จัดการรูปภาพสลิป
    $slipFileName = null; 
    if ($netPayable > 0) {
        if (!isset($_FILES['slip_image']) || $_FILES['slip_image']['error'] != 0) {
            showSweetAlert('แนบสลิป', 'กรุณาแนบหลักฐานการโอนเงินเพื่อยืนยันรายการครับ', 'error', 'back');
            exit();
        }
        $targetDir = "uploads/slips/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        $fileExt = strtolower(pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION));
        $slipFileName = "slip_" . time() . "_" . $userId . "_" . rand(100,999) . "." . $fileExt;
        move_uploaded_file($_FILES['slip_image']['tmp_name'], $targetDir . $slipFileName);
    } 

    // 7. บันทึกลงฐานข้อมูล
    try {
        $pdo->beginTransaction();

        $initialStatus = ($netPayable <= 0) ? 'paid' : 'pending';

        // 🌟 แก้ไขตรงนี้: total_amount = $productSubtotal (เฉพาะราคาสินค้า)
        $sqlOrder = "INSERT INTO orders (
                        user_id, total_amount, delivery_fee, discount_amount, points_used, points_earned,
                        slip_image, status, order_type, shipping_phone, 
                        shipping_address_detail, delivery_lat, delivery_lng, delivery_distance, created_at
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $pdo->prepare($sqlOrder)->execute([
            $userId, 
            $productSubtotal, // 🌟 เปลี่ยนจาก $netPayable เป็น $productSubtotal
            $shippingFee, 
            $discountAmount, 
            $pointsToUse, 
            $pointsEarned,
            $slipFileName, 
            $initialStatus, 
            $orderType, 
            $shippingPhone,
            $shippingAddressDetail, 
            $deliveryLat, 
            $deliveryLng, 
            $deliveryDistance
        ]);
        
        $orderId = $pdo->lastInsertId();

        // บันทึกรายการสินค้าลง order_items
        $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
        $stmtItem = $pdo->prepare($sqlItem);

        foreach ($dbCartItems as $item) {
            $stmtItem->execute([$orderId, $item['product_id'], $item['qty'], $productsDB[$item['product_id']]['price']]);
            
            $addons = json_decode($item['addons'], true);
            if (!empty($addons)) {
                foreach ($addons as $ad) {
                    $aId = is_array($ad) ? ($ad['id'] ?? 0) : (int)$ad;
                    $aQty = is_array($ad) ? ($ad['qty'] ?? 1) : 1;
                    if (isset($productsDB[$aId])) {
                        $stmtItem->execute([$orderId, $aId, $aQty, $productsDB[$aId]['price']]);
                    }
                }
            }
        }

        // ตัดแต้มสะสม
        if ($pointsToUse > 0) {
            $pdo->prepare("UPDATE users SET current_points = current_points - ? WHERE id = ?")->execute([$pointsToUse, $userId]);
            $pdo->prepare("INSERT INTO point_logs (user_id, order_id, points_change, description) VALUES (?, ?, ?, 'ใช้แต้มแลกส่วนลดสินค้า')")
                ->execute([$userId, $orderId, -$pointsToUse]);
        }

        // ลบจากตะกร้า
        $pdo->prepare("DELETE FROM cart_items WHERE id IN ($placeholders) AND user_id = ?")
            ->execute(array_merge($selectedKeys, [$userId]));

        $pdo->commit();
        showSweetAlert('สำเร็จ', 'บันทึกคำสั่งซื้อเรียบร้อย ขอบคุณที่ใช้บริการครับ', 'success', 'history.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        if ($slipFileName) @unlink("uploads/slips/" . $slipFileName);
        showSweetAlert('เกิดข้อผิดพลาด', 'ไม่สามารถบันทึกได้: ' . $e->getMessage(), 'error', 'cart.php');
    }
}