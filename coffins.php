<?php 
session_start();
require_once 'config/db.php'; 

// 1. ดึงข้อมูลหมวดหมู่ย่อย (Sub Category)
$stmtCat = $pdo->query("SELECT DISTINCT sub_category FROM products WHERE type = 'coffin' AND sub_category IS NOT NULL AND sub_category != '' ORDER BY sub_category ASC");
$subCategories = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

// 2. ดึงรูปแบบการขาย (Sale Type)
$stmtSaleType = $pdo->query("SELECT DISTINCT sale_type FROM products WHERE type = 'coffin' AND sale_type IS NOT NULL");
$saleTypes = $stmtSaleType->fetchAll(PDO::FETCH_COLUMN);

// 3. ดึงข้อมูลสินค้าทั้งหมด
$sql = "SELECT * FROM products WHERE type = 'coffin' AND is_active = 1 ORDER BY sale_type DESC, sub_category ASC, id DESC";
$stmt = $pdo->query($sql);
$all_products = $stmt->fetchAll();

// 4. จัดกลุ่มสินค้า: แยก Set ออกมา และกลุ่ม Individual ให้แยกตามหมวดหมู่ย่อย
$set_products = [];
$individual_groups = [];

foreach ($all_products as $p) {
    if ($p['sale_type'] === 'set') {
        $set_products[] = $p;
    } else {
        $catName = $p['sub_category'] ?: 'หีบศพมาตรฐาน';
        $individual_groups[$catName][] = $p;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการหีบศพ | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { font-family: 'Noto Sans Thai', sans-serif; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .bg-charcoal { background-color: #121212; }
        .tag-subcat {
            background-color: #D4AF37; color: #000; font-weight: 800;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2);
        }
        .product-card:hover img { transform: scale(1.08); }
        .custom-scrollbar::-webkit-scrollbar { height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #D4AF37; border-radius: 10px; }
    </style>
</head>
<body class="bg-white text-gray-800">

    <?php require_once 'includes/navbar.php'; ?>

    <section class="relative py-24 md:py-32 bg-charcoal flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0 opacity-30">
            <img src="uploads/banner_coffin.png" class="w-full h-full object-cover" alt="Banner" onerror="this.src='https://images.unsplash.com/photo-1551024709-8f23befc6f87?q=80&w=2000'">
        </div>
        <div class="relative z-10 text-center px-6">
            <h1 class="text-4xl md:text-6xl text-white mb-4 leading-tight">หีบศพ</h1>
            <p class="text-gray-400 font-light max-w-xl mx-auto leading-relaxed text-sm md:text-base ">
                คัดสรรด้วยความประณีต เพื่อความสง่างามในวาระสุดท้าย
            </p>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 md:px-6 -mt-10 relative z-20">
        <div class="bg-white shadow-2xl rounded-sm p-2 border border-gray-100">
            <form id="searchForm" class="flex flex-col md:flex-row items-stretch md:items-center">
                <div class="flex-grow flex items-center px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100">
                    <i class="fas fa-search text-gray-300 mr-3"></i>
                    <input type="text" id="searchInput" class="w-full focus:outline-none text-sm font-light" placeholder="ค้นหาหีบศพที่ต้องการ...">
                </div>

                <div class="w-full md:w-56 px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100">
                    <select id="subCategorySelect" class="w-full focus:outline-none text-sm bg-transparent cursor-pointer font-medium">
                        <option value="">ทุกหมวดหมู่สินค้า</option>
                        <?php foreach($subCategories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-full md:w-56 px-4 py-3 border-b md:border-b-0">
                    <select id="saleTypeSelect" class="w-full focus:outline-none text-sm bg-transparent cursor-pointer font-medium text-yellow-700">
                        <option value="">รูปแบบการขาย</option>
                        <option value="set">แพ็กเกจจัดเซ็ต</option>
                        <option value="individual">เลือกจัดเอง</option>
                    </select>
                </div>

                <button type="button" onclick="performSearch()" class="w-full md:w-auto gold-gradient text-black font-black py-4 px-10 text-xs uppercase tracking-widest hover:opacity-90 transition shadow-lg">
                    ค้นหา
                </button>
            </form>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 md:px-6 py-12 md:py-20" id="main-content">
    
    <?php if (count($set_products) > 0): ?>
    <section class="category-section mb-24" id="section-sets">
        <div class="mb-10 border-l-4 border-[#D4AF37] pl-6 flex justify-between items-end">
            <div>
                <h2 class="text-3xl md:text-4xl mt-1 text-gray-900 leading-tight">โปรโมชั่น</h2>
                <p class="text-gray-500 mt-2 font-light text-sm md:text-base">แพ็กเกจสุดคุ้มที่คัดสรรมาเพื่อความสมบูรณ์แบบในวาระสุดท้าย</p>
            </div>
        </div>
        <div class="overflow-x-auto pb-8 custom-scrollbar scroll-container">
            <div class="grid grid-rows-2 grid-flow-col gap-4 md:gap-8 auto-cols-[85%] sm:auto-cols-[45%] lg:auto-cols-[calc(33.333%-1.35rem)] min-w-full">
                <?php foreach ($set_products as $p): ?>
                    <?= renderProductCard($p) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <div id="individual-container">
        <?php foreach ($individual_groups as $category => $products): ?>
        <section class="category-section mb-20">
            <div class="mb-10 border-l-4 border-gray-300 pl-6">
                <h2 class="text-3xl md:text-4xl text-gray-900 leading-tight"><?= htmlspecialchars($category) ?></h2>
                
                <?php if ($category === 'หีบศพแบบคลาสสิค'): ?>
                    <p class="text-gray-500 mt-2 font-light text-sm md:text-base">
                        หีบศพแบบคลาสสิค หีบศพผลิตจากวัสดุที่คัดสรรเป็นอย่างดี ใช้ไม้สักที่มีความแข็งแรงทนทาน แกะลาย ลงทอง ติดมุก และมีดีไซน์ที่มีความคลาสสิค ดูดี ภายในหีบศพบุผ้าพลาสติกกันการรั่วซึมอย่างดีและบุทับด้วยผ้าต่วนสีมันวาว
                    </p>
                <?php elseif ($category === 'หีบศพแบบมาตรฐาน'): ?>
                    <p class="text-gray-500 mt-2 font-light text-sm md:text-base">
                        หีบศพแบบมาตรฐาน เป็นหีบศพที่นิยมใช้ทั่วไปมีความเป็นมาตรฐาน ตัวหีบศพมาตรฐาน มีขนาดประมาณความกว้างของหีบศพ 20 นิ้ว ความยาวของหีบศพ 185 ซม. ความสูงของหีบศพ 65 ซม. ขนาดหีบศพแบบมาตรฐาน มีให้เลือกตามความกว้างของหีบศพตั้งแต่ 20 นิ้ว, 22 นิ้ว, 24 นิ้ว
                    </p>
                <?php endif; ?>

            </div>
            <div class="overflow-x-auto pb-8 custom-scrollbar scroll-container">
                <div class="grid grid-rows-2 grid-flow-col gap-4 md:gap-8 auto-cols-[85%] sm:auto-cols-[45%] lg:auto-cols-[calc(33.333%-1.35rem)] min-w-full">
                    <?php foreach ($products as $p): ?>
                        <?= renderProductCard($p) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endforeach; ?>
    </div>

    <div id="no-result-message" class="text-center py-20 md:py-32 border border-dashed border-gray-200 rounded-sm bg-gray-50/50" style="display:none;">
        <div class="max-w-md mx-auto px-6 text-center">
            <i class="fas fa-search text-gray-200 text-6xl mb-6"></i>
            <h3 class="text-2xl text-gray-400 mb-2 font-bold">ไม่พบรายการที่คุณต้องการ</h3>
            <button onclick="clearSearch()" class="gold-gradient text-black font-black py-3 px-8 text-[10px] uppercase tracking-widest hover:opacity-90 transition shadow-lg mt-4">
                แสดงสินค้าทั้งหมด
            </button>
        </div>
    </div>

</main>
    <?php require_once 'includes/footer.php'; ?>

    <?php 
    // Helper function: Render Card
    function renderProductCard($p) {
        $img = $p['image_url'] ? "uploads/" . $p['image_url'] : "https://via.placeholder.com/400x300";
        $price = number_format($p['price']);
        $name = htmlspecialchars($p['name']);
        $desc = strip_tags($p['description']);
        $id = $p['id'];
        
        // 🌟 ป้าย Tag ให้แสดงตามชื่อสินค้า
        $tagName = (mb_strlen($name) > 20) ? mb_substr($name, 0, 20) . '...' : $name;

        return "
        <div class='bg-white group overflow-hidden shadow-sm border border-gray-100 relative product-card h-full flex flex-col'>
            <div class='absolute top-3 left-3 z-10 tag-subcat text-[9px] md:text-[10px] tracking-widest px-3 py-1 rounded-sm uppercase'>
                $tagName
            </div>
            <div class='aspect-[4/3] bg-gray-100 overflow-hidden relative'>
                <img src='$img' alt='$name' class='w-full h-full object-cover transition duration-700' loading='lazy'>
            </div>
            <div class='p-5 md:p-8 flex-grow flex flex-col'>
                <h4 class='text-lg md:text-xl mb-2  text-gray-900 font-bold'>$name</h4>
                <p class='text-gray-400 text-xs md:text-sm mb-6 line-clamp-2 font-light'>$desc</p>
                <div class='mt-auto flex justify-between items-center border-t border-gray-50 pt-6'>
                    <span class='text-base md:text-lg font-bold text-gray-900'>฿$price</span>
                    <a href='product_detail.php?id=$id' class='text-[16px] uppercase tracking-tighter font-black border-b-2 border-yellow-600 pb-1 hover:text-yellow-700 transition'>รายละเอียด</a>
                </div>
            </div>
        </div>";
    }
    ?>

    <script>
    // 🌟 AJAX Search
    async function performSearch() {
        const keyword = document.getElementById('searchInput').value;
        const subCat = document.getElementById('subCategorySelect').value;
        const saleType = document.getElementById('saleTypeSelect').value;
        const mainContent = document.getElementById('main-content');

        try {
            const response = await fetch(`ajax_search_coffins.php?q=${encodeURIComponent(keyword)}&sc=${encodeURIComponent(subCat)}&st=${encodeURIComponent(saleType)}`);
            const groupedData = await response.json(); 

            // ล้างข้อมูลเก่า
            const sections = document.querySelectorAll('.category-section');
            sections.forEach(s => s.remove());

            const renderJS = (p) => {
                let img = p.image_url ? `uploads/${p.image_url}` : 'https://via.placeholder.com/400x300';
                let tagName = p.name.length > 20 ? p.name.substring(0, 20) + '...' : p.name;
                return `
                <div class="bg-white group overflow-hidden shadow-sm border border-gray-100 relative product-card h-full flex flex-col">
                    <div class="absolute top-3 left-3 z-10 tag-subcat text-[9px] md:text-[10px] tracking-widest px-3 py-1 rounded-sm uppercase">
                        ${tagName}
                    </div>
                    <div class="aspect-[4/3] bg-gray-100 overflow-hidden relative">
                        <img src="${img}" class="w-full h-full object-cover transition duration-700">
                    </div>
                    <div class="p-5 md:p-8 flex-grow flex flex-col">
                        <h4 class="text-lg md:text-xl mb-2  text-gray-900 font-bold">${p.name}</h4>
                        <p class="text-gray-400 text-xs md:text-sm mb-6 line-clamp-2 font-light">${p.description ? p.description.replace(/<[^>]*>?/gm, '') : ''}</p>
                        <div class="mt-auto flex justify-between items-center border-t border-gray-50 pt-6">
                            <span class="text-base md:text-lg font-bold text-gray-900">฿${Number(p.price).toLocaleString()}</span>
                            <a href="product_detail.php?id=${p.id}" class="text-[16px] uppercase tracking-tighter font-bold border-b-2 border-yellow-600 pb-1 hover:text-yellow-700 transition">รายละเอียด</a>
                        </div>
                    </div>
                </div>`;
            };

            let hasAny = false;

            // 1. วนลูปหา SET ก่อนเพื่อเอาขึ้นบนสุด
            for (const [category, products] of Object.entries(groupedData)) {
                if (products.some(p => p.sale_type === 'set')) {
                    hasAny = true;
                    appendSection(mainContent, "แพ็กเกจหีบศพจัดเซ็ต", products, renderJS, true);
                    delete groupedData[category]; // ลบออกเพื่อไม่ให้แสดงซ้ำในหมวดหมู่ธรรมดา
                }
            }

            // 2. วนลูปแสดงผลหมวดหมู่ที่เหลือ (Individual)
            for (const [category, products] of Object.entries(groupedData)) {
                if (products.length > 0) {
                    hasAny = true;
                    appendSection(mainContent, category, products, renderJS, false);
                }
            }

            document.getElementById('no-result-message').style.display = hasAny ? 'none' : 'block';
            initMouseWheelScroll();

        } catch (error) { console.error('Error:', error); }
    }

    function appendSection(target, title, products, renderFn, isGold) {
    const borderClass = isGold ? 'border-[#D4AF37]' : 'border-gray-300';
    
    // 🌟 ส่วนที่เพิ่ม: กำหนดคำอธิบายตามชื่อหมวดหมู่ให้ตรงกับ PHP
    let description = '';
    if (title === "แพ็กเกจหีบศพจัดเซ็ต") {
        description = 'แพ็กเกจสุดคุ้มที่คัดสรรมาเพื่อความสมบูรณ์แบบในวาระสุดท้าย';
    } else if (title === "หีบศพแบบคลาสสิค") {
        description = 'คัดสรรไม้คุณภาพสูงพร้อมงานแกะสลักลวดลายวิจิตรบรรจง สะท้อนความภูมิฐานและเกียรติยศอันสูงสุด';
    } else if (title === "หีบศพแบบมาตรฐาน") {
        description = 'เน้นความเรียบง่ายแต่แฝงด้วยความสง่างาม ผลิตด้วยวัสดุที่แข็งแรงทนทานตามมาตรฐานสากล';
    }

    const descHtml = description ? `<p class="text-gray-500 mt-2 font-light text-sm md:text-base">${description}</p>` : '';

    const sectionHtml = `
        <section class="category-section mb-24">
            <div class="mb-10 border-l-4 ${borderClass} pl-6">
                <h2 class="text-3xl md:text-4xl text-gray-900 leading-tight">${title}</h2>
                ${descHtml}
            </div>
            <div class="overflow-x-auto pb-8 custom-scrollbar scroll-container">
                <div class="grid grid-rows-2 grid-flow-col gap-4 md:gap-8 auto-cols-[85%] sm:auto-cols-[45%] lg:auto-cols-[calc(33.333%-1.35rem)] min-w-full">
                    ${products.map(renderFn).join('')}
                </div>
            </div>
        </section>`;
    target.insertAdjacentHTML('beforeend', sectionHtml);
}

    function initMouseWheelScroll() {
        const containers = document.querySelectorAll('.scroll-container');
        containers.forEach(c => {
            c.onwheel = null; 
            c.addEventListener('wheel', (evt) => {
                if (c.scrollWidth > c.clientWidth) {
                    evt.preventDefault();
                    c.scrollLeft += evt.deltaY;
                }
            }, { passive: false });
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        document.getElementById('searchInput').addEventListener('input', performSearch);
        document.getElementById('subCategorySelect').addEventListener('change', performSearch);
        document.getElementById('saleTypeSelect').addEventListener('change', performSearch);
        initMouseWheelScroll();
    });
    </script>
</body>
</html>