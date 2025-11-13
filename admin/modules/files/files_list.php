<?php
/**
 * Files List - Konsisten dengan Mazer Design System
 * Full Mazer Design with Soft Delete, Pagination, Search, File Preview
 * DIUBAH: Mengadopsi layout filter dari referensi (categories_list.php)
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../models/File.php';

$pageTitle = 'Kelola File Download';
$currentPage = 'files';

$db = Database::getInstance()->getConnection();
$fileModel = new File();

// Pagination & Filter
$page = max(1, (int)($_GET['page'] ?? 1));
// Ambil perPage dari settings, atau default ke 10
$perPage = (int)($_GET['per_page'] ?? getSetting('items_per_page', 10)); 
$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$showDeleted = $_GET['show_deleted'] ?? '0';

// Build filters
$filters = [];
if ($search) $filters['search'] = $search;
if ($status !== '') $filters['status'] = $status;
if ($showDeleted === '1') $filters['show_deleted'] = true;

// Get paginated data
$result = $fileModel->getPaginated($page, $perPage, $filters);
$files = $result['data'];
$totalFiles = $result['total'];
$totalPages = $result['last_page'];
$offset = ($page - 1) * $perPage;

// Options for dropdown
$statusOptions = [
    '' => 'Semua Status',
    '1' => 'Aktif',
    '0' => 'Nonaktif'
];
$perPageOptions = [10, 20, 50, 100];
// Perbarui nilai perPage default di dropdown jika tidak ada di list standar
if (!in_array($perPage, $perPageOptions) && $perPage > 0) {
    $perPageOptions[] = $perPage;
    sort($perPageOptions);
}

$showDeletedOptions = [
    '0' => 'Tampilkan Data Aktif',
    '1' => 'Tampilkan Data Terhapus'
];

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola semua file yang dapat diunduh</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Files</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="card-title m-0 fw-bold">Daftar File Download</div>
                <div>
                    <a href="files_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i>
                        <span class="d-none d-md-inline">Tambah File</span>
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-md-3">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Cari judul, deskripsi..." class="form-control">
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="status" class="form-select">
                            <?php foreach ($statusOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $status === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="show_deleted" class="form-select">
                            <?php foreach ($showDeletedOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $showDeleted === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="per_page" class="form-select">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage == $n ? ' selected' : '' ?>><?= $n ?>/hlm</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search"></i>
                            <span class="d-none d-md-inline">Filter</span>
                        </button>
                    </div>
                </form>

                <?php // Tombol reset dipindah ke luar form, sesuai referensi
                $isFiltered = $search || $status !== '' || $showDeleted === '1' || (isset($_GET['per_page']) && $_GET['per_page'] != getSetting('items_per_page', 10));
                if ($isFiltered): ?>
                    <div class="mb-3">
                        <a href="files_list.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                <?php endif; ?>
                <?php if (empty($files)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="text-muted mt-3">Tidak ada file ditemukan</h5>
                        <p class="text-muted mb-0">Coba ubah filter atau <a href="files_add.php">upload file baru</a></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive d-none d-lg-block">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="30%">Nama File</th>
                                    <th width="12%">Ukuran/Tipe</th>
                                    <th width="28%">Deskripsi</th>
                                    <th width="8%" class="text-center">Download</th>
                                    <th width="8%" class="text-center">Status</th>
                                    <th width="11%" class="text-center">Diunggah</th>
                                    <th width="8%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $idx => $file):
                                    $isTrashed = !is_null($file['deleted_at'] ?? null);
                                    $fileIcon = 'bi-file-earmark';
                                    $fileColor = 'text-secondary';
                                    
                                    $fileType = strtolower($file['file_type']);
                                    if (in_array($fileType, ['pdf'])) {
                                        $fileIcon = 'bi-file-earmark-pdf';
                                        $fileColor = 'text-danger';
                                    } elseif (in_array($fileType, ['doc', 'docx'])) {
                                        $fileIcon = 'bi-file-earmark-word';
                                        $fileColor = 'text-primary';
                                    } elseif (in_array($fileType, ['xls', 'xlsx'])) {
                                        $fileIcon = 'bi-file-earmark-excel';
                                        $fileColor = 'text-success';
                                    } elseif (in_array($fileType, ['ppt', 'pptx'])) {
                                        $fileIcon = 'bi-file-earmark-slides';
                                        $fileColor = 'text-warning';
                                    } elseif (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                                        $fileIcon = 'bi-file-earmark-image';
                                        $fileColor = 'text-info';
                                    } elseif (in_array($fileType, ['zip', 'rar', '7z'])) {
                                        $fileIcon = 'bi-file-earmark-zip';
                                        $fileColor = 'text-secondary';
                                    }
                                ?>
                                    <tr<?= $isTrashed ? ' class="table-danger text-muted"' : '' ?>>
                                        <td><?= $offset + $idx + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi <?= $fileIcon ?> <?= $fileColor ?> fs-5 me-2"></i>
                                                <div>
                                                    <strong class="text-break"><?= htmlspecialchars($file['title']) ?></strong>
                                                    <?php if (!$isTrashed): ?>
                                                        <br>
                                                        <a href="#" class="small text-primary file-preview-link text-break"
                                                           data-file-url="<?= publicFileUrl($file['file_path']) ?>"
                                                           data-file-type="<?= htmlspecialchars(strtolower($file['file_type'])) ?>"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#filePreviewModal"><?= basename($file['file_path']) ?></a>
                                                    <?php else: ?>
                                                        <br>
                                                        <span class="badge bg-secondary">Terhapus</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?= formatFileSize($file['file_size'] ?? 0) ?></div>
                                            <span class="badge bg-secondary"><?= htmlspecialchars(strtoupper($file['file_type'])) ?></span>
                                        </td>
                                        <td>
                                            <small class="text-break"><?= htmlspecialchars(truncateText($file['description'] ?? '', 50)) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= formatNumber($file['download_count'] ?? 0) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($file['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <small><?= formatTanggal($file['created_at'], 'd M Y') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isTrashed): ?>
                                                <small class="text-danger">
                                                    <strong>Deleted</strong>
                                                </small>
                                            <?php else: ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="files_edit.php?id=<?= $file['id'] ?>" class="btn btn-outline-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="files_delete.php?id=<?= $file['id'] ?>" class="btn btn-outline-danger"
                                                       data-confirm-delete
                                                       data-title="<?= htmlspecialchars($file['title']) ?>"
                                                       data-message="File &quot;<?= htmlspecialchars($file['title']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                                       data-loading-text="Menghapus file..."
                                                       title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-lg-none">
                        <?php foreach ($files as $idx => $file):
                            $isTrashed = !is_null($file['deleted_at'] ?? null);
                            $fileIcon = 'bi-file-earmark';
                            $fileColor = 'text-secondary';
                            
                            $fileType = strtolower($file['file_type']);
                            if (in_array($fileType, ['pdf'])) {
                                $fileIcon = 'bi-file-earmark-pdf';
                                $fileColor = 'text-danger';
                            } elseif (in_array($fileType, ['doc', 'docx'])) {
                                $fileIcon = 'bi-file-earmark-word';
                                $fileColor = 'text-primary';
                            } elseif (in_array($fileType, ['xls', 'xlsx'])) {
                                $fileIcon = 'bi-file-earmark-excel';
                                $fileColor = 'text-success';
                            } elseif (in_array($fileType, ['ppt', 'pptx'])) {
                                $fileIcon = 'bi-file-earmark-slides';
                                $fileColor = 'text-warning';
                            } elseif (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                                $fileIcon = 'bi-file-earmark-image';
                                $fileColor = 'text-info';
                            } elseif (in_array($fileType, ['zip', 'rar', '7z'])) {
                                $fileIcon = 'bi-file-earmark-zip';
                                $fileColor = 'text-secondary';
                            }
                        ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center flex-grow-1" style="min-width: 0;">
                                            <i class="bi <?= $fileIcon ?> <?= $fileColor ?> fs-4 me-2"></i>
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <strong class="text-break"><?= htmlspecialchars($file['title']) ?></strong>
                                                <div class="small text-muted"><?= formatFileSize($file['file_size'] ?? 0) ?> · <?= htmlspecialchars(strtoupper($file['file_type'])) ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <?php if ($file['is_active']): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($file['description']): ?>
                                        <div class="mb-2">
                                            <small class="text-muted text-break"><?= htmlspecialchars(truncateText($file['description'], 60)) ?></small>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center mb-3 small">
                                        <span class="text-muted">
                                            <i class="bi bi-download"></i> <?= formatNumber($file['download_count'] ?? 0) ?> downloads
                                        </span>
                                        <span class="text-muted"><?= formatTanggal($file['created_at'], 'd M Y') ?></span>
                                    </div>

                                    <?php if ($isTrashed): ?>
                                        <span class="badge bg-danger w-100 text-center py-2">Terhapus</span>
                                    <?php else: ?>
                                        <div class="d-flex gap-2">
                                            <a href="#" class="btn btn-sm btn-outline-info file-preview-link flex-grow-1"
                                               data-file-url="<?= publicFileUrl($file['file_path']) ?>"
                                               data-file-type="<?= htmlspecialchars(strtolower($file['file_type'])) ?>"
                                               data-bs-toggle="modal"
                                               data-bs-target="#filePreviewModal">
                                                <i class="bi bi-eye"></i> Preview
                                            </a>
                                            <a href="files_edit.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="files_delete.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-danger"
                                               data-confirm-delete
                                               data-title="<?= htmlspecialchars($file['title']) ?>"
                                               data-message="File akan dipindahkan ke Trash. Lanjutkan?"
                                               data-loading-text="Menghapus...">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($totalFiles > 0): ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                        <div>
                            <small class="text-muted">
                                Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($files) ?> dari <?= $totalFiles ?> file
                            </small>
                        </div>
                        
                        <nav aria-label="Page navigation">
                            <ul class="pagination mb-0">
                                
                                <?php // Tombol Previous ?>
                                <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php // Nomor Halaman dengan Logika "..." ?>
                                <?php
                                $from = max(1, $page - 2);
                                $to = min($totalPages, $page + 2);
                                
                                if ($from > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => 1])).'">1</a></li>';
                                    if ($from > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $from; $i <= $to; $i++): ?>
                                    <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; 
                                
                                if ($to < $totalPages) {
                                    if ($to < $totalPages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => $totalPages])).'">'.$totalPages.'</a></li>';
                                }
                                ?>
                                
                                <?php // Tombol Next ?>
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

<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filePreviewModalLabel">Preview File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0" id="file-preview-area" style="min-height:60vh;overflow:auto;background:#f5f5f5;">
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="downloadLink" class="btn btn-primary btn-sm" target="_blank" download>
                    <i class="bi bi-download"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filePreviewLinks = document.querySelectorAll(".file-preview-link");
    const filePreviewArea = document.getElementById("file-preview-area");
    const downloadLink = document.getElementById("downloadLink");
    
    filePreviewLinks.forEach(function(link) {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            const url = this.dataset.fileUrl;
            const type = this.dataset.fileType;
            
            // Set download link
            downloadLink.href = url;
            
            // Show loading
            filePreviewArea.innerHTML = `
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Generate preview based on file type
            setTimeout(() => {
                let preview = '';
                
                if(["pdf"].includes(type)) {
                    preview = `<embed src="${url}#toolbar=0&navpanes=0" type="application/pdf" style="width:100%;min-height:70vh;" />`;
                } else if(["jpg","jpeg","png","gif","webp","svg"].includes(type)) {
                    preview = `<div style="padding:20px;"><img src="${url}" style="max-width:100%;max-height:80vh;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.1);" /></div>`;
                } else if(["doc","docx","ppt","pptx","xls","xlsx"].includes(type)) {
                    preview = `<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(url)}" width="100%" height="600px" frameborder="0" style="border:none;"></iframe>`;
                } else {
                    preview = `
                        <div style="padding:40px 20px;text-align:center;">
                            <div class="alert alert-info" style="margin:0;max-width:400px;margin:0 auto;">
                                <i class="bi bi-info-circle"></i> Preview tidak tersedia untuk file ini
                            </div>
                            <a href="${url}" target="_blank" class="btn btn-primary btn-sm mt-3" download>
                                <i class="bi bi-download"></i> Download File
                            </a>
                        </div>
                    `;
                }
                
                filePreviewArea.innerHTML = preview;
            }, 500);
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>