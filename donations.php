<?php 
session_start();
require_once 'config/db.php'; 

// 🌟 ดึงเฉพาะสินค้าที่เป็นหีบศพ (type='coffin') และขายแยก (sale_type='individual')
$stmt = $pdo->prepare("SELECT * FROM products WHERE type = 'coffin' AND sale_type = 'individual' AND is_active = 1 ORDER BY price ASC");
$stmt->execute();
$coffins = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร่วมบริจาคโลงศพ | สมหมายหีบทองอยุธยา</title>
    <link rel="icon" type="image/x-icon" href="uploads/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'IBM Plex Sans Thai', sans-serif; background-color: #fcfcfc; }
        .serif { font-family: 'Playfair Display', serif; }
        .gold-gradient { background: linear-gradient(135deg, #D4AF37 0%, #F1D38A 50%, #B8860B 100%); }
        .bg-charcoal { background-color: #121212; }
        
        /* ซ่อนลูกศรของ input number */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/navbar.php'; ?>
    <div class="h-32"></div>

    <main class="max-w-7xl mx-auto px-6 pb-24">
        <div class="text-center mb-16">
            <h1 class="serif text-5xl text-gray-900 mb-6">ร่วมบริจาคโลงศพ</h1>
            <p class="text-gray-500 max-w-2xl mx-auto">เลือกบริจาคโลงศพเพื่อมอบเป็นกุศลแก่วาระสุดท้ายของผู้ยากไร้ ท่านสามารถเลือกรูปแบบหีบศพที่ต้องการร่วมทำบุญได้จากรายการด้านล่างนี้</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
            <?php foreach($coffins as $product): ?>
                <div class="bg-white border border-gray-100 shadow-sm transition-all duration-500 rounded-sm overflow-hidden group">
                    <div class="relative overflow-hidden aspect-[4/3]">
                        <img src="uploads/<?= $product['image_url'] ?: 'default.png' ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-1000">
                    </div>
                    
                    <div class="p-8 text-center">
                        <h3 class="serif text-2xl mb-2 text-gray-900"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="text-gold font-bold text-xl mb-6">฿<?= number_format($product['price']) ?></p>
                        
                        <form action="cart_action.php" method="POST" class="space-y-4">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="order_purpose" value="donation">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="flex items-center justify-center gap-1 bg-gray-50 p-1 border border-gray-100 rounded-sm">
                                <button type="button" onclick="changeQty(<?= $product['id'] ?>, -1)" class="w-12 h-12 flex items-center justify-center text-gray-400 hover:text-black transition">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                
                                <input type="number" 
                                       name="qty" 
                                       id="qty_<?= $product['id'] ?>" 
                                       value="1" 
                                       min="1" 
                                       max="999" 
                                       maxlength="3"
                                       oninput="validateQty(this)"
                                       class="w-16 bg-transparent text-center font-bold text-lg focus:outline-none">
                                
                                <button type="button" onclick="changeQty(<?= $product['id'] ?>, 1)" class="w-12 h-12 flex items-center justify-center text-gray-400 hover:text-black transition">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            
                            <button type="submit" class="w-full bg-charcoal text-white py-4 rounded-sm uppercase tracking-widest text-[11px] font-bold hover:bg-gold hover:text-black transition duration-300 transform active:scale-95">
                                เลือกบริจาครายการนี้
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php include 'chat_widget.php'; ?>
    <?php require_once 'includes/footer.php'; ?>

    <script>
        // 🌟 ฟังก์ชันเพิ่ม/ลดจำนวน
        function changeQty(productId, delta) {
            const input = document.getElementById('qty_' + productId);
            let currentVal = parseInt(input.value) || 1;
            let newVal = currentVal + delta;

            // บังคับให้อยู่ในช่วง 1 - 999
            if (newVal < 1) newVal = 1;
            if (newVal > 999) newVal = 999;

            input.value = newVal;
        }

        // 🌟 ฟังก์ชันตรวจสอบการกรอกตัวเลขด้วยตัวเอง (ไม่เกิน 3 หลัก)
        function validateQty(input) {
            // ลบทุกอย่างที่ไม่ใช่ตัวเลข
            input.value = input.value.replace(/[^0-9]/g, '');

            // จำกัดไม่ให้เกิน 3 หลัก
            if (input.value.length > 3) {
                input.value = input.value.slice(0, 3);
            }

            // ถ้าเป็นค่าว่างหรือ 0 ให้แก้เป็น 1
            if (input.value === '' || parseInt(input.value) === 0) {
                // ปล่อยให้ว่างได้ขณะพิมพ์ แต่ตอนบันทึกระบบจะเช็คอีกที
            } else if (parseInt(input.value) > 999) {
                input.value = 999;
            }
        }
    </script>
</body>
</html>