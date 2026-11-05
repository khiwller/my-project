<?php 
require_once 'layout/header.php'; 
checkAdmin(); 

// กำหนดตำแหน่งโฟลเดอร์สำหรับเก็บรูปภาพกิจกรรม
$upload_dir = '../uploads/donations/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); 
}

// --- 1. จัดการคำร้องขอ POST (เพิ่มโพสต์ใหม่ แบบหลายรูป) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $uploaded_images = [];

    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $file_count = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['images']['error'][$i] == 0) {
                $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowed_ext)) {
                    $new_name = uniqid('donate_') . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_dir . $new_name)) {
                        $uploaded_images[] = $new_name;
                    }
                }
            }
        }
    }

    if (count($uploaded_images) > 0) {
        $images_json = json_encode($uploaded_images);
        $stmt = $pdo->prepare("INSERT INTO donation_posts (image_url, title, description, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$images_json, $title, $description]);
        
        echo "<script>window.location.href = 'manage_donation_posts.php?msg=success';</script>";
        exit();
    } else {
        echo "<script>alert('กรุณาเลือกรูปภาพอย่างน้อย 1 รูป หรือไฟล์ไม่รองรับ');</script>";
    }
}

// --- 2. จัดการคำร้องขอ POST (แก้ไขโพสต์) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['post_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    // ตรวจสอบว่ามีการอัปโหลดรูปใหม่มาด้วยหรือไม่
    if (isset($_FILES['edit_images']) && !empty($_FILES['edit_images']['name'][0])) {
        $uploaded_images = [];
        $file_count = count($_FILES['edit_images']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['edit_images']['error'][$i] == 0) {
                $ext = strtolower(pathinfo($_FILES['edit_images']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $new_name = uniqid('donate_edit_') . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($_FILES['edit_images']['tmp_name'][$i], $upload_dir . $new_name)) {
                        $uploaded_images[] = $new_name;
                    }
                }
            }
        }

        // ถ้ามีรูปใหม่ถูกอัปโหลดสำเร็จ ให้ไปลบรูปเก่าทิ้งก่อน
        if (count($uploaded_images) > 0) {
            $stmt = $pdo->prepare("SELECT image_url FROM donation_posts WHERE id = ?");
            $stmt->execute([$id]);
            $old_img_data = $stmt->fetchColumn();
            
            if ($old_img_data) {
                $old_images = json_decode($old_img_data, true);
                if (is_array($old_images)) {
                    foreach ($old_images as $old_img) {
                        if (file_exists($upload_dir . $old_img)) unlink($upload_dir . $old_img);
                    }
                } else {
                    if (file_exists($upload_dir . $old_img_data)) unlink($upload_dir . $old_img_data);
                }
            }
            
            // อัปเดตข้อมูลพร้อมรูปใหม่
            $images_json = json_encode($uploaded_images);
            $stmt = $pdo->prepare("UPDATE donation_posts SET title=?, description=?, image_url=? WHERE id=?");
            $stmt->execute([$title, $description, $images_json, $id]);
        }
    } else {
        // ถ้าไม่ได้อัปโหลดรูปใหม่ ให้อัปเดตแค่ข้อความ
        $stmt = $pdo->prepare("UPDATE donation_posts SET title=?, description=? WHERE id=?");
        $stmt->execute([$title, $description, $id]);
    }

    echo "<script>window.location.href = 'manage_donation_posts.php?msg=updated';</script>";
    exit();
}

// --- 3. จัดการคำร้องขอ GET (เปิด/ปิด สถานะ) ---
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE donation_posts SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location.href = 'manage_donation_posts.php?msg=updated';</script>";
    exit();
}

// --- 4. จัดการคำร้องขอ GET (ลบข้อมูลและรูปภาพทั้งหมด) ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT image_url FROM donation_posts WHERE id = ?");
    $stmt->execute([$id]);
    $img_data = $stmt->fetchColumn();
    
    if ($img_data) {
        $images = json_decode($img_data, true);
        if (is_array($images)) {
            foreach ($images as $img) {
                if (file_exists($upload_dir . $img)) unlink($upload_dir . $img);
            }
        } else {
            if (file_exists($upload_dir . $img_data)) unlink($upload_dir . $img_data);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM donation_posts WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location.href = 'manage_donation_posts.php?msg=deleted';</script>";
    exit();
}

// --- 5. ดึงข้อมูลมาแสดงผล ---
$stmt = $pdo->query("SELECT * FROM donation_posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
    .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .table-custom th { font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; color: #6c757d; }
    .table-custom td { vertical-align: middle; }
    .img-thumbnail-custom { width: 120px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6; }
    .preview-img { width: 100px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6; margin: 5px; }
</style>

<div class="container-fluid py-4">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-images text-primary me-2"></i> จัดการภาพกิจกรรมบริจาค</h2>
            <p class="text-muted mb-0 small">อัปโหลด แก้ไข หรือลบภาพกิจกรรม (1 โพสต์สามารถมีได้หลายรูป)</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="bi bi-cloud-arrow-up-fill me-2"></i> อัปโหลดรูปใหม่
        </button>
    </div>

    <div class="card card-custom overflow-hidden">
        <div class="table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" width="180">รูปภาพ (ภาพปก)</th>
                        <th>หัวข้อ / รายละเอียด</th>
                        <th class="text-center" width="150">วันที่โพสต์</th>
                        <th class="text-center" width="150">สถานะ (แสดงผล)</th>
                        <th class="text-end pe-4" width="180">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($posts) > 0): ?>
                        <?php foreach($posts as $post): 
                            // ถอดรหัส JSON เพื่อดึงรูปแรกมาโชว์
                            $images = json_decode($post['image_url'], true);
                            if (!is_array($images)) {
                                $images = [$post['image_url']]; // รองรับข้อมูลเก่าที่เป็นรูปเดียว
                            }
                            $first_img = !empty($images) ? $images[0] : '';
                            $img_count = count($images);
                        ?>
                        <tr>
                            <td class="text-center py-3 position-relative">
                                <img src="<?= $upload_dir . htmlspecialchars($first_img) ?>" class="img-thumbnail-custom shadow-sm" alt="ภาพกิจกรรม">
                                <?php if($img_count > 1): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                                        +<?= $img_count - 1 ?> รูป
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($post['title'] ?: 'ไม่มีหัวข้อ') ?></div>
                                <div class="text-muted small mt-1 text-truncate" style="max-width: 400px;">
                                    <?= htmlspecialchars($post['description'] ?: '-') ?>
                                </div>
                            </td>
                            <td class="text-center text-muted small">
                                <?= date('d/m/Y', strtotime($post['created_at'])) ?><br>
                                <?= date('H:i', strtotime($post['created_at'])) ?> น.
                            </td>
                            <td class="text-center">
                                <?php if($post['is_active']): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">แสดงที่หน้าเว็บ</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 py-2 rounded-pill">ซ่อนอยู่</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-primary rounded-circle" 
                                            onclick='openEditModal(<?= htmlspecialchars(json_encode($post), ENT_QUOTES, 'UTF-8') ?>)' 
                                            title="แก้ไขโพสต์">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>

                                    <button class="btn btn-sm rounded-circle <?= $post['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" 
                                            onclick="confirmToggle(<?= $post['id'] ?>, <?= $post['is_active'] ?>)" 
                                            title="<?= $post['is_active'] ? 'ซ่อนโพสต์' : 'เปิดแสดงผล' ?>">
                                        <i class="bi <?= $post['is_active'] ? 'bi-eye-slash-fill' : 'bi-eye-fill' ?>"></i>
                                    </button>

                                    <!-- <button class="btn btn-sm btn-outline-danger rounded-circle" 
                                            onclick="confirmDelete(<?= $post['id'] ?>)" title="ลบถาวร">
                                        <i class="bi bi-trash-fill"></i>
                                    </button> -->
                                    
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-image display-4 opacity-25 d-block mb-3"></i>
                                <h5 class="fw-bold">ยังไม่มีรูปภาพกิจกรรม</h5>
                                <p class="small">คลิกปุ่ม "อัปโหลดรูปใหม่" มุมขวาบนเพื่อเริ่มต้น</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-dark text-white border-0" style="border-radius: 12px 12px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-images me-2 text-warning"></i>อัปโหลดรูปกิจกรรมใหม่</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">เลือกรูปภาพ (ลากคลุมเลือกได้หลายรูป) <span class="text-danger">*</span></label>
                        <input type="file" name="images[]" class="form-control" accept="image/*" multiple required onchange="previewMultipleImages(event, 'imgPreviewContainer')">
                        <div id="imgPreviewContainer" class="d-flex flex-wrap gap-2 mt-3 p-3 bg-light rounded border border-dashed text-muted text-center align-items-center justify-content-center" style="min-height: 120px;">
                            ยังไม่ได้เลือกรูปภาพ
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">หัวข้อกิจกรรม <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="เช่น บริจาคโลงศพ ณ วัด..." required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small text-muted">รายละเอียดเพิ่มเติม (ไม่บังคับ)</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="ระบุรายละเอียดเพิ่มเติม..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">บันทึกและเผยแพร่</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-primary text-white border-0" style="border-radius: 12px 12px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลกิจกรรม</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="post_id" id="edit_post_id">
                
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">รูปภาพเดิมที่แสดงอยู่</label>
                        <div id="editCurrentImages" class="d-flex flex-wrap gap-2 mb-3 p-3 bg-light rounded border"></div>

                        <label class="form-label fw-bold small text-primary">อัปโหลดรูปชุดใหม่ (หากไม่ต้องการเปลี่ยน ปล่อยว่างไว้)</label>
                        <input type="file" name="edit_images[]" class="form-control border-primary" accept="image/*" multiple onchange="previewMultipleImages(event, 'editPreviewContainer')">
                        <div class="form-text text-danger mb-2">*หากอัปโหลดรูปใหม่ รูปเก่าของโพสต์นี้จะถูกลบทิ้งทั้งหมด</div>
                        
                        <div id="editPreviewContainer" class="d-flex flex-wrap gap-2 p-3 bg-white rounded border border-dashed text-muted align-items-center justify-content-center" style="min-height: 100px; display:none;">
                            รอภาพพรีวิวชุดใหม่...
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">หัวข้อกิจกรรม <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold small text-muted">รายละเอียดเพิ่มเติม</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">อัปเดตข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ฟังก์ชัน Preview รูปภาพแบบหลายรูป
function previewMultipleImages(event, containerId) {
    var container = document.getElementById(containerId);
    var files = event.target.files;
    
    container.innerHTML = '';
    container.style.display = 'flex';
    
    if (files.length === 0) {
        container.innerHTML = '<span class="text-muted">ยังไม่ได้เลือกรูปภาพ</span>';
        return;
    }

    for (var i = 0; i < files.length; i++) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'preview-img shadow-sm border border-primary';
            container.appendChild(img);
        };
        reader.readAsDataURL(files[i]);
    }
}

// ฟังก์ชันเปิด Modal แก้ไขและดึงข้อมูลเดิมมาแสดง
function openEditModal(post) {
    // ใส่ค่าลงใน input
    document.getElementById('edit_post_id').value = post.id;
    document.getElementById('edit_title').value = post.title;
    document.getElementById('edit_description').value = post.description;
    
    // จัดการรูปภาพเดิมที่แสดงโชว์
    var currentImgContainer = document.getElementById('editCurrentImages');
    currentImgContainer.innerHTML = '';
    
    let images = [];
    try {
        images = JSON.parse(post.image_url);
    } catch(e) {
        images = [post.image_url]; // เผื่อกรณีข้อมูลเก่าไม่ใช่ JSON
    }

    if (Array.isArray(images)) {
        images.forEach(img => {
            let imgElement = document.createElement('img');
            imgElement.src = '../uploads/donations/' + img;
            imgElement.className = 'preview-img shadow-sm';
            currentImgContainer.appendChild(imgElement);
        });
    }

    // เคลียร์ Preview รูปใหม่
    document.getElementById('editPreviewContainer').innerHTML = 'รอภาพพรีวิวชุดใหม่...';
    document.querySelector('input[name="edit_images[]"]').value = '';
    
    // เปิด Modal
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

// ฟังก์ชันยืนยันเปิด/ปิด
function confirmToggle(id, currentStatus) {
    let actionText = currentStatus == 1 ? 'ซ่อนโพสต์นี้' : 'เปิดให้แสดงผล';
    Swal.fire({
        title: `ต้องการ${actionText}หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `manage_donation_posts.php?toggle=${id}`;
        }
    });
}

// ฟังก์ชันยืนยันลบข้อมูล
function confirmDelete(id) {
    Swal.fire({
        title: 'ลบโพสต์และรูปภาพทั้งหมด?',
        text: "ข้อมูลและรูปภาพที่เกี่ยวข้องทั้งหมดจะถูกลบถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `manage_donation_posts.php?delete=${id}`;
        }
    });
}

// เช็ค Alert จาก GET Parameter
<?php if(isset($_GET['msg'])): ?>
    let msg = "<?= $_GET['msg'] ?>";
    let titleText = "";
    let iconType = "success";

    if(msg === 'success') titleText = "อัปโหลดภาพกิจกรรมสำเร็จ";
    else if(msg === 'updated') titleText = "อัปเดตข้อมูลสำเร็จ";
    else if(msg === 'deleted') titleText = "ลบข้อมูลเรียบร้อยแล้ว";

    if (titleText !== "") {
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                toast: true, position: 'top-end',
                icon: iconType, title: titleText,
                showConfirmButton: false, timer: 2500, timerProgressBar: true
            });
            window.history.replaceState(null, null, window.location.pathname);
        });
    }
<?php endif; ?>
</script>