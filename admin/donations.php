<?php 
require_once 'layout/header.php'; 
checkAdmin();

// --- รับค่าการค้นหาและฟิลเตอร์จาก GET ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// 1. ดึงข้อมูลรายการบริจาค (ระบุ orders. เพื่อป้องกัน ambiguous column)
$sql = "SELECT orders.*, users.username 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        WHERE orders.order_type = 'donation'";

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
$donations = $stmt->fetchAll();

// 2. คำนวณยอดรวมที่อนุมัติแล้ว (สถิติหัวเว็บ)
$totalDonationAmount = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE order_type = 'donation' AND status IN ('paid', 'completed')")->fetchColumn() ?: 0;
?>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="dashboard-title mb-1"><i class="bi bi-heart-fill text-danger me-2 fs-3"></i> รายการบริจาคโลงศพ</h2>
            <p class="text-muted mb-0 small">ตรวจสอบความถูกต้องและอนุมัติยอดเงินบริจาคเพื่อการกุศล</p>
        </div>
        <div class="card card-custom border-start border-5 border-danger shadow-sm">
            <div class="card-body py-2 px-4">
                <div class="text-muted small fw-bold text-uppercase">ยอดบริจาคสำเร็จทั้งหมด</div>
                <div class="h3 fw-bold text-danger mb-0"><?= number_format($totalDonationAmount, 2) ?></div>
            </div>
        </div>
    </div>

    <div class="card card-custom mb-4">
        <div class="card-body p-4">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">ค้นหาผู้บริจาค หรือ เลขที่บิล</label>
                    <div class="input-group shadow-sm rounded-pill overflow-hidden border">
                        <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 shadow-none" placeholder="พิมพ์ชื่อผู้ใช้งาน หรือ เลขที่บิล..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">สถานะการตรวจสอบ</label>
                    <select name="status" class="form-select rounded-pill shadow-sm">
                        <option value="">ทั้งหมด</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>⏳ รอตรวจสอบ</option>
                        <option value="paid" <?= $status_filter == 'paid' ? 'selected' : '' ?>>✅ สำเร็จ (อนุมัติแล้ว)</option>
                        <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>❌ ยกเลิก</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill shadow-sm fw-bold">กรองข้อมูล</button>
                    <?php if($search || $status_filter): ?>
                        <a href="donations.php" class="btn btn-outline-danger rounded-pill shadow-sm px-3" title="ล้างค่า"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-custom overflow-hidden">
        <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4" width="100">เลขบิล</th>
                        <th>ผู้บริจาค</th>
                        <th>ยอดเงินบริจาค</th>
                        <th class="text-center">สลิป</th>
                        <th class="text-center">สถานะ</th>
                        <th>แต้มที่ได้รับ</th>
                        <th>วันที่แจ้ง</th>
                        <th class="text-center pe-4">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($donations) > 0): ?>
                        <?php foreach ($donations as $d): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-muted">#<?= str_pad($d['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="donation-icon me-3 shadow-sm">
                                        <?= strtoupper(substr($d['username'], 0, 1)) ?>
                                    </div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($d['username']) ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold fs-6 text-danger"><?= number_format($d['total_amount'], 2) ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($d['slip_image']): ?>
                                    <img src="../uploads/slips/<?= $d['slip_image'] ?>" 
                                         class="slip-thumbnail shadow-sm" 
                                         onclick="viewSlip('../uploads/slips/<?= $d['slip_image'] ?>')">
                                <?php else: ?>
                                    <span class="text-muted small italic">ไม่มีหลักฐาน</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                    if ($d['status'] == 'pending') echo '<span class="badge-soft badge-don-pending"><i class="bi bi-hourglass-split me-1"></i>รอตรวจสอบ</span>';
                                    elseif ($d['status'] == 'paid' || $d['status'] == 'completed') echo '<span class="badge-soft badge-don-success"><i class="bi bi-check-circle-fill me-1"></i>สำเร็จ</span>';
                                    else echo '<span class="badge-soft badge-don-cancel">ยกเลิก</span>';
                                ?>
                            </td>
                            <td>
                                <span class="text-warning fw-bold"><i class="bi bi-star-fill me-1"></i>+<?= number_format(floor($d['total_amount'] / 10)) ?></span>
                            </td>
                            <td>
                                <div class="small text-dark fw-bold"><?= date('d/m/Y', strtotime($d['created_at'])) ?></div>
                                <div class="small text-muted"><?= date('H:i', strtotime($d['created_at'])) ?> น.</div>
                            </td>
                            <td class="text-center pe-4">
                                <?php if ($d['status'] == 'pending'): ?>
                                    <div class="d-flex justify-content-center gap-2">
                                        <button onclick="confirmAction('order_approve.php?id=<?= $d['id'] ?>&action=approve', 'อนุมัติ', 'ยอดเงินบริจาคจะถูกบันทึก และผู้บริจาคจะได้รับแต้มสะสม')" 
                                                class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                                            <i class="bi bi-check-lg"></i> อนุมัติ
                                        </button>
                                        <button onclick="confirmAction('order_approve.php?id=<?= $d['id'] ?>&action=reject', 'ปฏิเสธ', 'รายการบริจาคนี้จะถูกยกเลิก')" 
                                                class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm">
                                            <i class="bi bi-x"></i> ปฏิเสธ
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-success small fw-bold"><i class="bi bi-check-all"></i> ตรวจสอบแล้ว</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-heartbreak display-4 text-muted opacity-25 d-block mb-3"></i>
                                <h5 class="text-muted fw-bold">ไม่พบรายการบริจาค</h5>
                                <p class="text-muted small">ลองเปลี่ยนคำค้นหา หรือสถานะ</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// ฟังก์ชันดูสลิป
function viewSlip(url) {
    Swal.fire({
        title: 'หลักฐานการบริจาค',
        imageUrl: url,
        imageAlt: 'Slip Image',
        confirmButtonText: 'ปิด',
        confirmButtonColor: '#d4af37',
        customClass: { confirmButton: 'rounded-pill px-4' }
    });
}

// ฟังก์ชันยืนยันการอนุมัติ/ปฏิเสธ
function confirmAction(url, actionTitle, actionText) {
    Swal.fire({
        title: actionTitle + 'รายการนี้?',
        text: actionText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: (actionTitle === 'อนุมัติ' ? '#198754' : '#dc3545'),
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ' + actionTitle,
        cancelButtonText: 'ยกเลิก',
        customClass: {
            confirmButton: 'rounded-pill px-4 shadow-sm',
            cancelButton: 'rounded-pill px-4 shadow-sm'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}

// แจ้งเตือนเมื่อดำเนินการสำเร็จ (รับจาก GET msg/status ถ้ามี)
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'ดำเนินการเรียบร้อยแล้ว',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
<?php endif; ?>
</script>