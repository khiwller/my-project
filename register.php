<?php 
// ตรวจสอบ Session ภายใน navbar.php แล้ว
require_once 'includes/navbar.php'; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #ffffff; }
        .serif { font-family: 'Playfair Display', serif; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .bg-charcoal { background-color: #121212; }
        
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

        input::-ms-reveal, input::-ms-clear { display: none; }
        
        /* แอนิเมชันสำหรับเครื่องหมายติ๊กถูก */
        .check-item { transition: all 0.3s ease; display: flex; align-items: center; }
        .check-item i { margin-right: 8px; width: 14px; }
        .valid { color: #10b981 !important; } /* สีเขียว Tailwind */
    </style>
</head>
<body class="text-gray-800">

    <div class="h-12 md:h-20"></div>

    <main class="min-h-[80vh] flex items-center justify-center px-6 py-12 md:py-24">
        <div class="max-w-md w-full">
            
            <div class="text-center mb-10">
                <h1 class="serif text-4xl text-gray-900 font-bold">สร้างบัญชีใหม่</h1>
                <div class="h-[1px] w-12 bg-[#D4AF37] mx-auto mt-4"></div>
            </div>

            <div class="bg-white border border-gray-100 shadow-2xl rounded-sm overflow-hidden">
                <div class="bg-charcoal h-1 w-full"></div>
                
                <div class="p-8 md:p-10">
                    <form action="register_process.php" method="POST" class="space-y-6">
                        
                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">ชื่อผู้ใช้งาน</label>
                            <input type="text" name="username" class="input-premium" placeholder="ตั้งชื่อผู้ใช้งาน" required>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">อีเมล</label>
                            <input type="email" name="email" class="input-premium" placeholder="example@mail.com" required>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">รหัสผ่าน</label>
                            <div class="relative">
                                <input type="password" name="password" id="password" class="input-premium !pr-12" placeholder="รหัสผ่าน" required>
                                <button type="button" onclick="togglePassword('password', 'eye-icon')" class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-400 hover:text-gold transition-colors">
                                    <i id="eye-icon" class="fas fa-eye"></i>
                                </button>
                            </div>
                            
                            <div class="mt-3 h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div id="strength-bar" class="h-full w-0 transition-all duration-500 ease-out bg-gray-300"></div>
                            </div>

                            <div class="mt-3 space-y-1.5">
                                <div id="strength-label" class="text-[13px] mb-2 font-bold uppercase tracking-wider text-gray-400">ระดับความปลอดภัย <span id="strength-text"></span></div>
                                
                                <div id="req-len" class="check-item text-[13px] font-medium text-gray-400">
                                    <i class="far fa-circle"></i> ความยาวอย่างน้อย 6 ตัวอักษร
                                </div>
                                <div id="req-upnum" class="check-item text-[13px] font-medium text-gray-400">
                                    <i class="far fa-circle"></i> มีตัวอักษรพิมพ์ใหญ่ (A-Z) และตัวเลข (0-9)
                                </div>
                                <div id="req-spec" class="check-item text-[13px] font-medium text-gray-400">
                                    <i class="far fa-circle"></i> มีอักขระพิเศษ (!@#$%)
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-[13px] font-black uppercase tracking-widest text-gray-400 mb-2">ยืนยันรหัสผ่าน</label>
                            <input type="password" name="confirm_password" class="input-premium" placeholder="ยืนยันรหัสผ่านอีกครั้ง" required>
                        </div>

                        <div class="pt-4">
                            <button type="submit" name="register" class="w-full gold-gradient text-black font-black py-4 rounded-sm uppercase tracking-[0.2em] text-xs hover:shadow-[0_0_30px_rgba(212,175,55,0.4)] transition duration-500 transform active:scale-95">
                                สมัครสมาชิก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        
        // Requirements Elements
        const reqLen = document.getElementById('req-len');
        const reqUpNum = document.getElementById('req-upnum');
        const reqSpec = document.getElementById('req-spec');

        passwordInput.addEventListener('input', function() {
            const val = passwordInput.value;
            
            // ตรวจสอบเงื่อนไขรายข้อ
            const isLenValid = val.length >= 6;
            const isUpNumValid = /[A-Z]/.test(val) && /[0-9]/.test(val);
            const isSpecValid = /[!@#$%^&*(),.?":{}|<>]/.test(val);

            // อัปเดตไอคอนและสีตัวหนังสือ (ติ๊กถูก)
            updateRequirement(reqLen, isLenValid);
            updateRequirement(reqUpNum, isUpNumValid);
            updateRequirement(reqSpec, isSpecValid);

            // คำนวณคะแนนรวม
            let score = 0;
            if (isLenValid) score++;
            if (isUpNumValid) score++;
            if (isSpecValid) score++;

            // ปรับแต่งหลอดความแรง (ใช้ transition-all ของ Tailwind ทำงานร่วมกับความกว้าง)
            strengthBar.classList.remove('bg-red-500', 'bg-yellow-500', 'bg-green-500', 'bg-gray-300');

            if (val.length === 0) {
                strengthBar.style.width = "0%";
                strengthBar.classList.add('bg-gray-300');
                strengthText.innerText = "ว่างเปล่า";
                strengthText.style.color = "#9ca3af";
            } else if (score === 1) {
                strengthBar.style.width = "33.33%";
                strengthBar.classList.add('bg-red-500');
                strengthText.innerText = "ต่ำ";
                strengthText.style.color = "#ef4444";
            } else if (score === 2) {
                strengthBar.style.width = "66.66%";
                strengthBar.classList.add('bg-yellow-500');
                strengthText.innerText = "ปานกลาง";
                strengthText.style.color = "#f59e0b";
            } else if (score === 3) {
                strengthBar.style.width = "100%";
                strengthBar.classList.add('bg-green-500');
                strengthText.innerText = "สูง";
                strengthText.style.color = "#10b981";
            }
        });

        function updateRequirement(element, isValid) {
            const icon = element.querySelector('i');
            if (isValid) {
                element.classList.add('valid');
                icon.classList.replace('far', 'fas');
                icon.classList.replace('fa-circle', 'fa-check-circle');
            } else {
                element.classList.remove('valid');
                icon.classList.replace('fas', 'far');
                icon.classList.replace('fa-check-circle', 'fa-circle');
            }
        }

        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>