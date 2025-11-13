<?php
/**
 * PDF Template: Laporan Posts
 * Clean black & white design - Portrait
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
            font-size: 10pt;
            color: #000;
            margin: 0;
            padding: 0;
        }
        
        /* Header */
        .header {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .header-table {
            width: 100%;
        }
        
        .header-logo {
            width: 80px;
            vertical-align: middle;
        }
        
        .header-info {
            text-align: center;
            vertical-align: middle;
        }
        
        .header-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .header-contact {
            font-size: 9pt;
            line-height: 1.4;
        }
        
        /* Title */
        h1 {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0 5px 0;
            letter-spacing: 1px;
        }
        
        h2 {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table.data-table {
            border: 1px solid #000;
        }
        
        table.data-table th {
            background-color: #f0f0f0;
            padding: 8px 5px;
            text-align: left;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 8pt;
        }
        
        table.data-table td {
            padding: 6px 5px;
            border: 1px solid #000;
            font-size: 8pt;
        }
        
        /* Stats Table */
        .stats-table {
            width: 100%;
            margin: 15px 0;
        }
        
        .stats-table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #000;
            background-color: #f9f9f9;
        }
        
        .stats-label {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-value {
            font-size: 14pt;
            font-weight: bold;
        }
        
        /* Utilities */
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    <?php if ($logoBase64): ?>
                        <img src="<?= $logoBase64 ?>" style="height: 60px;">
                    <?php endif; ?>
                </td>
                <td class="header-info">
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
                </td>
                <td style="width: 80px;"></td>
            </tr>
        </table>
    </div>
    
    <!-- Title -->
    <h1>LAPORAN POSTS/ARTIKEL</h1>
    
    <!-- Statistics -->
    <h2>RINGKASAN STATISTIK</h2>
    <table class="stats-table">
        <tr>
            <td>
                <div class="stats-label">TOTAL POSTS</div>
                <div class="stats-value"><?= formatNumber($stats['total']) ?></div>
            </td>
            <td>
                <div class="stats-label">PUBLISHED</div>
                <div class="stats-value"><?= formatNumber($stats['published']) ?></div>
            </td>
            <td>
                <div class="stats-label">DRAFT</div>
                <div class="stats-value"><?= formatNumber($stats['draft']) ?></div>
            </td>
            <td>
                <div class="stats-label">TOTAL LIKES</div>
                <div class="stats-value"><?= formatNumber($stats['total_likes']) ?></div>
            </td>
            <td>
                <div class="stats-label">TOTAL KOMENTAR</div>
                <div class="stats-value"><?= formatNumber($stats['total_comments']) ?></div>
            </td>
            <td>
                <div class="stats-label">TOTAL VIEWS</div>
                <div class="stats-value"><?= formatNumber($stats['total_views']) ?></div>
            </td>
        </tr>
    </table>
    
    <!-- Posts List -->
    <h2>DAFTAR POSTS</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 28%;">Judul</th>
                <th style="width: 10%;">Tanggal Post</th>
                <th style="width: 12%;">Kategori</th>
                <th style="width: 12%;">Penulis</th>
                <th style="width: 9%;">Status</th>
                <th style="width: 7%;">Likes</th>
                <th style="width: 9%;">Komentar</th>
                <th style="width: 7%;">Views</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px;">Tidak ada data posts</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($posts as $post): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($post['title']) ?><?= $post['is_featured'] ? ' (Featured)' : '' ?></td>
                        <td class="text-center"><?= formatTanggal($post['created_at'], 'd/m/Y') ?></td>
                        <td><?= htmlspecialchars($post['category_name']) ?></td>
                        <td><?= htmlspecialchars($post['author_name']) ?></td>
                        <td class="text-center"><?= ucfirst($post['status']) ?></td>
                        <td class="text-center"><?= formatNumber($post['like_count']) ?></td>
                        <td class="text-center"><?= formatNumber($post['comment_count']) ?></td>
                        <td class="text-center"><?= formatNumber($post['view_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Category & Author Stats -->
    <div class="mt-20">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <h2>POSTS PER KATEGORI</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama Kategori</th>
                                <th style="width: 20%;" class="text-center">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categoryStats)): ?>
                                <tr>
                                    <td colspan="2" class="text-center">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categoryStats as $cat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cat['name']) ?></td>
                                        <td class="text-center"><strong><?= formatNumber($cat['total']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <h2>TOP 10 PENULIS</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama Penulis</th>
                                <th style="width: 20%;" class="text-center">Posts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($authorStats)): ?>
                                <tr>
                                    <td colspan="2" class="text-center">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($authorStats as $author): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($author['name']) ?></td>
                                        <td class="text-center"><strong><?= formatNumber($author['total']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
