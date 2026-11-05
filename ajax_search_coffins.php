<?php
require_once 'config/db.php';

// 1. รับค่าจาก AJAX (JS ส่ง q, sc, st มา)
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sub_cat = isset($_GET['sc']) ? trim($_GET['sc']) : '';
$sale_type = isset($_GET['st']) ? trim($_GET['st']) : '';

// 2. สร้าง Query พื้นฐาน
$sql = "SELECT * FROM products WHERE type = 'coffin' AND is_active = 1";
$params = [];

// 3. เงื่อนไขการค้นหา (ชื่อ หรือ รายละเอียด)
if ($search !== '') {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// 4. เงื่อนไขหมวดหมู่ย่อย (Sub Category)
if ($sub_cat !== '') {
    $sql .= " AND sub_category = ?";
    $params[] = $sub_cat;
}

// 5. เงื่อนไขรูปแบบการขาย (Individual หรือ Set)
if ($sale_type !== '') {
    $sql .= " AND sale_type = ?";
    $params[] = $sale_type;
}

// เรียงลำดับ: เอาแบบเซ็ตขึ้นก่อน ตามด้วยหมวดหมู่ย่อย และ ID ล่าสุด
$sql .= " ORDER BY sale_type DESC, sub_category ASC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. 🌟 จัดกลุ่มข้อมูลให้ตรงกับที่ JavaScript คาดหวัง
$groupedResults = [];

foreach ($allProducts as $p) {
    if ($p['sale_type'] === 'set') {
        // ถ้าเป็นแบบเซ็ต ให้รวมกลุ่มไว้ในหัวข้อ "แพ็กเกจหีบศพจัดเซ็ต"
        $groupedResults['แพ็กเกจหีบศพจัดเซ็ต'][] = $p;
    } else {
        // ถ้าเป็นแบบรายชิ้น ให้แยกตาม sub_category (ถ้าไม่มีให้ใช้ 'หีบศพมาตรฐาน')
        $catName = !empty($p['sub_category']) ? $p['sub_category'] : 'หีบศพมาตรฐาน';
        $groupedResults[$catName][] = $p;
    }
}

// 7. ส่งค่ากลับเป็น JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($groupedResults);
exit;