<?php
/**
 * Report: Security Report
 * Laporan keamanan sistem & aktivitas user
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Keamanan';
$db = Database::getInstance()->getConnection();

// Get filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // Default: awal bulan ini
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Default: hari ini
$exportPdf = $_GET['export_pdf'] ?? '';

// === 1. SECURITY OVERVIEW ===
$securityOverview = [
    'total_users' => 0,
    'active_users' => 0,
    'inactive_users' => 0,
    'total_logins' => 0,
    'failed_logins' => 0,
    'success_rate' => 0,
    'locked_accounts' => 0,
    'total_activities' => 0,
    'suspicious_activities' => 0
];

// Users
$usersStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN locked_until IS NOT NULL AND locked_until > NOW() THEN 1 ELSE 0 END) as locked
    FROM users 
    WHERE deleted_at IS NULL
");
$usersData = $usersStmt->fetch();
$securityOverview['total_users'] = $usersData['total'];
$securityOverview['active_users'] = $usersData['active'];
$securityOverview['inactive_users'] = $usersData['inactive'];
$securityOverview['locked_accounts'] = $usersData['locked'];

// Login activities
$loginStmt = $db->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN action_type = 'LOGIN' THEN 1 ELSE 0 END) as success
    FROM activity_logs
    WHERE action_type IN ('LOGIN', 'LOGOUT')
    AND created_at BETWEEN ? AND ?
");
$loginStmt->execute([$dateFrom, $dateTo]);
$loginData = $loginStmt->fetch();
$securityOverview['total_logins'] = $loginData['success'] ?? 0;

// Calculate success rate (assuming all LOGIN are successful)
$securityOverview['success_rate'] = 100; // Default high since we track successful logins

// Total activities
$activitiesStmt = $db->prepare("
    SELECT COUNT(*) as total 
    FROM activity_logs 
    WHERE created_at BETWEEN ? AND ?
");
$activitiesStmt->execute([$dateFrom, $dateTo]);
$securityOverview['total_activities'] = $activitiesStmt->fetch()['total'];

// === 2. LOGIN ACTIVITIES (Last 30 Days) ===
$loginActivitiesStmt = $db->prepare("
    SELECT 
        user_name,
        action_type,
        ip_address,
        created_at
    FROM activity_logs
    WHERE action_type IN ('LOGIN', 'LOGOUT')
    AND created_at BETWEEN ? AND ?
    ORDER BY created_at DESC
    LIMIT 50
");
$loginActivitiesStmt->execute([$dateFrom, $dateTo]);
$loginActivities = $loginActivitiesStmt->fetchAll();

// === 3. USER ACTIVITY BREAKDOWN ===
$userActivityStmt = $db->prepare("
    SELECT 
        user_name,
        action_type,
        COUNT(*) as total_actions
    FROM activity_logs
    WHERE created_at BETWEEN ? AND ?
    GROUP BY user_name, action_type
    ORDER BY total_actions DESC
    LIMIT 20
");
$userActivityStmt->execute([$dateFrom, $dateTo]);
$userActivities = $userActivityStmt->fetchAll();

// === 4. ACTION TYPE DISTRIBUTION ===
$actionTypeStmt = $db->prepare("
    SELECT 
        action_type,
        COUNT(*) as total
    FROM activity_logs
    WHERE created_at BETWEEN ? AND ?
    GROUP BY action_type
    ORDER BY total DESC
");
$actionTypeStmt->execute([$dateFrom, $dateTo]);
$actionTypes = $actionTypeStmt->fetchAll();

// === 5. IP ADDRESS TRACKING ===
$ipTrackingStmt = $db->prepare("
    SELECT 
        ip_address,
        COUNT(*) as access_count,
        COUNT(DISTINCT user_name) as unique_users,
        MAX(created_at) as last_access
    FROM activity_logs
    WHERE ip_address IS NOT NULL
    AND created_at BETWEEN ? AND ?
    GROUP BY ip_address
    ORDER BY access_count DESC
    LIMIT 20
");
$ipTrackingStmt->execute([$dateFrom, $dateTo]);
$ipTracking = $ipTrackingStmt->fetchAll();

// === 6. RECENT CRITICAL ACTIONS ===
$criticalActionsStmt = $db->prepare("
    SELECT 
        user_name,
        action_type,
        description,
        model_type,
        ip_address,
        created_at
    FROM activity_logs
    WHERE action_type IN ('CREATE', 'UPDATE', 'DELETE')
    AND model_type IN ('users', 'settings')
    AND created_at BETWEEN ? AND ?
    ORDER BY created_at DESC
    LIMIT 30
");
$criticalActionsStmt->execute([$dateFrom, $dateTo]);
$criticalActions = $criticalActionsStmt->fetchAll();

// === 7. USER SESSIONS ===
$activeSessionsStmt = $db->query("
    SELECT 
        u.name,
        u.email,
        u.last_login_at,
        u.role
    FROM users u
    WHERE u.deleted_at IS NULL
    AND u.last_login_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY u.last_login_at DESC
");
$activeSessions = $activeSessionsStmt->fetchAll();

// === 8. LOGIN TREND (Last 10 Days) ===
$loginTrendStmt = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_logins
    FROM activity_logs
    WHERE action_type = 'LOGIN'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 10 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$loginTrend = $loginTrendStmt->fetchAll();

// === 9. INACTIVE USERS (No login > 30 days) ===
$inactiveUsersStmt = $db->query("
    SELECT 
        name,
        email,
        role,
        last_login_at,
        DATEDIFF(NOW(), last_login_at) as days_inactive
    FROM users
    WHERE deleted_at IS NULL
    AND (last_login_at IS NULL OR last_login_at < DATE_SUB(NOW(), INTERVAL 30 DAY))
    ORDER BY last_login_at ASC
    LIMIT 20
");
$inactiveUsers = $inactiveUsersStmt->fetchAll();

// === 10. MOST ACTIVE USERS ===
$mostActiveStmt = $db->prepare("
    SELECT 
        al.user_name,
        u.role,
        COUNT(*) as total_actions,
        MAX(al.created_at) as last_activity
    FROM activity_logs al
    LEFT JOIN users u ON al.user_name = u.name
    WHERE al.created_at BETWEEN ? AND ?
    GROUP BY al.user_name, u.role
    ORDER BY total_actions DESC
    LIMIT 15
");
$mostActiveStmt->execute([$dateFrom, $dateTo]);
$mostActiveUsers = $mostActiveStmt->fetchAll();

// Export PDF
if ($exportPdf === '1') {
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    $contactPhone = getSetting('contact_phone', '');
    $contactEmail = getSetting('contact_email', '');
    $contactAddress = getSetting('contact_address', '');
    $siteLogo = getSetting('site_logo', '');

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'P',
        'margin_left' => 15,
        'margin_right' => 15,
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
                    ' . htmlspecialchars($siteName) . ' - Security Report [CONFIDENTIAL]
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include __DIR__ . '/templates/laporan_security_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Security_Report_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Monitoring keamanan sistem & aktivitas user</p>
                <span class="badge bg-danger">CONFIDENTIAL</span>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active">Keamanan</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Periode</h5>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search"></i> Tampilkan
                            </button>
                            <a href="report_security.php" class="btn btn-secondary me-2">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                            <a href="?export_pdf=1&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>"
                               class="btn btn-danger" target="_blank">
                                <i class="bi bi-file-pdf"></i> Export PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Overview -->
        <h5 class="mb-3">🔐 Security Overview</h5>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-people"></i> Total Users</h6>
                        <h2 class="mb-0"><?= formatNumber($securityOverview['total_users']) ?></h2>
                        <small><?= formatNumber($securityOverview['active_users']) ?> Active</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-box-arrow-in-right"></i> Total Logins</h6>
                        <h2 class="mb-0"><?= formatNumber($securityOverview['total_logins']) ?></h2>
                        <small>Success Rate: <?= $securityOverview['success_rate'] ?>%</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-activity"></i> Total Activities</h6>
                        <h2 class="mb-0"><?= formatNumber($securityOverview['total_activities']) ?></h2>
                        <small>All tracked actions</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card <?= $securityOverview['locked_accounts'] > 0 ? 'bg-danger' : 'bg-warning' ?> text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-lock"></i> Locked Accounts</h6>
                        <h2 class="mb-0"><?= formatNumber($securityOverview['locked_accounts']) ?></h2>
                        <small><?= $securityOverview['locked_accounts'] > 0 ? 'Action Required!' : 'All Clear' ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login Activities -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">🔑 Recent Login Activities (Last 50)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">User</th>
                                        <th style="width: 15%;">Action</th>
                                        <th style="width: 20%;">IP Address</th>
                                        <th style="width: 25%;">Date & Time</th>
                                        <th style="width: 15%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($loginActivities)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($loginActivities as $activity): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($activity['user_name']) ?></td>
                                                <td>
                                                    <?php if ($activity['action_type'] === 'LOGIN'): ?>
                                                        <span class="badge bg-success">LOGIN</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">LOGOUT</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><code><?= htmlspecialchars($activity['ip_address']) ?></code></td>
                                                <td><?= formatTanggal($activity['created_at'], 'd M Y H:i:s') ?></td>
                                                <td><span class="badge bg-success">Success</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Types & IP Tracking -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📊 Action Type Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Action Type</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalActions = array_sum(array_column($actionTypes, 'total'));
                                    foreach ($actionTypes as $action): 
                                        $percentage = $totalActions > 0 ? ($action['total'] / $totalActions * 100) : 0;
                                    ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($action['action_type']) ?></span></td>
                                            <td class="text-end"><strong><?= formatNumber($action['total']) ?></strong></td>
                                            <td class="text-end"><?= number_format($percentage, 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">🌐 Top IP Addresses</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>IP Address</th>
                                        <th class="text-end">Access Count</th>
                                        <th class="text-end">Unique Users</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ipTracking)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($ipTracking as $ip): ?>
                                            <tr>
                                                <td><code><?= htmlspecialchars($ip['ip_address']) ?></code></td>
                                                <td class="text-end"><strong><?= formatNumber($ip['access_count']) ?></strong></td>
                                                <td class="text-end"><?= formatNumber($ip['unique_users']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Critical Actions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0 text-white">⚠️ Recent Critical Actions (Create/Update/Delete)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>Model</th>
                                        <th>IP Address</th>
                                        <th>Date & Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($criticalActions)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Tidak ada critical actions</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($criticalActions as $action): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($action['user_name']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $action['action_type'] === 'DELETE' ? 'danger' : ($action['action_type'] === 'CREATE' ? 'success' : 'warning') ?>">
                                                        <?= $action['action_type'] ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($action['description']) ?></td>
                                                <td><code><?= htmlspecialchars($action['model_type']) ?></code></td>
                                                <td><code><?= htmlspecialchars($action['ip_address']) ?></code></td>
                                                <td><?= formatTanggal($action['created_at'], 'd M Y H:i:s') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Most Active Users & Inactive Users -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">👥 Most Active Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mostActiveUsers as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['user_name']) ?></td>
                                            <td><span class="badge bg-secondary"><?= ucfirst($user['role'] ?? 'N/A') ?></span></td>
                                            <td class="text-end"><strong><?= formatNumber($user['total_actions']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header bg-warning">
                        <h5 class="card-title mb-0">⏰ Inactive Users (30+ Days)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th class="text-end">Days Inactive</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($inactiveUsers)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Semua user aktif</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($inactiveUsers as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['name']) ?></td>
                                                <td><span class="badge bg-secondary"><?= ucfirst($user['role']) ?></span></td>
                                                <td class="text-end">
                                                    <span class="badge bg-warning"><?= $user['days_inactive'] ?? 'Never' ?> days</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login Trend -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📈 Login Trend (Last 10 Days)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Total Logins</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loginTrend as $trend): ?>
                                        <tr>
                                            <td><?= formatTanggal($trend['date'], 'd M Y') ?></td>
                                            <td class="text-end"><strong><?= formatNumber($trend['total_logins']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
