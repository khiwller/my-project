<?php 
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// --- 2. รับค่าตัวกรอง (Filters) ---
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$orderType = $_GET['order_type'] ?? '';

// --- 3. สร้างคำสั่ง SQL ---
$sql = "SELECT * FROM orders WHERE user_id = ?";
$params = [$userId];

if (!empty($startDate)) {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $startDate;
}
if (!empty($endDate)) {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $endDate;
}
if (!empty($orderType)) {
    $sql .= " AND order_type = ?";
    $params[] = $orderType;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติรายการของคุณ | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* 🌟 CSS มาตรฐานเพื่อให้ Navbar/Footer สีและฟอนต์ถูกต้อง */
        html, body { margin: 0; padding: 0; width: 100%; background-color: #fcfcfc; font-family: 'Noto Sans Thai', sans-serif; }
        .serif { font-family: 'Playfair Display', serif !important; }
        .bg-charcoal { background-color: #121212 !important; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .text-gold { color: #D4AF37; }

        /* Scoped Font สำหรับเนื้อหาประวัติ */
        .history-content { font-family: 'IBM Plex Sans Thai', sans-serif; }
        
        /* Status Badge Styling */
        .status-badge { padding: 4px 12px; border-radius: 50px; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid transparent; }
        .status-pending { background-color: #fff4e5; color: #b06a00; border-color: #ffe3b3; }
        .status-paid { background-color: #e6fcf5; color: #087f5b; border-color: #c3fae8; }
        .status-completed { background-color: #e7f5ff; color: #1971c2; border-color: #d0ebff; }
        .status-cancelled { background-color: #fff5f5; color: #c92a2a; border-color: #ffdeeb; }

        .custom-scrollbar::-webkit-scrollbar { height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #D4AF37; border-radius: 10px; }
    </style>
</head>
<body class="text-gray-800">

    <?php require_once 'includes/navbar.php'; ?>

    <div class="h-24 md:h-32"></div> 

    <main class="max-w-[1400px] mx-auto px-4 md:px-8 pb-24 history-content">
        
        <div class="mb-10">
            <nav class="flex mb-4 text-[14px] uppercase tracking-[0.3em] font-bold text-gray-300">
                <a href="index.php" class="hover:text-gold transition">หน้าหลัก</a>
                <span class="mx-3 text-gray-200">/</span>
                <span class="text-gray-900">ประวัติรายการ</span>
            </nav>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-gray-100 pb-6">
                <h1 class="serif text-3xl md:text-4xl font-bold text-gray-900">ประวัติรายการของคุณ</h1>
                <p class="text-gray-400 text-sm">รวมรายการสั่งซื้อและรายการบริจาคทั้งหมดของท่าน</p>
            </div>
        </div>

        <div class="bg-white border border-gray-100 shadow-sm p-5 md:p-6 mb-8 rounded-sm">
            <form method="GET" action="history.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-[13px] font-black uppercase tracking-widest text-gray-400 mb-2">ตั้งแต่จากวันที่</label>
                    <input type="date" name="start_date" class="w-full bg-gray-50 border border-gray-100 p-2.5 text-sm focus:outline-none focus:border-gold" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div>
                    <label class="block text-[13px] font-black uppercase tracking-widest text-gray-400 mb-2">ถึงวันที่</label>
                    <input type="date" name="end_date" class="w-full bg-gray-50 border border-gray-100 p-2.5 text-sm focus:outline-none focus:border-gold" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div>
                    <label class="block text-[13px] font-black uppercase tracking-widest text-gray-400 mb-2">ประเภท</label>
                    <select name="order_type" class="w-full bg-gray-50 border border-gray-100 p-2.5 text-sm focus:outline-none focus:border-gold font-medium">
                        <option value="">ทั้งหมด</option>
                        <option value="purchase" <?= $orderType == 'purchase' ? 'selected' : '' ?>>สั่งซื้อสินค้า</option>
                        <option value="donation" <?= $orderType == 'donation' ? 'selected' : '' ?>>บริจาคโลงศพ</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-grow gold-gradient text-black font-black py-2.5 px-6 uppercase tracking-widest text-[13px] hover:shadow-md transition">
                        ค้นหาบิล
                    </button>
                    <?php if($startDate || $endDate || $orderType): ?>
                        <a href="history.php" class="bg-gray-100 text-gray-400 px-4 flex items-center justify-center hover:text-red-500 transition" title="ล้างค่า">
                            <i class="fas fa-undo-alt"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (empty($orders)): ?>
            <div class="bg-white border border-dashed border-gray-200 py-24 text-center rounded-sm">
                <i class="fas fa-receipt text-gray-100 text-7xl mb-6"></i>
                <h4 class="text-xl text-gray-400 mb-6">ยังไม่พบประวัติการทำรายการในขณะนี้</h4>
                <a href="index.php" class="inline-block gold-gradient text-black font-bold py-3 px-10 rounded-sm uppercase tracking-widest text-[11px]">
                    ไปหน้าหลัก
                </a>
            </div>
        <?php else: ?>
            
            <div class="bg-white border border-gray-100 shadow-xl rounded-sm overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[850px]">
                        <thead>
                            <tr class="bg-charcoal text-[#D4AF37]">
                                <th class="p-5 text-[13px] uppercase tracking-widest font-bold">หมายเลขบิล</th>
                                <th class="p-5 text-[13px] uppercase tracking-widest font-bold">วันที่ / เวลา</th>
                                <th class="p-5 text-[13px] uppercase tracking-widest font-bold">ประเภท</th>
                                <th class="p-5 text-[13px] uppercase tracking-widest font-bold text-right">ยอดชำระจริง</th>
                                <th class="p-5 text-[13px] uppercase tracking-widest font-bold text-center">สถานะ</th>
                                <th class="p-5 text-[13px] uppercase tracking-widest font-bold text-center">ตัวเลือก</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($orders as $order): 
                                // 🌟 คำนวณยอดสุทธิ: (ค่าสินค้า + ค่าส่ง) - ส่วนลด
                                $netPayable = ($order['total_amount'] + $order['delivery_fee']) - $order['discount_amount'];
                            ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-5">
                                    <span class="font-bold text-gray-900 tracking-wider">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                </td>
                                <td class="p-5">
                                    <div class="text-sm font-bold"><?= date('d/m/Y', strtotime($order['created_at'])) ?></div>
                                    <div class="text-[13px] text-gray-400"><?= date('H:i', strtotime($order['created_at'])) ?> น.</div>
                                </td>
                                <td class="p-5">
                                    <?php if($order['order_type'] == 'donation'): ?>
                                        <span class="text-[13px] font-bold text-green-600 uppercase tracking-wider"><i class="fas fa-heart mr-1"></i> บริจาค</span>
                                    <?php else: ?>
                                        <span class="text-[13px] font-bold text-blue-600 uppercase tracking-wider"><i class="fas fa-shopping-bag mr-1"></i> สั่งซื้อ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-5 text-right font-bold text-gray-900">
                                    ฿<?= number_format($netPayable, 2) ?>
                                </td>
                                <td class="p-5 text-center">
                                    <?php 
                                        switch ($order['status']) {
                                            case 'pending': echo '<span class="status-badge status-pending">รอตรวจสอบ</span>'; break;
                                            case 'paid': echo '<span class="status-badge status-paid">ชำระแล้ว</span>'; break;
                                            case 'completed': echo '<span class="status-badge status-completed">สำเร็จ</span>'; break;
                                            case 'cancelled': echo '<span class="status-badge status-cancelled">ยกเลิก</span>'; break;
                                        }
                                    ?>
                                </td>
                                <td class="p-5 text-center">
                                    <div class="flex justify-center gap-3">
                                        <a href="order_details.php?id=<?= $order['id'] ?>" class="text-[11px] font-black uppercase tracking-widest bg-gray-900 text-white px-4 py-1.5 rounded-sm hover:bg-gold  transition">
                                            รายละเอียด
                                        </a>
                                        <?php if($order['status'] == 'completed' || $order['status'] == 'paid'): ?>
                                            <a href="print_receipt.php?id=<?= $order['id'] ?>" target="_blank" class="text-gray-400 hover:text-blue-600 transition" title="พิมพ์ใบเสร็จ">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
    </main>

    <?php require_once 'includes/footer.php'; ?>
    <?php include 'chat_widget.php'; ?>

</body>
</html>