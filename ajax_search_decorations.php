<?php
require_once 'config/db.php';

// รับค่าจาก AJAX
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sub_cat = isset($_GET['sc']) ? trim($_GET['sc']) : '';

// 1. ค้นหาเฉพาะของตกแต่ง (decoration)
$sql = "SELECT * FROM products WHERE type = 'decoration' AND is_active = 1";
$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($sub_cat !== '') {
    $sql .= " AND sub_category = ?";
    $params[] = $sub_cat;
}

$sql .= " ORDER BY sub_category ASC, id DESC"; // เรียงตามกลุ่มหมวดหมู่

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allDecorations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. จัดกลุ่มแบบ Dynamic
$groupedResults = [];

foreach($allDecorations as $p) {
    // ถ้า sub_category เป็นค่าว่าง ให้จัดอยู่ในกลุ่ม "อื่นๆ"
    $categoryName = !empty($p['sub_category']) ? $p['sub_category'] : 'อื่นๆ';
    
    // สร้าง Array ย่อยตามชื่อกลุ่มถ้ายังไม่มี
    if (!isset($groupedResults[$categoryName])) {
        $groupedResults[$categoryName] = [];
    }
    
    // เพิ่มสินค้าลงในกลุ่มนั้นๆ
    $groupedResults[$categoryName][] = $p;
}

// 3. ส่งข้อมูลกลับไปให้ JS
header('Content-Type: application/json; charset=utf-8');
echo json_encode($groupedResults);
?>