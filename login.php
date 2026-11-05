<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #ffffff; }
        .serif { font-family: 'Playfair Display', serif; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        
        /* Custom Input Style */
        .input-premium {
            width: 100%;
            background-color: #f9fafb;
            border: 1px solid #f3f4f6;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            border-radius: 0.125rem;
        }
        .input-premium:focus {
            outline: none;
            border-color: #D4AF37;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
        }
    </style>
</head>
<body class="text-gray-800">

    <main class="min-h-screen flex items-center justify-center px-6 py-12 bg-gray-50/50">
        <div class="max-w-md w-full">
            
            <div class="text-center mb-10">
                <a href="index.php" class="serif text-3xl md:text-4xl font-bold tracking-widest text-gray-900 uppercase block mb-2">
                    สมหมายหีบทอง
                </a>
            </div>

            <div class="bg-white border border-gray-100 shadow-2xl rounded-sm overflow-hidden">
                <div class="bg-[#121212] h-1.5 w-full"></div>
                
                <div class="p-8 md:p-10">
                    <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center serif">ยินดีต้อนรับกลับมา</h2>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-medium flex items-center">
                            <i class="fas fa-exclamation-circle mr-3"></i>
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="login_process.php" method="POST" class="space-y-6">
                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">Username</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-300">
                                    <i class="fas fa-user text-xs"></i>
                                </span>
                                <input type="text" name="username" class="input-premium pl-11" placeholder="ชื่อผู้ใช้งานของคุณ" required autofocus>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">Password</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-300">
                                    <i class="fas fa-lock text-xs"></i>
                                </span>
                                <input type="password" name="password" class="input-premium pl-11" placeholder="รหัสผ่าน" required>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <a href="forgot_password.php" class="text-[11px] font-bold text-gray-400 hover:text-[#D4AF37] transition">
                                ลืมรหัสผ่านใช่หรือไม่?
                            </a>
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" name="login" class="w-full gold-gradient text-black font-black py-4 rounded-sm uppercase tracking-[0.3em] text-xs hover:shadow-[0_0_30px_rgba(212,175,55,0.4)] transition duration-500 transform active:scale-95">
                                เข้าสู่ระบบ
                            </button>
                        </div>
                    </form>

                    <div class="mt-10 text-center space-y-4 pt-8 border-t border-gray-50">
                        <p class="text-sm text-gray-400">ยังไม่มีบัญชีสมาชิก?</p>
                        <a href="register.php" class="inline-block text-xs font-bold uppercase tracking-widest text-[#D4AF37] hover:text-black transition">
                            สมัครสมาชิกใหม่ <i class="fas fa-arrow-right ml-2 text-[10px]"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-8">
                <a href="index.php" class="text-[16px] font-bold uppercase tracking-[0.5em] text-gray-300 hover:text-gray-900 transition">
                    <i class="fas fa-long-arrow-alt-left mr-2"></i> กลับหน้าหลัก
                </a>
            </div>
        </div>
    </main>

</body>
</html>