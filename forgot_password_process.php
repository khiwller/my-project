<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

session_start();
require_once 'config/db.php';
date_default_timezone_set('Asia/Bangkok');

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $stmtUpdate = $pdo->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE email = :email");
        $stmtUpdate->execute([
            ':token' => $token,
            ':expires' => $expires,
            ':email' => $email
        ]);

        $resetLink = "http://localhost/sha/reset_password.php?token=" . $token; 
        
        $mail = new PHPMailer(true);

        try {
            // 🌟 1. เปิดโหมด Debug ดูข้อความจาก Server 
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER; 

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'thanapholmuansaard@gmail.com';  
            $mail->Password   = 'cxdgfuezxagmrkrw'; // 🔴 อย่าลืมใส่รหัสผ่านแอป 16 ตัวตรงนี้นะครับ
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port       = 587; 

            // 🌟 2. ข้ามการตรวจ SSL Certificate ของ XAMPP
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->CharSet = 'UTF-8';
            $mail->setFrom('thanapholmuansaard@gmail.com', 'สมหมายหีบทองอยุธยา'); 
            $mail->addAddress($email); 

            $mail->isHTML(true);
            $mail->Subject = 'แจ้งเตือนการรีเซ็ทรหัสผ่าน - สมหมายหีบทองอยุธยา';
            $mail->Body = "คลิกที่ลิงก์เพื่อตั้งรหัสผ่านใหม่: <a href='{$resetLink}'>{$resetLink}</a>";
            
            // สั่งส่งเมล
            $mail->send();

            // 🌟 3. ถ้าสำเร็จ ค่อยเด้งกลับหน้า Login
            echo "<script>
                    alert('ส่งอีเมลสำเร็จ! กรุณาตรวจสอบกล่องจดหมาย'); 
                    window.location='login.php';
                  </script>";
            exit();

        } catch (Exception $e) {
            // 🌟 4. ถ้าส่งไม่ผ่าน โชว์ Error ค้างไว้ให้เห็นชัดๆ
            echo "<div style='background: #fff; padding: 20px; border: 2px solid red;'>";
            echo "<h2>❌ ระบบส่งอีเมลล้มเหลว!</h2>";
            echo "<strong>สาเหตุจาก PHPMailer:</strong> " . $mail->ErrorInfo;
            echo "</div>";
            exit(); 
        }
    } else {
        echo "<script>alert('ไม่พบอีเมลนี้ในระบบ'); window.location='forgot_password.php';</script>";
        exit();
    }
}
?>