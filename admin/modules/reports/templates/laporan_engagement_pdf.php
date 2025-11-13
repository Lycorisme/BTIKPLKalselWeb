<?php
/**
 * PDF Template: Engagement Report
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
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0 5px 0;
            letter-spacing: 1px;
        }
        
        .subtitle {
            text-align: center;
            font-size: 10pt;
            color: #666;
            margin-bottom: 20px;
        }
        
        h2 {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 3px;
        }
        
        h3 {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
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
        
        /* Stats Grid */
        .stats-grid {
            width: 100%;
            margin: 15px 0;
        }
        
        .stats-grid td {
            padding: 12px;
            text-align: center;
            border: 1px solid #000;
            background-color: #f9f9f9;
            width: 25%;
        }
        
        .stats-label {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .stats-value {
            font-size: 18pt;
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
    <h1>ENGAGEMENT REPORT</h1>
    <div class="subtitle">Periode: <?= formatTanggal($dateFrom, 'd F Y') ?> - <?= formatTanggal($dateTo, 'd F Y') ?></div>
    
    <!-- Overall Metrics -->
    <h2>OVERALL ENGAGEMENT METRICS</h2>
    <table class="stats-grid">
        <tr>
            <td>
                <div class="stats-label">TOTAL VIEWS</div>
                <div class="stats-value"><?= formatNumber($overallMetrics['total_views']) ?></div>
            </td>
            <td>
                <div class="stats-label">TOTAL LIKES</div>
                <div class="stats-value"><?= formatNumber($overallMetrics['total_likes']) ?></div>
            </td>
            <td>
                <div class="stats-label">TOTAL COMMENTS</div>
                <div class="stats-value"><?= formatNumber($overallMetrics['total_comments']) ?></div>
            </td>
            <td>
                <div class="stats-label">ENGAGEMENT RATE</div>
                <div class="stats-value"><?= $overallMetrics['avg_engagement_rate'] ?>%</div>
            </td>
        </tr>
    </table>
    
    <!-- Top Engaging Posts -->
    <h2>TOP 10 MOST ENGAGING POSTS</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">Rank</th>
                <th style="width: 40%;">Judul Post</th>
                <th style="width: 15%;">Kategori</th>
                <th style="width: 10%;">Views</th>
                <th style="width: 10%;">Likes</th>
                <th style="width: 10%;">Comments</th>
                <th style="width: 10%;">Score</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($topEngagingPosts)): ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php $rank = 1; foreach ($topEngagingPosts as $post): ?>
                    <tr>
                        <td class="text-center"><?= $rank++ ?></td>
                        <td><?= htmlspecialchars($post['title']) ?></td>
                        <td><?= htmlspecialchars($post['category_name']) ?></td>
                        <td class="text-center"><?= formatNumber($post['view_count']) ?></td>
                        <td class="text-center"><?= formatNumber($post['likes']) ?></td>
                        <td class="text-center"><?= formatNumber($post['comments']) ?></td>
                        <td class="text-center"><strong><?= formatNumber($post['engagement_score']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Category Engagement -->
    <h2 class="mt-20">ENGAGEMENT BY CATEGORY</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Category</th>
                <th class="text-right">Views</th>
                <th class="text-right">Likes</th>
                <th class="text-right">Comments</th>
                <th class="text-right">Rate</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categoryEngagement as $cat): ?>
                <tr>
                    <td><?= htmlspecialchars($cat['name']) ?></td>
                    <td class="text-right"><?= formatNumber($cat['total_views']) ?></td>
                    <td class="text-right"><?= formatNumber($cat['total_likes']) ?></td>
                    <td class="text-right"><?= formatNumber($cat['total_comments']) ?></td>
                    <td class="text-right"><strong><?= $cat['engagement_rate'] ?>%</strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Most Liked & Most Commented -->
    <div class="mt-20">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <h3>MOST LIKED POSTS</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Post Title</th>
                                <th style="width: 25%;" class="text-center">Likes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mostLikedPosts)): ?>
                                <tr>
                                    <td colspan="2" class="text-center">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($mostLikedPosts, 0, 8) as $post): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($post['title']) ?></td>
                                        <td class="text-center"><strong><?= formatNumber($post['total_likes']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <h3>MOST COMMENTED POSTS</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Post Title</th>
                                <th style="width: 25%;" class="text-center">Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mostCommentedPosts)): ?>
                                <tr>
                                    <td colspan="2" class="text-center">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($mostCommentedPosts, 0, 8) as $post): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($post['title']) ?></td>
                                        <td class="text-center"><strong><?= formatNumber($post['total_comments']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Top Commenters -->
    <h3 class="mt-20">TOP COMMENTERS</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th class="text-right" style="width: 20%;">Total Comments</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($topCommenters)): ?>
                <tr>
                    <td colspan="3" class="text-center">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php foreach ($topCommenters as $commenter): ?>
                    <tr>
                        <td><?= htmlspecialchars($commenter['name']) ?></td>
                        <td><?= htmlspecialchars($commenter['email']) ?></td>
                        <td class="text-right"><strong><?= formatNumber($commenter['total_comments']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Top Downloads -->
    <h3 class="mt-20">TOP 10 DOWNLOADED FILES</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Rank</th>
                <th style="width: 60%;">File Title</th>
                <th style="width: 15%;">Type</th>
                <th style="width: 15%;" class="text-right">Downloads</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($topDownloads)): ?>
                <tr>
                    <td colspan="4" class="text-center">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php $rank = 1; foreach ($topDownloads as $file): ?>
                    <tr>
                        <td class="text-center"><?= $rank++ ?></td>
                        <td><?= htmlspecialchars($file['title']) ?></td>
                        <td class="text-center"><?= strtoupper($file['file_type']) ?></td>
                        <td class="text-right"><strong><?= formatNumber($file['download_count']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
