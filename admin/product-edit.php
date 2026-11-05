<?php 
require_once 'layout/header.php'; 
checkAdmin();

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<script>alert('ไม่พบสินค้านี้'); window.location='products.php';</script>";
    exit();
}

$is_set = ($product['sale_type'] === 'set');
$success = false; 

if ($is_set) {
    $coffins = $pdo->query("SELECT * FROM products WHERE type = 'coffin' AND sale_type = 'individual' AND is_active = 1 ORDER BY sub_category ASC")->fetchAll();
    $decorations = $pdo->query("SELECT * FROM products WHERE type = 'decoration' AND is_active = 1 ORDER BY sub_category ASC")->fetchAll();
    
    $stmtItems = $pdo->prepare("SELECT child_product_id FROM product_set_items WHERE parent_product_id = ?");
    $stmtItems->execute([$id]);
    $current_items = $stmtItems->fetchAll(PDO::FETCH_COLUMN);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $sub_category = trim($_POST['sub_category']);
    $is_active = $_POST['is_active'];
    $image_url = $product['image_url'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFilename = time() . "_" . rand(1000,9999) . "." . $ext;
        if(move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $newFilename)) {
            $image_url = $newFilename;
        }
    }

    try {
        $pdo->beginTransaction();
        $sql = "UPDATE products SET name=?, description=?, price=?, sub_category=?, image_url=?, is_active=? WHERE id=?";
        $pdo->prepare($sql)->execute([$name, $description, $price, $sub_category, $image_url, $is_active, $id]);

        if ($is_set) {
            $pdo->prepare("DELETE FROM product_set_items WHERE parent_product_id = ?")->execute([$id]);
            $coffin_id = $_POST['coffin_id'] ?? null;
            $dec_ids = $_POST['decoration_ids'] ?? [];
            $stmtIns = $pdo->prepare("INSERT INTO product_set_items (parent_product_id, child_product_id) VALUES (?, ?)");
            if ($coffin_id) $stmtIns->execute([$id, $coffin_id]);
            foreach ($dec_ids as $d_id) { $stmtIns->execute([$id, $d_id]); }
        }
        $pdo->commit();
        $success = true;
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if ($is_set) {
            $stmtItems->execute([$id]);
            $current_items = $stmtItems->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (Exception $e) { $pdo->rollBack(); echo "Error: " . $e->getMessage(); }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">
<style>
    :root { --gold: #D4AF37; --dark: #121212; --light-gold: #fdfaf0; }
    body { background-color: #f4f6f9; font-family: 'Prompt', sans-serif; }

    /* 🎨 Card & Selection Styling */
    .product-card-select { cursor: pointer; border: 2px solid transparent; transition: 0.3s; border-radius: 12px; background: white; position: relative; overflow: hidden; }
    .product-card-select.selected { border-color: var(--gold); background-color: var(--light-gold); box-shadow: 0 4px 15px rgba(212, 175, 55, 0.2); }
    .selected-badge { position: absolute; top: -5px; right: -5px; background: var(--gold); color: white; width: 22px; height: 22px; border-radius: 50%; display: none; align-items: center; justify-content: center; z-index: 5; font-size: 12px; }
    .product-card-select.selected .selected-badge { display: flex; }

    /* 🖼️ แก้ไขรูปภาพไม่ให้ยืด */
    .img-fix { width: 100%; height: 100%; object-fit: cover; display: block; }
    
    .preview-container { width: 200px; height: 200px; overflow: hidden; border-radius: 15px; margin: 0 auto; border: 1px solid #ddd; }
    
    .coffin-thumb-wrapper { width: 60px; height: 60px; overflow: hidden; border-radius: 8px; flex-shrink: 0; background: #eee; }
    
    .dec-img-wrapper { height: 100px; width: 100%; overflow: hidden; border-radius: 8px; background: #eee; margin-bottom: 8px; }

    .selection-scroll { height: calc(100vh - 400px); min-height: 400px; overflow-y: auto; padding: 10px; border-radius: 10px; }
    .search-pill { border-radius: 50px; padding-left: 15px; border: 1px solid #ddd; font-size: 0.85rem; }
</style>

<div class="container-fluid py-4 px-xl-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="bi bi-pencil-square text-gold me-2"></i>แก้ไขสินค้า</h2>
            <p class="text-muted small"><?= $is_set ? 'ปรับปรุงองค์ประกอบและราคาของสินค้าชุดเซ็ต' : 'แก้ไขข้อมูลพื้นฐานของสินค้าทั่วไป' ?></p>
        </div>
        <a href="products.php" class="btn btn-outline-dark rounded-pill px-4 shadow-sm bg-white">
            <i class="bi bi-arrow-left me-1"></i> กลับหน้าสินค้า
        </a>
    </div>

    <?php if (!$is_set): ?>
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row g-4">
                                <div class="col-md-4 text-center border-end">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-muted text-uppercase">รูปภาพสินค้า</label>
                                        <div class="preview-container mb-3 shadow-sm">
                                            <img src="../uploads/<?= $product['image_url'] ?: 'default.png' ?>" class="img-fix">
                                        </div>
                                        <input type="file" name="image" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">ชื่อสินค้า</label>
                                        <input type="text" name="name" class="form-control form-control-lg" value="<?= htmlspecialchars($product['name']) ?>" required>
                                    </div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">ราคาขายสุทธิ (บาท)</label>
                                            <input type="number" name="price" class="form-control form-control-lg fw-bold text-danger" value="<?= $product['price'] ?>" required step="0.01">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">หมวดหมู่ย่อย</label>
                                            <input type="text" name="sub_category" class="form-control form-control-lg" value="<?= htmlspecialchars($product['sub_category']) ?>">
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">สถานะการขาย</label>
                                        <select name="is_active" class="form-select form-select-lg">
                                            <option value="1" <?= $product['is_active'] == 1 ? 'selected' : '' ?>>✅ เปิดการขาย</option>
                                            <option value="0" <?= $product['is_active'] == 0 ? 'selected' : '' ?>>❌ ปิดการขาย</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">รายละเอียดสินค้า</label>
                                    <textarea name="description" id="description" class="form-control"><?= htmlspecialchars($product['description']) ?></textarea>
                                </div>
                                <div class="col-12 text-end mt-4">
                                    <button type="submit" class="btn btn-dark rounded-pill px-5 py-3 fw-bold shadow">บันทึกข้อมูลสินค้า</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <form action="" method="POST" enctype="multipart/form-data" id="setEditForm">
            <div class="row g-4">
                <div class="col-lg-3">
                    <h6 class="fw-bold mb-2 border-start border-gold border-4 ps-2">หีบศพหลัก</h6>
                    <input type="text" class="form-control form-control-sm mb-2 search-pill" placeholder="🔍 ค้นหาหีบศพ..." onkeyup="filterList(this, 'coffin-item-wrapper')">
                    <div class="selection-scroll bg-white shadow-sm">
                        <div class="row g-2" id="coffinList">
                            <?php foreach($coffins as $c): ?>
                                <div class="col-12 coffin-item-wrapper" data-name="<?= strtolower(htmlspecialchars($c['name'])) ?>">
                                    <div class="product-card-select p-2 shadow-sm <?= in_array($c['id'], $current_items) ? 'selected' : '' ?>" 
                                         onclick="selectCoffin(<?= $c['id'] ?>, '<?= addslashes($c['name']) ?>', <?= $c['price'] ?>, '<?= addslashes($c['sub_category']) ?>')" 
                                         id="coffin_<?= $c['id'] ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="coffin-thumb-wrapper me-2 border">
                                                <img src="../uploads/<?= $c['image_url'] ?: 'default.png' ?>" class="img-fix">
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="small fw-bold text-truncate"><?= $c['name'] ?></div>
                                                <div class="text-gold extra-small font-bold">฿<?= number_format($c['price']) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <h6 class="fw-bold mb-2 border-start border-gold border-4 ps-2">ของตกแต่ง</h6>
                    <input type="text" class="form-control form-control-sm mb-2 search-pill" placeholder="🔍 ค้นหาของตกแต่ง..." onkeyup="filterList(this, 'decoration-item-wrapper')">
                    <div class="selection-scroll bg-white shadow-sm">
                        <div class="row g-2" id="decorationList">
                            <?php foreach($decorations as $d): ?>
                                <div class="col-6 decoration-item-wrapper" data-name="<?= strtolower(htmlspecialchars($d['name'])) ?>">
                                    <div class="product-card-select p-2 shadow-sm text-center <?= in_array($d['id'], $current_items) ? 'selected' : '' ?>" 
                                         onclick="toggleDecoration(<?= $d['id'] ?>, '<?= addslashes($d['name']) ?>', <?= $d['price'] ?>)" 
                                         id="dec_<?= $d['id'] ?>">
                                        <div class="dec-img-wrapper border">
                                            <img src="../uploads/<?= $d['image_url'] ?: 'default.png' ?>" class="img-fix">
                                        </div>
                                        <div class="extra-small fw-bold text-truncate"><?= $d['name'] ?></div>
                                        <div class="text-success extra-small font-bold">+฿<?= number_format($d['price']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <h6 class="fw-bold mb-3 border-start border-gold border-4 ps-2 uppercase">ข้อมูลชุดเซ็ต</h6>
                    <div class="card border-0 shadow-lg rounded-4 sticky-top" style="top: 20px;">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="fw-bold small">ชื่อชุดเซ็ตสินค้า</label>
                                <input type="text" name="name" id="set_name" class="form-control border-2" value="<?= htmlspecialchars($product['name']) ?>" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="fw-bold small">หมวดหมู่ชุดเซ็ต</label>
                                    <input type="text" name="sub_category" id="sub_category_input" class="form-control" value="<?= htmlspecialchars($product['sub_category']) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="fw-bold small">รูปภาพชุดเซ็ต</label>
                                    <input type="file" name="image" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold small">รายละเอียดเซ็ต</label>
                                <textarea name="description" id="description_set" class="form-control" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                            </div>
                            <div class="p-3 rounded-4 bg-light mb-3 border">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="small text-muted">ราคารวมแยกชิ้น:</span>
                                    <span class="fw-bold" id="costTotal">฿0</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark">ราคาเซ็ต:</span>
                                    <div class="input-group input-group-sm" style="width: 150px;">
                                        <span class="input-group-text bg-white">฿</span>
                                        <input type="number" name="price" id="set_price" class="form-control fw-bold text-primary" value="<?= $product['price'] ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="extra-small fw-bold text-muted text-uppercase mb-2 d-block">รายการที่เลือก:</label>
                                <div id="componentsList" class="bg-white rounded-3 border p-2" style="max-height: 100px; overflow-y: auto;"></div>
                            </div>
                            <input type="hidden" name="coffin_id" id="coffin_id_input">
                            <input type="hidden" name="is_active" value="<?= $product['is_active'] ?>">
                            <div id="hiddenDecs"></div>
                            <button type="submit" class="btn btn-dark w-100 rounded-pill py-3 fw-bold shadow-lg" style="background: #121212; border: 1px solid var(--gold);">บันทึกการแก้ไข</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
<script>
    const editorId = '<?= $is_set ? "#description_set" : "#description" ?>';
    ClassicEditor.create(document.querySelector(editorId)).catch(e => console.error(e));

    <?php if ($success): ?>
        Swal.fire({ icon: 'success', title: 'อัปเดตข้อมูลสำเร็จ!', timer: 1500, showConfirmButton: false });
    <?php endif; ?>

    <?php if ($is_set): ?>
    let selectedCoffin = null;
    let selectedDecs = new Map();

    window.onload = () => {
        <?php 
        foreach($coffins as $c) {
            if(in_array($c['id'], $current_items)) {
                echo "selectCoffin({$c['id']}, '".addslashes($c['name'])."', {$c['price']}, '".addslashes($c['sub_category'])."');";
            }
        }
        foreach($decorations as $d) {
            if(in_array($d['id'], $current_items)) {
                echo "selectedDecs.set({$d['id']}, {name: '".addslashes($d['name'])."', price: {$d['price']}});";
            }
        }
        ?>
        updateSummary();
    };

    function filterList(input, className) {
        const filter = input.value.toLowerCase();
        const items = document.getElementsByClassName(className);
        for (let i = 0; i < items.length; i++) {
            const name = items[i].getAttribute('data-name');
            items[i].style.display = name.includes(filter) ? "" : "none";
        }
    }

    function selectCoffin(id, name, price, subCat) {
        document.querySelectorAll('.coffin-item-wrapper .product-card-select').forEach(el => el.classList.remove('selected'));
        const el = document.getElementById('coffin_' + id);
        if(el) el.classList.add('selected');
        selectedCoffin = { id, name, price };
        document.getElementById('coffin_id_input').value = id;
        document.getElementById('sub_category_input').value = subCat;
        updateSummary();
    }

    function toggleDecoration(id, name, price) {
        const el = document.getElementById('dec_' + id);
        if (selectedDecs.has(id)) {
            selectedDecs.delete(id);
            if(el) el.classList.remove('selected');
        } else {
            selectedDecs.set(id, { name, price });
            if(el) el.classList.add('selected');
        }
        updateSummary();
    }

    function updateSummary() {
        const list = document.getElementById('componentsList');
        const hidden = document.getElementById('hiddenDecs');
        list.innerHTML = ''; hidden.innerHTML = '';
        let totalCost = 0;

        if (selectedCoffin) {
            totalCost += selectedCoffin.price;
            list.innerHTML += `<div class="extra-small p-1 border-bottom fw-bold text-dark"><i class="bi bi-box me-1 text-gold"></i> ${selectedCoffin.name}</div>`;
        }

        selectedDecs.forEach((val, id) => {
            totalCost += val.price;
            list.innerHTML += `<div class="extra-small p-1 border-bottom text-muted"><i class="bi bi-flower1 me-1"></i> ${val.name}</div>`;
            hidden.innerHTML += `<input type="hidden" name="decoration_ids[]" value="${id}">`;
        });

        if (list.innerHTML === '') list.innerHTML = '<p class="text-muted extra-small fst-italic p-2">ยังไม่ได้เลือกส่วนประกอบ</p>';
        document.getElementById('costTotal').innerText = '฿' + totalCost.toLocaleString();
    }
    <?php endif; ?>
</script>