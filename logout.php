<?php
// 1. เริ่มต้น Session (จำเป็นต้องเรียกก่อน เพื่อให้รู้ว่าจะทำลาย Session ของใคร)
session_start();

// 2. ล้างค่าตัวแปร Session ทั้งหมดในหน่วยความจำ
$_SESSION = array();

// 3. ทำลาย Session ID ที่ฝั่ง Server
session_destroy();

// 4. (ไม่บังคับ แต่แนะนำ) ลบ Cookie ของ Session ที่ฝั่ง Browser เพื่อความสะอาดหมดจด
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. ส่งผู้ใช้กลับไปหน้า Login หรือหน้าแรก
header("Location: index.php");
exit();
?>