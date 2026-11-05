<?php 
session_start();
require_once 'config/db.php';

// ดึงสินค้าประเภทหีบศพที่มียอดขายสูงสุด 3 อันดับแรก
$sql_featured = "SELECT p.*, SUM(oi.quantity) as total_sold 
                 FROM products p 
                 LEFT JOIN order_items oi ON p.id = oi.product_id 
                 LEFT JOIN orders o ON oi.order_id = o.id 
                 WHERE p.is_active = 1 
                   AND p.type = 'coffin' 
                   AND (o.status IN ('paid', 'completed') OR o.status IS NULL)
                 GROUP BY p.id 
                 ORDER BY total_sold DESC, p.id DESC 
                 LIMIT 3";

$stmt = $pdo->query($sql_featured);
$featuredProducts = $stmt->fetchAll();

// 🌟 ดึงข้อมูลภาพกิจกรรมการบริจาค
$stmtActivity = $pdo->query("SELECT * FROM donation_posts WHERE is_active = 1 ORDER BY created_at DESC");
$activities = $stmtActivity->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บริจาคหีบศพ | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { font-family: 'Prompt', sans-serif; }
        .serif { font-family: 'Playfair Display', serif; }
        .gold-gradient {
            background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%);
        }
        .text-gold {
            background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .bg-charcoal { background-color: #121212; }
        
        .custom-scrollbar::-webkit-scrollbar { height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #D4AF37; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #B8860B; }

        /* สไตล์สำหรับ Lightbox (ดูรูปขยาย) */
        #lightbox { display: none; z-index: 9999; }
        #lightbox.active { display: flex; }
    </style>
</head>
<body class="bg-white text-gray-800">

<?php include 'includes/navbar.php'; ?>

<header class="relative h-screen flex items-center justify-center overflow-hidden bg-charcoal">
    <div class="absolute inset-0 scale-105 animate-[zoom-out_20s_infinite_alternate]">
        <img src="uploads/banner_donate2.png" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-black/40 to-black/80"></div> 
    </div>

    <div class="relative z-10 text-center px-4 max-w-4xl mx-auto">

        <h1 class="serif text-4xl md:text-7xl text-white mb-6 leading-tight tracking-wide" data-aos="fade-up">
            สะพานบุญ <br> <span class="text-gold">วาระสุดท้าย</span>
        </h1>

        <p class="text-gray-300 font-light text-sm md:text-lg max-w-xl mx-auto mb-10 leading-relaxed italic" data-aos="fade-up" data-aos-delay="300">
            "ร่วมเป็นส่วนหนึ่งในการส่งมอบความอาลัยอย่างสมเกียรติ <br class="hidden md:block"> เพื่อผู้วายชนม์ที่ไร้ญาติและยากไร้"
        </p>
        
        <div class="flex flex-col sm:flex-row justify-center gap-6" data-aos="zoom-in" data-aos-delay="600">
            <a href="donations.php" class="gold-gradient text-black text-center font-black py-5 px-14 rounded-sm uppercase tracking-[0.3em] text-[11px] md:text-xs hover:shadow-[0_0_50px_rgba(212,175,55,0.4)] transition-all duration-500 transform hover:-translate-y-2 active:scale-95 shadow-2xl">
                <i class="fas fa-heart mr-3"></i> ร่วมบริจาคศพ
            </a>
        </div>
    </div>
</header>

<style>
/* แอนิเมชันสำหรับภาพพื้นหลัง */
@keyframes zoom-out {
    from { transform: scale(1.1); }
    to { transform: scale(1.0); }
}
.serif { font-family: 'Playfair Display', serif; }
</style>

    <section class="py-20 md:py-32 bg-white relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full pointer-events-none opacity-20">
            <div class="absolute w-[600px] h-[600px] bg-[#D4AF37] rounded-full blur-[150px] -top-60 -left-40"></div>
            <div class="absolute w-[400px] h-[400px] bg-gray-400 rounded-full blur-[120px] bottom-0 right-0"></div>
        </div>

        <div class="max-w-4xl mx-auto px-6 relative z-10 text-center">
            <div class="mb-12">
                <h2 class="serif text-4xl md:text-5xl text-gray-900 font-bold mb-4">สะพานบุญแห่งการให้</h2>
                <div class="h-[2px] w-24 bg-[#D4AF37] mx-auto"></div>
            </div>

            <div class="space-y-6 text-gray-600 text-base md:text-lg leading-relaxed font-light mb-16 px-4 md:px-12 text-justify md:text-center">
                <p>
                    <strong class="text-xl text-[#D4AF37] font-medium serif">“งานศพ”</strong> 
                    สำหรับใครหลายคนอาจเป็นเพียงพิธีกรรมตามประเพณี แต่สำหรับครอบครัวที่ต้องสูญเสียเสาหลัก หรืองานศพกลับกลายเป็นความพะวักพะวนที่ซ้ำเติมบาดแผลในใจ เพราะคงไม่มีอะไรหดหู่ <br>ไปกว่าการที่ “ไม่มีโอกาสแม้แต่จะจัดงานอำลา” ให้กับคนที่เรารักเป็นครั้งสุดท้าย
                </p>
                <p>
                    บนเส้นทางแห่งการบริการหลังความตายของ <strong class="text-gray-900 font-medium">สมหมายหีบทองอยุธยา</strong> เราตระหนักดีว่าความตายคือสัจธรรมที่ยุติธรรมที่สุด แต่ความเหลื่อมล้ำในสังคมกลับทำให้การจากลาของบางคนเต็มไปด้วยความลำบาก เราจึงขออาสาเป็น “ตัวกลางแห่งความเมตตา” เพื่อดูแลหัวใจของผู้ที่ยังอยู่และมอบศักดิ์ศรี<br> ให้แก่ผู้ที่จากไป
                </p>
            </div>

            <div class="inline-block bg-gray-50 border border-gray-100 p-8 md:p-12 rounded-sm shadow-sm mb-16 text-left w-full md:w-auto">
                <h4 class="text-gray-900 font-bold mb-6 text-lg border-b border-gray-200 pb-3">อาสาประคองความเศร้า ด้วยหีบศพเพื่อผู้ยากไร้</h4>
                <ul class="space-y-5 text-gray-700 font-medium">
                    <li class="flex items-start">
                        <i class="fas fa-circle text-[#D4AF37] text-[8px] mt-2.5 mr-4"></i>
                        <span class="leading-relaxed">ท่านสามารถส่งต่อสายธารน้ำใจผ่านเราได้ทุกเมื่อ เงินทำบุญของท่านทุกบาททุกสตางค์ จะถูกแปรเปลี่ยนเป็นหีบศพคุณภาพเพื่อส่งตรงถึงมือผู้ที่เดือดร้อนจริง โดยไม่มีการหักค่าใช้จ่ายในการดำเนินงาน</span>
                    </li>
                </ul>
            </div>

            <p class="text-xl md:text-2xl text-gray-900 font-bold serif italic mb-12">
                " ร่วมเป็นส่วนหนึ่งของการให้ครั้งสุดท้าย <br class="md:hidden"> ที่เปี่ยมด้วยบุญกุศลและความหมาย "
            </p>

        </div>
    </section>

    <section class="py-20 bg-gray-50 border-t border-gray-100">
        <div class="max-w-[1400px] mx-auto px-6">
            
            <div class="text-center mb-10">
                <h2 class="serif text-3xl md:text-4xl text-gray-900 font-bold mb-4">ภาพกิจกรรมส่งมอบหีบศพ</h2>
                <p class="text-gray-500 text-sm">ภาพแห่งความประทับใจจากการส่งมอบความช่วยเหลือไปยังวัดและผู้ยากไร้</p>
            </div>

            <?php if(count($activities) > 0): ?>
                <div class="relative group">
                    <div class="grid grid-rows-1 md:grid-rows-2 grid-flow-col auto-cols-[85%] md:auto-cols-[45%] lg:auto-cols-[32%] gap-6 overflow-x-auto pb-8 snap-x snap-mandatory custom-scrollbar" style="scroll-behavior: smooth;">
                        
                        <?php foreach($activities as $act): 
                            // ถอดรหัส JSON เพื่อรองรับการเก็บหลายรูป
                            $img_array = json_decode($act['image_url'], true);
                            if (!is_array($img_array)) {
                                $img_array = [$act['image_url']];
                            }
                            $cover_img = $img_array[0];
                            $img_count = count($img_array);
                            
                            // นำ Array รูปภาพมาต่อกันเป็น String คั่นด้วยเครื่องหมายคอมม่า เพื่อส่งไปให้ JavaScript
                            $img_list_str = implode(',', $img_array);
                        ?>
                        <div class="snap-start h-full">
                            <div class="bg-white rounded-sm overflow-hidden shadow-sm hover:shadow-xl transition-shadow duration-300 group border border-gray-100 h-full flex flex-col cursor-pointer" 
                                 onclick="openLightbox('<?= htmlspecialchars($img_list_str, ENT_QUOTES) ?>')">
                                
                                <div class="relative h-48 md:h-56 overflow-hidden bg-gray-200 shrink-0">
                                    <img src="uploads/donations/<?= htmlspecialchars($cover_img) ?>" 
                                         class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" 
                                         alt="กิจกรรมบริจาค">
                                    
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition duration-300 flex items-center justify-center">
                                        <i class="fas fa-search-plus text-white text-3xl opacity-0 group-hover:opacity-100 transition duration-300 transform scale-50 group-hover:scale-100"></i>
                                    </div>

                                    <?php if($img_count > 1): ?>
                                        <div class="absolute bottom-4 right-4 bg-white/90 text-black px-3 py-1 rounded-full text-[10px] font-bold shadow-sm">
                                            <i class="fas fa-images text-[#D4AF37] mr-1"></i> +<?= $img_count - 1 ?> รูป
                                        </div>
                                    <?php endif; ?>

                                    <div class="absolute top-4 right-4 bg-black/80 backdrop-blur-sm text-white px-3 py-1 rounded-sm text-[10px] uppercase tracking-widest font-bold">
                                        <?= date('d/m/Y', strtotime($act['created_at'])) ?>
                                    </div>
                                </div>
                                
                                <div class="p-5 flex-grow flex flex-col justify-center">
                                    <h3 class="font-bold text-gray-900 text-base mb-1 line-clamp-1" style="display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?= htmlspecialchars($act['title'] ?: 'กิจกรรมมอบหีบศพ') ?>
                                    </h3>
                                    <p class="text-gray-500 text-xs leading-relaxed" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?= htmlspecialchars($act['description'] ?: 'ขอขอบคุณผู้ใจบุญทุกท่านที่ร่วมเป็นส่วนหนึ่งในการให้ครั้งสุดท้าย') ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    </div>
                </div>
                <div class="text-center mt-2 text-gray-400 text-xs flex items-center justify-center gap-2">
                    <i class="fas fa-arrows-alt-h text-[#D4AF37]"></i> เลื่อนซ้าย-ขวา เพื่อดูภาพกิจกรรมเพิ่มเติม
                </div>
            <?php else: ?>
                <div class="text-center py-16 bg-white border border-dashed border-gray-200 rounded-sm">
                    <i class="fas fa-images text-4xl text-gray-200 mb-4 block"></i>
                    <p class="text-gray-400 font-medium">ยังไม่มีภาพกิจกรรมในขณะนี้</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div id="lightbox" class="fixed inset-0 bg-black/95 items-center justify-center hidden flex-col">
        <button onclick="closeLightbox()" class="absolute top-6 right-6 text-white hover:text-[#D4AF37] text-3xl transition">
            <i class="fas fa-times"></i>
        </button>

        <div class="relative w-full max-w-5xl h-[80vh] flex items-center justify-center px-4">
            <img id="lightboxImage" src="" class="max-w-full max-h-full object-contain rounded-sm shadow-2xl" alt="ภาพขยาย">
            
            <button id="btnPrev" onclick="changeImage(-1)" class="absolute left-4 md:left-10 text-white bg-black/50 hover:bg-[#D4AF37] w-12 h-12 rounded-full flex items-center justify-center transition hidden">
                <i class="fas fa-chevron-left text-xl"></i>
            </button>
            <button id="btnNext" onclick="changeImage(1)" class="absolute right-4 md:right-10 text-white bg-black/50 hover:bg-[#D4AF37] w-12 h-12 rounded-full flex items-center justify-center transition hidden">
                <i class="fas fa-chevron-right text-xl"></i>
            </button>
        </div>

        <div id="imageCounter" class="text-white mt-4 font-bold tracking-widest text-sm hidden">1 / 1</div>
    </div>


<?php require_once 'includes/footer.php'; ?>

<script>
    let currentImages = [];
    let currentIndex = 0;
    const basePath = 'uploads/donations/'; // Path โฟลเดอร์รูปภาพ

    // เปิดหน้าต่างดูรูป
    function openLightbox(imagesString) {
        currentImages = imagesString.split(',');
        currentIndex = 0;
        
        updateLightboxImage();

        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const counter = document.getElementById('imageCounter');

        if (currentImages.length > 1) {
            btnPrev.classList.remove('hidden');
            btnNext.classList.remove('hidden');
            counter.classList.remove('hidden');
        } else {
            btnPrev.classList.add('hidden');
            btnNext.classList.add('hidden');
            counter.classList.add('hidden');
        }

        document.getElementById('lightbox').classList.add('active');
        document.body.style.overflow = 'hidden'; 
    }

    // ปิดหน้าต่างดูรูป
    function closeLightbox() {
        document.getElementById('lightbox').classList.remove('active');
        document.body.style.overflow = 'auto'; 
    }

    // เปลี่ยนรูปภาพ
    function changeImage(direction) {
        if (currentImages.length <= 1) return; // ถ้ารูปเดียวไม่ต้องทำอะไร

        currentIndex += direction;
        
        if (currentIndex >= currentImages.length) {
            currentIndex = 0;
        } else if (currentIndex < 0) {
            currentIndex = currentImages.length - 1;
        }
        
        updateLightboxImage();
    }

    // อัปเดตการแสดงผล
    function updateLightboxImage() {
        const imgElement = document.getElementById('lightboxImage');
        const counterElement = document.getElementById('imageCounter');
        
        imgElement.src = basePath + currentImages[currentIndex].trim();
        counterElement.innerText = (currentIndex + 1) + " / " + currentImages.length;
    }

    // 🌟 ส่วนที่เพิ่มใหม่: ดักจับการกดปุ่มบนคีย์บอร์ด (Keyboard Navigation)
    document.addEventListener('keydown', function(event) {
        const lightbox = document.getElementById('lightbox');
        
        // เช็คว่า Lightbox เปิดอยู่หรือไม่
        if (lightbox.classList.contains('active')) {
            if (event.key === 'ArrowLeft') {
                // กดปุ่มลูกศรซ้าย
                changeImage(-1);
            } else if (event.key === 'ArrowRight') {
                // กดปุ่มลูกศรขวา
                changeImage(1);
            } else if (event.key === 'Escape') {
                // กดปุ่ม ESC เพื่อปิดหน้าต่าง
                closeLightbox();
            }
        }
    });
</script>
<?php include 'chat_widget.php'; ?>
</body>
</html>