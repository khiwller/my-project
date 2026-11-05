<?php
require_once '../config/db.php';
require_once 'layout/header.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<script>alert('ไม่พบผู้ใช้งาน'); window.location='users.php';</script>";
    exit();
}

$success = false;
$error_msg = ""; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $points = intval($_POST['current_points'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');
    
    // 🌟 ระบบตรวจสอบรหัสผ่านใหม่ (บังคับแค่ความยาว)
    if (!empty($_POST['new_password'])) {
        $new_pw = $_POST['new_password'];
        
        // เงื่อนไขบังคับมีเพียงอย่างเดียวคือ ความยาว >= 6
        if (strlen($new_pw) >= 6) {
            $hashedPassword = password_hash($new_pw, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hashedPassword, $id]);
        } else {
            $error_msg = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
        }
    }

    if (empty($error_msg)) {
        $sql = "UPDATE users SET email = ?, role = ?, current_points = ?, phone = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$email, $role, $points, $phone, $id]);
        $success = true;
        
        $stmt->execute([$id]);
        $user = $stmt->fetch();
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .pw-requirement { font-size: 0.75rem; transition: 0.3s; }
    .pw-requirement.valid { color: #198754 !important; font-weight: bold; }
    .pw-requirement i { margin-right: 5px; }
    .progress-tiny { height: 6px; border-radius: 10px; margin-top: 10px; transition: all 0.5s; }
    .form-control-custom:focus { border-color: #D4AF37; box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.1); }
</style>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="dashboard-title mb-1"><i class="bi bi-person-gear text-primary me-2 fs-3"></i> แก้ไขข้อมูลผู้ใช้งาน</h2>
            <p class="text-muted mb-0 small">รหัสสมาชิก: #<?= $user['id'] ?></p>
        </div>
        <a href="users.php" class="btn btn-outline-dark rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-arrow-left me-2"></i> ย้อนกลับ
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <div class="card card-custom shadow-sm border-0">
                <div class="card-body p-5">
                    <form action="" method="POST" id="editUserForm">
                        
                        <div class="form-section-title mb-4"><i class="bi bi-shield-lock text-warning me-2"></i> ข้อมูลบัญชี (Account Info)</div>
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">ชื่อผู้ใช้ (Username)</label>
                                <input type="text" class="form-control form-control-custom bg-light" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">อีเมล (Email) <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control form-control-custom" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label-custom text-primary fw-bold">เปลี่ยนรหัสผ่าน (ขั้นต่ำ 6 ตัวอักษร)</label>
                                <div class="position-relative">
                                    <input type="password" name="new_password" id="new_password" 
                                           class="form-control form-control-custom" 
                                           placeholder="ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน">
                                    <button type="button" onclick="togglePassword()" class="btn position-absolute top-50 end-0 translate-middle-y border-0 text-muted">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>

                                <div class="progress progress-tiny bg-light">
                                    <div id="pw-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-sm-6 pw-requirement text-muted" id="req-len">
                                        <i class="bi bi-circle"></i> ความยาวอย่างน้อย 6 ตัวอักษร (จำเป็น)
                                    </div>
                                    <div class="col-sm-6 pw-requirement text-muted" id="req-upnum">
                                        <i class="bi bi-circle"></i> มีพิมพ์ใหญ่ (A-Z) และตัวเลข (0-9)
                                    </div>
                                    <div class="col-sm-12 pw-requirement text-muted mt-1" id="req-spec">
                                        <i class="bi bi-circle"></i> มีอักขระพิเศษ (!@#$%) เพื่อความปลอดภัยสูงสุด
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section-title mt-5 mb-4"><i class="bi bi-star-fill text-warning me-2"></i> สิทธิ์และแต้มสะสม</div>
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">สิทธิ์การใช้งาน (Role)</label>
                                <select name="role" class="form-select form-select-custom">
                                    <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>ผู้ใช้ทั่วไป (User)</option>
                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>ผู้ดูแลระบบ (Admin)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">แต้มสะสม (Points)</label>
                                <input type="number" name="current_points" class="form-control form-control-custom" value="<?= $user['current_points'] ?>" min="0">
                            </div>
                        </div>

                        <div class="col-md-12 mt-4">
                                <label class="form-label-custom">เบอร์โทรศัพท์</label>
                                <input type="text" name="phone" class="form-control form-control-custom" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="08xxxxxxxx">
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-5">
                            <a href="users.php" class="btn btn-light rounded-pill px-4 border">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow">
                                <i class="bi bi-save me-2"></i> บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('new_password');
    const icon = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

const pwInput = document.getElementById('new_password');
const pwBar = document.getElementById('pw-bar');

pwInput.addEventListener('input', () => {
    const val = pwInput.value;
    let score = 0;

    if (val.length === 0) {
        pwBar.style.width = '0%';
        resetRequirements();
        return;
    }

    // ตรวจสอบความยาว (เงื่อนไขเดียวที่บล็อก backend)
    const isLenValid = val.length >= 6;
    updateStatus('req-len', isLenValid);
    if (isLenValid) score++;

    // ตรวจสอบพิมพ์ใหญ่และตัวเลข (เพื่อความปลอดภัยเสริม)
    const isUpNumValid = /[A-Z]/.test(val) && /[0-9]/.test(val);
    updateStatus('req-upnum', isUpNumValid);
    if (isUpNumValid) score++;

    // ตรวจสอบอักขระพิเศษ (เพื่อความปลอดภัยเสริม)
    const isSpecValid = /[!@#$%^&*(),.?":{}|<>]/.test(val);
    updateStatus('req-spec', isSpecValid);
    if (isSpecValid) score++;

    // แสดงเกจความปลอดภัย
    const colors = ['#dc3545', '#ffc107', '#198754']; 
    const widths = ['33%', '66%', '100%'];
    
    pwBar.style.width = widths[score - 1] || '0%';
    pwBar.style.backgroundColor = colors[score - 1] || '#dee2e6';
});

function updateStatus(id, isValid) {
    const el = document.getElementById(id);
    const icon = el.querySelector('i');
    if (isValid) {
        el.classList.add('valid');
        el.classList.remove('text-muted');
        icon.classList.replace('bi-circle', 'bi-check-circle-fill');
    } else {
        el.classList.remove('valid');
        el.classList.add('text-muted');
        icon.classList.replace('bi-check-circle-fill', 'bi-circle');
    }
}

function resetRequirements() {
    ['req-len', 'req-upnum', 'req-spec'].forEach(id => {
        const el = document.getElementById(id);
        el.classList.remove('valid');
        el.classList.add('text-muted');
        el.querySelector('i').className = 'bi bi-circle';
    });
}
</script>

<?php if ($success): ?>
<script>
    Swal.fire({ title: 'สำเร็จ!', text: 'อัปเดตข้อมูลเรียบร้อยแล้ว', icon: 'success', confirmButtonColor: '#198754' });
</script>
<?php endif; ?>

<?php if ($error_msg): ?>
<script>
    Swal.fire({ title: 'คำเตือน', text: '<?= $error_msg ?>', icon: 'warning', confirmButtonColor: '#ffc107' });
</script>
<?php endif; ?>