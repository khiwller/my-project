<?php 
require_once 'layout/header.php'; 
checkAdmin();

// --- 1. ตรวจสอบตัวกรองช่วงเวลา (Filter) ---
$period = isset($_GET['period']) ? $_GET['period'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$sqlCondition = "";
if (!empty($start_date) && !empty($end_date)) {
    $sqlCondition = " AND DATE(orders.created_at) BETWEEN '$start_date' AND '$end_date' ";
    $periodTitle = date('d/m/Y', strtotime($start_date)) . " ถึง " . date('d/m/Y', strtotime($end_date));
} elseif (!empty($start_date)) {
    $sqlCondition = " AND DATE(orders.created_at) = '$start_date' ";
    $periodTitle = date('d/m/Y', strtotime($start_date));
} else {
    switch ($period) {
        case 'today': $sqlCondition = " AND DATE(orders.created_at) = CURDATE() "; $periodTitle = 'วันนี้ (Today)'; break;
        case 'week':  $sqlCondition = " AND YEARWEEK(orders.created_at, 1) = YEARWEEK(CURDATE(), 1) "; $periodTitle = 'สัปดาห์นี้ (This Week)'; break;
        case 'month': $sqlCondition = " AND DATE_FORMAT(orders.created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') "; $periodTitle = 'เดือนนี้ (This Month)'; break;
        default: $sqlCondition = ""; $periodTitle = 'ทั้งหมด (All Time)'; break;
    }
}

// --- 2. ดึงข้อมูลสรุปยอด (Stats) ---
$totalSales = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE order_type = 'purchase' AND status IN ('paid','completed')" . $sqlCondition)->fetchColumn() ?: 0;
$totalDonations = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE order_type = 'donation' AND status IN ('paid', 'completed')" . $sqlCondition)->fetchColumn() ?: 0;
$countPending = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$countUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

// =========================================================================
// --- 3. ข้อมูลกราฟแนวโน้มแบบ Dynamic (สร้างแกน X ให้ครบเพื่อวาดเส้นกราฟได้) ---
// =========================================================================
$salesDataMap = []; 
$donationDataMap = [];

// ก. ดึงข้อมูลจาก DB มาเก็บเป็น Map ก่อน (Key คือวันที่ หรือ เดือน)
if ($period == 'all') {
    // ถ้าเลือก "ทั้งหมด" ให้ Group by 'เดือน-ปี'
    $stmtTrend = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as date_key, 
                                SUM(CASE WHEN order_type = 'purchase' THEN total_amount ELSE 0 END) as sales,
                                SUM(CASE WHEN order_type = 'donation' THEN total_amount ELSE 0 END) as donations
                              FROM orders 
                              WHERE status IN ('paid', 'completed') 
                              GROUP BY date_key ORDER BY date_key ASC");
} else {
    // ช่วงเวลาอื่นๆ Group by 'วัน'
    $stmtTrend = $pdo->query("SELECT DATE(created_at) as date_key, 
                                SUM(CASE WHEN order_type = 'purchase' THEN total_amount ELSE 0 END) as sales,
                                SUM(CASE WHEN order_type = 'donation' THEN total_amount ELSE 0 END) as donations
                              FROM orders 
                              WHERE status IN ('paid', 'completed') $sqlCondition
                              GROUP BY date_key ORDER BY date_key ASC");
}

while($row = $stmtTrend->fetch()){
    $salesDataMap[$row['date_key']] = floatval($row['sales']);
    $donationDataMap[$row['date_key']] = floatval($row['donations']);
}

// ข. สร้างแกน X ให้ครบตามเงื่อนไขที่เลือก (Fill Missing Dates)
$finalLabels = [];
$finalSales = [];
$finalDonations = [];

if ($period == 'all') {
    // แสดงเป็นเดือนทั้งหมดที่มีในระบบ
    if (empty($salesDataMap)) {
        $finalLabels[] = date('m/Y'); $finalSales[] = 0; $finalDonations[] = 0;
    } else {
        foreach ($salesDataMap as $key => $val) {
            $finalLabels[] = date('m/Y', strtotime($key . '-01')); // Format: 03/2026
            $finalSales[] = $val;
            $finalDonations[] = $donationDataMap[$key] ?? 0;
        }
    }
} elseif ($period == 'week') {
    // สร้างแกน X 7 วัน (จันทร์ - อาทิตย์)
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    for ($i = 0; $i < 7; $i++) {
        $currDate = date('Y-m-d', strtotime("$startOfWeek +$i days"));
        $finalLabels[] = date('d/m', strtotime($currDate));
        $finalSales[] = $salesDataMap[$currDate] ?? 0;
        $finalDonations[] = $donationDataMap[$currDate] ?? 0;
    }
} elseif ($period == 'month') {
    // สร้างแกน X ตามจำนวนวันในเดือนปัจจุบัน (วันที่ 1 ถึง สิ้นเดือน)
    $start = date('Y-m-01');
    $end = date('Y-m-t');
    $currDate = $start;
    while (strtotime($currDate) <= strtotime($end)) {
        $finalLabels[] = date('d/m', strtotime($currDate));
        $finalSales[] = $salesDataMap[$currDate] ?? 0;
        $finalDonations[] = $donationDataMap[$currDate] ?? 0;
        $currDate = date('Y-m-d', strtotime("$currDate +1 day"));
    }
} elseif ($period == 'custom' && !empty($start_date) && !empty($end_date)) {
    // สร้างแกน X ตามช่วงเวลาที่ระบุ
    $currDate = $start_date;
    while (strtotime($currDate) <= strtotime($end_date)) {
        $finalLabels[] = date('d/m', strtotime($currDate));
        $finalSales[] = $salesDataMap[$currDate] ?? 0;
        $finalDonations[] = $donationDataMap[$currDate] ?? 0;
        $currDate = date('Y-m-d', strtotime("$currDate +1 day"));
    }
} else {
    // กรณีเลือก "วันนี้"
    $today = date('Y-m-d');
    $finalLabels[] = date('d/m', strtotime($today));
    $finalSales[] = $salesDataMap[$today] ?? 0;
    $finalDonations[] = $donationDataMap[$today] ?? 0;
}
// =========================================================================

// --- 4. ข้อมูลกราฟแท่ง (ยอดขายแยกตามประเภทหีบ) ---
$catLabels = []; $catValues = [];
$stmtCat = $pdo->query("SELECT p.sub_category, SUM(oi.quantity) as total_qty 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        JOIN orders ON oi.order_id = orders.id
                        WHERE p.type = 'coffin' AND orders.status IN ('paid','completed') $sqlCondition
                        GROUP BY p.sub_category LIMIT 5");
while($row = $stmtCat->fetch()){
    $catLabels[] = $row['sub_category'] ?: 'ทั่วไป';
    $catValues[] = $row['total_qty'];
}

// --- 5. ข้อมูลรูปแบบการขายยอดนิยม (Set vs Individual) ---
$saleTypeLabels = []; $saleTypeValues = [];
$stmtSaleType = $pdo->query("SELECT p.sale_type, SUM(oi.quantity) as total_qty 
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.id 
                             JOIN orders ON oi.order_id = orders.id
                             WHERE p.type = 'coffin' AND orders.status IN ('paid','completed') $sqlCondition
                             GROUP BY p.sale_type");
while($row = $stmtSaleType->fetch()){
    $saleTypeLabels[] = ($row['sale_type'] == 'set') ? 'แบบจัดชุดสำเร็จ' : 'เลือกจัดเอง';
    $saleTypeValues[] = $row['total_qty'];
}

// --- 6. ข้อมูลพิกัดสำหรับการส่งสินค้า (Map Data) ---
$mapLocations = [];
$stmtMap = $pdo->query("SELECT delivery_lat, delivery_lng, shipping_address_detail 
                        FROM orders 
                        WHERE status IN ('paid','completed') AND order_type = 'purchase' 
                        AND delivery_lat IS NOT NULL AND delivery_lng IS NOT NULL $sqlCondition 
                        LIMIT 50"); 
while($row = $stmtMap->fetch()){
    $mapLocations[] = [
        'lat' => floatval($row['delivery_lat']),
        'lon' => floatval($row['delivery_lng']),
        'detail' => $row['shipping_address_detail']
    ];
}
?>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">
<script src="https://api.longdo.com/map/?key=70a15443673186fdac9c1c56bca19dcd"></script>

<style>
    /* สไตล์เพิ่มเติมสำหรับแผนที่ และ Card ที่กดได้ */
    #deliveryMap { width: 100%; height: 350px; border-radius: 0 0 16px 16px; }
    .card-clickable { cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
    .card-clickable:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center mb-4 gap-3">
        <div>
            <h2 class="dashboard-title mb-1"><i class="bi bi-grid-1x2-fill text-primary me-2 fs-3"></i> ภาพรวมระบบ</h2>
            <p class="text-muted mb-0 small">ข้อมูลสถิติช่วงเวลา: <strong class="text-dark bg-white px-2 py-1 rounded shadow-sm border"><?= $periodTitle ?></strong></p>
        </div>
        
        <button type="button" class="btn btn-dark rounded-pill px-4 shadow-sm fw-bold" onclick="printReport()">
            <i class="bi bi-printer-fill me-2"></i> พิมพ์รายงาน
        </button>
    </div>

    <div class="bg-white p-3 rounded-4 shadow-sm border mb-4 d-flex flex-column flex-md-row align-items-md-center gap-3">
        <span class="fw-bold text-muted small text-uppercase tracking-widest"><i class="bi bi-funnel-fill text-primary"></i> Slicer ตัวกรองช่วงเวลา:</span>
        
        <div class="btn-group shadow-sm" role="group">
            <a href="?period=today" class="btn btn-outline-primary <?= $period == 'today' ? 'active fw-bold' : '' ?>">วันนี้</a>
            <a href="?period=week" class="btn btn-outline-primary <?= $period == 'week' ? 'active fw-bold' : '' ?>">สัปดาห์นี้</a>
            <a href="?period=month" class="btn btn-outline-primary <?= $period == 'month' ? 'active fw-bold' : '' ?>">เดือนนี้</a>
            <a href="?period=all" class="btn btn-outline-primary <?= $period == 'all' ? 'active fw-bold' : '' ?>">ทั้งหมด</a>
        </div>

        <form action="" method="GET" class="d-flex align-items-center gap-2 ms-md-auto" id="filterForm">
            <input type="hidden" name="period" value="custom">
            <div class="input-group input-group-sm shadow-sm">
                <span class="input-group-text bg-light border-primary text-primary fw-bold">จาก</span>
                <input type="date" name="start_date" class="form-control border-primary" value="<?= $start_date ?>" required>
                <span class="input-group-text bg-light border-primary text-primary fw-bold">ถึง</span>
                <input type="date" name="end_date" class="form-control border-primary" value="<?= $end_date ?>" required>
                <button type="submit" class="btn btn-primary fw-bold px-3">ค้นหา</button>
            </div>
            <?php if($period == 'custom'): ?>
                <a href="?" class="btn btn-sm btn-outline-danger shadow-sm"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3" onclick="window.location.href='orders.php';" title="คลิกเพื่อดูรายการสั่งซื้อทั้งหมด">
            <div class="card card-custom card-clickable h-100">
                <div class="card-body d-flex justify-content-between align-items-center p-4">
                    <div>
                        <p class="text-muted mb-1 small fw-bold text-uppercase">ยอดขายสินค้า</p>
                        <h3 class="fw-bold mb-0" style="color: #2b3445;"><?= number_format($totalSales) ?></h3>
                    </div>
                    <div class="icon-box bg-light-primary"><i class="bi bi-wallet2"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3" onclick="window.location.href='donations.php';" title="คลิกเพื่อดูรายการบริจาคทั้งหมด">
            <div class="card card-custom card-clickable h-100">
                <div class="card-body d-flex justify-content-between align-items-center p-4">
                    <div>
                        <p class="text-muted mb-1 small fw-bold text-uppercase">ยอดบริจาค</p>
                        <h3 class="fw-bold mb-0" style="color: #2b3445;"><?= number_format($totalDonations) ?></h3>
                    </div>
                    <div class="icon-box bg-light-danger"><i class="bi bi-heart-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3" onclick="window.location.href='orders.php?status=pending';" title="คลิกเพื่อดูรายการที่รอตรวจสอบ">
            <div class="card card-custom card-clickable h-100">
                <div class="card-body d-flex justify-content-between align-items-center p-4">
                    <div>
                        <p class="text-muted mb-1 small fw-bold text-uppercase">รอตรวจสอบสลิป</p>
                        <h3 class="fw-bold mb-0" style="color: #2b3445;"><?= number_format($countPending) ?> <span class="fs-6 text-muted fw-normal">รายการ</span></h3>
                    </div>
                    <div class="icon-box bg-light-warning"><i class="bi bi-hourglass-split"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3" onclick="window.location.href='users.php';" title="คลิกเพื่อจัดการผู้ใช้งาน">
            <div class="card card-custom card-clickable h-100">
                <div class="card-body d-flex justify-content-between align-items-center p-4">
                    <div>
                        <p class="text-muted mb-1 small fw-bold text-uppercase">สมาชิกลูกค้า</p>
                        <h3 class="fw-bold mb-0" style="color: #2b3445;"><?= number_format($countUsers) ?> <span class="fs-6 text-muted fw-normal">คน</span></h3>
                    </div>
                    <div class="icon-box bg-light-success"><i class="bi bi-people-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card card-custom h-100">
                <div class="chart-header">
                    <i class="bi bi-bar-chart-fill text-primary me-2"></i> แนวโน้มยอดเงิน
                </div>
                <div class="card-body p-4">
                    <canvas id="trendChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card card-custom h-100">
                <div class="chart-header">
                    <i class="bi bi-pie-chart-fill text-warning me-2"></i> รูปแบบการขายยอดนิยม
                </div>
                <div class="card-body d-flex align-items-center justify-content-center p-4">
                    <canvas id="saleTypeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-8"> 
            <div class="card card-custom h-100">
                <div class="chart-header">
                    <i class="bi bi-bar-chart-steps text-success me-2"></i> หมวดหมู่หีบศพยอดนิยม
                </div>
                <div class="card-body p-4">
                    <canvas id="categoryChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>
        
</div>

<div id="printArea" style="display: none;">
    <?php include 'report_template.php'; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

<script>
Chart.defaults.font.family = "'Prompt', sans-serif";
Chart.defaults.color = '#6c757d';
Chart.register(ChartDataLabels);

// 1. กราฟ Column (Bar Chart) แทนที่กราฟเส้นเดิม
new Chart(document.getElementById('trendChart'), {
    type: 'bar', // เปลี่ยนเป็น 'bar' สำหรับ Column Chart
    data: {
        labels: <?= json_encode($finalLabels) ?>,
        datasets: [
            { 
                label: 'ยอดขาย', 
                data: <?= json_encode($finalSales) ?>, 
                backgroundColor: 'rgba(13, 110, 253, 0.85)', // สีน้ำเงินทึบ
                borderRadius: 4
            }, 
            { 
                label: 'ยอดบริจาค', 
                data: <?= json_encode($finalDonations) ?>, 
                backgroundColor: 'rgba(220, 53, 69, 0.85)', // สีแดงทึบ
                borderRadius: 4
            }
        ]
    },
    options: { 
        responsive: true, 
        plugins: { 
            legend: { position: 'top' },
            datalabels: {
                align: 'top',
                anchor: 'end',
                color: '#444',
                font: { weight: 'bold', size: 10 },
                formatter: function(value) {
                    return value > 0 ? '' + Number(value).toLocaleString() : '';
                }
            }
        }, 
        scales: { 
            x: { grid: { display: false } }, 
            y: { 
                border: { dash: [4, 4] },
                beginAtZero: true
            } 
        } 
    }
});

// 2. กราฟ Doughnut
new Chart(document.getElementById('saleTypeChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($saleTypeLabels) ?>,
        datasets: [{
            data: <?= json_encode($saleTypeValues) ?>,
            backgroundColor: ['#fd7e14', '#6c757d'],
            borderWidth: 0, hoverOffset: 5
        }]
    },
    options: { 
        responsive: true, 
        cutout: '65%', 
        plugins: { 
            legend: { position: 'bottom' },
            datalabels: {
                color: '#fff',
                font: { weight: 'bold', size: 14 },
                formatter: function(value) {
                    return Number(value).toLocaleString() + ' ชุด';
                }
            }
        } 
    }
});

// 3. กราฟแท่ง
new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{ label: 'จำนวน (ชุด)', data: <?= json_encode($catValues) ?>, backgroundColor: '#198754', borderRadius: 4 }]
    },
    options: { 
        responsive: true, 
        plugins: { 
            legend: { display: false },
            datalabels: {
                anchor: 'end',
                align: 'top',
                color: '#198754',
                font: { weight: 'bold', size: 12 },
                formatter: function(value) {
                    return value > 0 ? Number(value).toLocaleString() + ' ชุด' : '';
                }
            }
        }, 
        scales: { 
            x: { grid: { display: false } }, 
            y: { border: { dash: [4, 4] }, beginAtZero: true, suggestedMax: Math.max(...<?= json_encode(empty($catValues) ? [0] : $catValues) ?>) + 2 } 
        } 
    }
});
function printReport() {
    const printContents = document.getElementById('printArea').innerHTML;
    const printWindow = window.open('', '_blank');

    printWindow.document.write('<html><head><title>รายงานสรุปยอด - สมหมายหีบทองอยุธยา</title>');
    // โหลดฟอนต์ Sarabun ให้ตรงกับ Template
    printWindow.document.write('<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: "Sarabun", sans-serif; margin: 0; padding: 0; }');
    printWindow.document.write('@media print { @page { margin: 1cm; } .no-print { display: none; } }');
    printWindow.document.write('</style></head><body>');
    
    printWindow.document.write(printContents);
    printWindow.document.write('</body></html>');
    printWindow.document.close();

    // รอให้เนื้อหาและฟอนต์โหลดเสร็จแล้วสั่งพิมพ์ทันที
    printWindow.onload = function() {
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };
}
</script>