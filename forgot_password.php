<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน | สมหมายหีบทองอยุธยา</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body { font-family: 'Prompt', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-md w-full bg-white p-8 md:p-10 shadow-2xl rounded-sm border-t-4 border-[#D4AF37]">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-50 text-[#D4AF37] mb-4">
                <i class="fas fa-envelope-open-text text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">รีเซ็ทรหัสผ่าน</h2>
            <p class="text-sm text-gray-500">กรุณากรอกอีเมลที่ใช้สมัครสมาชิก เราจะส่งลิงก์สำหรับตั้งรหัสผ่านใหม่ไปให้คุณทางอีเมล</p>
        </div>
        
        <form action="forgot_password_process.php" method="POST" class="space-y-6">
            <div>
                <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-300">
                        <i class="fas fa-at text-xs"></i>
                    </span>
                    <input type="email" name="email" class="w-full bg-gray-50 border border-gray-200 p-3 pl-11 text-sm focus:outline-none focus:border-[#D4AF37] focus:ring-1 focus:ring-[#D4AF37] rounded-sm transition-all" placeholder="อีเมลของคุณ" required autofocus>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-[#121212] text-white font-black py-4 rounded-sm uppercase tracking-[0.2em] text-xs hover:bg-[#D4AF37] transition duration-300 shadow-md">
                ส่งลิงก์ยืนยัน
            </button>
        </form>

        <div class="mt-8 text-center pt-6 border-t border-gray-100">
            <a href="login.php" class="text-[11px] font-bold uppercase tracking-widest text-gray-400 hover:text-gray-900 transition flex items-center justify-center">
                <i class="fas fa-long-arrow-alt-left mr-2"></i> กลับไปหน้าเข้าสู่ระบบ
            </a>
        </div>

    </div>
</body>
</html>