<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPoints = 0;
$cartCount = 0;

if (isset($_SESSION['user_id']) && isset($pdo)) {
    // 1. ดึงแต้มผู้ใช้ล่าสุด
    $stmtPoints = $pdo->prepare("SELECT current_points FROM users WHERE id = ?");
    $stmtPoints->execute([$_SESSION['user_id']]);
    $userRow = $stmtPoints->fetch();
    if ($userRow) {
        $currentPoints = $userRow['current_points'];
        $_SESSION['points'] = $currentPoints; 
    }

    // 🌟 2. นับจำนวนสินค้าในตะกร้าจาก Database (สำหรับ User ที่ Login แล้ว)
    $stmtCart = $pdo->prepare("SELECT COUNT(*) FROM cart_items WHERE user_id = ?");
    $stmtCart->execute([$_SESSION['user_id']]);
    $cartCount = $stmtCart->fetchColumn();

} else {
    // 3. กรณีไม่ได้ Login ให้นับจาก Session ตามเดิม
    $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
}
?>

<style>
    .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
    .text-gold {
        background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .serif { font-family: 'Playfair Display', serif; }
    
    #mobileMenu {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        max-height: 0; opacity: 0; overflow: hidden; pointer-events: none;
    }
    
    #mobileMenu.is-open { max-height: 600px; opacity: 1; pointer-events: auto; }
    
    #userDropdown { min-width: 240px; z-index: 110; display: none; }
    #userDropdown.show { display: block; }
</style>

<nav class="fixed top-0 left-0 w-full z-[100] bg-black/80 backdrop-blur-md border-b border-yellow-900/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 md:py-4 flex justify-between items-center">
        
        <div class="flex items-center space-x-3 md:space-x-0">
            <button class="md:hidden text-gray-300 text-2xl p-2 hover:text-gold transition-colors" id="hamburgerBtn" onclick="toggleMobileMenu(event)">
                <i class="fas fa-bars"></i>
            </button>

            <a href="index.php" class="text-xl md:text-2xl font-bold tracking-widest text-gold serif uppercase whitespace-nowrap">
                สมหมายหีบทองอยุธยา
            </a>
        </div>
        
        <div class="hidden md:flex space-x-8 text-[16px] uppercase tracking-widest text-gray-300 font-medium">
            <a href="index.php" class="hover:text-yellow-500 transition <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-yellow-500 font-bold' : '' ?>">หน้าแรก</a>
            <a href="coffins.php" class="hover:text-yellow-500 transition <?= basename($_SERVER['PHP_SELF']) == 'coffins.php' ? 'text-yellow-500 font-bold' : '' ?>">หีบศพ</a>
            <a href="decorations.php" class="hover:text-yellow-500 transition <?= basename($_SERVER['PHP_SELF']) == 'decorations.php' ? 'text-yellow-500 font-bold' : '' ?>">ดอกไม้หน้าพิธี</a>
            <a href="donate.php" class="hover:text-yellow-500 transition <?= basename($_SERVER['PHP_SELF']) == 'donate.php' ? 'text-yellow-500 font-bold' : '' ?>">บริจาค</a>
        </div>

        <div class="flex items-center space-x-2 md:space-x-5">
            <a href="cart.php" class="relative p-2 text-gray-300 hover:text-gold transition">
                <i class="fas fa-shopping-basket text-lg md:text-xl"></i>
                <?php if($cartCount > 0): ?>
                    <span class="absolute top-1 right-0 bg-red-600 text-white text-[9px] w-4 h-4 flex items-center justify-center rounded-full border border-black font-bold">
                        <?= $cartCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="relative">
                    <button onclick="toggleUserDropdown(event)" class="flex items-center space-x-2 p-1 md:p-2 text-gray-300 hover:text-gold transition focus:outline-none" id="userBtn">
                        <i class="fas fa-user-circle text-xl md:text-2xl"></i>
                        <div class="text-left hidden sm:block">
                            <p class="text-[16px] font-bold leading-none mb-1 text-gray-100"><?= htmlspecialchars($_SESSION['username']) ?></p>
                            <p class="text-[13px] text-gold leading-none font-bold"><?= number_format($currentPoints) ?> แต้ม</p>
                        </div>
                        <i class="fas fa-chevron-down text-[10px] opacity-50 hidden sm:inline"></i>
                    </button>
                    
                    <div id="userDropdown" class="absolute right-0 bg-white shadow-2xl rounded-sm py-2 mt-3 border border-gray-100 overflow-hidden">
                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="admin/index.php" class="block px-4 py-3 text-[13px] text-blue-600 hover:bg-blue-50 font-black border-b border-gray-50">
                                <i class="fas fa-user-shield mr-2"></i> ระบบจัดการหลังบ้าน
                            </a>
                        <?php endif; ?>
                        <a href="profile_edit.php" class="block px-4 py-3 text-[13px] text-gray-700 hover:bg-gray-50 border-b border-gray-50"><i class="fas fa-user-edit mr-3 text-gray-400"></i>แก้ไขข้อมูล</a>
                        <a href="history.php" class="block px-4 py-3 text-[13px] text-gray-700 hover:bg-gray-50 border-b border-gray-50"><i class="fas fa-history mr-3 text-gray-400"></i>ประวัติคำสั่งซื้อ</a>
                        <a href="javascript:void(0)" onclick="confirmUserLogout()" class="block px-4 py-3 text-[13px] text-red-600 hover:bg-red-50 font-bold transition">
                            <i class="fas fa-sign-out-alt mr-3"></i>ออกจากระบบ
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex items-center space-x-2">
                    <a href="login.php" class="text-gray-300 hover:text-gold text-base tracking-widest uppercase font-bold px-2 py-1 transition">เข้าสู่ระบบ</a>
                    <a href="register.php" class="hidden lg:block border border-yellow-700/50 text-gold hover:bg-gold hover:text-black transition text-[16px] tracking-widest uppercase font-bold py-2 px-4 rounded-sm">สมัครสมาชิก</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="mobileMenu" class="md:hidden bg-black/95 border-t border-yellow-900/30">
        <div class="flex flex-col p-4 space-y-1">
            <a href="index.php" class="py-3 px-4 rounded-sm text-[13px] uppercase tracking-widest <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-gold bg-yellow-900/10 font-bold' : 'text-gray-300' ?>">
                <i class="fas fa-home mr-3 w-5"></i> หน้าแรก
            </a>
            <a href="coffins.php" class="py-3 px-4 rounded-sm text-[13px] uppercase tracking-widest <?= basename($_SERVER['PHP_SELF']) == 'coffins.php' ? 'text-gold bg-yellow-900/10 font-bold' : 'text-gray-300' ?>">
                <i class="fas fa-box mr-3 w-5"></i> หีบศพพรีเมียม
            </a>
            <a href="decorations.php" class="py-3 px-4 rounded-sm text-[13px] uppercase tracking-widest <?= basename($_SERVER['PHP_SELF']) == 'decorations.php' ? 'text-gold bg-yellow-900/10 font-bold' : 'text-gray-300' ?>">
                <i class="fas fa-fan mr-3 w-5"></i> ดอกไม้และของตกแต่ง
            </a>
            <a href="donate.php" class="py-3 px-4 rounded-sm text-[13px] uppercase tracking-widest <?= basename($_SERVER['PHP_SELF']) == 'donate.php' ? 'text-gold bg-yellow-900/10 font-bold' : 'text-gray-300' ?>">
                <i class="fas fa-hand-holding-heart mr-3 w-5"></i> บริจาค
            </a>
            <hr class="border-yellow-900/20 my-2">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="profile_edit.php" class="py-3 px-4 text-gray-300 text-[13px] uppercase tracking-widest"><i class="fas fa-user-edit mr-3 w-5"></i> ข้อมูลส่วนตัว</a>
                <a href="history.php" class="py-3 px-4 text-gray-300 text-[13px] uppercase tracking-widest"><i class="fas fa-history mr-3 w-5"></i> ประวัติคำสั่งซื้อ</a>
                <a href="javascript:void(0)" onclick="confirmUserLogout()" class="py-3 px-4 rounded-sm text-[13px] uppercase tracking-widest text-red-500 font-bold">
                    <i class="fas fa-sign-out-alt mr-3 w-5"></i> ออกจากระบบ
                </a>
            <?php else: ?>
                <a href="login.php" class="py-3 px-4 text-gold text-[16px] uppercase tracking-widest font-bold"><i class="fas fa-sign-in-alt mr-3 w-5"></i> เข้าสู่ระบบ / สมัครสมาชิก</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    function confirmUserLogout() {
        Swal.fire({
            title: 'ต้องการออกจากระบบ?',
            text: "คุณสามารถกลับมาเลือกชมสินค้าของเราได้ทุกเมื่อ",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#000000',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก',
            background: '#ffffff',
            color: '#1a1a1a',
            customClass: { title: 'serif fw-bold', confirmButton: 'gold-gradient text-black border-0' }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    }

    function toggleMobileMenu(event) {
        if (event) event.stopPropagation();
        const menu = document.getElementById('mobileMenu');
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) dropdown.classList.remove('show');
        menu.classList.toggle('is-open');
    }

    function toggleUserDropdown(event) {
        if (event) event.stopPropagation();
        const dropdown = document.getElementById('userDropdown');
        const menu = document.getElementById('mobileMenu');
        if (menu) menu.classList.remove('is-open');
        dropdown.classList.toggle('show');
    }

    window.onclick = function(event) {
        const dropdown = document.getElementById('userDropdown');
        const menu = document.getElementById('mobileMenu');
        const userBtn = document.getElementById('userBtn');
        const hamburgerBtn = document.getElementById('hamburgerBtn');

        if (dropdown && dropdown.classList.contains('show')) {
            if (!dropdown.contains(event.target) && (!userBtn || !userBtn.contains(event.target))) {
                dropdown.classList.remove('show');
            }
        }
        if (menu && menu.classList.contains('is-open')) {
            if (!menu.contains(event.target) && (!hamburgerBtn || !hamburgerBtn.contains(event.target))) {
                menu.classList.remove('is-open');
            }
        }
    }
</script>