<?php
// สมมติฐานข้อมูล: $totalSales, $totalDonations, $countUsers, $catLabels, $catValues, $periodTitle
?>

<style>
    /* ตั้งค่าหน้ากระดาษ */
    @page {
        size: A4;
        margin: 0;
    }

    @media print {
        body {
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact; /* ให้สีพื้นหลังแสดงผลเวลาพิมพ์ */
        }
        .no-print {
            display: none;
        }
        .page-container {
            box-shadow: none !important;
            margin: 0 !important;
            border: none !important;
        }
    }

    /* พื้นหลังจำลองหน้ากระดาษ A4 บนหน้าจอ */
    .page-container {
        width: 210mm;
        min-height: 297mm;
        padding: 20mm;
        margin: 10mm auto;
        background: white;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        position: relative;
        box-sizing: border-box;
        font-family: 'Sarabun', sans-serif;
        color: #000;
        overflow: hidden;
    }

    /* 🌟 ตั้งค่าลายน้ำให้ปรากฏทุกหน้า (Layer หน้าสุด) */
    .watermark-fixed {
        position: fixed; /* 👈 สำคัญ: ทำให้ลายน้ำขึ้นทุกหน้าเวลาพิมพ์ */
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        pointer-events: none;
        display: flex;
        align-items: center;
        justify-content: center;
        user-select: none;
        opacity: 0.5; /* ปรับความเข้มตามต้องการ */
    }

    .watermark-content {
        text-align: center;
    }

    /* การจัดการเนื้อหา */
    .report-content {
        position: relative;
        z-index: 10;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 12px;
    }

    /* บังคับไม่ให้ตัดแถวตารางกลางหน้า (ถ้าเป็นไปได้) */
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    h1, h3, h4 {
        margin-top: 0;
    }

    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
</style>

<div class="watermark-fixed">
    <div class="watermark-content">
        <img src="../uploads/watermark.png" alt="ลายน้ำ" 
             style="width: 450px; height: auto;" 
             onerror="this.style.display='none'">
    </div>
</div>

<div class="page-container">
    <div class="report-content">
        
        <div class="text-center" style="margin-bottom: 30px;">
            <h1 style="margin-bottom: 5px; font-size: 28px;">รายงานสรุปยอด สมหมายหีบทองอยุธยา</h1>
            <p style="font-size: 14px; color: #666;">ข้อมูล ณ วันที่: <?= date('d/m/Y H:i') ?> น.</p>
            <hr style="border: 0.5px solid #333; margin: 20px 0;">
            <h3 style="color: #444; text-transform: uppercase; letter-spacing: 1px;">ช่วงเวลา: <?= $periodTitle ?></h3>
        </div>

        <table>
            <thead>
                <tr style="background: #121212; color: #D4AF37;">
                    <th style="text-align: left; border: 1px solid #333;">หัวข้อสรุป</th>
                    <th style="text-align: right; border: 1px solid #333;">จำนวน / ยอดเงินสุทธิ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>ยอดขายสินค้า (หีบศพและของดอกไม้หน้าพิธี)</td>
                    <td class="text-right bold">฿<?= number_format($totalSales, 2) ?></td>
                </tr>
                <tr>
                    <td>ยอดรับบริจาคโลงศพ</td>
                    <td class="text-right bold">฿<?= number_format($totalDonations, 2) ?></td>
                </tr>
                <tr style="background: #fff9e6; color: #b8860b;">
                    <td class="bold">รวมรายรับทั้งสิ้น (NET TOTAL)</td>
                    <td class="text-right bold" style="font-size: 18px; border: 2px solid #D4AF37;">฿<?= number_format($totalSales + $totalDonations, 2) ?></td>
                </tr>
                <tr>
                    <td style="color: #666;">จำนวนสมาชิกในระบบทั้งหมด</td>
                    <td class="text-right"><?= number_format($countUsers) ?> ราย</td>
                </tr>
            </tbody>
        </table>

        <h4 style="margin-bottom: 15px; border-left: 5px solid #D4AF37; padding-left: 10px;">รายการยอดนิยม (Top Category)</h4>
        <table>
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="text-align: left;">หมวดหมู่ / ประเภทหีบศพ</th>
                    <th style="text-align: center; width: 150px;">จำนวนที่จำหน่าย</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($catLabels)): ?>
                    <?php foreach($catLabels as $index => $label): ?>
                    <tr>
                        <td><?= htmlspecialchars($label) ?></td>
                        <td class="text-center bold"><?= number_format($catValues[$index]) ?> ชุด</td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2" class="text-center" style="padding: 20px; color: #ccc;">ไม่มีข้อมูลรายการยอดนิยม</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 50px; text-align: right; padding-right: 50px;">
            <p style="margin-bottom: 50px;">ลงชื่อ..........................................................................</p>
            <p class="bold">( ผู้ดูแลระบบ / Admin )</p>
            <p style="font-size: 12px; color: #999;">สมหมายหีบทองอยุธยา - บริการด้วยใจในวาระสุดท้าย</p>
        </div>

    </div>
</div>

<div class="text-center no-print" style="margin-bottom: 50px;">
    <button onclick="window.print()" style="padding: 10px 30px; background: #D4AF37; border: none; font-weight: bold; cursor: pointer; border-radius: 5px;">
        <i class="fas fa-print"></i> พิมพ์รายงาน (A4)
    </button>
</div>