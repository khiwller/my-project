<?php 
require_once 'layout/header.php'; 
checkAdmin();

// 1. ดึง Sub Category ที่มีอยู่แล้วในระบบมาแยกตาม Type (เพื่อนำไปใช้ใน JavaScript)
$stmtSub = $pdo->query("SELECT type, sub_category FROM products WHERE sub_category IS NOT NULL AND sub_category != '' GROUP BY type, sub_category");
$subCats = ['coffin' => [], 'decoration' => []];
foreach ($stmtSub->fetchAll() as $row) {
    $subCats[$row['type']][] = $row['sub_category'];
}

// กำหนดค่าเริ่มต้นกรณีในฐานข้อมูลยังไม่มีข้อมูลเลย
if (empty($subCats['decoration'])) $subCats['decoration'] = ['ดอกไม้หน้าหีบ', 'พวงหรีด'];
if (empty($subCats['coffin'])) $subCats['coffin'] = ['ไม้สัก', 'ไม้อัด', 'โลงจำปา'];
?>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="dashboard-title mb-1"><i class="bi bi-plus-circle-fill text-primary me-2 fs-3"></i> เพิ่มสินค้าใหม่</h2>
            <p class="text-muted mb-0 small">เพิ่มข้อมูลหีบศพ หรือ ของตกแต่ง (รายชิ้น) เข้าสู่ระบบ</p>
        </div>
        <div class="d-flex gap-2">
            <a href="manage-sets.php" class="btn btn-purple text-white rounded-pill px-4 shadow-sm fw-bold" style="background-color: #6f42c1;">
                <i class="bi bi-gift-fill me-2"></i> ไปหน้าจัดการเซ็ต
            </a>
            <a href="products.php" class="btn btn-outline-dark rounded-pill px-4 shadow-sm fw-bold">
                <i class="bi bi-arrow-left me-2"></i> ย้อนกลับ
            </a>
        </div>
    </div>

    <div class="card card-custom shadow-sm border-0">
        <div class="card-body p-4 p-xl-5">
            <form action="product-save.php" method="POST" enctype="multipart/form-data" id="productForm">
                
                <div class="row g-5">
                    <div class="col-xl-7 border-end-xl">
                        <div class="form-section-title"><i class="bi bi-info-circle-fill text-info me-2"></i> ข้อมูลพื้นฐานสินค้า</div>
                        
                        <div class="mb-4">
                            <label class="form-label-custom">ชื่อสินค้า <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-custom form-control-lg fs-6" required placeholder="เช่น หีบศพเทพพนม, ดอกไม้หน้าหีบแบบที่ 1">
                        </div>

                        <div class="mb-4">
                            <label class="form-label-custom">รายละเอียดสินค้า</label>
                            <textarea name="description" id="description" class="form-control"></textarea>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">ราคาขาย (บาท) <span class="text-danger">*</span></label>
                                <div class="input-group shadow-sm" style="border-radius: 10px; overflow: hidden;">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-currency-bitcoin text-gold"></i></span>
                                    <input type="number" name="price" class="form-control border-0" required min="0" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">รูปภาพสินค้า <span class="text-danger">*</span></label>
                                <input type="file" name="image" class="form-control form-control-custom" accept="image/*" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-5">
                        <div class="form-section-title"><i class="bi bi-gear-fill text-warning me-2"></i> การตั้งค่าหมวดหมู่</div>
                        
                        <div class="mb-4">
                            <label class="form-label-custom">หมวดหมู่หลัก (Type)</label>
                            <select name="type" id="typeSelect" class="form-select form-select-custom bg-light fw-bold" onchange="toggleOptions()">
                                <option value="coffin" class="text-dark">หีบศพ (สินค้าหลัก)</option>
                                <option value="decoration" class="text-primary">ของตกแต่ง (Add-on)</option>
                            </select>
                        </div>

                        <div class="mb-4 p-4 border rounded-4 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label-custom text-dark mb-0"><i class="bi bi-tags-fill me-1"></i> หมวดหมู่ย่อย (Sub Category)</label>
                                <a href="manage-options.php" class="btn btn-sm btn-outline-primary rounded-pill px-3" style="font-size: 0.75rem;">
                                    <i class="bi bi-gear-fill me-1"></i> จัดการหมวดหมู่
                                </a>
                            </div>
                            <select name="sub_category_select" id="subCategorySelect" class="form-select form-select-custom fw-bold" required>
                                </select>
                            <div class="mt-2 small text-muted">
                                <i class="bi bi-info-circle me-1"></i> หากไม่พบหมวดหมู่ที่ต้องการ กรุณากดปุ่ม <b>"จัดการหมวดหมู่"</b> เพื่อเพิ่มใหม่
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-custom">สถานะการแสดงผล</label>
                            <select name="is_active" class="form-select form-select-custom">
                                <option value="1">✅ เปิดการขาย (แสดงบนหน้าเว็บ)</option>
                                <option value="0">❌ ปิดการขาย (ซ่อนจากหน้าเว็บ)</option>
                            </select>
                        </div>

                       
                    </div>
                </div>

                <hr class="border-light my-5">
                
                <div class="d-flex justify-content-end gap-3">
                    <a href="products.php" class="btn btn-light rounded-pill px-5 fw-bold shadow-sm border">ยกเลิก</a>
                    <button type="submit" name="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                        <i class="bi bi-save-fill me-2"></i> บันทึกข้อมูลสินค้า
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
<script>
// ตั้งค่า Text Editor
ClassicEditor.create(document.querySelector('#description'), {
    toolbar: ['heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo']
}).catch(error => { console.error(error); });

// ข้อมูลหมวดหมู่จาก PHP
const existingSubCats = <?= json_encode($subCats) ?>;

// ฟังก์ชันสลับรายการหมวดหมู่ย่อยตามประเภทสินค้าหลัก
function toggleOptions() {
    const type = document.getElementById('typeSelect').value;
    const subCatSelect = document.getElementById('subCategorySelect');

    // ล้างค่าเดิมใน Dropdown
    subCatSelect.innerHTML = '';
    
    // ดึงหมวดหมู่ที่ตรงกับประเภทมาใส่
    const options = existingSubCats[type] || [];
    
    if (options.length === 0) {
        let opt = document.createElement('option');
        opt.value = "";
        opt.textContent = "-- ยังไม่มีหมวดหมู่ กรุณาเพิ่มที่หน้าจัดการ --";
        subCatSelect.appendChild(opt);
    } else {
        options.forEach(cat => {
            let opt = document.createElement('option');
            opt.value = cat;
            opt.textContent = cat;
            subCatSelect.appendChild(opt);
        });
    }
}

// โหลดข้อมูลครั้งแรกเมื่อเปิดหน้าเว็บ
document.addEventListener("DOMContentLoaded", () => {
    toggleOptions();
});
</script>