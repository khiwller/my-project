<?php
session_start();
require_once 'config/db.php'; 

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// --- 🌟 ส่วนที่ 1: เพิ่มสินค้าลงตะกร้า ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    
    if (!isset($_POST['product_id'])) {
        header("Location: index.php");
        exit();
    }

    $productId = $_POST['product_id'];
    $mainQty = isset($_POST['qty']) ? max(1, intval($_POST['qty'])) : 1; 
    $purpose = (isset($_POST['order_purpose']) && $_POST['order_purpose'] === 'donation') ? 'donation' : 'purchase'; 
    
    // ดึงข้อมูลสินค้าหลักจาก DB
    $stmt = $pdo->prepare("SELECT price, name FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $mainProduct = $stmt->fetch();
    
    if (!$mainProduct) {
        header("Location: index.php");
        exit();
    }

    $basePrice = floatval($mainProduct['price']);

    // 1. จัดการ Addons (ของตกแต่งเลือกเอง)
    $selectedAddons = $_POST['addons'] ?? []; 
    $addonQtys = $_POST['addon_qty'] ?? []; 
    $finalAddons = [];
    $addonsTotalPrice = 0;

    if (!empty($selectedAddons)) {
        $placeholders = implode(',', array_fill(0, count($selectedAddons), '?'));
        $stmtAddons = $pdo->prepare("SELECT id, price, name FROM products WHERE id IN ($placeholders)");
        $stmtAddons->execute($selectedAddons);
        $addonsData = $stmtAddons->fetchAll(PDO::FETCH_UNIQUE);

        foreach ($selectedAddons as $addonId) {
            if (isset($addonsData[$addonId])) {
                $qty = max(1, intval($addonQtys[$addonId] ?? 1));
                $price = floatval($addonsData[$addonId]['price']);
                $finalAddons[] = [
                    'id' => $addonId,
                    'name' => $addonsData[$addonId]['name'],
                    'qty' => $qty,
                    'price' => $price,
                    'subtotal' => $price * $qty
                ];
                $addonsTotalPrice += ($price * $qty);
            }
        }
    }

    // 2. จัดการ Set Items (ถ้าเป็นสินค้าชุดเซ็ต)
    $finalSetItems = [];
    if (isset($_POST['is_set']) && isset($_POST['set_items_qty'])) {
        foreach ($_POST['set_items_qty'] as $setId => $setQty) {
            $sQty = max(1, intval($setQty));
            $finalSetItems[] = ['id' => $setId, 'qty' => $sQty];
        }
    }
    
    $itemTotal = ($basePrice * $mainQty) + $addonsTotalPrice;

    // 🚀 --- ตรวจสอบว่า Login หรือไม่ เพื่อเลือกว่าจะลง DB หรือ Session ---
    if (isset($_SESSION['user_id'])) {
        // ✅ กรณี Login: บันทึกลงตาราง cart_items ใน Database
        try {
            $sql = "INSERT INTO cart_items (user_id, product_id, qty, purpose, addons, set_items) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtDB = $pdo->prepare($sql);
            $stmtDB->execute([
                $_SESSION['user_id'],
                $productId,
                $mainQty,
                $purpose,
                json_encode($finalAddons, JSON_UNESCAPED_UNICODE),
                json_encode($finalSetItems, JSON_UNESCAPED_UNICODE)
            ]);
        } catch (Exception $e) {
            die("Error saving to database: " . $e->getMessage());
        }
    } else {
        // ⚪ กรณีไม่ได้ Login: บันทึกลง Session ตามเดิม (เผื่อลูกค้า Guest)
        $cartKey = uniqid();
        $_SESSION['cart'][$cartKey] = [
            'product_id'  => $productId,
            'product_name'=> $mainProduct['name'],
            'base_price'  => $basePrice,
            'qty'         => $mainQty,
            'purpose'     => $purpose,
            'addons'      => $finalAddons,   
            'set_items'   => $finalSetItems, 
            'total_price' => $itemTotal,
            'added_at'    => time()
        ];
    }

    header("Location: cart.php");
    exit();
}

// --- 🌟 ส่วนที่ 2: ลบสินค้าออกจากตะกร้า ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['key'])) {
    $key = $_GET['key'];

    if (isset($_SESSION['user_id'])) {
        // ✅ ลบจาก Database (ID ของแถว)
        $stmtDel = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        $stmtDel->execute([$key, $_SESSION['user_id']]);
    } else {
        // ⚪ ลบจาก Session
        if (isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
        }
    }
    header("Location: cart.php");
    exit();
}

// --- 🌟 ส่วนที่ 3: เคลียร์ตะกร้าทั้งหมด ---
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    if (isset($_SESSION['user_id'])) {
        // ✅ เคลียร์ใน Database เฉพาะของ User คนนี้
        $stmtClear = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmtClear->execute([$_SESSION['user_id']]);
    } else {
        // ⚪ เคลียร์ใน Session
        unset($_SESSION['cart']);
    }
    header("Location: cart.php");
    exit();
}
?>