<?php
/**
 * Report: Laporan Pesan Kontak
 * Menampilkan data log dari tabel contact_messages.
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Pesan Kontak';
$db = Database::getInstance()->getConnection();

// Get filters - Default ke 30 hari terakhir
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$spamIpFilter = $_GET['spam_ip'] ?? ''; // Filter baru
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$exportPdf = $_GET['export_pdf'] ?? '';

// Per page options
$perPageOptions = [10, 25, 50, 100, 200];

// === Build WHERE conditions for Filtering ===
$whereConditions = ["deleted_at IS NULL"];
$params = [];

$whereConditions[] = "DATE(created_at) BETWEEN :date_from AND :date_to";
$params[':date_from'] = $dateFrom;
$params[':date_to'] = $dateTo;

if ($statusFilter) {
    $whereConditions[] = "status = :status";
    $params[':status'] = $statusFilter;
}

if ($searchQuery) {
    $whereConditions[] = "(name LIKE :search OR email LIKE :search OR subject LIKE :search)";
    $params[':search'] = "%$searchQuery%";
}

// Tambahkan filter spam IP baru ke WHERE
if ($spamIpFilter) {
    $whereConditions[] = "ip_address = :spam_ip";
    $params[':spam_ip'] = $spamIpFilter;
}

$whereClause = implode(' AND ', $whereConditions);

// === SUMMARY STATISTICS (4 Kartu) ===
$summaryStats = [
    'total_messages' => 0,
    'unread_messages' => 0,
    'replied_messages' => 0,
    'suspicious_name' => null, // Diganti untuk menyimpan nama
];

// Card 1: Total pesan kontak masuk (Sesuai Filter)
$totalSql = "SELECT COUNT(*) FROM contact_messages WHERE $whereClause";
$totalStmt = $db->prepare($totalSql);
$totalStmt->execute($params);
$totalItems = $totalStmt->fetchColumn();
$summaryStats['total_messages'] = $totalItems;
$totalPages = ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;

// Card 2: Pesan belum dibaca (Sesuai Filter)
$unreadSql = "SELECT COUNT(*) FROM contact_messages WHERE $whereClause AND status = 'unread'";
$unreadStmt = $db->prepare($unreadSql);
$unreadStmt->execute($params);
$summaryStats['unread_messages'] = $unreadStmt->fetchColumn();

// Card 3: Pesan sudah dijawab (Sesuai Filter)
$repliedSql = "SELECT COUNT(*) FROM contact_messages WHERE $whereClause AND status = 'replied'";
$repliedStmt = $db->prepare($repliedSql);
$repliedStmt->execute($params);
$summaryStats['replied_messages'] = $repliedStmt->fetchColumn();
 
// Card 4: Pesan Spam/Suspicious (Global - Hari ini)
// Ambil NAMA pengirim terakhir dari IP yang spam hari ini
$spamSql = "
    SELECT name
    FROM contact_messages
    WHERE ip_address IN (
        SELECT ip_address
        FROM contact_messages
        WHERE deleted_at IS NULL
          AND DATE(created_at) = CURDATE()
        GROUP BY ip_address
        HAVING COUNT(*) >= 3
    )
    AND DATE(created_at) = CURDATE()
    ORDER BY created_at DESC
    LIMIT 1
";
$spamName = $db->query($spamSql)->fetchColumn();
$summaryStats['suspicious_name'] = $spamName;

// === Query for Spam Filter Dropdown ===
// Ambil semua IP spam, nama terakhir mereka, dan jumlah pesannya
$spamListSql = "
    SELECT 
        t1.name, 
        t1.ip_address,
        (SELECT COUNT(*) FROM contact_messages t3 WHERE t3.ip_address = t1.ip_address AND DATE(t3.created_at) = CURDATE()) as msg_count
    FROM contact_messages t1
    JOIN (
        SELECT ip_address, MAX(created_at) as max_created
        FROM contact_messages
        WHERE deleted_at IS NULL AND DATE(created_at) = CURDATE()
        GROUP BY ip_address
        HAVING COUNT(*) >= 3
    ) t2 ON t1.ip_address = t2.ip_address AND t1.created_at = t2.max_created
    ORDER BY t1.name ASC
";
$spamList = $db->query($spamListSql)->fetchAll(PDO::FETCH_ASSOC);


// === MAIN DATA (Paginated) ===
$sql = "
    SELECT id, name, email, subject, phone, status, created_at, ip_address
    FROM contact_messages
    WHERE $whereClause
    ORDER BY created_at DESC
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
        SELECT id, name, email, subject, phone, status, created_at, ip_address
        FROM contact_messages
        WHERE $whereClause
        ORDER BY created_at DESC
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
                    ' . htmlspecialchars($siteName ?? '') . ' - Laporan Pesan Kontak
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include dirname(__FILE__) . '/templates/laporan_contact_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Pesan_Kontak_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= htmlspecialchars($pageTitle) ?></h3>
                <p class="text-subtitle text-muted">Statistik dan daftar pesan yang masuk melalui form kontak.</p>
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
             <div class="col-6 col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-envelope-fill"></i> Pesan Masuk</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_messages']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-envelope-exclamation-fill"></i> Belum Dibaca</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['unread_messages']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-envelope-check-fill"></i> Sudah Dijawab</h6>
                         <h2 class="mb-0"><?= formatNumber($summaryStats['replied_messages']) ?></h2>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-shield-shaded"></i> Spam Terdeteksi</h6>
                        
                        <?php if ($summaryStats['suspicious_name']): ?>
                            <h2 class="mb-0"><?= htmlspecialchars($summaryStats['suspicious_name']) ?></h2>
                        <?php else: ?>
                            <h2 class="mb-0">N/A</h2>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter Laporan (Filter Tanggal berdasarkan Waktu Masuk)</h5>
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
                        <label class="form-label">Nama / Email / Subjek</label>
                        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Cari...">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="unread" <?= $statusFilter == 'unread' ? 'selected' : '' ?>>Unread</option>
                            <option value="read" <?= $statusFilter == 'read' ? 'selected' : '' ?>>Read</option>
                            <option value="replied" <?= $statusFilter == 'replied' ? 'selected' : '' ?>>Replied</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">User Spam (Hari Ini)</label>
                        <select name="spam_ip" class="form-select">
                            <option value="">-- Semua User --</option>
                            <?php foreach ($spamList as $spammer): ?>
                                <option value="<?= htmlspecialchars($spammer['ip_address']) ?>" <?= $spamIpFilter == $spammer['ip_address'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($spammer['name']) ?> (<?= $spammer['msg_count'] ?> pesan)
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
                
                <?php if (($dateFrom != date('Y-m-d', strtotime('-30 days'))) || ($dateTo != date('Y-m-d')) || $statusFilter || $searchQuery || $spamIpFilter): ?>
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
                        <h6 class="mb-1">Laporan Detail Pesan Kontak</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                        <?php if (($dateFrom != date('Y-m-d', strtotime('-30 days'))) || ($dateTo != date('Y-m-d')) || $statusFilter || $searchQuery || $spamIpFilter): ?>
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
                    Daftar Pesan Masuk
                    <span class="badge bg-primary"><?= $totalItems ?> Data</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Pengirim</th>
                                <th>Subjek</th>
                                <th>No. Telpon</th>
                                <th>Status</th>
                                <th>IP Address</th>
                                <th>Waktu Masuk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mainData)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada data pesan</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = $offset + 1; 
                                foreach ($mainData as $row): 
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['name'] ?? '') ?></strong>
                                            <br>
                                            <small><?= htmlspecialchars($row['email'] ?? '') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($row['subject'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <?php if ($row['status'] == 'unread'): ?>
                                                <span class="badge bg-warning">Unread</span>
                                            <?php elseif ($row['status'] == 'read'): ?>
                                                <span class="badge bg-info">Read</span>
                                            <?php else: // replied ?>
                                                <span class="badge bg-success">Replied</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['ip_address'] ?? '-') ?></td>
                                        <td classs="text-center">
                                            <small><?= formatTanggal($row['created_at'], 'd/m/Y H:i') ?></small>
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
                                Halaman <?= $page ?> dari <?= $totalPages ?> &middot; Menampilkan <?= count($mainData) ?> dari <?= $totalItems ?> data
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