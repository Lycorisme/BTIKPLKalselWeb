<?php
/**
 * PDF Template: Executive Summary Report
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
            font-size: 9pt;
        }
        
        table.data-table td {
            padding: 6px 5px;
            border: 1px solid #000;
            font-size: 9pt;
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
        
        .stats-sub {
            font-size: 8pt;
            color: #666;
            margin-top: 3px;
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
        
        .rank-badge {
            display: inline-block;
            padding: 2px 6px;
            background-color: #f0f0f0;
            border: 1px solid #000;
            font-weight: bold;
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
    <h1>EXECUTIVE SUMMARY REPORT</h1>
    <div class="subtitle">Periode: <?= date('d F Y') ?></div>
    
    <!-- Overview Statistics -->
    <h2>RINGKASAN STATISTIK</h2>
    <table class="stats-grid">
        <tr>
            <td>
                <div class="stats-label">TOTAL POSTS</div>
                <div class="stats-value"><?= formatNumber($overviewStats['total_posts']) ?></div>
                <div class="stats-sub"><?= formatNumber($overviewStats['total_published']) ?> Published</div>
            </td>
            <td>
                <div class="stats-label">TOTAL VIEWS</div>
                <div class="stats-value"><?= formatNumber($overviewStats['total_views']) ?></div>
                <div class="stats-sub">Avg: <?= formatNumber($engagementMetrics['avg_views_per_post']) ?>/post</div>
            </td>
            <td>
                <div class="stats-label">TOTAL USERS</div>
                <div class="stats-value"><?= formatNumber($overviewStats['total_users']) ?></div>
                <div class="stats-sub">Active Contributors</div>
            </td>
            <td>
                <div class="stats-label">ENGAGEMENT RATE</div>
                <div class="stats-value"><?= $engagementMetrics['engagement_rate'] ?>%</div>
                <div class="stats-sub">Likes + Comments</div>
            </td>
        </tr>
    </table>
    
    <table class="stats-grid">
        <tr>
            <td>
                <div class="stats-label">LIKES</div>
                <div class="stats-value"><?= formatNumber($overviewStats['total_likes']) ?></div>
            </td>
            <td>
                <div class="stats-label">COMMENTS</div>
                <div class="stats-value"><?= formatNumber($overviewStats['total_comments']) ?></div>
            </td>
            <td>
                <div class="stats-label">DOWNLOADS</div>
                <div class="stats-value"><?= formatNumber($overviewStats['total_downloads']) ?></div>
            </td>
            <td>
                <div class="stats-label">MESSAGES</div>
                <div class="stats-value"><?= formatNumber($overviewStats['total_messages']) ?></div>
            </td>
        </tr>
    </table>
    
    <!-- Top 10 Posts -->
    <h2>TOP 10 PERFORMING POSTS</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">Rank</th>
                <th style="width: 40%;">Judul Post</th>
                <th style="width: 15%;">Kategori</th>
                <th style="width: 15%;">Penulis</th>
                <th style="width: 10%;">Views</th>
                <th style="width: 8%;">Likes</th>
                <th style="width: 7%;">Comments</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($topPosts)): ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php $rank = 1; foreach ($topPosts as $post): ?>
                    <tr>
                        <td class="text-center">
                            <?php if ($rank <= 3): ?>
                                <span class="rank-badge">
                                    <?php if ($rank == 1): ?>🥇
                                    <?php elseif ($rank == 2): ?>🥈
                                    <?php else: ?>🥉
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <?= $rank ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($post['title']) ?></td>
                        <td><?= htmlspecialchars($post['category_name']) ?></td>
                        <td><?= htmlspecialchars($post['author_name']) ?></td>
                        <td class="text-center"><?= formatNumber($post['view_count']) ?></td>
                        <td class="text-center"><?= formatNumber($post['likes']) ?></td>
                        <td class="text-center"><?= formatNumber($post['comments']) ?></td>
                    </tr>
                    <?php $rank++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Contributors & Category Distribution -->
    <div class="mt-20">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <h3>TOP CONTRIBUTORS</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th class="text-center" style="width: 20%;">Posts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($topContributors, 0, 8) as $contributor): ?>
                                <tr>
                                    <td><?= htmlspecialchars($contributor['name']) ?></td>
                                    <td><?= ucfirst($contributor['role']) ?></td>
                                    <td class="text-center"><strong><?= formatNumber($contributor['total_posts']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <h3>CATEGORY DISTRIBUTION</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-center" style="width: 25%;">Posts</th>
                                <th class="text-center" style="width: 25%;">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryDistribution as $category): ?>
                                <tr>
                                    <td><?= htmlspecialchars($category['name']) ?></td>
                                    <td class="text-center"><strong><?= formatNumber($category['total_posts']) ?></strong></td>
                                    <td class="text-center"><?= formatNumber($category['total_views']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Recent Activities -->
    <h3 class="mt-20">RECENT ACTIVITIES (LAST 10 DAYS)</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Date</th>
                <th class="text-right">Total Activities</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentActivities as $activity): ?>
                <tr>
                    <td><?= formatTanggal($activity['date'], 'd F Y') ?></td>
                    <td class="text-right"><strong><?= formatNumber($activity['total_activities']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
