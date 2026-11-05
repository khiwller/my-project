<?php 
session_start();
require_once 'config/db.php';

// 1. ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: history.php");
    exit();
}

$userId = $_SESSION['user_id'];
$orderId = $_GET['id'];

// 2. ดึงข้อมูล Order
$stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmtOrder->execute([$orderId, $userId]);
$order = $stmtOrder->fetch();

if (!$order) {
    echo "<script>alert('ไม่พบข้อมูลรายการสั่งซื้อนี้'); window.location='history.php';</script>";
    exit();
}

// 3. ดึงรายการสินค้า
$stmtItems = $pdo->prepare("
    SELECT oi.*, p.name, p.image_url, p.type 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

// 🌟 ยอดรวมสินค้า (ดึงจาก total_amount เพราะเราเก็บเฉพาะราคาสินค้าแล้ว)
$productSubtotal = floatval($order['total_amount']);
$deliveryFee = floatval($order['delivery_fee']);
$discountAmount = floatval($order['discount_amount']);

// 🌟 คำนวณยอดโอนสุทธิ (ยอดจริงที่ลูกค้าโอนตามสลิป)
$netPayable = ($productSubtotal + $deliveryFee) - $discountAmount;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดบิล #<?= $orderId ?> | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://api.longdo.com/map/?key=70a15443673186fdac9c1c56bca19dcd"></script>
    
    <style>
        /* 🌟 CSS มาตรฐานสำหรับโครงสร้างหลัก */
        html, body { margin: 0; padding: 0; width: 100%; background-color: #fcfcfc; font-family: 'Noto Sans Thai', sans-serif; }
        .serif { font-family: 'Playfair Display', serif !important; }
        .bg-charcoal { background-color: #121212 !important; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .text-gold { color: #D4AF37; }

        /* Scoped Font สำหรับเนื้อหาหลัก */
        .order-details-content { font-family: 'IBM Plex Sans Thai', sans-serif; }
        
        #map { height: 300px; width: 100%; border-radius: 8px; border: 1px solid #eee; }
        
        .status-pill { padding: 6px 16px; border-radius: 50px; font-weight: 700; font-size: 13px; text-transform: uppercase; border: 1px solid transparent; }
        .status-pending { background-color: #fff4e5; color: #b06a00; border-color: #ffe3b3; }
        .status-paid { background-color: #e7f5ff; color: #1971c2; border-color: #d0ebff; }
        .status-completed { background-color: #e6fcf5; color: #087f5b; border-color: #c3fae8; }
        .status-cancelled { background-color: #fff5f5; color: #c92a2a; border-color: #ffdeeb; }
    </style>
</head>
<body class="text-gray-800">

    <?php require_once 'includes/navbar.php'; ?>

    <div class="h-24 md:h-32"></div>

    <main class="max-w-[1400px] mx-auto px-4 md:px-8 pb-24 order-details-content">
        
        <div class="mb-10 border-b border-gray-100 pb-8">
            <nav class="flex mb-4 text-[12px] uppercase tracking-[0.3em] font-bold text-gray-300">
                <a href="history.php" class="hover:text-gold transition">ประวัติคำสั่งซื้อ</a>
                <span class="mx-3 text-gray-200">/</span>
                <span class="text-gray-900">BILL #<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?></span>
            </nav>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                <h1 class="serif text-3xl md:text-4xl font-bold text-gray-900 leading-tight">รายละเอียดรายการ</h1>
                <a href="history.php" class="text-sm font-bold text-gray-400 hover:text-gold transition uppercase tracking-widest">
                    <i class="fas fa-arrow-left mr-2"></i> กลับหน้าประวัติ
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-100 shadow-sm p-6 md:p-8 mb-10 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-6">
                <?php 
                    $s = $order['status'];
                    $statusName = ($s=='pending'?'รอตรวจสอบ':($s=='paid'?'ชำระเงินแล้ว':($s=='completed'?'สำเร็จ':'ยกเลิก')));
                ?>
                <span class="status-pill status-<?= $s ?>"><?= $statusName ?></span>
                <div class="border-l pl-6 border-gray-100">
                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">วันที่ทำรายการ</div>
                    <div class="font-bold text-gray-900"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?> น.</div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">ยอดโอนสุทธิ</div>
                <div class="text-3xl font-black text-[#D4AF37]">฿ <?= number_format($netPayable, 2) ?></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <div class="lg:col-span-8 space-y-10">
                <div class="bg-white border border-gray-100 shadow-xl rounded-sm overflow-hidden">
                    <div class="bg-charcoal text-gold px-6 py-4 flex justify-between items-center">
                        <h3 class="serif text-lg font-bold tracking-widest uppercase mb-0">รายการสินค้าในบิล</h3>
                        <i class="fas fa-list-ul"></i>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="p-5 text-[11px] font-black uppercase tracking-widest text-gray-400">รายละเอียด</th>
                                    <th class="p-5 text-[11px] font-black uppercase tracking-widest text-gray-400 text-center">ประเภท</th>
                                    <th class="p-5 text-[11px] font-black uppercase tracking-widest text-gray-400 text-center">จำนวน</th>
                                    <th class="p-5 text-[11px] font-black uppercase tracking-widest text-gray-400 text-right">รวม</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach ($items as $item): 
                                    $rowTotal = $item['price_at_purchase'] * $item['quantity'];
                                ?>
                                <tr>
                                    <td class="p-5">
                                        <div class="flex items-center gap-4">
                                            <img src="uploads/<?= $item['image_url'] ?: 'default.png' ?>" class="w-16 h-16 object-cover border border-gray-100 rounded-sm">
                                            <div>
                                                <div class="font-bold text-gray-900"><?= htmlspecialchars($item['name']) ?></div>
                                                <div class="text-[12px] text-gray-400">฿ <?= number_format($item['price_at_purchase'], 2) ?> / หน่วย</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-5 text-center">
                                        <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-1 bg-gray-100 rounded-sm text-gray-500">
                                            <?= $item['type'] == 'coffin' ? 'หีบศพ' : 'ตกแต่ง' ?>
                                        </span>
                                    </td>
                                    <td class="p-5 text-center font-bold text-gray-500">x<?= $item['quantity'] ?></td>
                                    <td class="p-5 text-right font-bold text-gray-900">฿ <?= number_format($rowTotal, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50/50">
                                <tr>
                                    <td colspan="3" class="p-5 text-right font-bold text-gray-400 text-sm uppercase tracking-widest">รวมราคาสินค้า:</td>
                                    <td class="p-5 text-right font-bold text-gray-900">฿ <?= number_format($productSubtotal, 2) ?></td>
                                </tr>
                                <?php if($order['order_type'] == 'purchase'): ?>
                                <tr>
                                    <td colspan="3" class="px-5 pb-5 text-right font-bold text-gray-400 text-sm uppercase tracking-widest">ค่าบริการจัดส่ง:</td>
                                    <td class="px-5 pb-5 text-right font-bold text-blue-600">+ ฿<?= number_format($deliveryFee, 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="px-5 pb-5 text-right font-bold text-gray-400 text-sm uppercase tracking-widest">ส่วนลดแต้มสะสม:</td>
                                    <td class="px-5 pb-5 text-right font-bold text-red-500">- ฿ <?= number_format($discountAmount, 2) ?></td>
                                </tr>
                                <tr class="bg-white border-t-2 border-gray-100">
                                    <td colspan="3" class="p-6 text-right font-black text-gray-900 uppercase tracking-[0.2em]">ยอดโอนสุทธิ</td>
                                    <td class="p-6 text-right font-black text-2xl text-[#D4AF37]">฿ <?= number_format($netPayable, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="bg-white border border-gray-100 shadow-xl p-8">
                    <h3 class="serif text-xl text-gray-900 font-bold tracking-widest uppercase mb-8 border-b pb-4">
                        <i class="fas fa-file-invoice-dollar text-gold mr-2"></i> หลักฐานการชำระเงิน
                    </h3>
                    <div class="flex justify-center bg-gray-50 p-6 border border-dashed border-gray-200">
                        <?php if($order['slip_image']): ?>
                            <a href="uploads/slips/<?= $order['slip_image'] ?>" target="_blank">
                                <img src="uploads/slips/<?= $order['slip_image'] ?>" class="max-w-full h-auto shadow-2xl " style="max-height: 600px;">
                            </a>
                        <?php else: ?>
                            <div class="py-12 text-center text-gray-400">
                                <i class="fas fa-star text-gold text-4xl mb-4"></i>
                                <p class="font-bold uppercase tracking-widest">ชำระด้วยแต้มสะสมทั้งหมด</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 space-y-10">
                <?php if($order['order_type'] == 'purchase'): ?>
                <div class="bg-white border border-gray-100 shadow-xl p-6 rounded-sm">
                    <h4 class="serif text-lg font-bold uppercase tracking-widest mb-6 border-b border-gray-50 pb-4">
                        <i class="fas fa-map-marker-alt text-red-500 mr-2"></i> ที่อยู่จัดส่ง
                    </h4>
                    <div class="mb-6">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-widest block mb-1">เบอร์โทรศัพท์ติดต่อ</label>
                        <div class="font-bold text-gray-900"><?= htmlspecialchars($order['shipping_phone']) ?></div>
                    </div>
                    <div class="mb-6">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-widest block mb-1">รายละเอียดที่อยู่</label>
                        <div class="text-sm text-gray-600 leading-relaxed italic"><?= nl2br(htmlspecialchars($order['shipping_address_detail'])) ?></div>
                    </div>
                    <?php if($order['delivery_lat']): ?>
                        <div id="map" class="mb-4"></div>
                        <a href="https://www.google.com/maps?q=<?= $order['delivery_lat'] ?>,<?= $order['delivery_lng'] ?>" target="_blank" class="block w-full text-center bg-charcoal text-gold font-black py-4 uppercase tracking-[0.3em] text-[11px] hover:bg-black transition rounded-sm">
                            <i class="fab fa-google mr-2"></i> Open in Google Maps
                        </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="bg-charcoal text-white p-10 text-center shadow-2xl relative overflow-hidden">
                    <i class="fas fa-heart text-[#D4AF37] text-6xl mb-6"></i>
                    <h4 class="serif text-2xl font-bold text-gold mb-2">รายการบริจาค</h4>
                    <p class="text-lg text-gray-400 font-light leading-relaxed">"ขออนุโมทนาในกุศลจิตครั้งนี้"</p>
                </div>
                <?php endif; ?>

                <div class="bg-gray-50 border-l-4 border-gold p-8 rounded-sm shadow-sm">
                    <h6 class="font-bold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-headset mr-3 text-gold "></i> ต้องการความช่วยเหลือ?
                    </h6>
                    <p class="text-lg text-gray-500 leading-relaxed">
                        หากพบปัญหาเกี่ยวกับรายการสั่งซื้อนี้ สามารถติดต่อเราได้ที่ 063-431-781208 หรือ Facebook สมหมายหีบทองอยุธยา <a href="https://www.facebook.com/sm.hmay.hib.thxng.xyuthya" class="text-gray-500 hover:text-white transition-all duration-300 transform hover:scale-110"><i class="fab fa-facebook"></i></a>

                    </p>
                    <p href="" class="text-lg text-gray-500 leading-relaxed ">
                        เรายินดีให้บริการและช่วยเหลือคุณเสมอค่ะ
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>

    <script>
        function initMap() {
            <?php if(!empty($order['delivery_lat'])): ?>
            var map = new longdo.Map({
                placeholder: document.getElementById('map'),
                lastview: false
            });
            map.location({ lon: <?= $order['delivery_lng'] ?>, lat: <?= $order['delivery_lat'] ?> }, true);
            map.zoom(15, true);
            map.Overlays.add(new longdo.Marker({ lon: <?= $order['delivery_lng'] ?>, lat: <?= $order['delivery_lat'] ?> }));
            map.ui.LayerSelector.visible(false);
            map.ui.Zoombar.visible(false);
            <?php endif; ?>
        }
        window.onload = initMap;
    </script>
</body>
</html>