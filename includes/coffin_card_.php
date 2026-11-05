<!-- <div class="card h-100 shadow-sm border-0 overflow-hidden" style="border-radius: 20px; transition: 0.3s;">
    <div class="position-absolute top-0 start-0 m-3" style="z-index: 2;">
        <?php if($p['sale_type'] == 'set'): ?>
            <span class="badge bg-danger px-3 py-2 rounded-pill shadow-sm">
                <i class="bi bi-stars"></i> ชุดเซ็ตสุดคุ้ม
            </span>
        <?php else: ?>
            <span class="badge bg-dark text-gold px-3 py-2 rounded-pill shadow-sm border border-secondary">
                หีบศพมาตรฐาน
            </span>
        <?php endif; ?>
    </div>
    
    <?php $img = $p['image_url'] ? "uploads/" . $p['image_url'] : "https://via.placeholder.com/400x300"; ?>
    <a href="product_detail.php?id=<?= $p['id'] ?>" class="overflow-hidden">
        <img src="<?= $img ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>">
    </a>
    
    <div class="card-body p-4 bg-white">
        <h5 class="card-title text-truncate fw-bold mb-2 text-dark"><?= htmlspecialchars($p['name']) ?></h5>
        <div class="mb-3">
            <h4 class="fw-bold mb-0 <?= $p['sale_type'] == 'set' ? 'text-danger' : 'text-gold' ?>">
                ฿<?= number_format($p['price']) ?>
            </h4>
        </div>
        <p class="text-muted small mb-4" style="height: 40px; overflow: hidden; line-height: 1.4;">
            <?= mb_strimwidth(strip_tags($p['description']), 0, 70, '...') ?>
        </p>
        <a href="product_detail.php?id=<?= $p['id'] ?>" class="btn btn-dark w-100 rounded-pill py-2 fw-bold" style="background: #1a1a1a; color: #d4af37; border: none;">
            ดูรายละเอียด
        </a>
    </div>
</div> -->