<?php
/**
 * PDF Template: Executive Summary Report (Simple Version)
 * Clean design dengan logo besar di atas - Landscape
 */

// Convert logo to base64
$logoBase64 = '';
if ($siteLogo && uploadExists($siteLogo)) {
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
            font-size: 9pt;
            color: #000;
            margin: 0;
            padding: 0;
        }
        
        /* Header dengan Logo di Atas */
        .header {
            width: 100%;
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header-logo {
            margin-bottom: 10px;
        }
        
        .header-logo img {
            height: 100px;
            max-width: 200px;
        }
        
        .header-title {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .header-contact {
            font-size: 9pt;
            line-height: 1.5;
            color: #333;
        }
        
        /* Title */
        h1 {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0 5px 0;
            letter-spacing: 2px;
        }
        
        .subtitle {
            text-align: center;
            font-size: 10pt;
            color: #666;
            margin-bottom: 15px;
        }
        
        /* Summary Stats Box */
        .stats-summary {
            width: 100%;
            margin: 15px 0;
            border: 2px solid #000;
            background-color: #f5f5f5;
        }
        
        .stats-summary td {
            padding: 10px;
            text-align: center;
            border-right: 1px solid #000;
            width: 20%;
        }
        
        .stats-summary td:last-child {
            border-right: none;
        }
        
        .stats-label {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            color: #555;
        }
        
        .stats-value {
            font-size: 16pt;
            font-weight: bold;
            color: #000;
        }
        
        /* Main Table */
        h2 {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            text-transform: uppercase;
        }
        
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-top: 10px;
        }
        
        table.data-table th {
            background-color: #e0e0e0;
            padding: 8px 4px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
        }
        
        table.data-table td {
            padding: 6px 4px;
            border: 1px solid #000;
            font-size: 8pt;
            vertical-align: middle;
        }
        
        table.data-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        table.data-table tfoot {
            background-color: #d0d0d0;
            font-weight: bold;
        }
        
        /* Utilities */
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-left {
            text-align: left;
        }
        
        .rank-badge {
            display: inline-block;
            padding: 2px 8px;
            background-color: #fff;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 9pt;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            font-size: 7pt;
        }
        
        .notes {
            margin-top: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            font-size: 8pt;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <!-- Header dengan Logo Besar di Atas -->
    <div class="header">
        <?php if ($logoBase64): ?>
            <div class="header-logo">
                <img src="<?= $logoBase64 ?>" alt="Logo">
            </div>
        <?php endif; ?>
        
        <div class="header-title"><?= strtoupper($siteName) ?></div>
        <div class="header-contact">
            <?php if ($contactAddress): ?>
                <?= htmlspecialchars($contactAddress) ?><br>
            <?php endif; ?>
            <?php if ($contactPhone): ?>
                Telp: <?= htmlspecialchars($contactPhone) ?>
            <?php endif; ?>
            <?php if ($contactPhone && $contactEmail): ?>
                 | 
            <?php endif; ?>
            <?php if ($contactEmail): ?>
                Email: <?= htmlspecialchars($contactEmail) ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Title -->
    <h1>EXECUTIVE SUMMARY REPORT</h1>
    <div class="subtitle">Tanggal: <?= date('d F Y, H:i') ?> WIB</div>
    
    <!-- Summary Statistics -->
    <table class="stats-summary">
        <tr>
            <td>
                <div class="stats-label">Total Posts</div>
                <div class="stats-value"><?= formatNumber($summaryStats['total_posts']) ?></div>
            </td>
            <td>
                <div class="stats-label">Total Views</div>
                <div class="stats-value"><?= formatNumber($summaryStats['total_views']) ?></div>
            </td>
            <td>
                <div class="stats-label">Total Likes</div>
                <div class="stats-value"><?= formatNumber($summaryStats['total_likes']) ?></div>
            </td>
            <td>
                <div class="stats-label">Total Comments</div>
                <div class="stats-value"><?= formatNumber($summaryStats['total_comments']) ?></div>
            </td>
            <td>
                <div class="stats-label">Avg. Engagement</div>
                <div class="stats-value"><?= $summaryStats['avg_engagement'] ?>%</div>
            </td>
        </tr>
    </table>
    
    <!-- Main Table -->
    <h2>Top 20 Performing Posts - Detail Report</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Judul Post</th>
                <th style="width: 10%;">Kategori</th>
                <th style="width: 12%;">Penulis</th>
                <th style="width: 7%;">Role</th>
                <th style="width: 7%;">Views</th>
                <th style="width: 7%;">Likes</th>
                <th style="width: 7%;">Comments</th>
                <th style="width: 8%;">Engagement</th>
                <th style="width: 9%;">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mainData)): ?>
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px;">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php foreach ($mainData as $row): ?>
                    <tr>
                        <td class="text-left">
                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                        </td>
                        <td class="text-center">
                            <span class="badge"><?= htmlspecialchars($row['category_name']) ?></span>
                        </td>
                        <td class="text-left"><?= htmlspecialchars($row['author_name']) ?></td>
                        <td class="text-center">
                            <span class="badge"><?= ucfirst($row['author_role']) ?></span>
                        </td>
                        <td class="text-center"><strong><?= formatNumber($row['view_count']) ?></strong></td>
                        <td class="text-center"><?= formatNumber($row['likes']) ?></td>
                        <td class="text-center"><?= formatNumber($row['comments']) ?></td>
                        <td class="text-center"><strong><?= $row['engagement_rate'] ?>%</strong></td>
                        <td class="text-center"><?= formatTanggal($row['created_at'], 'd/m/Y') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">TOTAL:</th>
                <th class="text-center"><?= formatNumber(array_sum(array_column($mainData, 'view_count'))) ?></th>
                <th class="text-center"><?= formatNumber(array_sum(array_column($mainData, 'likes'))) ?></th>
                <th class="text-center"><?= formatNumber(array_sum(array_column($mainData, 'comments'))) ?></th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>
    
    <!-- Notes -->
    <div class="notes">
        <strong>KETERANGAN:</strong><br>
        Engagement Rate = (Likes + Comments) / Views × 100%<br>
        Data diurutkan berdasarkan jumlah views tertinggi<br>
        Hanya menampilkan post dengan status "published"<br>
        Laporan ini menampilkan 20 post dengan performa terbaik berdasarkan jumlah views
    </div>
</body>
</html>