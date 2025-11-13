<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

 $pageTitle = 'Edit File Download';
 $currentPage = 'files';

 $db = Database::getInstance()->getConnection();

 $id = (int)($_GET['id'] ?? 0);
 $stmt = $db->prepare("SELECT * FROM downloadable_files WHERE id = ? AND deleted_at IS NULL");
 $stmt->execute([$id]);
 $file = $stmt->fetch(PDO::FETCH_ASSOC);
 if (!$file) {
    setAlert('danger','File tidak ditemukan!');
    header("Location: files_list.php");
    exit;
 }

 $error = '';
 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_active = (int)($_POST['is_active'] ?? 1);
    $replace = ($_POST['replace_file'] ?? '');

    // Default values (file saat ini)
    $filePath = $file['file_path'];
    $fileType = $file['file_type'];
    $fileSize = $file['file_size'];
    $mimeType = $file['mime_type'];

    // [FIXED] Blok ini diubah total untuk meniru files_add.php
    if ($replace === '1' && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload = $_FILES['file'];
        $basename = time().'-'.preg_replace('/[^a-zA-Z0-9-_\.]/','_',basename($upload['name']));
        
        $dbPath = 'uploads/files/'.$basename;
        
        $diskPath = uploadPath('files/'.$basename); 
        
        $diskDir = dirname($diskPath);
        if (!is_dir($diskDir)) {
            if (!mkdir($diskDir, 0777, true)) {
                $error = 'Gagal membuat direktori upload.';
            }
        }

        // 4. Pindahkan file baru ke jalur fisik
        if (!$error) {
            if (!move_uploaded_file($upload['tmp_name'], $diskPath)) {
                $error = 'Upload file gagal! Periksa izin folder.';
            } else {
                // 5. Hapus file LAMA dari jalur fisik
                $oldDiskPath = uploadPath($file['file_path']); // Dapatkan jalur fisik file lama
                if (file_exists($oldDiskPath) && is_file($oldDiskPath)) {
                    unlink($oldDiskPath);
                }
                
                // 6. Update variabel untuk disimpan ke DB
                $filePath = $dbPath; // Path DB baru
                $fileType = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
                $fileSize = $upload['size'];
                $mimeType = $upload['type'];
            }
        }
    }

    if (!$error) {
        $update = $db->prepare("UPDATE downloadable_files SET title=?, description=?, file_path=?, file_type=?, file_size=?, mime_type=?, is_active=?, updated_at=NOW() WHERE id=?");
        $update->execute([$title, $description, $filePath, $fileType, $fileSize, $mimeType, $is_active, $id]);
        setAlert('success','Update file berhasil!');
        header("Location: files_list.php");
        exit;
    }
 }

 include '../../includes/header.php';
 ?>
 <div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 mb-2 mb-md-0">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted mb-0"></p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="files_list.php">File Download</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit File</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    
    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi File</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif ?>
                        
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Judul File</label>
                                <input type="text" class="form-control" id="title" name="title" maxlength="255" required value="<?= htmlspecialchars($file['title']) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi Singkat</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($file['description']) ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="replace_file" class="form-label">Ganti File?</label>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="replace_file" name="replace_file"
                                      onchange="document.getElementById('file_input').disabled = !this.checked;">
                                    <label class="form-check-label" for="replace_file">Upload file baru</label>
                                </div>
                                <input type="file" class="form-control" name="file" id="file_input" disabled>
                                <div class="form-text">Biarkan kosong jika tidak ingin mengganti file.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="is_active" class="form-label">Status</label>
                                <select name="is_active" id="is_active" class="form-select">
                                    <option value="1"<?= $file['is_active'] ? ' selected':'' ?>>Aktif</option>
                                    <option value="0"<?= !$file['is_active'] ? ' selected':'' ?>>Nonaktif</option>
                                </select>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button class="btn btn-warning" type="submit">
                                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                                </button>
                                <a href="files_list.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg me-1"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom">
                        <h5 class="mb-0">File Saat Ini</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <?php
                            $iconClass = 'bi-file-earmark';
                            $iconColor = 'text-secondary';
                            
                            if (in_array(strtolower($file['file_type']), ['pdf'])) {
                                $iconClass = 'bi-file-earmark-pdf';
                                $iconColor = 'text-danger';
                            } elseif (in_array(strtolower($file['file_type']), ['doc', 'docx'])) {
                                $iconClass = 'bi-file-earmark-word';
                                $iconColor = 'text-primary';
                            } elseif (in_array(strtolower($file['file_type']), ['xls', 'xlsx'])) {
                                $iconClass = 'bi-file-earmark-excel';
                                $iconColor = 'text-success';
                            } elseif (in_array(strtolower($file['file_type']), ['ppt', 'pptx'])) {
                                $iconClass = 'bi-file-earmark-slides';
                                $iconColor = 'text-warning';
                            } elseif (in_array(strtolower($file['file_type']), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                                $iconClass = 'bi-file-earmark-image';
                                $iconColor = 'text-info';
                            } elseif (in_array(strtolower($file['file_type']), ['zip', 'rar', '7z'])) {
                                $iconClass = 'bi-file-earmark-zip';
                                $iconColor = 'text-secondary';
                            }
                            ?>
                            <i class="bi <?= $iconClass ?> <?= $iconColor ?>" style="font-size: 4rem;"></i>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="text-center text-break"><?= htmlspecialchars(basename($file['file_path'])) ?></h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Ukuran:</span>
                                <span><?= formatFileSize($file['file_size']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tipe:</span>
                                <span class="badge bg-secondary"><?= htmlspecialchars($file['file_type']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Diunggah:</span>
                                <span><?= formatTanggal($file['created_at'], 'd M Y') ?></span>
                            </div>
                            
                            <div class="d-grid">
                                <a href="#" class="btn btn-outline-primary file-preview-link"
                                   data-file-url="<?= publicFileUrl($file['file_path']) ?>"
                                   data-file-type="<?= htmlspecialchars(strtolower($file['file_type'])) ?>"
                                   data-bs-toggle="modal"
                                   data-bs-target="#filePreviewModal">
                                    <i class="bi bi-eye me-1"></i> Preview
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm mt-3">
                    <div class="card-header border-bottom">
                        <h5 class="mb-0">Informasi Tambahan</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">ID File:</small>
                            <div><?= $file['id'] ?></div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">MIME Type:</small>
                            <div class="text-break"><?= htmlspecialchars($file['mime_type']) ?></div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Terakhir diubah:</small>
                            <div><?= $file['updated_at'] ? formatTanggal($file['updated_at'], 'd M Y H:i') : 'Belum pernah diubah' ?></div>
                        </div>
                    </div>
                </div>
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
            <div class="modal-body text-center p-0" id="file-preview-area" style="min-height:60vh;overflow:auto;">
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="downloadLink" class="btn btn-primary" target="_blank">
                    <i class="bi bi-download me-1"></i> Download
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
                    preview = `<embed src="${url}#toolbar=0&navpanes=0" type="application/pdf" style="width:100%;min-height:70vh" />`;
                } else if(["jpg","jpeg","png","gif","webp","svg"].includes(type)) {
                    preview = `<img src="${url}" style="max-width:98%;max-height:80vh;border-radius:6px;" />`;
                } else if(["doc","docx","ppt","pptx","xls","xlsx"].includes(type)) {
                    preview = `<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(url)}" width="100%" height="600" frameborder="0"></iframe>`;
                } else {
                    preview = `
                        <div class="p-4">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> Preview online tidak tersedia untuk tipe file ini.
                            </div>
                            <a href="${url}" target="_blank" class="btn btn-primary">
                                <i class="bi bi-download me-1"></i> Download File
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