<?php 
session_start();
require_once 'config/db.php';

// ตรวจสอบ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูล User ปัจจุบัน
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลส่วนตัว | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        html, body { margin: 0; padding: 0; width: 100%; background-color: #ffffff; font-family: 'Noto Sans Thai', sans-serif; }
        .serif { font-family: 'Playfair Display', serif; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .text-gold { color: #D4AF37; }
        .bg-charcoal { background-color: #121212; }
        
        .input-premium {
            width: 100%;
            background-color: #f9fafb;
            border: 1px solid #f3f4f6;
            padding: 12px 16px;
            font-size: 16px; 
            transition: all 0.3s;
        }
        .input-premium:focus {
            outline: none;
            border-color: #D4AF37;
            background-color: #ffffff;
            box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.1);
        }
    </style>
</head>
<body class="text-gray-800">

    <?php require_once 'includes/navbar.php'; ?>

    <div class="h-32 md:h-40"></div> 
    
    <main class="max-w-4xl mx-auto px-6 pb-24">
        
        <div class="mb-12 border-b border-gray-100 pb-8 text-center md:text-left">
            <nav class="flex justify-center md:justify-start mb-4 text-[16px] uppercase tracking-[0.4em] font-bold text-gray-300">
                <a href="index.php" class="hover:text-gold transition">หน้าแรก</a>
                <span class="mx-4 text-gray-200">/</span>
                <span class="text-gray-900">แก้ไขข้อมูลส่วนตัว</span>
            </nav>
            <h1 class="serif text-4xl md:text-5xl text-gray-900 leading-tight">แก้ไขข้อมูลส่วนตัว</h1>
        </div>

        <div class="bg-white border border-gray-100 shadow-2xl rounded-sm overflow-hidden">
            <div class="bg-charcoal text-gold p-6 flex items-center gap-4">
                <i class="fas fa-user-cog text-xl"></i>
                <h2 class="serif text-xl font-bold tracking-widest uppercase mb-0">รายละเอียดบัญชี</h2>
            </div>

            <div class="p-8 md:p-12">
                
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="mb-8 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm font-bold">
                        <i class="fas fa-check-circle mr-2"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="mb-8 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-bold">
                        <i class="fas fa-exclamation-triangle mr-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="profile_update.php" method="POST" class="space-y-8">
                    
                    <div>
                        <label class="block text-[16px] font-black uppercase tracking-widest text-gray-400 mb-3">ชื่อผู้ใช้ (Username) <span class="text-red-500">*</span></label>
                        <input type="text" name="username" class="input-premium" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-[16px] font-black uppercase tracking-widest text-gray-400 mb-3">อีเมล <span class="text-red-500">*</span></label>
                            <input type="email" name="email" class="input-premium" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div>
                            <label class="block text-[16px] font-black uppercase tracking-widest text-gray-400 mb-3">เบอร์โทรศัพท์</label>
                            <input type="tel" 
                                name="phone" 
                                class="input-premium" 
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                placeholder="08xxxxxxxx"
                                maxlength="10"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                            <p class="mt-2 text-[12px] text-gray-400">* กรอกเฉพาะตัวเลข 10 หลักเท่านั้น</p>
                        </div>
                    </div>

                    <div class="pt-8 border-t border-gray-100">
                        <div class="flex items-center gap-3 mb-8 text-gray-400">
                            <i class="fas fa-key text-xs"></i>
                            <h3 class="text-[16px] font-black uppercase tracking-[0.3em]">เปลี่ยนรหัสผ่าน (ถ้าต้องการ)</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-[16px] font-black uppercase tracking-widest text-gray-400 mb-3">รหัสผ่านใหม่</label>
                                <input type="password" name="new_password" class="input-premium" placeholder="อย่างน้อย 6 ตัวอักษร" minlength="6">
                                <p class="mt-2 text-[11px] text-gray-400 italic">* เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน</p>
                            </div>
                            <div>
                                <label class="block text-[16px] font-black uppercase tracking-widest text-gray-400 mb-3">ยืนยันรหัสผ่านใหม่</label>
                                <input type="password" name="confirm_password" class="input-premium" placeholder="ยืนยันรหัสผ่านอีกครั้ง" minlength="6">
                            </div>
                        </div>
                    </div>

                    <div class="pt-10 flex flex-col md:flex-row justify-end items-center gap-4">
                        <a href="index.php" class="w-full md:w-auto text-center px-10 py-4 text-xs font-bold uppercase tracking-widest text-gray-400 hover:text-gray-900 transition">
                            ยกเลิก
                        </a>
                        <button type="submit" name="update_profile" class="w-full md:w-auto gold-gradient text-black font-black px-12 py-4 text-xs uppercase tracking-[0.2em] rounded-sm hover:shadow-[0_0_30px_rgba(212,175,55,0.4)] transition duration-500 transform active:scale-95">
                            บันทึกข้อมูลส่วนตัว
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>

</body>
</html>