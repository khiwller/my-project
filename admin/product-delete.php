<?php
session_start();
require_once '../config/db.php';
checkAdmin(); // เช็คสิทธิ์ Admin

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // เริ่มต้น Transaction (ทำงานทุกอย่างพร้อมกัน ถ้าพลาดอย่างใดอย่างหนึ่ง ให้ยกเลิกทั้งหมด)
        $pdo->beginTransaction();

        // 1. ดึงข้อมูลสินค้าก่อน (เพื่อเอารูปภาพไปลบออกจากเครื่องทีหลัง)
        $stmt = $pdo->prepare("SELECT image_url, name FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            // ถ้าไม่เจอสินค้า ให้ Rollback และกลับไป
            $pdo->rollBack();
            header("Location: products.php");
            exit();
        }

        // 2. ลบความสัมพันธ์ในตาราง product_set_items ก่อน 
        // (เผื่อกรณีสินค้าเป็นแม่ข่ายเซ็ต หรือเป็นลูกข่ายในเซ็ต)
        $pdo->prepare("DELETE FROM product_set_items WHERE parent_product_id = ? OR child_product_id = ?")
            ->execute([$id, $id]);

        // 3. พยายามลบข้อมูลสินค้าจาก Database หลัก
        $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $deleteStmt->execute([$id]);

        // ถ้าไม่มี Error ถึงตรงนี้ ให้ยืนยันการลบ (Commit)
        $pdo->commit();

        // 4. ลบไฟล์รูปภาพออกจาก Server (ทำหลังจาก Commit สำเร็จแล้วเท่านั้น)
        if ($product['image_url'] && file_exists("../uploads/" . $product['image_url'])) {
            unlink("../uploads/" . $product['image_url']);
        }

        // กลับไปหน้ารายการสินค้า
        header("Location: products.php?msg=deleted");

    } catch (PDOException $e) {
        // ถ้าเกิด Error ให้กู้ข้อมูลคืน (Rollback) ทันที
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // --- ส่วนจัดการ Error ---
        
        // รหัส 23000 คือ Integrity constraint violation (ติด Foreign Key ในตาราง order_items)
        if ($e->getCode() == '23000') {
            echo "<script>
                alert('⚠️ ไม่สามารถลบสินค้านี้ได้ เนื่องจากมีประวัติการสั่งซื้อแล้ว\\n\\nคำแนะนำ: กรุณาแก้ไขสินค้านี้ แล้วปรับสถานะเป็น \"ปิดการขาย\" แทนครับ');
                window.location = 'products.php';
            </script>";
        } else {
            // Error อื่นๆ
            echo "Error: " . $e->getMessage();
        }
    }
} else {
    header("Location: products.php");
}
?>