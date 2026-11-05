<?php 
session_start();
require_once 'config/db.php'; 

if (!isset($_GET['id'])) { header("Location: decorations.php"); exit(); }
$id = $_GET['id'];

// ดึงข้อมูลของตกแต่ง
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND type = 'decoration' AND is_active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { header("Location: decorations.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        html, body { margin: 0; padding: 0; width: 100%; overflow-x: hidden; background-color: #ffffff; font-family: 'Noto Sans Thai', sans-serif; }
        .serif { font-family: 'Playfair Display', serif; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .text-gold { color: #D4AF37; }
        .bg-charcoal { background-color: #121212; }
        
        @media (min-width: 1024px) {
            .sticky-element { position: sticky; top: 120px; }
        }
        
        /* ซ่อน Scrollbar สำหรับ Breadcrumb มือถือ */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-white">

    <?php require_once 'includes/navbar.php'; ?>

    <div class="h-24 md:h-40"></div>

    <main class="max-w-[1500px] mx-auto px-6 md:px-12 lg:px-16 pb-24">
        
        <nav class="flex mb-8 md:mb-16 text-[13px] uppercase tracking-[0.5em] font-bold text-gray-300 overflow-x-auto whitespace-nowrap no-scrollbar pb-2">
            <a href="index.php" class="hover:text-gold transition">หน้าหลัก</a>
            <span class="mx-4 md:mx-6 text-gray-200">/</span>
            <a href="decorations.php" class="hover:text-gold transition">ดอกไม้หน้าพิธี</a>
            <span class="mx-4 md:mx-6 text-gray-200">/</span>
            <span class="text-gold opacity-50"><?= htmlspecialchars($product['name']) ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-start">
            
            <div class="sticky-element space-y-8 md:space-y-12">
                <header>
                    <h1 class="serif text-3xl md:text-5xl lg:text-7xl text-gray-900 leading-tight mb-4"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="flex items-center gap-4">
                        <span class="bg-charcoal text-gold text-[16px] md:text-[16px] font-black uppercase tracking-[0.3em] px-3 md:px-4 py-2 rounded-sm">
                            <?= htmlspecialchars($product['sub_category'] ?: 'Decoration') ?>
                        </span>
                        <div class="h-[1px] w-12 md:w-20 bg-gray-100"></div>
                    </div>
                </header>

                <div class="bg-white p-2 shadow-2xl border border-gray-50 rounded-sm overflow-hidden group">
                    <img src="uploads/<?= $product['image_url'] ? $product['image_url'] : 'default.png' ?>" 
                         class="w-full h-auto rounded-sm group-hover:scale-105 transition duration-1000" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                </div>
            </div>

            <div class="space-y-12 md:space-y-16 lg:pt-12">
                
                <section>
                    <div class="mb-2">
                    </div>
                    <h2 class=" text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900">
                        <span class=" font-bold text-gray-900">฿</span><?= number_format($product['price']) ?>
                    </h2>
                    <p class="text-gray-400 text-xs md:text-sm mt-4 font-light">ราคาเริ่มต้นอาจมีการปรับเปลี่ยนตามความเหมาะสมของสถานที่</p>
                </section>

                <section class="border-t border-gray-100 pt-10 md:pt-12">
                    <div class="relative mb-8 md:mb-10">
                        <h3 class="serif text-3xl md:text-4xl text-gray-900 leading-tight">รายละเอียดการจัดเตรียม</h3>
                        <div class="absolute -bottom-3 md:-bottom-4 left-0 w-16 md:w-24 h-1 gold-gradient"></div>
                    </div>
                    
                    <div>
                        <?= $product['description'] ?>
                    </div>
                </section>

                <div class="pt-8 md:pt-12 flex flex-col items-center lg:items-start gap-8">
                    <div class="h-[1px] w-full bg-gray-100 mb-4"></div>
                    <a href="decorations.php" class="group text-[13px] md:text-[13px] font-bold uppercase tracking-[0.4em] md:tracking-[0.5em] text-gray-400 hover:text-gold transition flex items-center">
                        <i class="fas fa-long-arrow-alt-left mr-4 group-hover:-translate-x-2 transition"></i> 
                        กลับไปดูดอกไม้หน้าพิธีทั้งหมด
                    </a>
                </div>

            </div>
        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>

</body>
</html>