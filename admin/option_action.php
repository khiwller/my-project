<?php
require_once '../config/db.php';
session_start();

$action = $_REQUEST['action'] ?? '';
$id = $_REQUEST['id'] ?? 0;

if ($action == 'add') {
    $stmt = $pdo->prepare("INSERT INTO product_options (label, option_type, parent_type) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['label'], $_POST['type'], $_POST['parent']]);
} 
elseif ($action == 'edit') {
    $stmt = $pdo->prepare("UPDATE product_options SET label = ?, option_type = ?, parent_type = ? WHERE id = ?");
    $stmt->execute([$_POST['label'], $_POST['type'], $_POST['parent'], $_POST['id']]);
} 
elseif ($action == 'toggle') {
    $stmt = $pdo->prepare("UPDATE product_options SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
} 
elseif ($action == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM product_options WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: product-options.php");
exit();