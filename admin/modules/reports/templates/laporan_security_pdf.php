<?php
/**
 * PDF Template: Laporan Keamanan
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

$now = new DateTime();
$total_blocked_now = 0;
foreach ($mainData as $row) {
    if ($row['is_blocked'] && $row['blocked_until']) {
        $blockedUntil = new DateTime($row['blocked_until']);
        if ($blockedUntil > $now) {
            $total_blocked_now++;
        }
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
            font-size: 8pt; /* Ukuran font kecil */
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
            padding: 6px 3px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 7pt;
            text-transform: uppercase;
        }
        
        table.data-table td {
            padding: 4px 3px;
            border: 1px solid #000;
            font-size: 7pt;
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
             font-size: 8pt;
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
        .text-danger {
            color: #dc3545;
            font-weight: bold;
        }
        .text-secondary {
            color: #6c757d;
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
    
    <h1>Laporan Keamanan</h1>
    <div class="subtitle">
        Tanggal Cetak: <?= date('d F Y, H:i') ?> WIB
    </div>
    
    <h2 style="border-bottom: none; padding-bottom: 2px;">Rekap Event Keamanan (Total: <?= count($mainData) ?> Data)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 10%;">Waktu</th>
                <th style="width: 10%;">IP Address</th>
                <th style="width: 7%;">Tipe Event</th>
                <th style="width: 12%;">User/Target</th>
                <th style="width: 8%;">Status Blokir</th>
                <th style="width: 20%;">Deskripsi</th>
                <th style="width: 30%;">Detail Device</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mainData)): ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">Tidak ada data event keamanan</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($mainData as $row): ?>
                    <?php
                        $isBlocked = false;
                        if ($row['is_blocked'] && $row['blocked_until']) {
                            $blockedUntil = new DateTime($row['blocked_until']);
                            if ($blockedUntil > $now) {
                                $isBlocked = true;
                            }
                        }
                    ?>
                    <tr>
                        <td class="text-center"><?= $no ?></td>
                        <td class="text-center"><?= formatTanggal($row['created_at'], 'd/m/Y H:i') ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['ip_address'] ?? '-') ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['action_type'] ?? '') ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['identifier'] ?? '-') ?></td>
                        <td class="text-center">
                            <?php if ($isBlocked): ?>
                                <span class="text-danger">Diblokir</span>
                            <?php else: ?>
                                <span class="text-secondary">Tidak</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-left"><?= htmlspecialchars($row['block_reason'] ?? '-') ?></td>
                        <td class="text-left">
                            <?php if (empty($row['user_agent'])): ?>
                                <span class="text-danger">Anomali</span>
                            <?php else: ?>
                                <?= htmlspecialchars($row['user_agent']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php $no++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="7" class="text-right">TOTAL EVENT (SESUAI FILTER):</th>
                <th class="text-center"><?= formatNumber(count($mainData)) ?></th>
            </tr>
            <tr>
                <th colspan="7" class="text-right">TOTAL SEDANG DIBLOKIR (DARI HASIL):</th>
                <th class="text-center"><?= formatNumber($total_blocked_now) ?></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>