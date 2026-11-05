<?php
session_start();
require_once 'config/db.php';

// 1. ตรวจสอบการเข้าสู่ระบบ และรับค่า ID
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("Access Denied.");
}

$userId = $_SESSION['user_id'];
$orderId = $_GET['id'];

// 2. ดึงข้อมูล Order และตรวจสอบว่าเป็นของ User คนนี้จริงๆ เพื่อความปลอดภัย
$stmt = $pdo->prepare("SELECT o.*, u.username, u.email, u.phone 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    die("ไม่พบข้อมูลคำสั่งซื้อ หรือคุณไม่มีสิทธิ์เข้าถึง");
}

// 3. ดึงรายการสินค้า
$stmtItems = $pdo->prepare("SELECT oi.*, p.name 
                            FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            WHERE oi.order_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

// คำนวณยอดสุทธิ
$totalProduct = floatval($order['total_amount']);
$delivery = floatval($order['delivery_fee']);
$discount = floatval($order['discount_amount']);
$netAmount = ($totalProduct + $delivery) - $discount;

// ตั้งชื่อเอกสารตามประเภท
$receiptTitle = ($order['order_type'] == 'donation') ? 'ใบรับเงินบริจาค (Donation)' : 'ใบเสร็จรับเงิน (Receipt)';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จรับเงิน #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body { font-family: 'Sarabun', sans-serif; color: #333; background-color: #eee; margin: 0; padding: 0; }
        
        /* จัดกรอบกระดาษ A4 สำหรับแสดงผลบนหน้าจอคอมพิวเตอร์ */
        .a4-page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }

        /* 🌟 ตั้งค่าสำหรับการ Print จริง (กระดาษ) */
        @media print {
            body { 
                background-color: white; 
                font-size: 13px; /* ลดขนาดฟอนต์ลงเล็กน้อยตอนพิมพ์เพื่อให้จุได้เยอะขึ้น */
            }
            .a4-page {
                margin: 0;
                padding: 10mm; /* ลดขอบกระดาษลงเพื่อให้มีพื้นที่แนวตั้งเพิ่มขึ้น */
                width: 100%;
                min-height: auto;
                box-shadow: none;
            }
            
            /* ลดช่องว่างในตารางเพื่อให้บรรจุแถวได้มากขึ้น */
            th, td { padding: 4px 8px !important; }
            .mb-8 { margin-bottom: 1rem !important; }
            .mb-6 { margin-bottom: 1rem !important; }
            .mt-20 { margin-top: 2rem !important; }
            .pt-10 { padding-top: 1rem !important; }

            /* ซ่อนปุ่มต่างๆ เวลา Print */
            .no-print { display: none !important; }

            /* คำสั่งสำคัญ: ห้ามเบราว์เซอร์ตัดแบ่งครึ่งบรรทัด หรือแยกส่วนสรุป/ลายเซ็น */
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; break-inside: avoid; page-break-after: auto; }
            .avoid-page-break { page-break-inside: avoid; break-inside: avoid; }
        }
    </style>
</head>
<body>

    <div class="text-center py-4 no-print bg-gray-800 text-white sticky top-0 z-50 shadow-md">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded mr-2 transition">
            <i class="fas fa-print mr-2"></i> สั่งพิมพ์ (Print)
        </button>
        <button onclick="window.close()" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded transition">
            ปิดหน้าต่าง
        </button>
    </div>

    <div class="a4-page">
        <div class="flex justify-between items-start border-b-2 border-gray-800 pb-6 mb-6">
            <div class="pt-2">
                <img src="uploads/logo.png" alt="โลโก้บริษัท" class="h-20 w-auto mb-4 object-contain" onerror="this.style.display='none'">
                <h1 class="text-3xl font-bold mb-1">สมหมายหีบทองอยุธยา</h1>
                <p class="text-sm text-gray-600 mt-2">
                    16 หมู่ 1 ต.พระยาบันลือ อ.ลาดบัวหลวง จ.พระนครศรีอยุธยา 13230<br>
                    โทรศัพท์: 063-431-7812
                </p>
            </div>
            
            <div class="text-right flex flex-col items-end">                
                <h2 class="text-2xl font-bold text-gray-800 mb-1"><?= $receiptTitle ?></h2>
                <p class="text-sm font-bold text-gray-600">เลขที่: <span class="text-black">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span></p>
                <p class="text-sm text-gray-600">วันที่: <?= date('d/m/Y', strtotime($order['created_at'])) ?></p>
            </div>
        </div>

        <div class="mb-8 mt-4">
            <h3 class="font-bold text-gray-800 border-l-4 border-gray-800 pl-2 mb-2">ข้อมูลลูกค้า</h3>
            <div class="bg-gray-50 p-4 rounded-sm text-sm border border-gray-200">
                <p><strong>ชื่อลูกค้า:</strong> <?= htmlspecialchars($order['username']) ?></p>
                <p><strong>อีเมล:</strong> <?= htmlspecialchars($order['email']) ?></p>
                <?php if($order['order_type'] == 'purchase'): ?>
                    <p><strong>เบอร์ติดต่อ:</strong> <?= htmlspecialchars($order['shipping_phone'] ?: '-') ?></p>
                    <p><strong>ที่อยู่จัดส่ง:</strong> <?= htmlspecialchars($order['shipping_address_detail'] ?: 'ไม่ระบุ') ?></p>
                <?php endif; ?>
            </div>
        </div>

        <h3 class="font-bold text-gray-800 border-l-4 border-gray-800 pl-2 mb-2">รายการ</h3>
        <table class="w-full text-sm mb-6 border border-gray-300">
            <thead class="bg-gray-100 border-b border-gray-300">
                <tr>
                    <th class="p-3 text-left w-12 border-r border-gray-300">ลำดับ</th>
                    <th class="p-3 text-left border-r border-gray-300">รายการสินค้า / รายละเอียด</th>
                    <th class="p-3 text-center w-24 border-r border-gray-300">จำนวน</th>
                    <th class="p-3 text-right w-32 border-r border-gray-300">ราคา/หน่วย</th>
                    <th class="p-3 text-right w-32">จำนวนเงิน</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if(count($items) > 0): ?>
                    <?php $i = 1; foreach($items as $item): ?>
                    <tr>
                        <td class="p-3 text-center border-r border-gray-200"><?= $i++ ?></td>
                        <td class="p-3 border-r border-gray-200"><?= htmlspecialchars($item['name']) ?></td>
                        <td class="p-3 text-center border-r border-gray-200"><?= number_format($item['quantity']) ?></td>
                        <td class="p-3 text-right border-r border-gray-200"><?= number_format($item['price_at_purchase'], 2) ?></td>
                        <td class="p-3 text-right font-bold"><?= number_format($item['quantity'] * $item['price_at_purchase'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="p-3 text-center text-gray-500 py-6">ทำรายการบริจาค / ไม่มีสินค้าย่อย</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="avoid-page-break">
            <div class="flex justify-end mb-10">
                <div class="w-full sm:w-1/2 md:w-1/3 text-sm">
                    <div class="flex justify-between py-1">
                        <span>รวมเป็นเงิน:</span>
                        <span>฿ <?= number_format($totalProduct, 2) ?></span>
                    </div>
                    
                    <?php if($order['order_type'] == 'purchase'): ?>
                    <div class="flex justify-between py-1">
                        <span>ค่าจัดส่ง:</span>
                        <span> ฿ <?= number_format($delivery, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if($discount > 0): ?>
                    <div class="flex justify-between py-1 text-red-600">
                        <span>ส่วนลด (แต้มสะสม):</span>
                        <span>- ฿ <?= number_format($discount, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between py-3 border-t-2 border-gray-800 font-bold text-lg mt-2">
                        <span>ยอดสุทธิ</span>
                        <span>฿ <?= number_format($netAmount, 2) ?></span>
                    </div>
                </div>
            </div>

            <div class="flex justify-between mt-20 text-center text-sm pt-10">
                <div class="w-1/3">
                    <div class="border-b border-gray-400 w-3/4 mx-auto mb-2"></div>
                    <p>ผู้รับเงิน (Receiver)</p>
                    <p class="text-gray-500 text-xs mt-1">วันที่ ______/______/______</p>
                </div>
                <div class="w-1/3 text-gray-500 flex items-center justify-center">
                    <?php if($order['order_type'] == 'donation'): ?>
                        <p class="italic font-bold text-gray-800">"ขออนุโมทนาบุญ ขอให้ท่านและครอบครัวมีความสุขความเจริญ"</p>
                    <?php else: ?>
                        <p class="italic">ขอบคุณที่ไว้วางใจใช้บริการของเรา</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500); 
        };
    </script>
</body>
</html>