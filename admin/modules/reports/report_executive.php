<?php
/**
 * Report: Laporan Harian (Executive Summary) - Versi Simple Growth
 * Fokus pada 4 metrik growth.
 * Logika ditulis ulang di PHP agar kompatibel dengan MySQL 5.7 (No WITH/LAG).
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Harian (Growth Summary)';
$db = Database::getInstance()->getConnection();
$exportPdf = $_GET['export_pdf'] ?? '';

// === HELPER FUNCTIONS (Lokal untuk file ini) ===

/**
 * Menghitung pertumbuhan persentase dengan aman (mencegah pembagian nol).
 */
function calculateGrowthRate($today, $yesterday) {
    if ($yesterday == 0) {
        // Jika kemarin 0 dan hari ini > 0, itu adalah pertumbuhan besar (kita set 100%)
        return ($today > 0) ? 100.00 : 0.00;
    }
    return round((($today - $yesterday) / $yesterday) * 100, 2);
}

/**
 * Memberikan kelas warna CSS berdasarkan nilai growth.
 */
function getGrowthColorClass($growth) {
    if ($growth > 0) return 'text-success';
    if ($growth < 0) return 'text-danger';
    return 'text-muted'; // Stabil
}

/**
 * Memberikan ikon CSS berdasarkan nilai growth.
 */
function getGrowthIcon($growth) {
    if ($growth > 0) return 'bi-arrow-up-right';
    if ($growth < 0) return 'bi-arrow-down-right';
    return 'bi-arrow-right';
}

// === 1. PENGUMPULAN DATA (15 HARI TERAKHIR) ===
// Kita ambil 15 hari agar hari pertama (14 hari lalu) punya data 'kemarin'
$startDate = date('Y-m-d', strtotime('-15 days'));
$params = [':start_date' => $startDate];
$masterData = [];

// Query 1: Post Baru (Berdasarkan TANGGAL BUAT, BUKAN PUBLISH - Sesuai SQL Anda)
$postSql = "
    SELECT DATE(created_at) as tanggal, COUNT(*) as post_baru
    FROM posts
    WHERE created_at >= :start_date AND deleted_at IS NULL AND status = 'published'
    GROUP BY DATE(created_at)";
$postMetrics = $db->prepare($postSql);
$postMetrics->execute($params);
$postData = $postMetrics->fetchAll(PDO::FETCH_KEY_PAIR);

// Query 2: Views (Menggunakan tabel 'page_views' yang benar)
$viewSql = "
    SELECT DATE(created_at) as tanggal, COUNT(*) as total_views
    FROM page_views 
    WHERE created_at >= :start_date
    GROUP BY DATE(created_at)";
$viewMetrics = $db->prepare($viewSql);
$viewMetrics->execute($params);
$viewData = $viewMetrics->fetchAll(PDO::FETCH_KEY_PAIR);


// Query 3: User Baru
$userSql = "
    SELECT DATE(created_at) as tanggal, COUNT(*) as new_users
    FROM users
    WHERE created_at >= :start_date AND deleted_at IS NULL
    GROUP BY DATE(created_at)";
$userMetrics = $db->prepare($userSql);
$userMetrics->execute($params);
$userData = $userMetrics->fetchAll(PDO::FETCH_KEY_PAIR);

// Query 4: Engagement (Likes + Comments) - Menggunakan '?' untuk kompatibilitas UNION
$engSql = "
    SELECT DATE(created_at) as tanggal, COUNT(*) as total_engagement
    FROM (
        SELECT created_at FROM post_likes WHERE created_at >= ?
        UNION ALL
        SELECT created_at FROM comments WHERE created_at >= ? AND status = 'approved'
    ) combined
    GROUP BY DATE(created_at)";
$engMetrics = $db->prepare($engSql);
$engMetrics->execute([$startDate, $startDate]); // Bind 2x
$engData = $engMetrics->fetchAll(PDO::FETCH_KEY_PAIR);

// === 2. MEMBUAT DATASET GABUNGAN (15 HARI) ===
for ($i = 15; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $masterData[$date] = [
        'tanggal' => $date,
        'post_baru' => (int)($postData[$date] ?? 0),
        'total_views' => (int)($viewData[$date] ?? 0),
        'new_users' => (int)($userData[$date] ?? 0),
        'total_engagement' => (int)($engData[$date] ?? 0)
    ];
}

// === 3. MENGHITUNG DELTA & GROWTH SCORE (Untuk Tabel 14 Hari) ===
$tableData = [];
$masterKeys = array_keys($masterData); // Daftar 16 tanggal (0-15)

// Kita loop dari index 1 (14 hari lalu) sampai akhir
for ($i = 1; $i < count($masterKeys); $i++) {
    $currentDate = $masterKeys[$i];
    $prevDate = $masterKeys[$i - 1];
    
    $currentStats = $masterData[$currentDate];
    $prevStats = $masterData[$prevDate];
    
    // Hitung semua delta
    $delta_post_persen = calculateGrowthRate($currentStats['post_baru'], $prevStats['post_baru']);
    $delta_views_persen = calculateGrowthRate($currentStats['total_views'], $prevStats['total_views']);
    $delta_users_persen = calculateGrowthRate($currentStats['new_users'], $prevStats['new_users']);
    $delta_engagement_persen = calculateGrowthRate($currentStats['total_engagement'], $prevStats['total_engagement']);
    
    $tableData[$currentDate] = $currentStats + [
        'delta_post_persen' => $delta_post_persen,
        'delta_views_persen' => $delta_views_persen,
        'delta_users_persen' => $delta_users_persen,
        'delta_engagement_persen' => $delta_engagement_persen,
    ];
}

// Balik urutan array agar tanggal terbaru di atas
$tableData = array_reverse($tableData);


// === 4. STATISTIK UNTUK 4 KARTU (Hari Ini vs Kemarin) ===
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$todayStats = $masterData[$today] ?? $masterData[end($masterKeys)]; 
$yesterdayStats = $masterData[$yesterday] ?? $masterData[prev($masterKeys)];

$summaryStats = [
    'content_growth' => calculateGrowthRate($todayStats['post_baru'], $yesterdayStats['post_baru']),
    'traffic_growth' => calculateGrowthRate($todayStats['total_views'], $yesterdayStats['total_views']),
    'user_growth' => calculateGrowthRate($todayStats['new_users'], $yesterdayStats['new_users']),
    'engagement_growth' => calculateGrowthRate($todayStats['total_engagement'], $yesterdayStats['total_engagement']),
];


// === 5. EXPORT PDF ===
if ($exportPdf === '1') {
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    $contactPhone = getSetting('contact_phone', '');
    $contactEmail = getSetting('contact_email', '');
    $contactAddress = getSetting('contact_address', '');
    $siteLogo = getSetting('site_logo', '');

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'L', // Landscape
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 20,
        'margin_header' => 0,
        'margin_footer' => 10,
    ]);
    $mpdf->SetDefaultFont('cambria');

    $footer = '
        <table width="100%" style="border-top: 1px solid #000; padding-top: 5px; font-size: 9pt;">
            <tr>
                <td width="70%" style="text-align: left;">
                    ' . htmlspecialchars($siteName ?? '') . ' - Laporan Harian (Simple Growth)
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    // $tableData dan $summaryStats akan digunakan di dalam file PDF
    include dirname(__FILE__) . '/templates/laporan_executive_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Simple_Growth_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= htmlspecialchars($pageTitle) ?></h3>
                <p class="text-subtitle text-muted">Ringkasan pertumbuhan website harian (vs kemarin).</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active">Laporan Harian</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row mb-3">
            <?php
                $growth = $summaryStats['content_growth'];
                $color = getGrowthColorClass($growth);
                $icon = getGrowthIcon($growth);
            ?>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2"><i class="bi bi-file-earmark-post"></i> Content Growth</h6>
                        <h2 class="mb-0 <?= $color ?>">
                            <i class="bi <?= $icon ?> fs-5 me-1"></i><?= ($growth >= 0 ? '+' : '') . $growth ?>%
                        </h2>
                    </div>
                </div>
            </div>
            
            <?php
                $growth = $summaryStats['traffic_growth'];
                $color = getGrowthColorClass($growth);
                $icon = getGrowthIcon($growth);
            ?>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2"><i class="bi bi-eye"></i> Traffic Growth</h6>
                        <h2 class="mb-0 <?= $color ?>">
                            <i class="bi <?= $icon ?> fs-5 me-1"></i><?= ($growth >= 0 ? '+' : '') . $growth ?>%
                        </h2>
                    </div>
                </div>
            </div>
            
             <?php
                $growth = $summaryStats['engagement_growth'];
                $color = getGrowthColorClass($growth);
                $icon = getGrowthIcon($growth);
            ?>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2"><i class="bi bi-graph-up"></i> Engagement Growth</h6>
                        <h2 class="mb-0 <?= $color ?>">
                            <i class="bi <?= $icon ?> fs-5 me-1"></i><?= ($growth >= 0 ? '+' : '') . $growth ?>%
                        </h2>
                    </div>
                </div>
            </div>

            <?php
                $growth = $summaryStats['user_growth'];
                $color = getGrowthColorClass($growth);
                $icon = getGrowthIcon($growth);
            ?>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2"><i class="bi bi-person-plus"></i> User Growth</h6>
                        <h2 class="mb-0 <?= $color ?>">
                            <i class="bi <?= $icon ?> fs-5 me-1"></i><?= ($growth >= 0 ? '+' : '') . $growth ?>%
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-column flex-md-row gap-2">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">Growth History (14 Hari Terakhir)</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                    </div>

                    <a href="?export_pdf=1" class="btn btn-danger flex-shrink-0" target="_blank">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Post Baru</th>
                                <th>Δ (%)</th>
                                <th>Total Views</th>
                                <th>Δ (%)</th>
                                <th>Engagement</th>
                                <th>Δ (%)</th>
                                <th>New Users</th>
                                <th>Δ (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tableData)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Tidak ada data historis</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tableData as $row): ?>
                                    <tr style="font-size: 0.9rem;">
                                        <td><strong><?= formatTanggal($row['tanggal'], 'd/m/Y') ?></strong></td>
                                        
                                        <td class="text-center"><?= formatNumber($row['post_baru']) ?></td>
                                        <td class="text-center <?= getGrowthColorClass($row['delta_post_persen']) ?>">
                                            <?= ($row['delta_post_persen'] >= 0 ? '+' : '') . $row['delta_post_persen'] ?>%
                                        </td>
                                        
                                        <td class="text-center"><?= formatNumber($row['total_views']) ?></td>
                                        <td class="text-center <?= getGrowthColorClass($row['delta_views_persen']) ?>">
                                            <?= ($row['delta_views_persen'] >= 0 ? '+' : '') . $row['delta_views_persen'] ?>%
                                        </td>
                                        
                                        <td class="text-center"><?= formatNumber($row['total_engagement']) ?></td>
                                        <td class="text-center <?= getGrowthColorClass($row['delta_engagement_persen']) ?>">
                                            <?= ($row['delta_engagement_persen'] >= 0 ? '+' : '') . $row['delta_engagement_persen'] ?>%
                                        </td>

                                        <td class="text-center"><?= formatNumber($row['new_users']) ?></td>
                                        <td class="text-center <?= getGrowthColorClass($row['delta_users_persen']) ?>">
                                            <?= ($row['delta_users_persen'] >= 0 ? '+' : '') . $row['delta_users_persen'] ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>