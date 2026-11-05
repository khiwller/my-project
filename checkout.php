<?php 
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. ตรวจสอบการเลือกรายการ (รับมาจากหน้าตะกร้า)
$selectedKeys = $_POST['selected_items'] ?? [];

if (empty($selectedKeys)) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'ไม่พบรายการที่เลือก',
                text: 'กรุณาเลือกรายการที่ต้องการชำระเงินจากหน้าตะกร้าก่อนครับ',
                icon: 'warning',
                confirmButtonColor: '#d4af37'
            }).then(() => { window.location='cart.php'; });
        });
    </script>";
    exit();
}

// 2. ดึงข้อมูลสินค้าทั้งหมดเตรียมไว้
$stmtAll = $pdo->query("SELECT * FROM products");
$productsDB = [];
foreach ($stmtAll->fetchAll() as $p) {
    $productsDB[$p['id']] = $p;
}

$stmtUser = $pdo->prepare("SELECT current_points, address, phone FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$userData = $stmtUser->fetch();
$userPoints = $userData['current_points'];

// 3. ดึงข้อมูลจาก Database (ตาราง cart_items)
$checkoutItems = [];
$foundPurposes = [];

$placeholders = implode(',', array_fill(0, count($selectedKeys), '?'));
$sqlCart = "SELECT * FROM cart_items WHERE id IN ($placeholders) AND user_id = ?";
$stmtCart = $pdo->prepare($sqlCart);
$stmtCart->execute(array_merge($selectedKeys, [$_SESSION['user_id']]));
$dbItems = $stmtCart->fetchAll(PDO::FETCH_ASSOC);

foreach ($dbItems as $row) {
    $itemData = [
        'product_id' => $row['product_id'],
        'qty'         => $row['qty'],
        'purpose'    => $row['purpose'],
        'addons'     => json_decode($row['addons'] ?? '[]', true),
        'set_items'  => json_decode($row['set_items'] ?? '[]', true)
    ];
    $checkoutItems[$row['id']] = $itemData;
    $foundPurposes[] = $row['purpose'];
}

$uniquePurposes = array_unique($foundPurposes);
if (count($uniquePurposes) > 1) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'ประเภทรายการไม่ตรงกัน',
                text: 'ไม่สามารถชำระเงินรายการ \"สั่งซื้อ\" และ \"บริจาค\" พร้อมกันได้',
                icon: 'error',
                confirmButtonColor: '#d4af37'
            }).then(() => { window.location='cart.php'; });
        });
    </script>";
    exit();
}

$orderType = !empty($uniquePurposes) ? current($uniquePurposes) : 'purchase';

// 4. คำนวณราคาสินค้าเท่านั้น (Product Subtotal)
$totalProductPrice = 0;
foreach ($checkoutItems as $item) {
    if (!isset($productsDB[$item['product_id']])) continue;
    $p = $productsDB[$item['product_id']];
    $totalProductPrice += (floatval($p['price']) * intval($item['qty']));
    if (!empty($item['addons'])) {
        foreach ($item['addons'] as $ad) {
            $aId = is_array($ad) ? ($ad['id'] ?? 0) : (int)$ad;
            $aQty = is_array($ad) ? ($ad['qty'] ?? 1) : 1;
            if (isset($productsDB[$aId])) {
                $totalProductPrice += (floatval($productsDB[$aId]['price']) * $aQty);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันรายการชำระเงิน | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { font-family: 'Noto Sans Thai', sans-serif; background-color: #ffffff; margin: 0; padding: 0; }
        .serif { font-family: 'Playfair Display', serif !important; }
        .bg-charcoal { background-color: #121212 !important; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .text-gold {
            background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .checkout-content { font-family: 'IBM Plex Sans Thai', sans-serif; }
        @media (min-width: 1024px) { .sticky-summary { position: sticky; top: 120px; } }
    </style>
</head>
<body class="text-gray-800">

    <?php require_once 'includes/navbar.php'; ?>
    <div class="h-24 md:h-32"></div>

    <main class="max-w-[1500px] mx-auto px-4 md:px-8 lg:px-16 pb-24 checkout-content">
        
        <div class="mb-10 md:mb-16 border-b border-gray-100 pb-8">
            <h1 class="serif text-3xl md:text-5xl text-gray-900 leading-tight">ยืนยันการชำระเงิน</h1>
        </div>

        <form action="checkout_process.php" method="POST" enctype="multipart/form-data" id="checkoutForm" onsubmit="return validateForm(event)">
            
            <input type="hidden" name="order_type" value="<?= $orderType ?>">
            <input type="hidden" name="total_amount" id="hidden_total_amount" value="<?= $totalProductPrice ?>"> 
            <input type="hidden" name="shipping_fee" id="hidden_shipping_fee" value="0">
            <input type="hidden" name="discount_amount" id="hidden_discount_amount" value="0">
            <input type="hidden" name="net_payable" id="hidden_net_payable" value="<?= $totalProductPrice ?>"> 

            <?php foreach($selectedKeys as $key): ?>
                <input type="hidden" name="checkout_keys[]" value="<?= htmlspecialchars($key) ?>">
            <?php endforeach; ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16">
                
                <div class="lg:col-span-7 space-y-12">
                    <?php if($orderType == 'purchase'): ?>
                        <div class="bg-white border border-gray-100 shadow-sm rounded-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                                <h5 class="serif text-lg font-bold mb-0 uppercase tracking-widest">ที่อยู่จัดส่ง</h5>
                                <i class="fas fa-map-marked-alt text-gold"></i>
                            </div>
                            <div class="p-0"><?php include 'includes/map_delivery.php'; ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="bg-white border border-gray-100 shadow-2xl rounded-sm overflow-hidden">
                        <div class="bg-charcoal text-[#D4AF37] px-8 py-5 flex justify-between items-center">
                            <h5 class="serif text-lg font-bold mb-0 uppercase tracking-widest">คำสั่งซื้อ</h5>
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse min-w-[600px]">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-100">
                                        <th class="p-6 text-sm font-black uppercase tracking-widest text-gray-400">รายละเอียดสินค้า</th>
                                        <th class="p-6 text-sm font-black uppercase tracking-widest text-gray-400 text-center">จำนวน</th>
                                        <th class="p-6 text-sm font-black uppercase tracking-widest text-gray-400 text-right">รวม</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php foreach ($checkoutItems as $item): 
                                        $p = $productsDB[$item['product_id']];
                                        $rowTotal = ($p['price'] * $item['qty']);
                                        $addonsHtml = "";
                                        foreach(($item['addons'] ?? []) as $ad) {
                                            $aId = is_array($ad) ? ($ad['id'] ?? 0) : (int)$ad;
                                            $aQty = is_array($ad) ? ($ad['qty'] ?? 1) : 1;
                                            if(isset($productsDB[$aId])) {
                                                $rowTotal += ($productsDB[$aId]['price'] * $aQty);
                                                $addonsHtml .= "<div class='text-[12px] text-gray-400 mt-1'><i class='fas fa-plus text-gold mr-2'></i> " . htmlspecialchars($productsDB[$aId]['name']) . " (x".$aQty.")</div>";
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td class="p-6">
                                            <div class="font-bold text-gray-900"><?= htmlspecialchars($p['name']) ?></div>
                                            <?= $addonsHtml ?>
                                        </td>
                                        <td class="p-6 text-center font-bold">x<?= $item['qty'] ?></td>
                                        <td class="p-6 text-right font-bold">฿<?= number_format($rowTotal, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 relative">
                    <div class="sticky-summary">
                        <div class="bg-white border border-gray-100 shadow-2xl rounded-sm overflow-hidden">
                            <div class="bg-charcoal text-[#D4AF37] text-center py-5">
                                <h4 class="serif text-xl font-bold mb-0 tracking-[0.2em] uppercase">สรุปการชำระเงิน</h4>
                            </div>
                            <div class="p-8">
                                
                                <div id="pointsSection" class="mb-10 bg-gray-50 p-6 border-l-4 border-gold" style="display: <?= $orderType == 'purchase' ? 'block' : 'none' ?>;">
                                    <label class="text-[13px] font-black uppercase tracking-[0.2em] text-gray-400 mb-3 block">ใช้คะแนนสะสม (มี <?= number_format($userPoints) ?>)</label>
                                    <div class="flex items-center">
                                        <span class="bg-white border border-r-0 px-4 py-3 text-gold"><i class="fas fa-star"></i></span>
                                        <input type="number" name="points_to_use" id="points_to_use" 
                                               class="w-full bg-white border p-3 text-base focus:outline-none" 
                                               value="0" min="0" max="<?= $userPoints ?>" oninput="calculateGrandTotal()">
                                    </div>
                                </div>

                                <div class="space-y-4 mb-10 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 font-bold uppercase tracking-widest">ยอดรวมสินค้า</span>
                                        <span class="font-bold text-gray-900">฿<?= number_format($totalProductPrice, 2) ?></span>
                                    </div>
                                    <div class="flex justify-between" id="shippingRow" style="display: <?= $orderType=='purchase' ? 'flex':'none' ?>;">
                                        <span class="text-gray-500 font-bold uppercase tracking-widest">ค่าจัดส่ง</span>
                                        <span class="font-bold text-blue-600" id="displayShipping">฿0.00</span>
                                    </div>
                                    
                                    <div class="flex justify-between text-red-500" id="discountRow" style="display: <?= $orderType == 'purchase' ? 'flex' : 'none' ?>;">
                                        <span class="font-bold uppercase tracking-widest">ส่วนลดจากคะแนน</span>
                                        <span class="font-bold" id="displayDiscount">-฿0.00</span>
                                    </div>
                                    
                                    <hr class="border-gray-100">
                                    <div class="flex justify-between items-end pt-2">
                                        <span class="text-lg font-bold">ยอดชำระสุทธิ</span>
                                        <span class="text-5xl font-bold text-gold">฿<span id="show_net_total"><?= number_format($totalProductPrice, 2) ?></span></span>
                                    </div>
                                </div>

                               <div id="payment_section" class="mb-10 border-t border-gray-100 pt-10">
                                    <div class="text-center mb-8">
                                        <h6 class="text-[13px] font-black uppercase tracking-[0.2em] text-gray-400 mb-5">สแกนเพื่อชำระเงิน</h6>
                                        <img src="uploads/qr.jpg" class="w-full max-w-[885px] mx-auto mb-4 block shadow-sm" style="aspect-ratio: 885/1200;" alt="QR Payment">
                                    </div>

                                    <div class="mb-8">
                                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-3 block">
                                            แนบหลักฐานการโอนเงิน<span class="text-red-500">*</span>
                                        </label>
                                        <input type="file" name="slip_image" id="slip_image" class="w-full text-sm border p-2 bg-white rounded-sm" accept="image/*" required>
                                    </div>
                                </div>

                                <button type="submit" name="confirm_order" class="w-full gold-gradient text-black font-black py-5 rounded-sm uppercase tracking-[0.3em] text-sm shadow-xl hover:opacity-90 transition-all active:scale-95">
                                    ยืนยันการชำระเงิน
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <?php include 'chat_widget.php'; ?>
    <?php require_once 'includes/footer.php'; ?>
                                    
    <script>
        const staticProductSubtotal = parseFloat(<?= $totalProductPrice ?>) || 0;
        const maxUserPointsAvailable = parseInt(<?= $userPoints ?>) || 0;
        const currentOrderType = '<?= $orderType ?>';
        
        window.deliveryFee = 0; 

        function calculateGrandTotal() {
            const pointsInput = document.getElementById('points_to_use');
            let pointsToUse = (pointsInput) ? (parseInt(pointsInput.value) || 0) : 0;
            let currentShipping = parseFloat(window.deliveryFee) || 0;

            // 🌟 Logic: ถ้าเป็นบริจาค ให้ค่าส่งและส่วนลดเป็น 0 เสมอ
            if (currentOrderType === 'donation') { 
                currentShipping = 0; 
                pointsToUse = 0; 
                if (pointsInput) {
                    pointsInput.value = 0;
                    pointsInput.disabled = true;
                }
            }

            let totalWithShipping = staticProductSubtotal + currentShipping;
            
            if (pointsToUse > maxUserPointsAvailable) pointsToUse = maxUserPointsAvailable;
            if (pointsToUse > totalWithShipping) pointsToUse = totalWithShipping;
            
            let finalNetPayable = totalWithShipping - pointsToUse;

            // อัปเดต UI
            if (document.getElementById('displayShipping')) {
                document.getElementById('displayShipping').innerText = '฿' + currentShipping.toLocaleString(undefined, {minimumFractionDigits: 2});
            }
            if (document.getElementById('displayDiscount')) {
                document.getElementById('displayDiscount').innerText = '-฿' + pointsToUse.toLocaleString(undefined, {minimumFractionDigits: 2});
            }
            document.getElementById('show_net_total').innerText = finalNetPayable.toLocaleString(undefined, {minimumFractionDigits: 2});

            // อัปเดต Hidden Inputs
            document.getElementById('hidden_total_amount').value = staticProductSubtotal; 
            document.getElementById('hidden_shipping_fee').value = currentShipping;
            document.getElementById('hidden_discount_amount').value = pointsToUse;
            document.getElementById('hidden_net_payable').value = finalNetPayable;

            // จัดการส่วนสลิป
            const ps = document.getElementById('payment_section');
            const si = document.getElementById('slip_image');
            if (finalNetPayable <= 0) { 
                if(ps) ps.style.display = 'none'; 
                if(si) si.removeAttribute('required'); 
            } else { 
                if(ps) ps.style.display = 'block'; 
                if(si) si.setAttribute('required', 'required'); 
            }
        }

        window.onload = function() { calculateGrandTotal(); };

        function validateForm(e) {
            const netPayable = parseFloat(document.getElementById('hidden_net_payable').value) || 0;
            const slipInput = document.getElementById('slip_image');

            if (currentOrderType === 'purchase') {
                const latValue = document.getElementById('hidden_lat')?.value;
                if (!latValue || latValue === "0" || latValue === "") {
                    Swal.fire('ระบุที่อยู่', 'กรุณาปักหมุดจุดจัดส่งบนแผนที่ก่อนครับ', 'warning');
                    e.preventDefault(); return false;
                }
            }
            if (netPayable > 0 && (!slipInput || slipInput.files.length === 0)) {
                Swal.fire('แนบสลิป', 'กรุณาแนบหลักฐานการโอนเงินเพื่อยืนยันรายการครับ', 'warning');
                e.preventDefault(); return false;
            }
            return true;
        }
    </script>
</body>
</html>