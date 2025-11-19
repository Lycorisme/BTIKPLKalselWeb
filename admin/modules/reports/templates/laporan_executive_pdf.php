<?php
/**
 * PDF Template: Laporan Harian (Simple Growth)
 * Menampilkan 4 kartu statistik dan tabel historis 14 hari.
 */

// Convert logo to base64
$logoBase64 = '';
if ($siteLogo && function_exists('uploadExists') && uploadExists($siteLogo)) {
    $logoPath = uploadPath($siteLogo);
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoExt = pathinfo($logoPath, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . $logoExt . ';base64,' . base64_encode($logoData);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "Cambria", serif;
            font-size: 8pt; 
            color: #000;
            margin: 0;
            padding: 0;
        }
        
        .header {
            width: 100%; text-align: center; border-bottom: 3px solid #000;
            padding-bottom: 15px; margin-bottom: 20px;
        }
        .header-logo { margin-bottom: 10px; }
        .header-logo img { height: 60px; max-width: 150px; }
        .header-title {
            font-size: 16pt; font-weight: bold; text-transform: uppercase;
            margin-bottom: 5px; letter-spacing: 1px;
        }
        .header-contact { font-size: 9pt; line-height: 1.5; color: #333; }
        
        h1 {
            text-align: center; font-size: 18pt; font-weight: bold;
            text-transform: uppercase; margin: 20px 0 5px 0; letter-spacing: 2px;
        }
        .subtitle {
            text-align: center; font-size: 10pt; color: #666; margin-bottom: 15px;
        }
        
        h2 {
            font-size: 12pt; font-weight: bold; margin-top: 20px;
            margin-bottom: 10px; border-bottom: 2px solid #000;
            padding-bottom: 5px; text-transform: uppercase;
        }

        /* Stats Cards Table */
        table.stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.stats-table td {
            width: 25%;
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
        }
        .stats-label {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 5px;
        }
        .stats-value {
            font-size: 16pt;
            font-weight: bold;
        }
        
        /* Main Data Table */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-top: 10px;
        }
        table.data-table th {
            background-color: #e0e0e0;
            padding: 6px 3px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 8pt; /* Sedikit lebih besar */
            text-transform: uppercase;
        }
        table.data-table td {
            padding: 5px 3px;
            border: 1px solid #000;
            font-size: 8pt; /* Sedikit lebih besar */
            vertical-align: middle;
            text-align: center;
        }
        table.data-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-center { text-align: center; }
        .text-success { color: #006400; } /* Dark Green */
        .text-danger { color: #8B0000; } /* Dark Red */
        .text-muted { color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <?php if ($logoBase64): ?>
            <div class="header-logo">
                <img src="<?= $logoBase64 ?>" alt="Logo" style="height: 60px; max-width: 150px;">
            </div>
        <?php endif; ?>
        
        <div class="header-title"><?= strtoupper(htmlspecialchars($siteName ?? '')) ?></div>
        <div class="header-contact">
            <?php if ($contactAddress): ?>
                <?= htmlspecialchars($contactAddress ?? '') ?><br>
            <?php endif; ?>
            <?php if ($contactPhone): ?>
                Telp: <?= htmlspecialchars($contactPhone ?? '') ?>
            <?php endif; ?>
            <?php if ($contactPhone && $contactEmail): ?>
                 | 
            <?php endif; ?>
            <?php if ($contactEmail): ?>
                Email: <?= htmlspecialchars($contactEmail ?? '') ?>
            <?php endif; ?>
        </div>
    </div>
    
    <h1>Laporan Harian (Simple Growth)</h1>
    <div class="subtitle">
        Tanggal Cetak: <?= date('d F Y, H:i') ?> WIB
    </div>
    
    <h2 style="border:none; text-align:center; margin-bottom: 5px;">Ringkasan Pertumbuhan (vs Kemarin)</h2>
    <table class="stats-table">
        <tr>
            <td>
                <div class="stats-label">Content Growth</div>
                <?php $g = $summaryStats['content_growth']; $c = getGrowthColorClass($g); ?>
                <div class="stats-value <?= $c ?>"><?= ($g >= 0 ? '+' : '') . $g ?>%</div>
            </td>
            <td>
                <div class="stats-label">Traffic Growth</div>
                <?php $g = $summaryStats['traffic_growth']; $c = getGrowthColorClass($g); ?>
                <div class="stats-value <?= $c ?>"><?= ($g >= 0 ? '+' : '') . $g ?>%</div>
            </td>
            <td>
                <div class="stats-label">Engagement Growth</div>
                <?php $g = $summaryStats['engagement_growth']; $c = getGrowthColorClass($g); ?>
                <div class="stats-value <?= $c ?>"><?= ($g >= 0 ? '+' : '') . $g ?>%</div>
            </td>
             <td>
                <div class="stats-label">User Growth</div>
                <?php $g = $summaryStats['user_growth']; $c = getGrowthColorClass($g); ?>
                <div class="stats-value <?= $c ?>"><?= ($g >= 0 ? '+' : '') . $g ?>%</div>
            </td>
        </tr>
    </table>

    
    <h2 style="border:none; margin-top: 15px; margin-bottom: 5px;">Growth History (14 Hari Terakhir)</h2>
    <table class="data-table">
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
                    <td colspan="9" class="text-center" style="padding: 20px;">Tidak ada data historis</td>
                </tr>
            <?php else: ?>
                <?php foreach ($tableData as $row): ?>
                    <tr>
                        <td><strong><?= formatTanggal($row['tanggal'], 'd/m/Y') ?></strong></td>
                        
                        <td><?= formatNumber($row['post_baru']) ?></td>
                        <td class="<?= getGrowthColorClass($row['delta_post_persen']) ?>">
                            <?= ($row['delta_post_persen'] >= 0 ? '+' : '') . $row['delta_post_persen'] ?>%
                        </td>
                        
                        <td><?= formatNumber($row['total_views']) ?></td>
                        <td class="<?= getGrowthColorClass($row['delta_views_persen']) ?>">
                            <?= ($row['delta_views_persen'] >= 0 ? '+' : '') . $row['delta_views_persen'] ?>%
                        </td>
                        
                        <td><?= formatNumber($row['total_engagement']) ?></td>
                        <td class="<?= getGrowthColorClass($row['delta_engagement_persen']) ?>">
                            <?= ($row['delta_engagement_persen'] >= 0 ? '+' : '') . $row['delta_engagement_persen'] ?>%
                        </td>
                        
                        <td><?= formatNumber($row['new_users']) ?></td>
                        <td class="<?= getGrowthColorClass($row['delta_users_persen']) ?>">
                            <?= ($row['delta_users_persen'] >= 0 ? '+' : '') . $row['delta_users_persen'] ?>%
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>