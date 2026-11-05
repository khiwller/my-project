<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['cat']) ? trim($_GET['cat']) : '';

$sql = "SELECT id, name, price, image_url FROM products WHERE type = 'decoration' AND sub_category = ? AND is_active = 1";
$params = [$category];

if ($search !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
}

$sql .= " ORDER BY id DESC LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>