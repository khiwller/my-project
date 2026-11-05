<?php
session_start();
require_once '../config/db.php';

// ฟังก์ชันเช็ค Admin (ถ้าคุณแยกไว้ใน header ให้ copy มาวางหรือ require ไฟล์ที่ถูกต้อง)
// checkAdmin(); 

if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $base_price = floatval($_POST['price']); // รับราคาโลงเปล่าๆ ที่พิมพ์มา
    $type = $_POST['type']; // coffin หรือ decoration
    $is_active = $_POST['is_active'];
    
    // -----------------------------------------------------------------
    // 🌟 จัดการระบบ Sub Category (ใช้ได้ทั้งโลงศพและของตกแต่ง)
    // -----------------------------------------------------------------
    $sub_category = null;
    if (isset($_POST['sub_category_select'])) {
        if ($_POST['sub_category_select'] === 'new') {
            // ถ้าแอดมินเลือก "+ เพิ่มหมวดหมู่ย่อยใหม่..." ให้ดึงค่าจาก input ที่พิมพ์เข้ามา
            $sub_category = trim($_POST['new_sub_category'] ?? '');
        } else {
            // ถ้าเลือกจาก dropdown ปกติ
            $sub_category = trim($_POST['sub_category_select']);
        }
    }

    // -----------------------------------------------------------------
    // 🌟 จัดการระบบ Sale Type (มีเฉพาะโลงศพเท่านั้น)
    // -----------------------------------------------------------------
    $sale_type = null;
    if ($type === 'coffin') {
        $sale_type = $_POST['sale_type'] ?? 'individual';
    }

    // -----------------------------------------------------------------
    // 🌟 ระบบคำนวณราคารวมอัตโนมัติ (ถ้ารูปแบบการขายเป็นแบบเซ็ต)
    // -----------------------------------------------------------------
    $final_price = $base_price; // ตั้งต้นด้วยราคาที่พิมพ์มา

    if ($type === 'coffin' && $sale_type === 'set' && !empty($_POST['set_items'])) {
        // สร้างเครื่องหมาย ? ตามจำนวนของที่ติ๊กเลือก เพื่อเอาไปดึงราคาจากฐานข้อมูล
        $placeholders = implode(',', array_fill(0, count($_POST['set_items']), '?'));
        
        // ดึงผลรวมราคาของตกแต่งที่ถูกเลือก
        $stmtCalc = $pdo->prepare("SELECT SUM(price) as total_addons FROM products WHERE id IN ($placeholders)");
        $stmtCalc->execute($_POST['set_items']);
        $resultCalc = $stmtCalc->fetch();
        
        $addon_price = floatval($resultCalc['total_addons']);
        
        // เอาราคาโลงเปล่า + ราคารวมของตกแต่ง
        $final_price = $base_price + $addon_price; 
    }
    // -----------------------------------------------------------------

    // อัปโหลดรูปภาพ
    $image_url = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFilename = time() . "_" . rand(1000,9999) . "." . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $newFilename)) {
            $image_url = $newFilename;
        }
    }

    try {
        $pdo->beginTransaction();

        // 1. บันทึกสินค้าหลัก
        $sql = "INSERT INTO products (name, description, price, type, sub_category, sale_type, image_url, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $description, $final_price, $type, $sub_category, $sale_type, $image_url, $is_active]);
        
        $parentId = $pdo->lastInsertId();

        // 2. ถ้าเป็นแบบเซ็ต (Set) -> บันทึกข้อมูลว่าเซ็ตนี้มีอะไรบ้างลงตารางลูก
        if ($type === 'coffin' && $sale_type === 'set' && !empty($_POST['set_items'])) {
            $sqlSet = "INSERT INTO product_set_items (parent_product_id, child_product_id) VALUES (?, ?)";
            $stmtSet = $pdo->prepare($sqlSet);
            
            foreach ($_POST['set_items'] as $childId) {
                $stmtSet->execute([$parentId, $childId]);
            }
        }

        $pdo->commit();
        header("Location: products.php?msg=added");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: products.php");
    exit();
}
?>