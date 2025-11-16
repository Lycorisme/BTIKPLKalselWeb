<?php
/**
 * PDF Template: Laporan Posts
 * Sesuai standar laporan executive (Landscape A4)
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
            height: 60px;
            max-width: 150px;
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
         table.data-table tfoot th {
             background-color: #d0d0d0;
             padding: 8px 4px;
             border: 1px solid #000;
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

    </style>
</head>
<body>
    <div class="header">
        <?php if ($logoBase64): ?>
            <div class="header-logo">
                <img src="<?= $logoBase64 ?>" alt="Logo" style="height: 60px; max-width: 150px;">
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
    
    <h1>Laporan Posts</h1>
    <div class="subtitle">
        Tanggal Cetak: <?= date('d F Y, H:i') ?> WIB
    </div>
    
    <h2 style="border-bottom: none; padding-bottom: 2px;">Data Posts (Total: <?= count($mainData) ?> Data)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 32%;">Judul Post</th>
                <th style="width: 12%;">Kategori</th>
                <th style="width: 12%;">Penulis</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 8%;">Views</th>
                <th style="width: 8%;">Likes</th>
                <th style="width: 8%;">Comments</th>
                <th style="width: 8%;">Tgl Publish</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mainData)): ?>
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px;">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($mainData as $row): ?>
                    <tr>
                        <td class="text-center"><?= $no ?></td>
                        <td class="text-left">
                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($row['category_name']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['author_name']) ?></td>
                        <td class="text-center"><?= ucfirst($row['status']) ?></td>
                        <td class="text-center"><strong><?= formatNumber($row['view_count']) ?></strong></td>
                        <td class="text-center"><?= formatNumber($row['likes']) ?></td>
                        <td class="text-center"><?= formatNumber($row['comments']) ?></td>
                        <td class="text-center"><?= formatTanggal($row['published_at'], 'd/m/Y') ?></td>
                    </tr>
                    <?php $no++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">TOTAL:</th>
                <th class="text-center"><?= formatNumber(array_sum(array_column($mainData, 'view_count'))) ?></th>
                <th class="text-center"><?= formatNumber(array_sum(array_column($mainData, 'likes'))) ?></th>
                <th class="text-center"><?= formatNumber(array_sum(array_column($mainData, 'comments'))) ?></th>
                <th class="text-center"></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>