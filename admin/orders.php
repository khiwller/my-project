<?php 
require_once 'layout/header.php'; 
checkAdmin();

// --- รับค่าการค้นหาจาก GET ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// --- ส่วน Query ข้อมูล ---
$sql = "SELECT orders.*, users.username 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        WHERE orders.order_type = 'purchase'"; 

$params = [];

if ($search !== '') {
    $sql .= " AND (users.username LIKE ? OR orders.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter !== '') {
    $sql .= " AND orders.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY orders.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body { font-family: 'IBM Plex Sans Thai', sans-serif; background-color: #f4f6f9; }
    .card-custom { border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .table-custom thead th { background-color: #121212; color: #D4AF37; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; padding: 15px; }
    .slip-thumbnail { width: 45px; height: 60px; object-fit: cover; border-radius: 5px; cursor: pointer; transition: 0.3s; border: 2px solid #eee; }
    .slip-thumbnail:hover { transform: scale(1.1); border-color: #D4AF37; }
    
    /* Status Badges */
    .badge-soft { padding: 6px 12px; border-radius: 50px; font-weight: 600; font-size: 0.75rem; }
    .status-pending { background-color: #fff4e5; color: #b06a00; }
    .status-paid { background-color: #e6fcf5; color: #087f5b; }
    .status-completed { background-color: #e7f5ff; color: #1971c2; }
    .status-cancelled { background-color: #fff5f5; color: #c92a2a; }
    
    .text-gold { color: #D4AF37 !important; }
</style>

<div class="container-fluid py-4 px-xl-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-receipt-cutoff text-dark me-2"></i> ตรวจสอบรายการสั่งซื้อ</h2>
            <p class="text-muted mb-0 small">ตรวจสอบสลิปโอนเงินตามยอดชำระสุทธิ และอัปเดตสถานะ</p>
        </div>
        <div class="text-end">
            <span class="badge bg-dark text-gold border shadow-sm rounded-pill px-4 py-2 uppercase tracking-widest">
                Total Orders: <?= count($orders) ?>
            </span>
        </div>
    </div>

    <div class="card card-custom mb-4 shadow-sm">
        <div class="card-body p-4">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted uppercase">ค้นหาลูกค้า / เลขบิล</label>
                    <div class="input-group border rounded-pill overflow-hidden bg-light">
                        <span class="input-group-text bg-transparent border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-transparent shadow-none" placeholder="พิมพ์ชื่อลูกค้า หรือ #เลขที่บิล..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted uppercase">กรองตามสถานะ</label>
                    <select name="status" class="form-select border rounded-pill shadow-none bg-light">
                        <option value="">ทั้งหมด</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>รอตรวจสอบสลิป</option>
                        <option value="paid" <?= $status_filter == 'paid' ? 'selected' : '' ?>>ชำระเงินแล้ว</option>
                        <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>จัดส่งเรียบร้อย</option>
                        <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>ยกเลิกรายการ</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">ค้นหา</button>
                    <?php if($search || $status_filter): ?>
                        <a href="orders.php" class="btn btn-outline-secondary rounded-pill px-3"><i class="bi bi-arrow-counterclockwise"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-custom overflow-hidden shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-dark text-white">
                    <tr>
                        <th class="ps-4">เลขที่บิล</th>
                        <th>ข้อมูลลูกค้า</th>
                        <th>ยอดชำระสุทธิ (โอนจริง)</th>
                        <th class="text-center">หลักฐานโอน</th>
                        <th class="text-center">สถานะ</th>
                        <th>วันที่บันทึก</th>
                        <th class="text-center pe-4">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php if(count($orders) > 0): ?>
                        <?php foreach ($orders as $order): 
                            // 🌟 คำนวณยอดสุทธิที่ลูกค้าโอนจริง: (ค่าสินค้า + ค่าส่ง) - ส่วนลด
                            $netPayable = ($order['total_amount'] + $order['delivery_fee']) - $order['discount_amount'];
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($order['username']) ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-telephone me-1"></i><?= $order['shipping_phone'] ?: 'ไม่ได้ระบุ' ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark fs-6">฿<?= number_format($netPayable, 2) ?></div>
                                <div class="text-muted" style="font-size: 0.65rem;">
                                    สินค้า: <?= number_format($order['total_amount']) ?> | 
                                    ส่ง: <?= number_format($order['delivery_fee']) ?> | 
                                    ลด: <?= number_format($order['discount_amount']) ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($order['slip_image']): ?>
                                    <img src="../uploads/slips/<?= $order['slip_image'] ?>" 
                                         class="slip-thumbnail shadow-sm border" 
                                         onclick="viewSlip('../uploads/slips/<?= $order['slip_image'] ?>')">
                                <?php else: ?>
                                    <span class="text-muted small italic">ไม่มีสลิป</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                    switch($order['status']) {
                                        case 'pending': echo '<span class="badge-soft status-pending">รอตรวจสอบ</span>'; break;
                                        case 'paid': echo '<span class="badge-soft status-paid">รับยอดแล้ว</span>'; break;
                                        case 'completed': echo '<span class="badge-soft status-completed">สำเร็จ</span>'; break;
                                        case 'cancelled': echo '<span class="badge-soft status-cancelled">ยกเลิก</span>'; break;
                                    }
                                ?>
                            </td>
                            <td>
                                <div class="small fw-bold text-dark"><?= date('d/m/Y', strtotime($order['created_at'])) ?></div>
                                <div class="text-muted" style="font-size: 0.7rem;"><?= date('H:i', strtotime($order['created_at'])) ?> น.</div>
                            </td>
                            <td class="text-center pe-4">
                                <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-outline-dark btn-sm rounded-pill px-3 shadow-sm">
                                    <i class="bi bi-search me-1"></i> รายละเอียด
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox display-1 text-light d-block mb-3"></i>
                                <h5 class="text-muted">ไม่พบข้อมูลรายการสั่งซื้อในระบบ</h5>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function viewSlip(url) {
    Swal.fire({
        title: 'หลักฐานการโอนเงิน',
        imageUrl: url,
        imageAlt: 'Payment Slip',
        confirmButtonText: 'ปิด',
        confirmButtonColor: '#121212',
        customClass: {
            title: 'fw-bold',
            image: 'rounded shadow-sm'
        }
    });
}
</script>