<?php
/**
 * Report: Laporan Aktivitas Sistem
 * Menampilkan data log dari tabel activity_logs.
 * Modifikasi: Menambahkan filter anomali & mengubah kartu anomali.
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

 $pageTitle = 'Laporan Aktivitas Sistem';
 $db = Database::getInstance()->getConnection();

// Get filters - Default ke 30 hari terakhir
 $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
 $dateTo = $_GET['date_to'] ?? date('Y-m-d');
 $userId = $_GET['user_id'] ?? '';
 $actionType = $_GET['action_type'] ?? '';
 $ipFilter = $_GET['ip_address'] ?? '';
 $anomalyUserId = $_GET['anomaly_user_id'] ?? ''; // Filter Anomali Baru
 $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
 $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
 $exportPdf = $_GET['export_pdf'] ?? '';

// Per page options
 $perPageOptions = [10, 25, 50, 100, 200];

// Get users for filter (Semua user)
 $usersStmt = $db->query("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name ASC");
 $users = $usersStmt->fetchAll();

// Get action types for filter
 $actionTypesStmt = $db->query("SELECT DISTINCT action_type FROM activity_logs WHERE action_type IS NOT NULL AND action_type != '' ORDER BY action_type ASC");
 $actionTypes = $actionTypesStmt->fetchAll(PDO::FETCH_COLUMN);

// MODIFIKASI: Get users untuk filter anomali (Hanya user anomali)
 $anomalyUsersStmt = $db->query("
    SELECT DISTINCT u.id, u.name
    FROM users u
    JOIN activity_logs al ON u.id = al.user_id
    WHERE al.action_type = 'LOGIN'
      AND (u.is_active IN (0, 3) OR u.is_active IS NULL)
    ORDER BY u.name ASC
");
 $anomalyUsers = $anomalyUsersStmt->fetchAll();

// Build WHERE conditions
 $whereConditions = ["DATE(al.created_at) BETWEEN :date_from AND :date_to"];
 $params = [
    ':date_from' => $dateFrom,
    ':date_to' => $dateTo
];

// Teks untuk sub-judul kartu
 $filterText = "(Sesuai Filter)";
 $filterUserName = '';
if ($userId) {
    $whereConditions[] = "al.user_id = :user_id";
    $params[':user_id'] = $userId;
    
    foreach ($users as $user) {
        if ($user['id'] == $userId) {
            $filterUserName = $user['name'];
            $filterText = "(User: " . htmlspecialchars($filterUserName) . ")";
            break;
        }
    }
}

if ($actionType) {
    $whereConditions[] = "al.action_type = :action_type";
    $params[':action_type'] = $actionType;
    $filterText = ($filterUserName ? $filterText . "" : "(Sesuai Filter)");
}

if ($ipFilter) {
    $whereConditions[] = "al.ip_address = :ip_address";
    $params[':ip_address'] = $ipFilter;
    $filterText = ($filterUserName || $actionType ? $filterText . " & IP" : "(Sesuai Filter)");
}

// *** MODIFIKASI: Logika Filter Anomali ***
if ($anomalyUserId) {
    $whereConditions[] = "al.user_id = :anomaly_user_id";
    $params[':anomaly_user_id'] = $anomalyUserId;
    
    $anomalyUserName = '';
    foreach ($anomalyUsers as $anomUser) {
        if ($anomUser['id'] == $anomalyUserId) {
            $anomalyUserName = $anomUser['name'];
            break;
        }
    }
    
    if ($filterText === "(Sesuai Filter)") {
        $filterText = "(Anomali: " . htmlspecialchars($anomalyUserName) . ")";
    } else {
        $filterText .= " & Anomali: " . htmlspecialchars($anomalyUserName);
    }
}


 $whereClause = implode(' AND ', $whereConditions);

// === SUMMARY STATISTICS (4 Kartu) ===
 $summaryStats = [
    'total_actions' => 0,
    'top_user_name' => 'N/A',
    'top_user_count' => 0,
    'top_action_name' => 'N/A',
    'top_action_count' => 0,
    'dangerous_logins_count' => 0,
];

// Card 1: Total aksi (sesuai filter)
 $totalSql = "SELECT COUNT(*) 
              FROM activity_logs al 
              LEFT JOIN users u ON al.user_id = u.id
              WHERE $whereClause";
 $totalStmt = $db->prepare($totalSql);
 $totalStmt->execute($params);
 $totalItems = $totalStmt->fetchColumn();
 $summaryStats['total_actions'] = $totalItems;
 $totalPages = ceil($totalItems / $perPage);
 $offset = ($page - 1) * $perPage;

// Card 2: Pengguna paling aktif (sesuai filter)
 $topUserSql = "SELECT al.user_name, COUNT(*) as count 
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE $whereClause AND al.user_name IS NOT NULL 
                GROUP BY al.user_name 
                ORDER BY count DESC 
                LIMIT 1";
 $topUserStmt = $db->prepare($topUserSql);
 $topUserStmt->execute($params);
 $topUser = $topUserStmt->fetch();
 if ($topUser) {
    $summaryStats['top_user_name'] = $topUser['user_name'];
    $summaryStats['top_user_count'] = $topUser['count'];
 }

// Card 3: Aksi paling sering dipakai (sesuai filter)
 $topActionSql = "SELECT al.action_type, COUNT(*) as count 
                  FROM activity_logs al
                  LEFT JOIN users u ON al.user_id = u.id
                  WHERE $whereClause 
                  GROUP BY al.action_type 
                  ORDER BY count DESC 
                  LIMIT 1";
 $topActionStmt = $db->prepare($topActionSql);
 $topActionStmt->execute($params);
 $topAction = $topActionStmt->fetch();
 if ($topAction) {
    $summaryStats['top_action_name'] = $topAction['action_type'];
    $summaryStats['top_action_count'] = $topAction['count'];
 }

// Card 4: Total Anomali Terdeteksi (Hanya filter tanggal)
 $paramsDateOnly = [':date_from' => $dateFrom, ':date_to' => $dateTo];
 $dangerSql = "
    SELECT COUNT(DISTINCT al.user_id)
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    WHERE DATE(al.created_at) BETWEEN :date_from AND :date_to
      AND al.action_type = 'LOGIN'
      AND (u.is_active IN (0, 3) OR u.is_active IS NULL)
";
 $dangerStmt = $db->prepare($dangerSql);
 $dangerStmt->execute($paramsDateOnly);
 $summaryStats['dangerous_logins_count'] = $dangerStmt->fetchColumn();


// === MAIN DATA (Paginated) ===
 $sql = "
    SELECT al.id, al.created_at, al.user_name, al.action_type, al.description, al.model_type, al.model_id, al.ip_address, al.user_agent
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE $whereClause
    ORDER BY al.created_at DESC
    LIMIT :limit OFFSET :offset
";

 $mainDataStmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $mainDataStmt->bindValue($key, $value);
}
 $mainDataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
 $mainDataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
 $mainDataStmt->execute();
 $mainData = $mainDataStmt->fetchAll();

// Export PDF (export all data without pagination)
if ($exportPdf === '1') {
    
    $pdfSql = "
        SELECT al.id, al.created_at, al.user_name, al.action_type, al.description, al.model_type, al.model_id, al.ip_address, al.user_agent
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE $whereClause
        ORDER BY al.created_at DESC
    ";
    $pdfDataStmt = $db->prepare($pdfSql);
    $pdfDataStmt->execute($params);
    $mainData = $pdfDataStmt->fetchAll(); // Override mainData for PDF export
    
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    $contactPhone = getSetting('contact_phone', '');
    $contactEmail = getSetting('contact_email', '');
    $contactAddress = getSetting('contact_address', '');
    $siteLogo = getSetting('site_logo', '');

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'L', // Landscape
        'margin_left' => 10,
        'margin_right' => 10,
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
                    ' . htmlspecialchars($siteName ?? '') . ' - Laporan Aktivitas Sistem
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include dirname(__FILE__) . '/templates/laporan_activities_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Aktivitas_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= htmlspecialchars($pageTitle) ?></h3>
                <p class="text-subtitle text-muted">Mencatat semua aktivitas pengguna di sistem.</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($pageTitle) ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row mb-3">

            <!-- Card 1 -->
            <div class="col-6 col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-activity"></i> Total Aksi</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_actions']) ?></h2>
                        <small><?= $filterText ?></small>

                        <!-- Baris tambahan untuk simetris -->
                        <div style="visibility:hidden;">(0)</div>

                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="col-6 col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-person-fill"></i> Paling Aktif</h6>
                        <h4 class="mb-0 text-white" title="<?= htmlspecialchars($summaryStats['top_user_name']) ?>">
                            <?= htmlspecialchars($summaryStats['top_user_name']) ?>
                        </h4>
                        <small>(<?= formatNumber($summaryStats['top_user_count']) ?> Aksi)</small>

                        <!-- Baris tambahan untuk simetris -->
                        <div style="visibility:hidden;">(0)</div>

                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="col-6 col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-hand-index-thumb-fill"></i> Aksi Paling Sering</h6>

                        <h4 class="mb-0 text-white"><?= htmlspecialchars($summaryStats['top_action_name']) ?></h4>
                        <small>(<?= formatNumber($summaryStats['top_action_count']) ?> Kali)</small>
                        <!-- Baris tambahan untuk simetris -->
                        <div style="visibility:hidden;">(0)</div>
                    </div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="col-6 col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-exclamation-triangle-fill"></i> Total Anomali Terdeteksi</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['dangerous_logins_count']) ?></h2>

                        <!-- Baris tambahan biar sama tinggi -->
                        <div style="visibility:hidden;">(0)</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Pengguna (Semua)</label>
                        <select name="user_id" class="form-select">
                            <option value="">-- Semua Pengguna --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= $userId == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tipe Aksi</label>
                        <select name="action_type" class="form-select">
                            <option value="">-- Semua Aksi --</option>
                            <?php foreach ($actionTypes as $type): ?>
                                <option value="<?= $type ?>" <?= $actionType == $type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" class="form-control" value="<?= htmlspecialchars($ipFilter) ?>" placeholder="Cari IP Address...">
                    </div>
                    
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tampilkan Anomali</label>
                        <select name="anomaly_user_id" class="form-select">
                            <option value="">-- Tidak Aktif --</option>
                            <?php foreach ($anomalyUsers as $anomUser): ?>
                                <option value="<?= $anomUser['id'] ?>" <?= $anomalyUserId == $anomUser['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($anomUser['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-6 col-md-3">
                        <label class="form-label">Per Hal</label>
                        <select name="per_page" class="form-select">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage == $n ? ' selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </form>
                
                <?php if (($dateFrom != date('Y-m-d', strtotime('-30 days'))) || ($dateTo != date('Y-m-d')) || $userId || $actionType || $ipFilter || $anomalyUserId): ?>
                    <div class="mt-2">
                        <a href="?" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset Filter
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-column flex-md-row gap-2">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">Laporan Detail Aktivitas</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                        <?php if (($dateFrom != date('Y-m-d', strtotime('-30 days'))) || ($dateTo != date('Y-m-d')) || $userId || $actionType || $ipFilter || $anomalyUserId): ?>
                            <br><span class="badge bg-info mt-1">Filter Aktif</span>
                        <?php endif; ?>
                    </div>

                    <a href="?export_pdf=1<?= http_build_query(array_filter($_GET, fn($key) => $key != 'page', ARRAY_FILTER_USE_KEY)) ?>"
                       class="btn btn-danger flex-shrink-0"
                       target="_blank">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-bottom-0">
                <h5 class="card-title mb-0">
                    Log Aktivitas Sistem
                    <span class="badge bg-primary"><?= $totalItems ?> Data</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Tipe Aksi</th>
                                <th>Model</th>
                                <th>Deskripsi</th>
                                <th>IP</th>
                                <th>User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mainData)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Tidak ada data aktivitas</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = $offset + 1; 
                                foreach ($mainData as $row): 
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no ?></td>
                                        <td><small><?= formatTanggal($row['created_at'], 'd/m/Y H:i') ?></small></td>
                                        <td><?= htmlspecialchars($row['user_name'] ?? 'Guest') ?></td>
                                        <td>
                                            <span class="badge bg-light-primary"><?= htmlspecialchars($row['action_type'] ?? '') ?></span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($row['model_type'] ?? '-') ?>
                                            <?= $row['model_id'] ? ' (ID: ' . $row['model_id'] . ')' : '' ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['ip_address'] ?? '') ?></td>
                                        <td>
                                            <small title="<?= htmlspecialchars($row['user_agent'] ?? '') ?>">
                                                <?= htmlspecialchars(substr($row['user_agent'] ?? '', 0, 40)) . '...' ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php $no++; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalItems > 0): ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                        <div class="flex-grow-1">
                            <small class="text-muted">
                                Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($mainData) ?> dari <?= $totalItems ?> data
                            </small>
                        </div>
                        <nav aria-label="Page navigation" class="flex-shrink-0">
                            <ul class="pagination mb-0">
                                <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php
                                $from = max(1, $page - 2);
                                $to = min($totalPages, $page + 2);
                                for ($i = $from; $i <= $to; $i++): ?>
                                    <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item<?= $page >= $totalPages ? ' disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>