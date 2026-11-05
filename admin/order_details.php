<?php 
require_once 'layout/header.php'; 
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$orderId = $_GET['id'];
$updateSuccess = false;

// 1. ดึงข้อมูลออเดอร์
$stmtOrder = $pdo->prepare("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmtOrder->execute([$orderId]);
$order = $stmtOrder->fetch();

if (!$order) {
    echo "<script>alert('ไม่พบข้อมูลคำสั่งซื้อ'); window.location='orders.php';</script>";
    exit();
}

// 2. ดึงรายการสินค้า
$stmtItems = $pdo->prepare("SELECT oi.*, p.name, p.type, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

// 3. จัดการการอัปเดตสถานะ
if (isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);
    
    // 🌟 แก้ไขเงื่อนไขตรงนี้: เปลี่ยนจาก 'purchase' เป็น 'donation'
    // เพื่อให้แต้มสะสมเฉพาะเมื่อเป็นการ "บริจาค" เท่านั้น
    if ($newStatus == 'completed' && $order['status'] != 'completed' && $order['order_type'] == 'donation') {
        
        // คำนวณแต้ม: ทุก 100 บาท ได้ 1 แต้ม
        $pointsEarned = floor($order['total_amount'] / 100); 
        
        if ($pointsEarned > 0) {
            // เพิ่มแต้มให้ User
            $pdo->prepare("UPDATE users SET current_points = current_points + ? WHERE id = ?")->execute([$pointsEarned, $order['user_id']]);
            
            // บันทึก Log การได้รับแต้ม
            $pdo->prepare("INSERT INTO point_logs (user_id, order_id, points_change, description) 
                           VALUES (?, ?, ?, 'ได้รับแต้มบุญจากการบริจาคหีบศพ')")->execute([$order['user_id'], $orderId, $pointsEarned]);
        }
    }
    
    $updateSuccess = true;
    // ดึงข้อมูลใหม่มาแสดง
    $stmtOrder->execute([$orderId]);
    $order = $stmtOrder->fetch();
}

// 🌟 คำนวณยอดสุทธิสุดท้ายที่ลูกค้าต้องโอนจริง (Net Total)
// สูตร: (ค่าสินค้า + ค่าส่ง) - ส่วนลด
$finalPayable = ($order['total_amount'] + $order['delivery_fee']) - $order['discount_amount'];
?>

<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">
<script src="https://api.longdo.com/map/?key=70a15443673186fdac9c1c56bca19dcd"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body { font-family: 'IBM Plex Sans Thai', sans-serif; background-color: #f8f9fa; }
    .detail-card { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: #fff; }
    .table-detail thead th { background-color: #fdfdfd; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px 20px; border-bottom: 1px solid #eee; }
    .status-badge-lg { padding: 8px 20px; border-radius: 50px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; }
    .bg-soft-warning { background-color: #fff4e5; color: #b06a00; }
    .bg-soft-success { background-color: #e6fcf5; color: #087f5b; }
    .bg-soft-primary { background-color: #e7f5ff; color: #1971c2; }
    .bg-soft-danger { background-color: #fff5f5; color: #c92a2a; }
    #adminMap { height: 250px; width: 100%; border-radius: 12px; border: 1px solid #eee; }
    .text-gold { color: #d4af37 !important; }
</style>

<div class="container-fluid py-4 px-xl-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="orders.php" class="text-decoration-none">รายการสั่งซื้อ</a></li>
                    <li class="breadcrumb-item active">รายละเอียดบิล #<?= $orderId ?></li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-0 text-dark">คำสั่งซื้อ <span class="text-gold">#<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?></span></h2>
            <p class="text-muted small mb-0">โดยคุณ: <?= htmlspecialchars($order['username']) ?> (<?= $order['email'] ?>)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="orders.php" class="btn btn-white border rounded-pill px-4 shadow-sm fw-bold">
                <i class="bi bi-arrow-left me-2"></i> ย้อนกลับ
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card detail-card mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark"><i class="bi bi-cart3 me-2 text-gold"></i>รายการสินค้าในบิล</span>
                    <span class="text-muted small">วันที่สั่ง: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-detail">
                            <thead>
                                <tr>
                                    <th class="ps-4">สินค้า</th>
                                    <th class="text-center">ประเภท</th>
                                    <th class="text-center">จำนวน</th>
                                    <th class="text-end pe-4">ราคารวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $item): 
                                    $itemSubtotal = $item['price_at_purchase'] * $item['quantity'];
                                ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-3 me-3 border overflow-hidden" style="width: 55px; height: 55px;">
                                                <img src="../uploads/<?= $item['image_url'] ?: 'default.png' ?>" class="w-100 h-100 object-cover">
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></div>
                                                <div class="text-muted small">฿<?= number_format($item['price_at_purchase'], 2) ?> / หน่วย</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $item['type'] == 'coffin' ? 'bg-dark' : 'bg-info' ?> rounded-pill px-3">
                                            <?= $item['type'] == 'coffin' ? 'หีบศพ' : 'ตกแต่ง' ?>
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold">x<?= number_format($item['quantity']) ?></td>
                                    <td class="text-end pe-4 fw-bold">฿<?= number_format($itemSubtotal, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="3" class="text-end py-2 text-muted small fw-bold uppercase">ยอดรวมสินค้า (Product Subtotal):</td>
                                    <td class="text-end pe-4 fw-bold">฿<?= number_format($order['total_amount'], 2) ?></td>
                                </tr>
                                <?php if($order['order_type'] == 'purchase'): ?>
                                <tr>
                                    <td colspan="3" class="text-end py-2 text-muted small fw-bold uppercase">ค่าบริการจัดส่ง (Shipping):</td>
                                    <td class="text-end pe-4 fw-bold text-primary">+ ฿<?= number_format($order['delivery_fee'], 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-end py-2 text-muted small fw-bold uppercase">ส่วนลดแต้มสะสม (Discount):</td>
                                    <td class="text-end pe-4 fw-bold text-danger">- ฿<?= number_format($order['discount_amount'], 2) ?></td>
                                </tr>
                                <tr class="border-top border-dark border-2">
                                    <td colspan="3" class="text-end py-3 fw-bold fs-5 text-dark uppercase">ยอดโอนสุทธิ (Net Total):</td>
                                    <td class="text-end pe-4 py-3 fw-bold fs-5 text-gold">฿<?= number_format($finalPayable, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card detail-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="" method="POST" id="statusForm">
                        <div class="row align-items-center">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <label class="small text-muted fw-bold d-block mb-2 text-uppercase">เปลี่ยนสถานะรายการ</label>
                                <select name="status" class="form-select form-select-lg border rounded-pill fw-bold shadow-none">
                                    <option value="pending" <?= $order['status']=='pending'?'selected':'' ?>>รอตรวจสอบ (Pending)</option>
                                    <option value="paid" <?= $order['status']=='paid'?'selected':'' ?>>รับยอดเงินแล้ว (Paid)</option>
                                    <option value="completed" <?= $order['status']=='completed'?'selected':'' ?>>จัดส่งสำเร็จ (Completed)</option>
                                    <option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>ยกเลิกรายการ (Cancelled)</option>
                                </select>
                            </div>
                            <div class="col-md-4 text-center">
                                <span class="small text-muted d-block mb-1">สถานะปัจจุบัน:</span>
                                <?php 
                                    $s = $order['status'];
                                    $cls = ($s=='pending'?'bg-soft-warning':($s=='paid'?'bg-soft-success':($s=='completed'?'bg-soft-primary':'bg-soft-danger')));
                                    echo "<span class='status-badge-lg $cls'>$s</span>";
                                ?>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="update_status" class="btn btn-dark w-100 btn-lg rounded-pill fw-bold shadow">อัปเดตสถานะ</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <?php if ($order['order_type'] == 'purchase'): ?>
            <div class="card detail-card mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 fw-bold border-bottom"><i class="bi bi-geo-alt-fill text-danger me-2"></i>ที่อยู่จัดส่ง</div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="text-muted extra-small fw-bold text-uppercase d-block mb-1">เบอร์โทรติดต่อ</label>
                        <div class="fw-bold text-dark fs-5"><i class="bi bi-telephone me-2"></i><?= htmlspecialchars($order['shipping_phone']) ?></div>
                    </div>
                    <div class="p-3 bg-light rounded-3 mb-4 small text-dark border">
                        <strong>รายละเอียด:</strong> <?= nl2br(htmlspecialchars($order['shipping_address_detail'])) ?>
                    </div>
                    <?php if(!empty($order['delivery_lat'])): ?>
                        <div id="adminMap" class="mb-3"></div>
                        <a href="https://www.google.com/maps?q=<?= $order['delivery_lat'] ?>,<?= $order['delivery_lng'] ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100 rounded-pill">
                            <i class="bi bi-google me-2"></i> เปิดใน Google Maps
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="card detail-card mb-4 bg-dark text-white p-4 text-center border-0">
                <i class="bi bi-heart-fill text-danger display-4 mb-2"></i>
                <h5 class="fw-bold text-gold">รายการบริจาค</h5>
                <p class="small opacity-75 mb-0 italic">ไม่ต้องระบุที่อยู่จัดส่ง</p>
            </div>
            <?php endif; ?>

            <div class="card detail-card overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom fw-bold text-primary"><i class="bi bi-image me-2"></i>หลักฐานโอนเงิน</div>
                <div class="card-body text-center p-3 bg-light">
                    <?php if ($order['slip_image']): ?>
                        <div class="position-relative">
                            <a href="../uploads/slips/<?= $order['slip_image'] ?>" target="_blank">
                                <img src="../uploads/slips/<?= $order['slip_image'] ?>" class="img-fluid rounded-3 shadow border bg-white" style="max-height: 450px; object-fit: contain;">
                            </a>
                            <div class="mt-3 small text-muted"><i class="bi bi-zoom-in me-1"></i>คลิกที่รูปเพื่อขยายใหญ่</div>
                        </div>
                    <?php else: ?>
                        <div class="py-5 text-center">
                            <i class="bi bi-credit-card-2-back display-4 text-muted d-block mb-2"></i>
                            <span class="fw-bold text-muted">ชำระด้วยแต้มสะสมทั้งหมด</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($updateSuccess): ?>
<script>
    Swal.fire({
        title: 'อัปเดตเรียบร้อย!',
        text: 'บันทึกสถานะการชำระเงินใหม่สำเร็จ',
        icon: 'success',
        confirmButtonColor: '#121212',
        customClass: { confirmButton: 'rounded-pill px-5' }
    });
</script>
<?php endif; ?>

<script>
function initAdminMap() {
    <?php if(!empty($order['delivery_lat'])): ?>
    var map = new longdo.Map({
        placeholder: document.getElementById('adminMap'),
        lastview: false
    });
    map.location({ lon: <?= $order['delivery_lng'] ?>, lat: <?= $order['delivery_lat'] ?> }, true);
    map.zoom(15, true);
    map.Overlays.add(new longdo.Marker({ 
        lon: <?= $order['delivery_lng'] ?>, 
        lat: <?= $order['delivery_lat'] ?>,
        title: 'จุดส่งสินค้า',
        detail: 'พิกัดลูกค้า'
    }));
    map.ui.LayerSelector.visible(false);
    <?php endif; ?>
}
window.onload = initAdminMap;
</script>
