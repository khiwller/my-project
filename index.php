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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมหมายหีบทองอยุธยา | หีบศพพรีเมียมและบริการครบวงจร</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap');
        
        body { font-family: 'Noto Sans Thai', sans-serif; overflow-x: hidden; }
        .serif { font-family: 'Playfair Display', serif; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .text-gold {
            background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .bg-charcoal { background-color: #121212; }
        .card-hover:hover { transform: translateY(-8px); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        
        /* แอนิเมชันสำหรับปุ่ม Header */
        @keyframes pulse-gold {
            0% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(212, 175, 55, 0); }
            100% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0); }
        }
        .btn-pulse { animation: pulse-gold 2s infinite; }
    </style>
</head>
<body class="bg-white text-gray-800">

<?php include 'includes/navbar.php'; ?>

    <header class="relative h-screen flex items-center justify-center overflow-hidden bg-charcoal">
        <div class="absolute inset-0 opacity-50 scale-110" data-aos="zoom-out" data-aos-duration="3000">
            <img src="uploads/banner.png" alt="Background" class="w-full h-full object-cover">
        </div>
        <div class="relative z-10 text-center px-6">
            <h1 class="serif text-4xl sm:text-5xl md:text-7xl text-white mb-6 tracking-tight leading-tight" data-aos="fade-up" data-aos-duration="1200">
                ส่งต่อความรัก <br> <span class="text-gold">ด้วยความประณีตสูงสุด</span>
            </h1>
            <p class="text-gray-400 text-base md:text-xl max-w-2xl mx-auto mb-10 font-light leading-relaxed" data-aos="fade-up" data-aos-delay="300" data-aos-duration="1200">
                เชิดชูเกียรติยศในวาระสุดท้าย ด้วยความสง่างามและคุณค่าอันเป็นนิรันดร์ เพื่อรำลึกถึงบุคคลสำคัญในวาระสุดท้ายอย่างสมบูรณ์แบบ
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-6" data-aos="fade-up" data-aos-delay="600" data-aos-duration="1200">
                <a href="coffins.php" class="gold-gradient btn-pulse text-black text-center font-bold py-4 px-12 rounded-sm uppercase tracking-widest text-xs sm:text-sm transition">
                    เลือกชมสินค้า
                </a>
                <a href="#services" class="border border-yellow-700 text-yellow-600 text-center font-bold py-4 px-12 rounded-sm uppercase tracking-widest text-xs sm:text-sm hover:bg-yellow-900/20 transition">
                    บริการของเรา
                </a>
            </div>
        </div>
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 animate-bounce text-yellow-600/50">
            <i class="fas fa-chevron-down text-2xl"></i>
        </div>
    </header>

    <section class="py-16 md:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 md:gap-16 text-center">
                <div class="space-y-4" data-aos="fade-up" data-aos-delay="0">
                    <div class="text-5xl text-gold mb-6"><i class="fas fa-hand-holding-heart"></i></div>
                    <h3 class="serif text-xl md:text-2xl text-gray-900 font-bold uppercase tracking-widest">ดูแลด้วยใจ</h3>
                    <p class="text-gray-500 leading-relaxed font-light">เราเข้าใจในทุกความรู้สึก และพร้อมให้คำปรึกษาด้วยความเคารพในทุกขั้นตอน</p>
                </div>
                <div class="space-y-4 border-y md:border-y-0 md:border-x border-gray-100 py-12 md:py-0" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-5xl text-gold mb-6"><i class="fas fa-gem"></i></div>
                    <h3 class="serif text-xl md:text-2xl text-gray-900 font-bold uppercase tracking-widest">งานฝีมือชั้นสูง</h3>
                    <p class="text-gray-500 leading-relaxed font-light">คัดสรรวัสดุพรีเมียมและไม้คุณภาพดีที่สุด ผ่านการประกอบอย่างประณีตทุกจุด</p>
                </div>
                <div class="space-y-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="text-5xl text-gold mb-6"><i class="fas fa-clock"></i></div>
                    <h3 class="serif text-xl md:text-2xl text-gray-900 font-bold uppercase tracking-widest">รวดเร็วตรงเวลา</h3>
                    <p class="text-gray-500 leading-relaxed font-light">บริการขนส่งและจัดเตรียมสถานที่ภายใน 1 วัน เพื่อให้พิธีกรรมราบรื่นที่สุด</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-12 md:mb-16 gap-4" data-aos="fade-right">
                <div>
                    <h2 class="serif text-3xl md:text-4xl mt-2 text-gray-900">หีบศพยอดนิยม</h2>
                    <p class="text-gray-400 mt-2 font-light">สินค้าที่ได้รับความไว้วางใจและเลือกใช้มากที่สุด</p>
                </div>
                <a href="coffins.php" class="text-gold hover:text-black transition text-xs md:text-sm uppercase tracking-widest border-b border-gold pb-1">ดูทั้งหมด</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                <?php $delay = 0; foreach($featuredProducts as $product): ?>
                <div class="bg-white group overflow-hidden card-hover shadow-sm border border-gray-100 relative" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="block aspect-[4/3] bg-gray-200 overflow-hidden relative">
                        <img src="uploads/<?= $product['image_url'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-1000">
                    </a>

                    <div class="p-8">
                        <a href="product_detail.php?id=<?= $product['id'] ?>" class="block hover:text-gold transition mb-3">
                            <h4 class="serif text-xl text-gray-900 font-bold"><?= htmlspecialchars($product['name']) ?></h4>
                        </a>
                        <p class="text-gray-400 text-sm mb-6 line-clamp-2 font-light"><?= strip_tags($product['description']) ?></p>
                        
                        <div class="flex justify-between items-center border-t border-gray-50 pt-6">
                            <div>
                                <span class="text-xl font-bold text-gray-900">฿<?= number_format($product['price']) ?></span>
                                <?php if($product['total_sold'] > 0): ?>
                                    <div class="text-[11px] text-gray-400 uppercase tracking-widest mt-1">
                                        ยอดขายสะสม <?= number_format($product['total_sold']) ?> ชุด
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="product_detail.php?id=<?= $product['id'] ?>" class="text-[13px] uppercase tracking-widest font-black text-gold hover:text-black transition">
                                ดูรายละเอียด
                            </a>
                        </div>
                    </div>
                </div>
                <?php $delay += 150; endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="serif text-3xl md:text-4xl mt-2 text-gray-900 leading-tight">ขั้นตอนการสั่งซื้อผ่านเว็บไซต์</h2>
                <p class="text-gray-500 mt-4 font-light">สั่งซื้อหีบศพและดอกไม้หน้าพิธีได้ง่ายๆ สะดวก ปลอดภัย พร้อมบริการจัดส่งถึงที่</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-12 md:gap-8 relative">
                <div class="hidden md:block absolute top-8 left-[12.5%] w-[75%] h-[1px] bg-yellow-900/20 z-0"></div>

                <div class="relative text-center group" data-aos="zoom-in" data-aos-delay="0">
                    <div class="w-16 h-16 mx-auto bg-white text-gold rounded-full flex items-center justify-center text-2xl font-bold mb-6 shadow-xl relative border border-gold/20 group-hover:bg-gold group-hover:text-black transition duration-500 z-10">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-2">เลือกชมสินค้า</h4>
                    <p class="text-sm text-gray-500 font-light px-4">เลือกหีบศพ ดอกไม้หน้าพิธี หรือชุดเซ็ตจัดโปรโมชั่น</p>
                </div>
                
                <div class="relative text-center group" data-aos="zoom-in" data-aos-delay="200">
                    <div class="w-16 h-16 mx-auto bg-white text-gold rounded-full flex items-center justify-center text-2xl font-bold mb-6 shadow-xl relative border border-gold/20 group-hover:bg-gold group-hover:text-black transition duration-500 z-10">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-2">กรอกข้อมูลจัดส่ง</h4>
                    <p class="text-sm text-gray-500 font-light px-4">ระบุสถานที่จัดส่ง (เช่น วัด หรือบ้าน) และติดต่อ</p>
                </div>
                
                <div class="relative text-center group" data-aos="zoom-in" data-aos-delay="400">
                    <div class="w-16 h-16 mx-auto bg-white text-gold rounded-full flex items-center justify-center text-2xl font-bold mb-6 shadow-xl relative border border-gold/20 group-hover:bg-gold group-hover:text-black transition duration-500 z-10">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-2">ชำระเงิน & แนบสลิป</h4>
                    <p class="text-sm text-gray-500 font-light px-4">โอนเงินผ่านคิวอาร์โค้ด และแนบสลิปในระบบ</p>
                </div>
                
                <div class="relative text-center group" data-aos="zoom-in" data-aos-delay="600">
                    <div class="w-16 h-16 mx-auto bg-white text-gold rounded-full flex items-center justify-center text-2xl font-bold mb-6 shadow-xl relative border border-gold/20 group-hover:bg-gold group-hover:text-black transition duration-500 z-10">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-2">รอรับการติดต่อ</h4>
                    <p class="text-sm text-gray-500 font-light px-4">แอดมินตรวจสอบนัดหมายจัดส่งทันที</p>
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="py-16 md:py-24 bg-charcoal text-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-16 items-center">
                <div data-aos="fade-right">
                    <h2 class="serif text-3xl md:text-5xl mt-2 mb-8 leading-tight">บริการเพื่อแบ่งเบา<br>ภาระของคุณ</h2>
                    <p class="text-gray-400 font-light leading-relaxed mb-10 text-base md:text-lg">
                        เรามุ่งเน้นการให้บริการที่มีคุณภาพและประณีตที่สุด เพื่อช่วยแบ่งเบาภาระและดูแลรายละเอียดสำคัญแทนท่านในวาระสุดท้ายอย่างสมเกียรติ
                    </p>
                    <ul class="space-y-6">
                        <li class="flex items-start text-gray-300 font-light">
                            <i class="fas fa-check-circle text-gold mr-4 mt-1 text-xl"></i> บริการจำหน่ายและจัดส่งหีบศพพรีเมียมทุกรูปแบบ
                        </li>
                        <li class="flex items-start text-gray-300 font-light">
                            <i class="fas fa-check-circle text-gold mr-4 mt-1 text-xl"></i> รับจัดดอกไม้หน้าหีบศพด้วยความประณีต สวยงาม
                        </li>
                        <li class="flex items-start text-gray-300 font-light">
                            <i class="fas fa-check-circle text-gold mr-4 mt-1 text-xl"></i> บริการจัดตกแต่งสถานที่ประกอบพิธีกรรมอย่างครบถ้วน
                        </li>
                    </ul>
                </div>
                <div class="grid grid-cols-2 gap-4" data-aos="fade-left">
                    <img src="uploads/flowers.jpg" alt="Flowers" class="w-full h-48 md:h-64 object-cover rounded-sm  transition duration-700 shadow-2xl">
                    <img src="uploads/delivery.jpg" alt="Ceremony" class="w-full h-48 md:h-64 object-cover rounded-sm mt-8 md:mt-16  transition duration-700 shadow-2xl">
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-32 gold-gradient text-center relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <i class="fas fa-leaf text-[30rem] absolute -left-20 -bottom-40 rotate-45 text-black"></i>
        </div>

        <div class="max-w-4xl mx-auto px-6 relative z-10" data-aos="fade-up">
            <h2 class="serif text-4xl md:text-6xl text-black mb-8 font-bold leading-tight">ให้เราช่วยดูแลทุกอย่าง<br>ในเวลาที่สำคัญที่สุด</h2>
            <div class="flex flex-col sm:flex-row justify-center items-center gap-6">
                <a href="tel:0634317812" class="w-full sm:w-auto bg-black text-white px-12 py-5 font-black rounded-sm shadow-2xl hover:bg-gray-900 transition-all duration-300 flex items-center justify-center text-sm uppercase tracking-widest transform hover:-translate-y-2">
                    <i class="fas fa-phone-alt mr-4 text-gold"></i> 063-431-7812
                </a>
                
                <a href="https://www.facebook.com/sm.hmay.hib.thxng.xyuthya" target="_blank" class="w-full sm:w-auto bg-white/20 backdrop-blur-md text-black border border-black/20 px-12 py-5 font-black rounded-sm hover:bg-white/40 transition-all duration-300 flex items-center justify-center text-sm uppercase tracking-widest transform hover:-translate-y-2">
                    <i class="fab fa-facebook-f mr-4"></i> Facebook 
                </a>
            </div>
        </div>
    </section>

<?php include 'chat_widget.php'; ?>
<?php require_once 'includes/footer.php'; ?>

<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script>
    // ตั้งค่าเริ่มต้นของแอนิเมชัน
    AOS.init({
        duration: 1000, // ระยะเวลา 1 วินาที
        easing: 'ease-out-cubic', // รูปแบบการเคลื่อนไหว
        once: true, // ให้แสดงแอนิเมชันแค่ครั้งเดียวตอนเลื่อนเจอ
        offset: 120, // ระยะก่อนจะเริ่มเล่นแอนิเมชัน
    });
</script>

</body>
</html>