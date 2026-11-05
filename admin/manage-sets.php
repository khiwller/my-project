<?php 
require_once 'layout/header.php'; 
checkAdmin();

// 1. ดึงข้อมูลหีบศพเฉพาะที่เป็น "Individual"
$stmtCoffins = $pdo->query("SELECT * FROM products WHERE type = 'coffin' AND sale_type = 'individual' AND is_active = 1 ORDER BY sub_category ASC, name ASC");
$coffins = $stmtCoffins->fetchAll();

// 2. ดึงข้อมูลของตกแต่งทั้งหมด
$stmtDecs = $pdo->query("SELECT * FROM products WHERE type = 'decoration' AND is_active = 1 ORDER BY sub_category ASC, name ASC");
$decorations = $stmtDecs->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">

<style>
    :root { --gold: #D4AF37; --dark: #121212; --light-gold: #fdfaf0; }
    body { background-color: #f4f6f9; font-family: 'Prompt', sans-serif; }

    .product-card { 
        border: 2px solid transparent; transition: all 0.3s ease; 
        cursor: pointer; border-radius: 12px; background: white; position: relative;
    }
    .product-card.selected { 
        border-color: var(--gold); background-color: var(--light-gold); 
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.2);
    }
    
    .selected-badge {
        position: absolute; top: -10px; right: -10px;
        background: var(--gold); color: white;
        width: 26px; height: 26px; border-radius: 50%;
        display: none; align-items: center; justify-content: center; z-index: 5;
    }
    .product-card.selected .selected-badge { display: flex; }

    .selection-container {
        height: calc(100vh - 280px); min-height: 550px;
        overflow-y: auto; padding-right: 10px;
    }

    .summary-card { border-radius: 20px; border: none; }
    .section-title { 
        font-size: 0.9rem; font-weight: 700; color: #555; 
        text-transform: uppercase; border-left: 4px solid var(--gold); padding-left: 10px;
    }

    .img-wrapper { height: 100px; overflow: hidden; border-radius: 8px; margin-bottom: 8px; background: #eee; }
    .img-wrapper img { width: 100%; height: 100%; object-fit: cover; }

    /* 🎨 ปรับแต่ง CKEditor ให้เข้ากับดีไซน์ */
    .ck-editor__editable {
        min-height: 200px;
        border-bottom-left-radius: 12px !important;
        border-bottom-right-radius: 12px !important;
    }
    .ck-toolbar {
        border-top-left-radius: 12px !important;
        border-top-right-radius: 12px !important;
        border-bottom: none !important;
    }
</style>

<div class="container-fluid py-4 px-xl-5">
    <div class="row align-items-center mb-4">
        <div class="col">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-plus-square-fill text-gold me-2"></i>ออกแบบชุดเซ็ตใหม่
            </h2>
            <p class="text-muted small">เลือกหีบศพรายชิ้นและของตกแต่งเพื่อสร้างเป็น "ชุดเซ็ตพรีเมียม" ใหม่</p>
        </div>
    </div>

    <form action="set-save-process.php" method="POST" id="setForm" enctype="multipart/form-data">
        <div class="row g-4">
            
            <div class="col-lg-3">
                <div class="section-title mb-3">1. เลือกหีบศพหลัก (รายชิ้น)</div>
                <div class="card border-0 shadow-sm p-2 mb-3">
                    <input type="text" class="form-control form-control-sm border-0" placeholder="ค้นหาหีบศพ..." onkeyup="searchItem(this, 'coffin-item')">
                </div>
                <div class="selection-container">
                    <div class="row g-2">
                        <?php foreach($coffins as $c): ?>
                        <div class="col-12 coffin-item" data-name="<?= strtolower($c['name']) ?>">
                            <div class="card product-card p-2 shadow-sm border-0" 
                                 onclick="selectCoffin(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name']) ?>', <?= $c['price'] ?>, '<?= htmlspecialchars($c['sub_category']) ?>')">
                                <div class="selected-badge"><i class="bi bi-check"></i></div>
                                <div class="d-flex align-items-center">
                                    <div class="img-wrapper mb-0 me-3" style="width: 60px; height: 60px;">
                                        <img src="../uploads/<?= $c['image_url'] ?: 'default.png' ?>">
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold small text-truncate"><?= $c['name'] ?></div>
                                        <div class="text-gold small">฿<?= number_format($c['price']) ?></div>
                                        <div class="text-muted" style="font-size: 0.6rem;"><?= $c['sub_category'] ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="section-title mb-3">2. เลือกของตกแต่งเครื่องตั้ง</div>
                <div class="card border-0 shadow-sm p-2 mb-3">
                    <input type="text" class="form-control form-control-sm border-0" placeholder="ค้นหาของตกแต่ง..." onkeyup="searchItem(this, 'dec-item')">
                </div>
                <div class="selection-container">
                    <div class="row g-2">
                        <?php foreach($decorations as $d): ?>
                        <div class="col-6 dec-item" data-name="<?= strtolower($d['name']) ?>">
                            <div class="card product-card p-2 shadow-sm border-0" onclick="toggleDecoration(<?= $d['id'] ?>, '<?= htmlspecialchars($d['name']) ?>', <?= $d['price'] ?>)">
                                <div class="selected-badge"><i class="bi bi-plus"></i></div>
                                <div class="img-wrapper">
                                    <img src="../uploads/<?= $d['image_url'] ?: 'default.png' ?>">
                                </div>
                                <div class="text-center">
                                    <div class="fw-bold text-truncate" style="font-size: 0.7rem;"><?= $d['name'] ?></div>
                                    <div class="text-success small" style="font-size: 0.7rem;">+฿<?= number_format($d['price']) ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="section-title mb-3">3. ข้อมูลชุดเซ็ตที่ต้องการสร้าง</div>
                <div class="card summary-card border-0 shadow-lg">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ชื่อชุดเซ็ตสินค้า <span class="text-danger">*</span></label>
                            <input type="text" name="set_name" id="set_name" class="form-control form-control-lg border-2" placeholder="เช่น ชุดพรีเมียม..." required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">หมวดหมู่ชุดเซ็ต</label>
                                <input type="text" name="sub_category" id="sub_category_input" class="form-control" placeholder="จะอิงตามหีบศพที่เลือก">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">รูปภาพประจำชุด</label>
                                <input type="file" name="set_image" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">รายละเอียดสินค้า (ข้อมูลในหน้าเว็บ)</label>
                            <textarea name="set_description" id="editor" class="form-control"></textarea>
                        </div>

                        <div class="p-3 rounded-4 bg-light mb-4 border">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">มูลค่ารวมสินค้าแยกชิ้น:</span>
                                <span class="fw-bold" id="costTotal">฿0</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">ราคาขายชุดเซ็ต:</span>
                                <div class="input-group mb-0" style="width: 180px;">
                                    <span class="input-group-text bg-white">฿</span>
                                    <input type="number" name="set_price" id="set_price" class="form-control fw-bold text-primary" placeholder="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="small fw-bold text-muted text-uppercase mb-2 d-block">รายการในชุด:</label>
                            <div id="componentsList" class="bg-white rounded-3 border p-2" style="max-height: 120px; overflow-y: auto;">
                                <p class="text-muted small fst-italic mb-0 p-2">ยังไม่ได้เลือกสินค้า...</p>
                            </div>
                        </div>

                        <input type="hidden" name="coffin_id" id="coffin_id_input">
                        <div id="hiddenDecs"></div>

                        <button type="submit" class="btn btn-dark w-100 rounded-pill py-3 fw-bold shadow-lg" style="background: #121212; border: 1px solid var(--gold);">
                            <i class="bi bi-check-circle-fill me-2 text-gold"></i> บันทึกข้อมูลชุดเซ็ตใหม่
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

<script>
let editorInstance; // เก็บตัวแปร editor

// 🌟 Initialize CKEditor 5
ClassicEditor
    .create(document.querySelector('#editor'), {
        toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo' ],
        placeholder: 'ระบุรายละเอียดสินค้า เช่น ขนาด วัสดุ และสิ่งที่จะได้รับ...'
    })
    .then(editor => {
        editorInstance = editor;
    })
    .catch(error => {
        console.error(error);
    });

let selectedCoffin = null;
let selectedDecs = new Map();

function searchItem(input, className) {
    const val = input.value.toLowerCase();
    document.querySelectorAll('.' + className).forEach(item => {
        const name = item.dataset.name;
        item.style.display = name.includes(val) ? "block" : "none";
    });
}

function selectCoffin(id, name, price, subCat) {
    document.querySelectorAll('.coffin-item .product-card').forEach(c => c.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    
    selectedCoffin = { id, name, price };
    document.getElementById('coffin_id_input').value = id;
    document.getElementById('sub_category_input').value = subCat;
    
    if(!document.getElementById('set_name').value) {
        document.getElementById('set_name').value = "ชุด " + name;
    }
    updateSummary();
}

function toggleDecoration(id, name, price) {
    const card = event.currentTarget;
    if (selectedDecs.has(id)) {
        selectedDecs.delete(id);
        card.classList.remove('selected');
    } else {
        selectedDecs.set(id, { name, price });
        card.classList.add('selected');
    }
    updateSummary();
}

function updateSummary() {
    const list = document.getElementById('componentsList');
    const hidden = document.getElementById('hiddenDecs');
    list.innerHTML = '';
    hidden.innerHTML = '';
    
    let totalCost = 0;

    if (selectedCoffin) {
        totalCost += selectedCoffin.price;
        list.innerHTML += `<div class="small p-1 border-bottom fw-bold text-dark"><i class="bi bi-archive-fill me-1 text-gold"></i> ${selectedCoffin.name}</div>`;
    }

    selectedDecs.forEach((val, id) => {
        totalCost += val.price;
        list.innerHTML += `<div class="small p-1 border-bottom text-muted"><i class="bi bi-flower1 me-1"></i> ${val.name}</div>`;
        hidden.innerHTML += `<input type="hidden" name="decoration_ids[]" value="${id}">`;
    });

    if (list.innerHTML === '') list.innerHTML = '<p class="text-muted small fst-italic mb-0 p-2">ยังไม่ได้เลือกสินค้า...</p>';
    document.getElementById('costTotal').innerText = '฿' + totalCost.toLocaleString();
    
    const priceInput = document.getElementById('set_price');
    if(!priceInput.value || priceInput.value == 0) {
        priceInput.value = totalCost;
    }
}

document.getElementById('setForm').addEventListener('submit', function(e) {
    if (!selectedCoffin) {
        e.preventDefault();
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณาเลือกหีบศพหลักอย่างน้อย 1 ใบ', 'warning');
    }
});
</script>