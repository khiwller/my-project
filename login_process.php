<?php
session_start();
require_once 'config/db.php';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            
            if ($user['is_active'] == 0) {
                $_SESSION['error'] = "บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ";
                header("Location: login.php");
                exit();
            }

            // บันทึก Session พื้นฐาน
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['points'] = $user['current_points'];

            // 🌟 --- เริ่มระบบ Merge Cart (ย้ายของจาก Session ลง Database) --- 🌟
            if (!empty($_SESSION['cart'])) {
                try {
                    $sqlMerge = "INSERT INTO cart_items (user_id, product_id, qty, purpose, addons, set_items) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmtMerge = $pdo->prepare($sqlMerge);

                    foreach ($_SESSION['cart'] as $item) {
                        $stmtMerge->execute([
                            $user['id'],
                            $item['product_id'],
                            $item['qty'],
                            $item['purpose'],
                            // แปลง array กลับเป็น json เพื่อเก็บลง Database
                            json_encode($item['addons'], JSON_UNESCAPED_UNICODE),
                            json_encode($item['set_items'], JSON_UNESCAPED_UNICODE)
                        ]);
                    }

                    // เมื่อย้ายลง Database ครบแล้ว ให้ล้างตะกร้าใน Session ทิ้ง
                    unset($_SESSION['cart']);
                    
                } catch (Exception $e) {
                    // ถ้า Merge พลาด (เช่น โครงสร้างตารางไม่ตรง) 
                    // ให้ข้ามไปก่อนเพื่อให้ user เข้าใช้งานได้ปกติ แล้วแอบบันทึก Log ไว้
                    error_log("Merge Cart Error: " . $e->getMessage());
                }
            }
            // -------------------------------------------------------------

            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();

        } else {
            $_SESSION['error'] = "ชื่อผู้ใช้ หรือ รหัสผ่านไม่ถูกต้อง!";
            header("Location: login.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "มีปัญหาในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}