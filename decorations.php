<?php 
session_start();
require_once 'config/db.php'; 

// 1. ดึงข้อมูลหมวดหมู่ย่อย (เฉพาะประเภทของตกแต่ง) เพื่อใช้ใน Dropdown
$stmtCat = $pdo->query("SELECT DISTINCT sub_category FROM products WHERE type = 'decoration' AND sub_category IS NOT NULL AND sub_category != '' ORDER BY sub_category ASC");
$subCategories = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

// 2. ดึงข้อมูลของตกแต่งที่เปิดใช้งานอยู่ทั้งหมด
$sql = "SELECT * FROM products WHERE type = 'decoration' AND is_active = 1 ORDER BY sub_category ASC, id DESC";
$stmt = $pdo->query($sql);
$all_decorations = $stmt->fetchAll();

// 3. จัดกลุ่มสินค้าตามหมวดหมู่ย่อย (Dynamic Grouping)
$grouped_products = [];
foreach ($all_decorations as $p) {
    $catName = $p['sub_category'] ?: 'สินค้าอื่นๆ'; // ถ้าไม่มีหมวดหมู่ย่อยให้ใส่ "สินค้าอื่นๆ"
    $grouped_products[$catName][] = $p;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดอกไม้หน้าพิธี | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { font-family: 'Noto Sans Thai', sans-serif; }
        .serif { font-family: 'Playfair Display', serif; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .bg-charcoal { background-color: #121212; }
        
        .custom-scrollbar::-webkit-scrollbar { height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #D4AF37; border-radius: 10px; }

        .product-card:hover img { transform: scale(1.08); }
    </style>
</head>
<body class="bg-white text-gray-800">

    <?php require_once 'includes/navbar.php'; ?>

    <section class="relative py-32 bg-charcoal flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0 opacity-30">
            <img src="uploads/banner_decoration.png" class="w-full h-full object-cover" alt="Banner" onerror="this.src='https://images.unsplash.com/photo-1518709268805-4e9042af9f23?q=80&w=2000'">
        </div>
        <div class="relative z-10 text-center px-6">
            <h1 class="serif text-4xl md:text-6xl text-white mb-4">ดอกไม้หน้าพิธี</h1>
            <p class="text-gray-400 font-light max-w-xl mx-auto leading-relaxed">
                สร้างบรรยากาศที่ประณีตและสมเกียรติ เพื่อส่งมอบความอาลัยครั้งสุดท้ายอย่างงดงาม
            </p>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-6 -mt-10 relative z-20">
        <div class="bg-white shadow-2xl rounded-sm p-2 border border-gray-100">
            <form id="searchForm" class="flex flex-wrap md:flex-nowrap items-center">
                <div class="flex-grow flex items-center px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100">
                    <i class="fas fa-search text-gray-300 mr-3"></i>
                    <input type="text" id="searchInput" class="w-full focus:outline-none text-sm font-light" placeholder="ค้นหาชื่อดอกไม้หน้าพิธี...">
                </div>

                <div class="w-full md:w-64 px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100">
                    <select id="subCategorySelect" class="w-full focus:outline-none text-sm bg-transparent cursor-pointer font-medium">
                        <option value="">ทุกหมวดหมู่</option>
                        <?php foreach($subCategories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="button" onclick="performSearch()" class="w-full md:w-auto gold-gradient text-black font-bold py-4 px-12 text-xs uppercase tracking-widest hover:opacity-90 transition">
                    ค้นหา
                </button>
            </form>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-6 py-20" id="main-content">
        
        <?php foreach ($grouped_products as $category => $products): ?>
            <div class="category-section mb-24" data-category="<?= htmlspecialchars($category) ?>">
                <div class="mb-12 border-l-4 border-[#D4AF37] pl-6">
                    <h2 class="serif text-4xl mt-2 text-gray-900"><?= htmlspecialchars($category) ?></h2>
                </div>

                <div class="overflow-x-auto pb-10 custom-scrollbar scroll-container">
                    <div class="grid grid-rows-2 grid-flow-col gap-8 auto-cols-[85%] md:auto-cols-[calc(33.333%-1.35rem)] min-w-full">
                        <?php foreach ($products as $p): ?>
                            <?= renderProductCard($p) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div id="no-result-message" class="text-center py-32 border border-dashed border-gray-200 rounded-sm bg-gray-50/50" style="display:none;">
            <div class="max-w-md mx-auto">
                <i class="fas fa-search text-gray-200 text-6xl mb-6"></i>
                <h3 class="serif text-2xl text-gray-400 mb-2">ไม่พบรายการที่คุณต้องการ</h3>
                <button onclick="clearSearch()" class="gold-gradient text-black font-bold py-3 px-8 text-xs uppercase tracking-widest hover:opacity-90 transition shadow-lg mt-4">
                    แสดงทั้งหมด
                </button>
            </div>
        </div>

    </main>

    <?php require_once 'includes/footer.php'; ?>

    <?php 
    // Helper function สำหรับสร้างการ์ดสินค้า
    function renderProductCard($p) {
        $img = $p['image_url'] ? "uploads/" . $p['image_url'] : "https://via.placeholder.com/400x300";
        $price = number_format($p['price']);
        $name = htmlspecialchars($p['name']);
        $desc = strip_tags($p['description']);
        $id = $p['id'];
        
        return "
        <div class='bg-white group overflow-hidden shadow-sm border border-gray-100 relative product-card h-full flex flex-col'>
            <div class='aspect-[4/3] bg-gray-100 overflow-hidden relative'>
                <img src='$img' alt='$name' class='w-full h-full object-cover transition duration-700'>
            </div>
            <div class='p-6 md:p-8 flex flex-col flex-grow'>
                <h4 class='text-xl mb-2  text-gray-900'>$name</h4>
                <p class='text-gray-400 text-sm mb-6 line-clamp-2'>$desc</p>
                <div class='mt-auto flex justify-between items-center border-t border-gray-50 pt-6'>
                    <span class='text-lg font-bold text-gray-900'>฿$price</span>
                    <a href='decoration_detail.php?id=$id' class='text-[16px] uppercase tracking-tighter font-bold border-b-2 border-yellow-600 pb-1 hover:text-yellow-700 transition'>ดูรายละเอียด</a>
                </div>
            </div>
        </div>";
    }
    ?>

    <script>
    // 1. ฟังก์ชันค้นหา AJAX (ปรับปรุงใหม่เพื่อรองรับ Dynamic Category)
    async function performSearch() {
        const keyword = document.getElementById('searchInput').value;
        const subCat = document.getElementById('subCategorySelect').value;
        const mainContent = document.getElementById('main-content');

        try {
            const response = await fetch(`ajax_search_decorations.php?q=${encodeURIComponent(keyword)}&sc=${encodeURIComponent(subCat)}`);
            const groupedData = await response.json(); // ต้องคืนค่าเป็น { "หมวดหมู่": [สินค้า], ... }

            // ล้างข้อมูลเก่า (ยกเว้น No Result Message)
            const currentSections = document.querySelectorAll('.category-section');
            currentSections.forEach(s => s.remove());

            const renderItem = (p) => {
                let img = p.image_url ? `uploads/${p.image_url}` : 'https://via.placeholder.com/400x300';
                return `
                <div class="bg-white group overflow-hidden shadow-sm border border-gray-100 relative product-card h-full flex flex-col">
                    <div class="aspect-[4/3] bg-gray-100 overflow-hidden relative">
                        <img src="${img}" class="w-full h-full object-cover transition duration-700">
                    </div>
                    <div class="p-6 md:p-8 flex flex-col flex-grow">
                        <h4 class="serif text-xl mb-2 text-gray-900">${p.name}</h4>
                        <p class="text-gray-400 text-sm mb-6 line-clamp-2">${p.description ? p.description.replace(/<[^>]*>?/gm, '') : ''}</p>
                        <div class="mt-auto flex justify-between items-center border-t border-gray-50 pt-6">
                            <span class="text-lg font-medium text-gray-900">฿${Number(p.price).toLocaleString()}</span>
                            <a href="decoration_detail.php?id=${p.id}" class="text-[16px] uppercase tracking-tighter font-bold border-b-2 border-yellow-600 pb-1 hover:text-yellow-700 transition">ดูรายละเอียด</a>
                        </div>
                    </div>
                </div>`;
            };

            let hasAny = false;
            for (const [category, products] of Object.entries(groupedData)) {
                if (products.length > 0) {
                    hasAny = true;
                    const sectionHtml = `
                        <div class="category-section mb-24">
                            <div class="mb-12 border-l-4 border-[#D4AF37] pl-6">
                                <h2 class="serif text-4xl mt-2 text-gray-900">${category}</h2>
                            </div>
                            <div class="overflow-x-auto pb-10 custom-scrollbar scroll-container">
                                <div class="grid grid-rows-2 grid-flow-col gap-8 auto-cols-[85%] md:auto-cols-[calc(33.333%-1.35rem)] min-w-full">
                                    ${products.map(renderItem).join('')}
                                </div>
                            </div>
                        </div>
                    `;
                    mainContent.insertAdjacentHTML('afterbegin', sectionHtml);
                }
            }

            document.getElementById('no-result-message').style.display = hasAny ? 'none' : 'block';
            initMouseWheelScroll(); // เรียกใช้ระบบเลื่อนใหม่สำหรับ Section ที่สร้างขึ้นมา

        } catch (error) { console.error('Error:', error); }
    }

    function clearSearch() {
        document.getElementById('searchInput').value = '';
        document.getElementById('subCategorySelect').value = '';
        performSearch();
    }

    // 3. ระบบ Mouse Wheel Horizontal Scroll
    function initMouseWheelScroll() {
        const containers = document.querySelectorAll('.scroll-container');
        containers.forEach(c => {
            c.onwheel = null; // Reset
            c.addEventListener('wheel', (evt) => {
                if (c.scrollWidth > c.clientWidth) {
                    evt.preventDefault();
                    c.scrollLeft += evt.deltaY;
                }
            }, { passive: false });
        });
    }

    // Event Listeners
    document.getElementById('searchInput').addEventListener('input', performSearch);
    document.getElementById('subCategorySelect').addEventListener('change', performSearch);
    document.addEventListener('DOMContentLoaded', initMouseWheelScroll);
    </script>
    
    <?php include 'chat_widget.php'; ?>
</body>
</html>