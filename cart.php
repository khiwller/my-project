<?php 
session_start();
require_once 'config/db.php'; 

// 1. ดึงข้อมูลสินค้าทั้งหมดมาเตรียมไว้ (Lookup Array) - ใช้เหมือนเดิม
$stmtAll = $pdo->query("SELECT * FROM products");
$productsDB = [];
foreach ($stmtAll->fetchAll() as $p) {
    $productsDB[$p['id']] = $p;
}

// 🌟 2. ปรับ Logic การดึงข้อมูลตะกร้าให้รองรับทั้ง DB และ Session
$cartItems = [];

if (isset($_SESSION['user_id'])) {
    // กรณี Login: ดึงจากตาราง cart_items
    $stmtCart = $pdo->prepare("SELECT * FROM cart_items WHERE user_id = ? ORDER BY id DESC");
    $stmtCart->execute([$_SESSION['user_id']]);
    $dbItems = $stmtCart->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dbItems as $row) {
        // ใช้ ID จากตาราง cart_items เป็น Key (สำคัญสำหรับการลบ)
        $cartItems[$row['id']] = [
            'product_id' => $row['product_id'],
            'qty'        => $row['qty'],
            'purpose'    => $row['purpose'],
            'addons'     => json_decode($row['addons'] ?? '[]', true),
            'set_items'  => json_decode($row['set_items'] ?? '[]', true)
        ];
    }
} else {
    // กรณีไม่ Login: ใช้ Session ตามเดิม
    $cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        html, body { margin: 0; padding: 0; width: 100%; overflow-x: hidden; background-color: #ffffff; font-family: 'Noto Sans Thai', sans-serif; }
        .serif { font-family: 'Playfair Display', serif; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .bg-charcoal { background-color: #121212; }
        
        .checkbox-gold { accent-color: #D4AF37; width: 1.25rem; height: 1.25rem; cursor: pointer; }
        .custom-scrollbar::-webkit-scrollbar { height: 4px; width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #D4AF37; border-radius: 10px; }
    </style>
</head>
<body class="text-gray-800">

    <?php require_once 'includes/navbar.php'; ?>

    <div class="h-24 md:h-32"></div>

    <main class="max-w-[1500px] mx-auto px-4 md:px-8 lg:px-12 py-8 md:py-12">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 md:mb-12 gap-4 border-b border-gray-100 pb-6 md:pb-8">
            <div>
                <h1 class="serif text-3xl md:text-5xl text-gray-900 leading-tight font-bold">ตะกร้าสินค้า</h1>
            </div>
            <span class="text-[13px] md:text-xs font-bold text-gray-400 uppercase tracking-widest bg-gray-50 px-3 py-1 rounded-sm">
                ในตะกร้า <span class="text-gray-900"><?= count($cartItems) ?></span> รายการ
            </span>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="bg-white py-20 md:py-32 border border-dashed border-gray-200 text-center rounded-sm shadow-sm">
                <i class="fas fa-shopping-basket text-gray-100 text-6xl md:text-8xl mb-8"></i>
                <h4 class="serif text-xl md:text-2xl text-gray-400 mb-8 px-4">ยังไม่มีสินค้าในตะกร้าของคุณในขณะนี้</h4>
                <a href="index.php" class="gold-gradient text-black font-bold py-3 md:py-4 px-10 md:px-12 rounded-sm uppercase tracking-widest text-[10px] md:text-xs hover:opacity-90 transition inline-block">
                    กลับไปเลือกชมสินค้า
                </a>
            </div>
        <?php else: ?>
            
            <form id="cartForm" action="checkout.php" method="POST">
                <div class="bg-white border border-gray-100 shadow-2xl rounded-sm overflow-hidden mb-12">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse min-w-[850px]">
                            <thead>
                                <tr class="bg-charcoal text-[#D4AF37] uppercase tracking-widest text-sm md:text-[18px]">
                                    <th class="p-4 md:p-6 text-center w-16 md:w-20">
                                        <input class="checkbox-gold" type="checkbox" id="selectAll">
                                    </th>
                                    <th class="p-4 md:p-6 font-serif">สินค้า</th>
                                    <th class="p-4 md:p-6 font-serif text-center">วัตถุประสงค์</th>
                                    <th class="p-4 md:p-6 font-serif text-right w-64 md:w-72">จำนวนสุทธิ</th>
                                    <th class="p-4 md:p-6 w-16 md:w-20"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach ($cartItems as $key => $item): 
                                    if (!isset($productsDB[$item['product_id']])) continue;

                                    $mainProduct = $productsDB[$item['product_id']];
                                    $mainQty = $item['qty'];
                                    $basePrice = $mainProduct['price'];
                                    $mainTotal = $basePrice * $mainQty;
                                    
                                    // คำนวณ Addons
                                    $addonTotal = 0;
                                    $addonHtml = "";
                                    if (!empty($item['addons'])) {
                                        $addonHtml .= "<div class='mt-3 md:mt-4 pt-3 md:pt-4 border-l-2 border-[#D4AF37] pl-4 space-y-1'>";
                                        foreach ($item['addons'] as $addonData) {
                                            $aId = is_array($addonData) ? ($addonData['id'] ?? 0) : (int)$addonData;
                                            $aQty = is_array($addonData) ? ($addonData['qty'] ?? 1) : 1;
                                            if (isset($productsDB[$aId])) {
                                                $addon = $productsDB[$aId];
                                                $subAddonPrice = $addon['price'] * $aQty;
                                                $addonTotal += $subAddonPrice;
                                                $addonHtml .= "<div class='text-[12px] md:text-[13px] text-gray-500 '><i class='fas fa-plus text-[8px] text-[#D4AF37] mr-2'></i> " 
                                                            . htmlspecialchars($addon['name']) 
                                                            . " <span class='font-bold text-gray-400 mx-1'>x$aQty</span> "
                                                            . "<span>(฿" . number_format($subAddonPrice) . ")</span></div>";
                                            }
                                        }
                                        $addonHtml .= "</div>";
                                    }

                                    // คำนวณ Set Items
                                    $setHtml = "";
                                    if (!empty($item['set_items'])) {
                                        $setHtml .= "<div class='mt-3 md:mt-4 pt-3 md:pt-4 border-l-2 border-gray-200 pl-4 space-y-1'>";
                                        $setHtml .= "<div class='text-[13px] md:text-[13px] uppercase font-bold text-gray-400 tracking-wider mb-2'>รายการภายในแพ็กเกจ</div>";
                                        foreach ($item['set_items'] as $setData) {
                                            $sId = is_array($setData) ? ($setData['id'] ?? 0) : (int)$setData;
                                            $sQty = is_array($setData) ? ($setData['qty'] ?? 1) : 1;
                                            if (isset($productsDB[$sId])) {
                                                $sItem = $productsDB[$sId];
                                                $setHtml .= "<div class='text-[13px] md:text-[12px] text-gray-500 font-light'><i class='fas fa-check text-[13px] text-gray-300 mr-2'></i> " 
                                                            . htmlspecialchars($sItem['name']) 
                                                            . " <span class='font-bold text-gray-400 ml-1'>x$sQty</span></div>";
                                            }
                                        }
                                        $setHtml .= "</div>";
                                    }

                                    $itemRowGrandTotal = $mainTotal + $addonTotal;
                                ?>
                                <tr class="group hover:bg-gray-50/50 transition-colors">
                                    <td class="p-4 md:p-6 text-center">
                                        <input class="checkbox-gold item-checkbox" type="checkbox" 
                                               name="selected_items[]" value="<?= $key ?>" 
                                               data-price="<?= $itemRowGrandTotal ?>">
                                    </td>
                                    <td class="p-4 md:p-6">
                                        <div class="flex items-start gap-4 md:gap-6">
                                            <img src="uploads/<?= $mainProduct['image_url'] ?: 'default.png' ?>" class="w-20 h-20 md:w-28 md:h-28 object-cover rounded-sm shadow-sm border border-gray-100 flex-shrink-0">
                                            <div class="flex-grow min-w-0">
                                                <h5 class="serif text-lg md:text-xl text-gray-900 font-bold mb-1 truncate"><?= htmlspecialchars($mainProduct['name']) ?></h5>
                                                
                                                <?php if($mainProduct['type'] == 'coffin'): ?>
                                                    <span class="inline-block text-[13px] md:text-[14px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-sm <?= $mainProduct['sale_type'] == 'set' ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-gray-100 text-gray-500 border border-gray-200' ?> mb-2">
                                                        <?= $mainProduct['sale_type'] == 'set' ? 'แพ็กเกจ' : 'เลือกจัดเอง' ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?= $addonHtml ?>
                                                <?= $setHtml ?>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-4 md:p-6 text-center">
                                        <?php if($item['purpose'] == 'donation'): ?>
                                            <span class="inline-flex items-center px-3 py-1 md:px-4 md:py-1.5 rounded-full bg-green-50 text-green-700 text-[10px] md:text-[12px] font-bold uppercase tracking-wider border border-green-100">
                                                <i class="fas fa-heart mr-2 text-[8px] md:text-[10px]"></i> บริจาค
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 md:px-4 md:py-1.5 rounded-full bg-blue-50 text-blue-700 text-[10px] md:text-[12px] font-bold uppercase tracking-wider border border-blue-100">
                                                <i class="fas fa-shopping-cart mr-2 text-[16px] md:text-[16px]"></i> สั่งซื้อ
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 md:p-6">
                                        <div class="bg-gray-50/80 p-4 md:p-5 rounded-sm border border-gray-100 space-y-2">
                                            <div class="flex justify-between text-[10px] md:text-[13px] uppercase tracking-widest text-gray-400">
                                                <span>สินค้าหลัก (x<?= $mainQty ?>)</span>
                                                <span class="text-gray-900 font-bold">฿<?= number_format($mainTotal) ?></span>
                                            </div>
                                            <?php if($addonTotal > 0): ?>
                                            <div class="flex justify-between text-[10px] md:text-[13px] uppercase tracking-widest text-gray-400">
                                                <span>ของตกแต่งเสริม</span>
                                                <span class="text-gray-900 font-bold">฿<?= number_format($addonTotal) ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <div class="border-t border-gray-200 my-2"></div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-[9px] md:text-[13px] font-black uppercase tracking-[0.2em] text-gray-400">รวม</span>
                                                <span class="text-xl md:text-2xl font-bold text-[#D4AF37]">฿<?= number_format($itemRowGrandTotal) ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-4 md:p-6 text-center">
                                        <a href="cart_action.php?action=delete&key=<?= $key ?>" class="text-gray-200 hover:text-red-500 transition-all text-xl md:text-2xl btn-delete-item">
                                            <i class="fas fa-times-circle"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>                        
                        </table>
                    </div>

                    <div class="bg-gray-50/80 p-6 md:p-12 border-t border-gray-100">
                        <div class="flex flex-col lg:flex-row justify-between items-center gap-10 md:gap-12">
                            <div class="flex flex-row items-center gap-6 md:gap-10">
                                <a href="cart_action.php?action=clear" class="text-[13px] font-bold uppercase tracking-[0.3em] text-gray-400 hover:text-red-600 transition btn-clear-cart flex items-center">
                                    <i class="fas fa-trash-alt mr-2 md:mr-3"></i> ลบทั้งหมด
                                </a>
                                <a href="index.php" class="text-[13px] font-bold uppercase tracking-[0.3em] text-[#D4AF37] hover:text-yellow-600 transition flex items-center">
                                    <i class="fas fa-arrow-left mr-2 md:mr-3"></i> กลับไปเลือกสินค้า
                                </a>
                            </div>

                            <div class="w-full lg:w-auto text-center lg:text-right">
                                <div class="mb-6 md:mb-10">
                                    <span class="text-gray-400 text-[10px] md:text-[11px] uppercase tracking-[0.4em] block mb-3 font-bold opacity-70">รวมทั้งหมด</span>
                                    <span class="text-4xl md:text-6xl font-bold text-gray-900 leading-none">
                                        <span class="text-3xl md:text-5xl text-gold mr-1">฿</span><span id="grandTotalDisplay" class="text-[#D4AF37]">0.00</span>
                                    </span>
                                </div>
                                
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <button type="submit" id="checkoutBtn" class="w-full lg:w-auto gold-gradient text-black font-black py-5 md:py-6 px-16 md:px-24 rounded-sm uppercase tracking-[0.4em] text-xs md:text-sm hover:shadow-2xl transition-all duration-500 disabled:opacity-20" disabled>
                                        ชำระเงิน <i class="fas fa-chevron-right ml-4"></i>
                                    </button>
                                <?php else: ?>
                                    <a href="login.php" class="w-full lg:w-auto inline-flex items-center justify-center bg-charcoal text-[#D4AF37] font-bold py-5 md:py-6 px-12 md:px-20 rounded-sm uppercase tracking-[0.2em] text-xs md:text-sm hover:bg-black transition shadow-xl">
                                        <i class="fas fa-lock mr-4 text-lg"></i> โปรดเข้าสู่ระบบเพื่อสั่งซื้อ
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        <?php endif; ?>
    </main>

    <?php require_once 'includes/footer.php'; ?>
    <?php include 'chat_widget.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const grandTotalDisplay = document.getElementById('grandTotalDisplay');
        const checkoutBtn = document.getElementById('checkoutBtn');

        // ฟังก์ชันคำนวณราคารวม (Checkbox logic เดิม)
        function updateGrandTotal() {
            let total = 0;
            let checkedCount = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    total += parseFloat(cb.getAttribute('data-price'));
                    checkedCount++;
                }
            });
            grandTotalDisplay.innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (checkoutBtn) { checkoutBtn.disabled = checkedCount === 0; }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
                updateGrandTotal();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!this.checked) { selectAll.checked = false; } 
                else { selectAll.checked = Array.from(checkboxes).every(c => c.checked); }
                updateGrandTotal();
            });
        });

        // SweetAlert2 สำหรับลบสินค้า
        document.querySelectorAll('.btn-delete-item').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                Swal.fire({
                    title: 'ลบรายการนี้?',
                    text: "คุณต้องการนำสินค้าชิ้นนี้ออกจากตะกร้าใช่หรือไม่",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#D4AF37',
                    cancelButtonColor: '#121212',
                    confirmButtonText: 'ยืนยันการลบ',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => { if (result.isConfirmed) window.location.href = url; });
            });
        });
        
        // SweetAlert2 สำหรับล้างตะกร้า
        const clearBtn = document.querySelector('.btn-clear-cart');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                Swal.fire({
                    title: 'ล้างตะกร้าสินค้า?',
                    text: "สินค้าทั้งหมดจะถูกลบออกถาวร",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'ลบทั้งหมด',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => { if (result.isConfirmed) window.location.href = url; });
            });
        }
    });
    </script>
</body>
</html>