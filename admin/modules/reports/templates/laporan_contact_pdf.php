<?php
/**
 * PDF Template: Laporan Pesan Kontak
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

// Hitung total untuk footer (dari data yang difilter)
$total_messages = count($mainData);
$total_unread = count(array_filter($mainData, fn($row) => $row['status'] == 'unread'));
$total_replied = count(array_filter($mainData, fn($row) => $row['status'] == 'replied'));

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
            word-wrap: break-word;
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
        .text-warning {
            color: #ffc107;
            font-weight: bold;
        }
        .text-success {
            color: #198754;
        }
        .text-info {
            color: #0dcaf0;
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
    
    <h1>Laporan Pesan Kontak</h1>
    <div class="subtitle">
        Tanggal Cetak: <?= date('d F Y, H:i') ?> WIB
    </div>
    
    <h2 style="border-bottom: none; padding-bottom: 2px;">Daftar Pesan Masuk (Total: <?= count($mainData) ?> Data)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 18%;">Nama</th>
                <th style="width: 18%;">Email</th>
                <th style="width: 20%;">Subjek</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 10%;">No. Telpon</th>
                <th style="width: 10%;">IP Address</th>
                <th style="width: 10%;">Waktu Masuk</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mainData)): ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">Tidak ada data pesan</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($mainData as $row): ?>
                    <tr>
                        <td class="text-center"><?= $no ?></td>
                        <td class="text-left">
                            <strong><?= htmlspecialchars($row['name'] ?? '') ?></strong>
                        </td>
                        <td class="text-left"><?= htmlspecialchars($row['email'] ?? '') ?></td>
                        <td class="text-left"><?= htmlspecialchars($row['subject'] ?? '') ?></td>
                        <td class="text-center">
                            <?php if ($row['status'] == 'unread'): ?>
                                <span class="text-warning">Unread</span>
                            <?php elseif ($row['status'] == 'read'): ?>
                                <span class="text-info">Read</span>
                            <?php else: // replied ?>
                                <span class="text-success">Replied</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['ip_address'] ?? '-') ?></td>
                        <td class="text-center"><?= formatTanggal($row['created_at'], 'd/m/Y H:i') ?></td>
                    </tr>
                    <?php $no++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">TOTAL PESAN (SESUAI FILTER):</th>
                <th colspan="4" class="text-left"><?= formatNumber($total_messages) ?></th>
            </tr>
             <tr>
                <th colspan="4" class="text-right">TOTAL BELUM DIBACA (DARI HASIL):</th>
                <th colspan="4" class="text-left"><?= formatNumber($total_unread) ?></th>
            </tr>
            <tr>
                <th colspan="4" class="text-right">TOTAL DIJAWAB (DARI HASIL):</th>
                <th colspan="4" class="text-left"><?= formatNumber($total_replied) ?></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>