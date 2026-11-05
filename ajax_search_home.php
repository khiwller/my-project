<?php
require_once 'config/db.php';

// รับค่าจาก AJAX Request
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sub_cat = isset($_GET['sc']) ? trim($_GET['sc']) : '';
$sale_type = isset($_GET['st']) ? trim($_GET['st']) : '';

// สร้างคำสั่ง SQL พื้นฐาน
$sql = "SELECT * FROM products WHERE type = 'coffin' AND is_active = 1";
$params = [];

// เงื่อนไขค้นหาชื่อ
if ($search !== '') {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// เงื่อนไขหมวดหมู่ย่อย
if ($sub_cat !== '') {
    $sql .= " AND sub_category = ?";
    $params[] = $sub_cat;
}

// เงื่อนไขรูปแบบการขาย
if ($sale_type !== '') {
    $sql .= " AND sale_type = ?";
    $params[] = $sale_type;
}

$sql .= " ORDER BY id DESC";

// ดึงข้อมูล
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$individuals = [];
$sets = [];

// แยกกลุ่มสินค้า
foreach($allProducts as $p) {
    if($p['sale_type'] == 'set') {
        $sets[] = $p;
    } else {
        $individuals[] = $p;
    }
}

// ตัดเอาเฉพาะ 4 รายการแรก (เหมือนที่ทำในหน้าแรก)
$result = [
    'individuals' => array_slice($individuals, 0, 4),
    'sets' => array_slice($sets, 0, 4)
];

// ส่งข้อมูลกลับเป็น JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
?>