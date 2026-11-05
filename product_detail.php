<?php 
session_start();
require_once 'config/db.php'; 

if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id = $_GET['id'];

// 1. ดึงข้อมูลสินค้าหลัก
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { header("Location: index.php"); exit(); }

// 2. ดึงข้อมูลของตกแต่ง
$setItems = [];
$groupedDecorations = []; 

if ($product['sale_type'] == 'set') {
    $sqlSet = "SELECT p.* FROM products p 
               JOIN product_set_items s ON p.id = s.child_product_id 
               WHERE s.parent_product_id = ?";
    $stmtSet = $pdo->prepare($sqlSet);
    $stmtSet->execute([$id]);
    $setItems = $stmtSet->fetchAll();
} else {
    $stmtDec = $pdo->query("SELECT * FROM products WHERE type = 'decoration' AND is_active = 1 AND sub_category IS NOT NULL AND sub_category != '' ORDER BY sub_category ASC");
    $allDecs = $stmtDec->fetchAll();
    
    foreach ($allDecs as $dec) {
        $subCat = $dec['sub_category'];
        if (!isset($groupedDecorations[$subCat])) {
            $groupedDecorations[$subCat] = [];
        }
        $groupedDecorations[$subCat][] = $dec;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        html, body { margin: 0; padding: 0; width: 100%; overflow-x: hidden; background-color: #ffffff; font-family: 'Noto Sans Thai', sans-serif; }
        .serif { font-family: 'Playfair Display', serif !important; }
        .bg-charcoal { background-color: #121212 !important; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .text-gold { color: #D4AF37; }
        
        .product-content { font-family: 'IBM Plex Sans Thai', sans-serif; }

        @media (min-width: 1024px) { .sticky-element { position: sticky; top: 120px; } }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #D4AF37; border-radius: 10px; }
        input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        .peer:checked + .checked-gold-gradient {
            background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%) !important;
            color: black !important;
            border-color: transparent !important;
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.2);
        }
        .peer:checked + .checked-gold-gradient * { color: black !important; }

        .qty-input-group { display: flex; align-items: center; background: rgba(255, 255, 255, 0.1); border-radius: 50px; padding: 4px; border: 1px solid rgba(255, 255, 255, 0.15); }
        .qty-btn { width: 32px; height: 32px; border-radius: 50%; border: none; background: #D4AF37; color: #000; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .qty-btn:hover { background: #fff; transform: scale(1.1); }
        .qty-val { width: 40px; text-align: center; border: none; background: transparent; color: #fff; font-weight: bold; font-size: 16px; }
    </style>
</head>
<body class="bg-white">

    <?php require_once 'includes/navbar.php'; ?>
    <div class="h-24 md:h-32"></div>

    <main class="max-w-[1500px] mx-auto px-6 md:px-12 lg:px-16 pb-16 product-content">
        
        <nav class="flex mb-8 md:mb-12 text-[14px] uppercase tracking-[0.3em] font-bold text-gray-300 overflow-x-auto whitespace-nowrap pb-2 custom-scrollbar">
            <a href="index.php" class="hover:text-gold transition">หน้าหลัก</a>
            <span class="mx-4 text-gray-200">/</span>
            <span class="text-gray-900"><?= htmlspecialchars($product['name']) ?></span>
        </nav>

        <form action="cart_action.php" method="POST" id="orderForm">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="order_purpose" value="purchase">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-24 items-start">
                
                <div class="sticky-element space-y-8 md:space-y-10">
                    <header>
                        <h1 class="serif text-4xl md:text-5xl lg:text-6xl text-gray-900 leading-tight mb-4"><?= htmlspecialchars($product['name']) ?></h1>
                    </header>

                    <div class="bg-white p-2 shadow-2xl border border-gray-100 rounded-sm">
                        <img src="uploads/<?= $product['image_url'] ?: 'default.png' ?>" class="w-full h-auto rounded-sm" alt="product">
                    </div>

                    <section class="bg-gray-50/50 p-6 md:p-8 border-y border-gray-100">
                        <div class="flex items-center space-x-4 mb-6">
                            <h3 class="serif text-lg md:text-xl text-gray-900 font-bold uppercase tracking-widest">สรุปรายการ</h3>
                            <div class="h-[1px] flex-grow bg-gray-200"></div>
                        </div>
                        
                        <div class="space-y-4 text-base md:text-lg">
                            <div class="flex justify-between items-center text-gray-400 font-light">
                                <span class="uppercase tracking-widest text-[16px] md:text-xs">ค่าสินค้าหลัก (x<span id="summary_main_qty" class="text-gold font-bold">1</span>)</span>
                                <span class="font-bold text-gray-900">฿<span id="summary_main_price">0</span></span>
                            </div>

                            <div class="flex justify-between items-center text-gray-400 font-light">
                                <span class="uppercase tracking-widest text-[16px] md:text-xs">ส่วนเสริม (x<span id="summary_addon_qty" class="text-gold font-bold">0</span>)</span>
                                <span class="font-bold text-gray-900">฿<span id="summary_addon_price">0</span></span>
                            </div>
                        </div>
                    </section>

                    <div class="bg-charcoal text-white p-8 md:p-12 rounded-sm shadow-2xl relative overflow-hidden">
                        <div class="text-center relative z-10 space-y-8">
                            <div>
                                <span class="text-gold tracking-[0.5em] uppercase text-[14px] font-bold mb-4 block opacity-50">ราคาสุทธิ</span>
                                <h2 class="text-5xl md:text-6xl font-bold text-gold">฿<span id="displayPrice">0</span></h2>
                            </div>
                            
                            <div class="flex items-center justify-between py-6 border-y border-white/10">
                                <span class="text-xs uppercase tracking-[0.2em] text-gray-400 font-bold">จำนวนชุด</span>
                                <div class="qty-input-group">
                                    <button type="button" class="qty-btn" onclick="changeQty('main_qty', -1)"><i class="fas fa-minus text-xs"></i></button>
                                    <input type="number" name="qty" id="main_qty" class="qty-val" value="1" min="1" readonly>
                                    <button type="button" class="qty-btn" onclick="changeQty('main_qty', 1)"><i class="fas fa-plus text-xs"></i></button>
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full gold-gradient text-black font-black py-5 rounded-sm uppercase tracking-[0.4em] text-xs md:text-sm hover:shadow-[0_0_40px_rgba(212,175,55,0.4)] transition duration-500">
                                <i class="fas fa-shopping-basket mr-4"></i> เพิ่มลงตะกร้าสินค้า
                            </button>
                        </div>
                    </div>
                </div>

                <div class="space-y-16">
                    
                    <section>
                        <div class="relative mb-8">
                            <h2 class="serif text-4xl text-gray-900">รายละเอียดสินค้า</h2>
                            <div class="absolute -bottom-3 left-0 w-20 h-1 gold-gradient"></div>
                        </div>
                        <div class="text-gray-600 leading-relaxed">
                            <?= $product['description'] ?>
                        </div>
                    </section>

                    <section class="bg-gray-50 p-8 md:p-12 rounded-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-10">
                            <h3 class="serif text-2xl text-gray-900 font-bold uppercase tracking-wide">
                                <?= ($product['sale_type'] == 'set') ? 'ส่วนเสริมในเซ็ต' : 'ส่วนเสริม' ?>
                            </h3>
                        </div>

                        <?php if($product['sale_type'] == 'set'): ?>
    <div class="grid gap-4">
        <?php foreach($setItems as $item): ?>
        <?php 
            // 🌟 ตรวจสอบประเภทสินค้าเพื่อเลือกหน้า Detail ที่ถูกต้อง
            // ถ้าเป็นหีบศพ (coffin) ให้ไปหน้า product_details.php
            // ถ้าเป็นอย่างอื่น (decoration) ให้ไปหน้า decoration_detail.php
            $targetPage = ($item['type'] == 'coffin') ? 'product_detail.php' : 'decoration_detail.php';
        ?>
        <div class="relative flex items-center justify-between p-4 bg-white border border-gray-100 rounded-sm shadow-sm hover:border-gold/30 transition-colors">
            <div class="flex items-center min-w-0 pr-10"> 
                <img src="uploads/<?= $item['image_url'] ?>" class="w-12 h-12 object-cover border mr-4">
                <span class="font-bold text-gray-800 text-sm md:text-base truncate"><?= htmlspecialchars($item['name']) ?></span>
            </div>
            
            <a href="<?= $targetPage ?>?id=<?= $item['id'] ?>" target="_blank" 
               class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gold transition-colors duration-300"
               title="ดูรายละเอียดสินค้า">
                <i class="fas fa-info-circle text-lg"></i>
            </a>

            <input type="hidden" name="set_items_qty[<?= $item['id'] ?>]" value="1">
        </div>
        <?php endforeach; ?>
    </div>
    <input type="hidden" name="is_set" value="1">
<?php else: ?>
                            
                            <?php foreach($groupedDecorations as $subCategoryName => $items): 
                                $groupId = 'cat_' . md5($subCategoryName); 
                            ?>
                            <div class="mb-12 last:mb-0">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4 border-l-4 border-gold pl-4">
                                    <h4 class="text-xs font-bold uppercase tracking-[0.3em] text-gold"><?= htmlspecialchars($subCategoryName) ?></h4>
                                    <div class="relative">
                                        <input type="text" class="w-full sm:w-64 pl-8 pr-4 py-2 text-[13px] border-b border-gray-200 focus:border-gold focus:outline-none bg-transparent" placeholder="ค้นหา..." onkeyup="filterDecorations('<?= $groupId ?>', this.value)">
                                        <i class="fas fa-search absolute left-0 top-1/2 -translate-y-1/2 text-gray-300 text-[13px]"></i>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                                    <?php foreach($items as $dec): ?>
<div class="item-<?= $groupId ?> relative" data-name="<?= htmlspecialchars($dec['name']) ?>">
    <a href="decoration_detail.php?id=<?= $dec['id'] ?>" target="_blank" 
       class="absolute top-4 right-4 z-20 text-gray-300 hover:text-gold transition-colors duration-300"
       title="ดูรายละเอียดการจัดเตรียม">
        <i class="fas fa-info-circle text-xl"></i>
    </a>

    <input type="checkbox" name="addons[]" value="<?= $dec['id'] ?>" data-price="<?= $dec['price'] ?>" id="dec_<?= $dec['id'] ?>" class="hidden peer addon-checkbox" onchange="updatePrice()">
    <label for="dec_<?= $dec['id'] ?>" class="checked-gold-gradient flex flex-col p-6 bg-white border border-gray-100 cursor-pointer transition-all duration-500 shadow-sm rounded-sm relative z-10">
        <div class="flex items-center mb-6">
            <img src="uploads/<?= $dec['image_url'] ?>" class="w-14 h-14 object-cover border mr-4">
            <div class="min-w-0 pr-6"> <p class="font-bold text-gray-900 text-sm truncate"><?= htmlspecialchars($dec['name']) ?></p>
                <p class="text-gold font-bold text-sm ">฿<?= number_format($dec['price']) ?></p>
            </div>
        </div>
        <div class="flex items-center justify-between pt-4 border-t border-gray-50">
            <span class="text-[8px] uppercase tracking-widest opacity-50 font-bold">จำนวน</span>
            <div class="flex items-center bg-gray-50 rounded-full border p-1">
                <button type="button" onclick="changeQty('qty_dec_<?= $dec['id'] ?>', -1)" class="w-6 h-6 text-xs">-</button>
                <input type="number" name="addon_qty[<?= $dec['id'] ?>]" id="qty_dec_<?= $dec['id'] ?>" class="w-8 text-center bg-transparent text-xs font-bold focus:outline-none" value="1" min="1" oninput="updatePrice()">
                <button type="button" onclick="changeQty('qty_dec_<?= $dec['id'] ?>', 1)" class="w-6 h-6 text-xs">+</button>
            </div>
        </div>
    </label>
</div>
<?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </form>
    </main>
<?php include 'chat_widget.php'; ?>
<?php require_once 'includes/footer.php'; ?>

    <script>
        function changeQty(inputId, delta) {
            let input = document.getElementById(inputId);
            if (!input) return;
            if (inputId.startsWith('qty_dec_')) {
                let checkboxId = inputId.replace('qty_', '');
                let checkbox = document.getElementById(checkboxId);
                if (checkbox && !checkbox.checked) return;
            }
            let newVal = parseInt(input.value) + delta;
            if (newVal < 1) newVal = 1;
            input.value = newVal;
            updatePrice();
        }

        function updatePrice() {
            const basePrice = parseFloat("<?= $product['price'] ?>") || 0;
            const mainQty = parseInt(document.getElementById('main_qty').value) || 1;
            let mainTotal = basePrice * mainQty;
            
            let addonTotal = 0;
            let addonCount = 0;

            document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
                let price = parseFloat(cb.dataset.price) || 0;
                let qty = parseInt(document.getElementById('qty_dec_' + cb.value)?.value) || 1;
                addonTotal += (price * qty);
                addonCount += qty;
            });

            const grandTotal = mainTotal + addonTotal;

            document.getElementById('displayPrice').innerText = grandTotal.toLocaleString();
            document.getElementById('summary_main_price').innerText = mainTotal.toLocaleString();
            document.getElementById('summary_main_qty').innerText = mainQty;
            document.getElementById('summary_addon_price').innerText = addonTotal.toLocaleString();
            document.getElementById('summary_addon_qty').innerText = addonCount;
        }

        function filterDecorations(groupId, val) {
            let filter = val.toLowerCase();
            document.querySelectorAll('.item-' + groupId).forEach(item => {
                let name = item.getAttribute('data-name').toLowerCase();
                item.style.display = name.includes(filter) ? "block" : "none";
            });
        }
        
        document.addEventListener("DOMContentLoaded", updatePrice);
    </script>
</body>
</html>