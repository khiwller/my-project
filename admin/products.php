<?php 
session_start();
require_once '../config/db.php'; 

// 🌟 ย้ายระบบสลับสถานะมาไว้บนสุด (ก่อน require header)
if (isset($_GET['toggle_status'])) {
    $targetId = $_GET['toggle_status'];
    $stmt = $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$targetId]);
    
    // เก็บค่า Query String เดิมไว้
    $params = $_GET;
    unset($params['toggle_status']);
    // เพิ่ม msg เพื่อแจ้งเตือนว่าอัปเดตแล้ว
    $params['msg'] = 'status_updated'; 
    $queryString = http_build_query($params);
    $redirectUrl = "products.php" . (!empty($queryString) ? "?$queryString" : "");
    
    header("Location: $redirectUrl");
    exit();
}

// 🌟 จากนั้นค่อยเรียก Header และตรวจสอบสิทธิ์
require_once 'layout/header.php'; 
checkAdmin(); 

// --- ระบบค้นหาและตัวกรอง (Search & Filter) ---
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($search != '') {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
}

if ($type_filter != '') {
    $sql .= " AND type = ?";
    $params[] = $type_filter;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="dashboard-title mb-1"><i class="bi bi-box-seam text-primary me-2 fs-3"></i> จัดการสินค้า</h2>
            <p class="text-muted mb-0 small">เพิ่ม แก้ไข และจัดการสถานะการขายของสินค้าทั้งหมด</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="manage-options.php" class="btn btn-outline-dark rounded-pill px-4 py-2 shadow-sm fw-bold">
                <i class="bi bi-tags me-2"></i>จัดการหมวดหมู่ย่อย
            </a>
            <a href="product-create.php" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-bold">
                <i class="bi bi-plus-lg me-2"></i>เพิ่มสินค้าใหม่
            </a>
        </div>
    </div>

    <div class="card card-custom mb-4">
        <div class="card-body p-4">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted">ค้นหาชื่อสินค้า</label>
                    <div class="input-group shadow-sm rounded-pill overflow-hidden border">
                        <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 shadow-none" placeholder="พิมพ์ชื่อสินค้าที่ต้องการ..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">ประเภทสินค้าหลัก</label>
                    <select name="type" class="form-select rounded-pill shadow-sm">
                        <option value="">ทุกประเภท</option>
                        <option value="coffin" <?= $type_filter == 'coffin' ? 'selected' : '' ?>>หีบศพ</option>
                        <option value="decoration" <?= $type_filter == 'decoration' ? 'selected' : '' ?>>ส่วนเสริม</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill shadow-sm fw-bold">กรองข้อมูล</button>
                    <?php if($search || $type_filter): ?>
                        <a href="products.php" class="btn btn-outline-danger rounded-pill shadow-sm px-3" title="ล้างตัวกรอง"><i class="bi bi-x-lg"></i></a>
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
                        <th width="80" class="text-center ps-4">รูปภาพ</th>
                        <th>ชื่อสินค้า</th>
                        <th>ประเภท & หมวดหมู่</th>
                        <th>รูปแบบการขาย</th>
                        <th>ราคา</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center pe-4">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($products) > 0): ?>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="text-center ps-4 py-3">
                                <?php $img = $p['image_url'] ? "../uploads/" . $p['image_url'] : "https://via.placeholder.com/60"; ?>
                                <img src="<?= $img ?>" alt="img" class="product-thumbnail">
                            </td>
                            
                            <td>
                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($p['name']) ?></div>
                            </td>
                            
                            <td>
                                <?php if($p['type'] == 'coffin'): ?>
                                    <span class="badge-soft badge-type-coffin mb-1 d-inline-block"><i class="bi bi-box me-1"></i>หีบศพ</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-type-decoration mb-1 d-inline-block"><i class="bi bi-flower1 me-1"></i>ส่วนเสริม</span>
                                <?php endif; ?>
                                <br>
                                <small class="text-muted"><i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($p['sub_category'] ?? 'ไม่มีหมวดหมู่ย่อย') ?></small>
                            </td>

                            <td>
                                <?php if($p['type'] == 'coffin'): ?>
                                    <?php if($p['sale_type'] == 'set'): ?>
                                        <span class="badge-soft badge-sale-set"><i class="bi bi-box-seam-fill me-1"></i>แบบจัดเซ็ต</span>
                                    <?php else: ?>
                                        <span class="badge-soft badge-sale-ind"><i class="bi bi-tools me-1"></i>แบบแยกชิ้น</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="fw-bold fs-6" style="color: #d4af37;">฿ <?= number_format($p['price'], 2) ?></span>
                            </td>
                            
                            <td class="text-center">
                                <?php if($p['is_active']): ?>
                                    <span class="badge-soft badge-status-active"><i class="bi bi-check-circle-fill me-1"></i>กำลังขาย</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-status-inactive"><i class="bi bi-pause-circle-fill me-1"></i>ปิดการขาย</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="product-edit.php?id=<?= $p['id'] ?>" class="btn btn-light action-btn border shadow-sm text-primary" title="แก้ไขข้อมูล">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <?php 
                                        // สร้าง URL โดยคงพารามิเตอร์ Search ไว้
                                        $toggleParams = $_GET;
                                        $toggleParams['toggle_status'] = $p['id'];
                                        $toggleUrl = "products.php?" . http_build_query($toggleParams);
                                    ?>
                                    <?php if($p['is_active']): ?>
                                        <a href="<?= $toggleUrl ?>" class="btn btn-light action-btn border shadow-sm text-danger" 
                                           onclick="confirmToggle(event, this.href, 'ปิดการขาย', 'สินค้านี้จะไม่แสดงให้ลูกค้าเห็นในหน้าเว็บ')" title="ปิดการขาย">
                                            <i class="bi bi-eye-slash-fill"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= $toggleUrl ?>" class="btn btn-light action-btn border shadow-sm text-success" 
                                           onclick="confirmToggle(event, this.href, 'เปิดการขาย', 'สินค้านี้จะกลับมาแสดงให้ลูกค้าสั่งซื้อได้อีกครั้ง')" title="เปิดการขาย">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-box-seam display-4 text-muted opacity-25 d-block mb-3"></i>
                                    <h5 class="text-muted fw-bold">ไม่พบข้อมูลสินค้า</h5>
                                    <p class="text-muted small">ลองเปลี่ยนคำค้นหา หรือล้างตัวกรองเพื่อดูข้อมูลทั้งหมด</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmToggle(event, url, actionText, descText) {
    event.preventDefault(); 
    Swal.fire({
        title: `ยืนยันการ${actionText} ?`,
        text: descText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: (actionText === 'ปิดการขาย' ? '#dc3545' : '#198754'),
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ดำเนินการ',
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

// แจ้งเตือนเมื่ออัปเดตสถานะสำเร็จ (รับค่าผ่าน GET msg)
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'status_updated'): ?>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'อัปเดตสถานะสำเร็จ',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
        });
        
        // ลบพารามิเตอร์ msg ออกจาก URL เพื่อไม่ให้เด้งซ้ำเวลา Refresh
        window.history.replaceState(null, null, window.location.pathname + window.location.search.replace(/&?msg=[^&]*/, '').replace(/^\?$/, ''));
    });
<?php endif; ?>
</script>
