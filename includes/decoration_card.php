<div class="card h-100 shadow-sm border-0 overflow-hidden" style="border-radius: 20px;">
    <div class="position-absolute top-0 end-0 m-2" style="z-index: 2;">
        <span class="badge bg-dark text-gold px-3 py-2 rounded-pill small border border-secondary shadow">
            ฿<?= number_format($p['price']) ?>
        </span>
    </div>
    
    <?php $img = $p['image_url'] ? "uploads/" . $p['image_url'] : "https://via.placeholder.com/300"; ?>
    <a href="decoration_detail.php?id=<?= $p['id'] ?>" class="overflow-hidden">
        <img src="<?= $img ?>" class="card-img-top" alt="<?= $p['name'] ?>">
    </a>
    
    <div class="card-body text-center p-4 bg-white">
        <h5 class="card-title text-truncate fw-bold mb-2"><?= htmlspecialchars($p['name']) ?></h5>
        <p class="text-muted small mb-3" style="height: 40px; overflow: hidden;">
            <?= mb_strimwidth(strip_tags($p['description']), 0, 60, '...') ?>
        </p>
        <a href="decoration_detail.php?id=<?= $p['id'] ?>" class="btn btn-gold w-100 rounded-pill fw-bold" style="background: #1a1a1a; color: #d4af37; border: none;">
            ดูข้อมูลเพิ่มเติม
        </a>
    </div>
</div>