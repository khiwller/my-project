<?php 
require_once 'layout/header.php'; 
checkAdmin(); 

// --- จัดการคำร้องขอ (Add / Update / Toggle) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $target_name = $_POST['target_name'] ?? '';
    $target_type = $_POST['target_type'] ?? '';

    try {
        if ($action == 'add_sub' && !empty($_POST['new_name'])) {
            $new_name = trim($_POST['new_name']);
            // เช็คก่อนว่ามีหมวดหมู่นี้อยู่แล้วหรือยัง
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sub_category = ? AND type = ?");
            $stmtCheck->execute([$new_name, $target_type]);
            if ($stmtCheck->fetchColumn() == 0) {
                // 🌟 เทคนิคสร้างหมวดหมู่ใหม่: ใส่สินค้าจำลอง (Dummy) ลงไปแล้วปิดการขายไว้
                // (ต้องตั้งค่า sale_type และอื่นๆ ให้ผ่าน constraints ของตาราง products ของคุณด้วย)
                $stmt = $pdo->prepare("INSERT INTO products (name, type, sub_category, price, is_active, sale_type) VALUES ('[หมวดหมู่ว่าง]', ?, ?, 0, 0, 'individual')");
                $stmt->execute([$target_type, $new_name]);
            }
        }
        elseif ($action == 'edit_sub' && !empty($_POST['new_name'])) {
            $new_name = trim($_POST['new_name']);
            $stmt = $pdo->prepare("UPDATE products SET sub_category = ? WHERE sub_category = ? AND type = ?");
            $stmt->execute([$new_name, $target_name, $target_type]);
        } 
        elseif ($action == 'toggle_sub') {
            $set_status = $_POST['set_status']; 
            $stmt = $pdo->prepare("UPDATE products SET is_active = ? WHERE sub_category = ? AND type = ?");
            $stmt->execute([$set_status, $target_name, $target_type]);
        }
        
        echo "<script>window.location.href = 'manage-options.php?msg=success';</script>";
        exit();
        
    } catch (Exception $e) {
        echo "<script>alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// --- ดึงข้อมูลจากตาราง Products แยกตาม Type ---
// ตัดสินค้าที่เป็น Dummy ([หมวดหมู่ว่าง]) ออกจากการนับจำนวนสินค้าจริง
$sqlCoffin = "SELECT sub_category as name, 
              SUM(CASE WHEN name != '[หมวดหมู่ว่าง]' THEN 1 ELSE 0 END) as total_used, 
              SUM(CASE WHEN name != '[หมวดหมู่ว่าง]' THEN is_active ELSE 0 END) as active_count 
              FROM products 
              WHERE type = 'coffin' AND sub_category IS NOT NULL AND sub_category != '' 
              GROUP BY sub_category ORDER BY name ASC";
$coffinSubs = $pdo->query($sqlCoffin)->fetchAll();

$sqlDec = "SELECT sub_category as name, 
           SUM(CASE WHEN name != '[หมวดหมู่ว่าง]' THEN 1 ELSE 0 END) as total_used, 
           SUM(CASE WHEN name != '[หมวดหมู่ว่าง]' THEN is_active ELSE 0 END) as active_count 
           FROM products 
           WHERE type = 'decoration' AND sub_category IS NOT NULL AND sub_category != '' 
           GROUP BY sub_category ORDER BY name ASC";
$decSubs = $pdo->query($sqlDec)->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
    .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .table-custom th { font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; color: #6c757d; }
    .table-custom td { vertical-align: middle; }
    .search-box { border-radius: 20px; background-color: #f1f3f5; border: none; padding-left: 20px; }
    .search-box:focus { background-color: #e9ecef; box-shadow: none; border-color: transparent; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-ui-radios text-primary me-2"></i> จัดการหมวดหมู่ย่อย (Sub Category)</h2>
            <p class="text-muted mb-0 small">เพิ่ม แก้ไขชื่อ หรือเปิด-ปิดการขายสินค้าในหมวดหมู่นั้นๆ</p>
        </div>
        <a href="product-create.php" class="btn btn-light border shadow-sm rounded-pill px-4 fw-bold">
            <i class="bi bi-arrow-left me-2"></i>กลับหน้าเพิ่มสินค้า
        </a>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-6">
            <div class="card card-custom h-100">
                <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-2 text-primary"></i> หมวดหมู่: หีบศพ</h5>
                    <button class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" onclick="addNewOption('coffin')">
                        <i class="bi bi-plus-lg"></i> เพิ่มหมวดหมู่
                    </button>
                </div>
                
                <div class="px-3 pb-3">
                    <input type="text" class="form-control search-box form-control-sm" placeholder="ค้นหาหมวดหมู่หีบศพ..." onkeyup="filterTable('coffinTable', this.value)">
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-custom table-hover mb-0" id="coffinTable">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th class="ps-4">ชื่อหมวดหมู่</th>
                                    <th class="text-center">สถานะการขาย</th>
                                    <th class="text-end pe-4">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($coffinSubs) > 0): ?>
                                    <?php foreach($coffinSubs as $sub): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark cat-name">
                                            <?= htmlspecialchars($sub['name']) ?>
                                            <div class="text-muted small fw-normal">มีสินค้าทั้งหมด <?= $sub['total_used'] ?> ชิ้น</div>
                                        </td>
                                        <td class="text-center">
                                            <?php if($sub['total_used'] == 0): ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">หมวดหมู่ว่าง (ยังไม่มีสินค้า)</span>
                                            <?php elseif($sub['active_count'] > 0): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success">กำลังขาย (<?= $sub['active_count'] ?>)</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">ปิดการขายทั้งหมด</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-outline-primary rounded-circle" onclick="editOption('coffin', '<?= htmlspecialchars($sub['name']) ?>')" title="แก้ไขชื่อ">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm rounded-circle ms-1 <?= $sub['active_count'] > 0 ? 'btn-outline-danger' : 'btn-outline-success' ?>" 
                                                    onclick="toggleOption('coffin', '<?= htmlspecialchars($sub['name']) ?>', <?= $sub['active_count'] ?>)" 
                                                    title="<?= $sub['active_count'] > 0 ? 'ปิดการขายทั้งหมวด' : 'เปิดการขายทั้งหมวด' ?>"
                                                    <?= $sub['total_used'] == 0 ? 'disabled' : '' ?>>
                                                <i class="bi <?= $sub['active_count'] > 0 ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">ไม่มีข้อมูลหมวดหมู่หีบศพ</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-custom h-100">
                <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-flower1 me-2 text-warning"></i> หมวดหมู่: ส่วนเสริม</h5>
                    <button class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" onclick="addNewOption('decoration')">
                        <i class="bi bi-plus-lg"></i> เพิ่มหมวดหมู่
                    </button>
                </div>

                <div class="px-3 pb-3">
                    <input type="text" class="form-control search-box form-control-sm" placeholder="ค้นหาหมวดหมู่ส่วนเสริม..." onkeyup="filterTable('decTable', this.value)">
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-custom table-hover mb-0" id="decTable">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th class="ps-4">ชื่อหมวดหมู่</th>
                                    <th class="text-center">สถานะการขาย</th>
                                    <th class="text-end pe-4">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($decSubs) > 0): ?>
                                    <?php foreach($decSubs as $sub): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark cat-name">
                                            <?= htmlspecialchars($sub['name']) ?>
                                            <div class="text-muted small fw-normal">มีสินค้าทั้งหมด <?= $sub['total_used'] ?> ชิ้น</div>
                                        </td>
                                        <td class="text-center">
                                            <?php if($sub['total_used'] == 0): ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">หมวดหมู่ว่าง (ยังไม่มีสินค้า)</span>
                                            <?php elseif($sub['active_count'] > 0): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success">กำลังขาย (<?= $sub['active_count'] ?>)</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">ปิดการขายทั้งหมด</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-outline-primary rounded-circle" onclick="editOption('decoration', '<?= htmlspecialchars($sub['name']) ?>')" title="แก้ไขชื่อ">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm rounded-circle ms-1 <?= $sub['active_count'] > 0 ? 'btn-outline-danger' : 'btn-outline-success' ?>" 
                                                    onclick="toggleOption('decoration', '<?= htmlspecialchars($sub['name']) ?>', <?= $sub['active_count'] ?>)" 
                                                    title="<?= $sub['active_count'] > 0 ? 'ปิดการขายทั้งหมวด' : 'เปิดการขายทั้งหมวด' ?>"
                                                    <?= $sub['total_used'] == 0 ? 'disabled' : '' ?>>
                                                <i class="bi <?= $sub['active_count'] > 0 ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">ไม่มีข้อมูลหมวดหมู่ส่วนเสริม</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="form_action">
    <input type="hidden" name="target_type" id="form_target_type">
    <input type="hidden" name="target_name" id="form_target_name">
    <input type="hidden" name="new_name" id="form_new_name">
    <input type="hidden" name="set_status" id="form_set_status">
</form>

<script>
function filterTable(tableId, inputVal) {
    let filter = inputVal.toLowerCase();
    let rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    rows.forEach(row => {
        let nameCol = row.querySelector('.cat-name');
        if (nameCol) {
            let text = nameCol.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        }
    });
}

// 🌟 ฟังก์ชันเพิ่มหมวดหมู่ใหม่
function addNewOption(type) {
    let typeLabel = type === 'coffin' ? 'หีบศพ' : 'ส่วนเสริม';

    Swal.fire({
        title: `เพิ่มหมวดหมู่ใหม่ (${typeLabel})`,
        input: 'text',
        inputPlaceholder: 'พิมพ์ชื่อหมวดหมู่ที่นี่...',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (value) => {
            if (!value || value.trim() === '') return 'กรุณาระบุชื่อหมวดหมู่!'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form_action').value = 'add_sub';
            document.getElementById('form_target_type').value = type;
            document.getElementById('form_new_name').value = result.value;
            document.getElementById('actionForm').submit();
        }
    });
}

function editOption(type, oldName) {
    let typeLabel = type === 'coffin' ? 'หีบศพ' : 'ส่วนเสริม';
    Swal.fire({
        title: `แก้ไขหมวดหมู่ (${typeLabel})`,
        text: `ระบุชื่อใหม่แทนที่ "${oldName}"`,
        input: 'text',
        inputValue: oldName,
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        confirmButtonText: 'บันทึกการเปลี่ยนแปลง',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (value) => {
            if (!value || value.trim() === '') return 'กรุณาระบุชื่อที่ต้องการแก้ไข!'
            if (value === oldName) return 'ชื่อยังคงเหมือนเดิม!'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form_action').value = 'edit_sub';
            document.getElementById('form_target_type').value = type;
            document.getElementById('form_target_name').value = oldName;
            document.getElementById('form_new_name').value = result.value;
            document.getElementById('actionForm').submit();
        }
    });
}

function toggleOption(type, targetName, activeCount) {
    let isTurningOff = activeCount > 0; 
    let newStatus = isTurningOff ? 0 : 1; 
    
    let titleText = isTurningOff ? `ปิดการขายหมวดหมู่นี้?` : `เปิดการขายหมวดหมู่นี้?`;
    let htmlText = isTurningOff 
        ? `ต้องการปิดการขายสินค้าในหมวด <b>"${targetName}"</b> ทั้งหมดหรือไม่?<br><small class="text-danger">สินค้าที่เกี่ยวข้องจะไม่แสดงในหน้าเว็บให้ลูกค้าเลือกซื้อ</small>`
        : `ต้องการเปิดการขายสินค้าในหมวด <b>"${targetName}"</b> ทั้งหมดหรือไม่?<br><small class="text-success">สินค้าที่เกี่ยวข้องจะกลับมาแสดงในหน้าเว็บอีกครั้ง</small>`;
    
    let btnColor = isTurningOff ? '#dc3545' : '#198754';
    let btnText = isTurningOff ? 'ใช่, ปิดการขาย' : 'ใช่, เปิดการขาย';

    Swal.fire({
        title: titleText,
        html: htmlText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: btnColor,
        confirmButtonText: btnText,
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form_action').value = 'toggle_sub';
            document.getElementById('form_target_type').value = type;
            document.getElementById('form_target_name').value = targetName;
            document.getElementById('form_set_status').value = newStatus;
            document.getElementById('actionForm').submit();
        }
    });
}

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'อัปเดตข้อมูลสำเร็จ', showConfirmButton: false, timer: 2500, timerProgressBar: true });
        window.history.replaceState(null, null, window.location.pathname);
    });
<?php endif; ?>
</script>