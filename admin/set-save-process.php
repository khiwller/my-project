<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['set_name'])) {
    
    // รับค่าจากฟอร์ม
    $set_name = $_POST['set_name'];
    $set_description = $_POST['set_description'] ?? '';
    $sub_category = $_POST['sub_category'] ?? 'ชุดเซ็ตพรีเมียม';
    $set_price = floatval($_POST['set_price']);
    $coffin_id = $_POST['coffin_id']; // หีบศพหลัก
    $decoration_ids = $_POST['decoration_ids'] ?? []; // ของตกแต่ง (Array)

    // จัดการเรื่องรูปภาพ (ถ้ามีการอัปโหลด)
    $image_name = NULL;
    if (isset($_FILES['set_image']) && $_FILES['set_image']['error'] == 0) {
        $ext = pathinfo($_FILES['set_image']['name'], PATHINFO_EXTENSION);
        $image_name = 'set_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['set_image']['tmp_name'], '../uploads/' . $image_name);
    }

    try {
        $pdo->beginTransaction();

        // 1. สร้างสินค้าใหม่ลงในตาราง products โดยระบุเป็นประเภท set
        $sqlInsertProduct = "INSERT INTO products (name, description, price, type, sale_type, sub_category, image_url, is_active) 
                             VALUES (?, ?, ?, 'coffin', 'set', ?, ?, 1)";
        $stmtProduct = $pdo->prepare($sqlInsertProduct);
        $stmtProduct->execute([
            $set_name, 
            $set_description, 
            $set_price, 
            $sub_category, 
            $image_name
        ]);

        // 2. ดึง ID ของสินค้าเซ็ตที่เพิ่งสร้าง
        $new_set_id = $pdo->lastInsertId();

        // 3. บันทึกความสัมพันธ์ของสินค้าในเซ็ต (Mapping Table)
        $stmtItem = $pdo->prepare("INSERT INTO product_set_items (parent_product_id, child_product_id) VALUES (?, ?)");

        // เพิ่มหีบศพหลักเข้าไปในรายการส่วนประกอบ
        if (!empty($coffin_id)) {
            $stmtItem->execute([$new_set_id, $coffin_id]);
        }

        // เพิ่มของตกแต่งทั้งหมดเข้าไปในรายการส่วนประกอบ
        if (!empty($decoration_ids)) {
            foreach ($decoration_ids as $child_id) {
                $stmtItem->execute([$new_set_id, $child_id]);
            }
        }

        $pdo->commit();
        header("Location: products.php?msg=create_set_success");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        // หากอัปโหลดรูปไปแล้วแต่ DB พัง ให้ลบรูปทิ้ง
        if ($image_name && file_exists('../uploads/' . $image_name)) {
            unlink('../uploads/' . $image_name);
        }
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
} else {
    header("Location: products.php");
    exit();
}