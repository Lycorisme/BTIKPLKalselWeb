<?php
/**
 * Report: Laporan Posts
 * Sesuai standar laporan executive
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

 $pageTitle = 'Laporan Posts';
 $db = Database::getInstance()->getConnection();

// Get filters
 $dateFrom = $_GET['date_from'] ?? '';
 $dateTo = $_GET['date_to'] ?? '';
 $categoryId = $_GET['category_id'] ?? '';
 $authorId = $_GET['author_id'] ?? '';
 $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
 $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
 $exportPdf = $_GET['export_pdf'] ?? '';

// Per page options
 $perPageOptions = [10, 25, 50, 100, 200];

// Get categories for filter
 $categoriesStmt = $db->query("SELECT id, name FROM post_categories WHERE deleted_at IS NULL ORDER BY name ASC");
 $categories = $categoriesStmt->fetchAll();

// Get authors for filter
 $authorsStmt = $db->query("SELECT id, name FROM users WHERE deleted_at IS NULL AND role IN ('super_admin', 'admin', 'editor', 'author') ORDER BY name ASC");
 $authors = $authorsStmt->fetchAll();

// Build WHERE conditions
 $whereConditions = ["p.deleted_at IS NULL", "p.status = 'published'"];
 $params = [];

// Filter berdasarkan TANGGAL PUBLISH
if ($dateFrom) {
    $whereConditions[] = "DATE(p.published_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if ($dateTo) {
    $whereConditions[] = "DATE(p.published_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

if ($categoryId) {
    $whereConditions[] = "p.category_id = :category_id";
    $params[':category_id'] = $categoryId;
}

if ($authorId) {
    $whereConditions[] = "p.author_id = :author_id";
    $params[':author_id'] = $authorId;
}

 $whereClause = implode(' AND ', $whereConditions);

// Get ALL data for statistics (tanpa paginasi)
 $sqlAll = "
    SELECT 
        p.id,
        p.view_count,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes,
        (SELECT COUNT(*) FROM comments WHERE commentable_type = 'post' AND commentable_id = p.id AND status = 'approved') as comments
    FROM posts p
    WHERE $whereClause
";
 $allDataStmt = $db->prepare($sqlAll);
 $allDataStmt->execute($params);
 $allData = $allDataStmt->fetchAll();

// === SUMMARY STATISTICS (4 Kartu) ===
 $summaryStats = [
    'total_posts' => 0,
    'total_views' => 0,
    'total_likes' => 0,
    'total_comments' => 0,
];

 $summaryStats['total_posts'] = count($allData);
 $summaryStats['total_views'] = array_sum(array_column($allData, 'view_count'));
 $summaryStats['total_likes'] = array_sum(array_column($allData, 'likes'));
 $summaryStats['total_comments'] = array_sum(array_column($allData, 'comments'));

// Count total items for pagination
 $totalItems = $summaryStats['total_posts'];
 $totalPages = ceil($totalItems / $perPage);

// Calculate offset for pagination
 $offset = ($page - 1) * $perPage;

// === MAIN DATA: All Posts with Filter and Pagination ===
 $sql = "
    SELECT 
        p.id,
        p.title,
        p.view_count,
        p.status,
        p.published_at,
        c.name as category_name,
        u.name as author_name,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes,
        (SELECT COUNT(*) FROM comments WHERE commentable_type = 'post' AND commentable_id = p.id AND status = 'approved') as comments
    FROM posts p
    LEFT JOIN post_categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE $whereClause
    ORDER BY p.published_at DESC
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
    // Get all data for PDF
    $pdfSql = "
        SELECT 
            p.id,
            p.title,
            p.view_count,
            p.status,
            p.published_at,
            c.name as category_name,
            u.name as author_name,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes,
            (SELECT COUNT(*) FROM comments WHERE commentable_type = 'post' AND commentable_id = p.id AND status = 'approved') as comments
        FROM posts p
        LEFT JOIN post_categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE $whereClause
        ORDER BY p.published_at DESC
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
                    ' . htmlspecialchars($siteName) . ' - Laporan Posts
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    // Menggunakan template PDF yang baru
    include dirname(__FILE__) . '/templates/laporan_posts_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Posts_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Laporan detail mengenai semua post.</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active"><?= $pageTitle ?></li>
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
                        <h6 class="text-white mb-2"><i class="bi bi-file-earmark-post"></i> Total Posts</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_posts']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-eye"></i> Total Views</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_views']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-heart-fill"></i> Total Likes</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_likes']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-chat-dots-fill"></i> Total Comments</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_comments']) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tanggal Publish Dari</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tanggal Publish Sampai</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Semua --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label">Penulis</label>
                        <select name="author_id" class="form-select">
                            <option value="">-- Semua --</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?= $author['id'] ?>" <?= $authorId == $author['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($author['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-1">
                        <label class="form-label">Per Hal</label>
                        <select name="per_page" class="form-select">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage == $n ? ' selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i>
                        </button>
                    </div>
                </form>
                <?php if ($dateFrom || $dateTo || $categoryId || $authorId): ?>
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
                        <h6 class="mb-1">Laporan Posts</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                        <?php if ($dateFrom || $dateTo || $categoryId || $authorId): ?>
                            <br><span class="badge bg-info mt-1">Filter Aktif</span>
                        <?php endif; ?>
                    </div>

                    <a href="?export_pdf=1<?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?><?= $categoryId ? '&category_id='.$categoryId : '' ?><?= $authorId ? '&author_id='.$authorId : '' ?>"
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
                    Data Posts - Detail Report
                    <span class="badge bg-primary"><?= $totalItems ?> Data</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Judul Post</th>
                                <th>Kategori</th>
                                <th>Penulis</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Likes</th>
                                <th>Comments</th>
                                <th>Tgl Publish</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mainData)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = $offset + 1; 
                                foreach ($mainData as $row): 
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars($row['category_name']) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row['author_name']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-light-success"><?= ucfirst($row['status']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?= formatNumber($row['view_count']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger">
                                                <?= formatNumber($row['likes']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= formatNumber($row['comments']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <small><?= formatTanggal($row['published_at'], 'd/m/Y') ?></small>
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