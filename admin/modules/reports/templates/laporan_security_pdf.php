<?php
/**
 * PDF Template: Security Report
 * Clean black & white design - Portrait - CONFIDENTIAL
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
        
        .confidential {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            color: #cc0000;
            margin-bottom: 20px;
            padding: 10px;
            border: 2px solid #cc0000;
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
        
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #000;
            padding: 10px;
            margin: 10px 0;
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
    <h1>SECURITY REPORT</h1>
    <div class="subtitle">Periode: <?= formatTanggal($dateFrom, 'd F Y') ?> - <?= formatTanggal($dateTo, 'd F Y') ?></div>
    <div class="confidential">⚠️ CONFIDENTIAL - FOR AUTHORIZED PERSONNEL ONLY ⚠️</div>
    
    <!-- Security Overview -->
    <h2>SECURITY OVERVIEW</h2>
    <table class="stats-grid">
        <tr>
            <td>
                <div class="stats-label">TOTAL USERS</div>
                <div class="stats-value"><?= formatNumber($securityOverview['total_users']) ?></div>
                <div class="stats-sub"><?= formatNumber($securityOverview['active_users']) ?> Active</div>
            </td>
            <td>
                <div class="stats-label">TOTAL LOGINS</div>
                <div class="stats-value"><?= formatNumber($securityOverview['total_logins']) ?></div>
                <div class="stats-sub">Success Rate: <?= $securityOverview['success_rate'] ?>%</div>
            </td>
            <td>
                <div class="stats-label">ACTIVITIES</div>
                <div class="stats-value"><?= formatNumber($securityOverview['total_activities']) ?></div>
                <div class="stats-sub">All tracked</div>
            </td>
            <td>
                <div class="stats-label">LOCKED ACCOUNTS</div>
                <div class="stats-value"><?= formatNumber($securityOverview['locked_accounts']) ?></div>
                <div class="stats-sub"><?= $securityOverview['locked_accounts'] > 0 ? 'Action Required!' : 'All Clear' ?></div>
            </td>
        </tr>
    </table>
    
    <?php if ($securityOverview['locked_accounts'] > 0): ?>
        <div class="warning-box">
            <strong>⚠️ ALERT:</strong> <?= $securityOverview['locked_accounts'] ?> account(s) are currently locked. Review and take appropriate action.
        </div>
    <?php endif; ?>
    
    <!-- Recent Login Activities -->
    <h2>RECENT LOGIN ACTIVITIES (LAST 30)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">User</th>
                <th style="width: 12%;">Action</th>
                <th style="width: 18%;">IP Address</th>
                <th style="width: 30%;">Date & Time</th>
                <th style="width: 15%;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($loginActivities)): ?>
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px;">No login activities recorded</td>
                </tr>
            <?php else: ?>
                <?php foreach (array_slice($loginActivities, 0, 30) as $activity): ?>
                    <tr>
                        <td><?= htmlspecialchars($activity['user_name']) ?></td>
                        <td><?= $activity['action_type'] ?></td>
                        <td><?= htmlspecialchars($activity['ip_address']) ?></td>
                        <td><?= formatTanggal($activity['created_at'], 'd M Y H:i:s') ?></td>
                        <td>Success</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Action Types & IP Tracking -->
    <div class="mt-20">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <h3>ACTION TYPE DISTRIBUTION</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Action Type</th>
                                <th class="text-right" style="width: 25%;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actionTypes as $action): ?>
                                <tr>
                                    <td><?= htmlspecialchars($action['action_type']) ?></td>
                                    <td class="text-right"><strong><?= formatNumber($action['total']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <h3>TOP IP ADDRESSES</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th class="text-right" style="width: 25%;">Access</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ipTracking)): ?>
                                <tr>
                                    <td colspan="2" class="text-center">No data</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($ipTracking, 0, 10) as $ip): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ip['ip_address']) ?></td>
                                        <td class="text-right"><strong><?= formatNumber($ip['access_count']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Critical Actions -->
    <h2 class="mt-20">CRITICAL ACTIONS (CREATE/UPDATE/DELETE)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 18%;">User</th>
                <th style="width: 10%;">Action</th>
                <th style="width: 32%;">Description</th>
                <th style="width: 15%;">Model</th>
                <th style="width: 25%;">Date & Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($criticalActions)): ?>
                <tr>
                    <td colspan="5" class="text-center">No critical actions recorded</td>
                </tr>
            <?php else: ?>
                <?php foreach (array_slice($criticalActions, 0, 20) as $action): ?>
                    <tr>
                        <td><?= htmlspecialchars($action['user_name']) ?></td>
                        <td><?= $action['action_type'] ?></td>
                        <td><?= htmlspecialchars($action['description']) ?></td>
                        <td><?= htmlspecialchars($action['model_type']) ?></td>
                        <td><?= formatTanggal($action['created_at'], 'd M Y H:i:s') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Most Active & Inactive Users -->
    <div class="mt-20">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <h3>MOST ACTIVE USERS</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th class="text-right" style="width: 20%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($mostActiveUsers, 0, 10) as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['user_name']) ?></td>
                                    <td><?= ucfirst($user['role'] ?? 'N/A') ?></td>
                                    <td class="text-right"><strong><?= formatNumber($user['total_actions']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <h3>INACTIVE USERS (30+ DAYS)</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th class="text-right" style="width: 25%;">Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inactiveUsers)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">All users active</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($inactiveUsers, 0, 10) as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['name']) ?></td>
                                        <td><?= ucfirst($user['role']) ?></td>
                                        <td class="text-right"><?= $user['days_inactive'] ?? 'Never' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Login Trend -->
    <h3 class="mt-20">LOGIN TREND (LAST 10 DAYS)</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 50%;">Date</th>
                <th class="text-right">Total Logins</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($loginTrend as $trend): ?>
                <tr>
                    <td><?= formatTanggal($trend['date'], 'd F Y') ?></td>
                    <td class="text-right"><strong><?= formatNumber($trend['total_logins']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
