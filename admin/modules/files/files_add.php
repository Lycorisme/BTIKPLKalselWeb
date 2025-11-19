<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

 $pageTitle = 'Upload File Download Baru';
 $currentPage = 'files';

 $db = Database::getInstance()->getConnection();

 $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_active = (int)($_POST['is_active'] ?? 1);
    $file = $_FILES['file'] ?? null;

    if (!$title) $error = 'Judul file harus diisi.';
    elseif (!$file || $file['error']!==0) $error = 'File harus diupload.';
    else {
        $basename = time().'-'.preg_replace('/[^a-zA-Z0-9-_\.]/','_',basename($file['name']));
        
        // Path untuk DB (relatif ke folder public)
        $dbPath = 'uploads/files/'.$basename;
        
        // Path untuk disk fisik (menggunakan helper)
        $diskPath = uploadPath('files/'.$basename);
        
        // Buat direktori jika belum ada
        $diskDir = dirname($diskPath);
        if (!is_dir($diskDir)) {
            if (!mkdir($diskDir, 0777, true)) {
                $error = 'Gagal membuat direktori upload.';
            }
        }

        if (!$error) {
            if (!move_uploaded_file($file['tmp_name'], $diskPath)) {
                $error = 'Upload file gagal! Periksa izin folder.';
            } else {
                $insert = $db->prepare("INSERT INTO downloadable_files (title, description, file_path, file_type, file_size, mime_type, is_active, uploaded_by)
                    VALUES (?,?,?,?,?,?,?,?)");
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $size = $file['size'];
                $mime = $file['type'];
                
                // Simpan $dbPath ke database
                $insert->execute([$title, $description, $dbPath, $ext, $size, $mime, $is_active, getCurrentUser()['id']]);
                
                setAlert('success','File berhasil diupload!');
                header("Location: files_list.php");
                exit;
            }
        }
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
                        <li class="breadcrumb-item active" aria-current="page">Upload File</li>
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
                        <h5 class="mb-0">Upload File Baru</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif ?>
                        
                        <form method="post" enctype="multipart/form-data" id="uploadForm">
                            <div class="mb-3">
                                <label for="title" class="form-label">Judul File <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" maxlength="255" required>
                                <div class="form-text">Judul yang akan ditampilkan kepada pengguna</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi Singkat</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                <div class="form-text">Jelaskan secara singkat tentang file ini</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="file" class="form-label">Pilih File <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="file" name="file" required>
                                <div class="form-text">Support: pdf, docx, xlsx, pptx, zip, rar, image, dll</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="is_active" class="form-label">Status</label>
                                <select name="is_active" id="is_active" class="form-select">
                                    <option value="1">Aktif - File dapat diunduh</option>
                                    <option value="0">Nonaktif - File disembunyikan</option>
                                </select>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-cloud-arrow-up me-1"></i> Upload File
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
                    <div class="card-header">
                        <h5 class="mb-0">Panduan Upload</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="text-primary">Format File yang Didukung</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-file-earmark-pdf text-danger me-2"></i> PDF (.pdf)</li>
                                <li class="mb-2"><i class="bi bi-file-earmark-word text-primary me-2"></i> Microsoft Word (.doc, .docx)</li>
                                <li class="mb-2"><i class="bi bi-file-earmark-excel text-success me-2"></i> Microsoft Excel (.xls, .xlsx)</li>
                                <li class="mb-2"><i class="bi bi-file-earmark-slides text-warning me-2"></i> Microsoft PowerPoint (.ppt, .pptx)</li>
                                <li class="mb-2"><i class="bi bi-file-earmark-image text-info me-2"></i> Gambar (.jpg, .jpeg, .png, .gif, .webp)</li>
                                <li class="mb-2"><i class="bi bi-file-earmark-zip text-secondary me-2"></i> Arsip (.zip, .rar, .7z)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Preview File</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center py-4" id="filePreviewContainer">
                            <i class="bi bi-cloud-upload text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">Preview akan muncul setelah file dipilih</p>
                        </div>
                        <div id="fileInfo" class="d-none">
                            <div class="mb-2">
                                <small class="text-muted">Nama File:</small>
                                <div id="fileName" class="text-break"></div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Ukuran:</small>
                                <div id="fileSize"></div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Tipe:</small>
                                <div id="fileType"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('file');
    const filePreviewContainer = document.getElementById('filePreviewContainer');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileType = document.getElementById('fileType');
    
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Update file info
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileType.textContent = file.type || 'Unknown';
            
            // Show file info, hide placeholder
            filePreviewContainer.classList.add('d-none');
            fileInfo.classList.remove('d-none');
            
            // Show appropriate icon based on file type
            const ext = file.name.split('.').pop().toLowerCase();
            let iconClass = 'bi-file-earmark';
            let iconColor = 'text-secondary';
            
            if (ext === 'pdf') {
                iconClass = 'bi-file-earmark-pdf';
                iconColor = 'text-danger';
            } else if (['doc', 'docx'].includes(ext)) {
                iconClass = 'bi-file-earmark-word';
                iconColor = 'text-primary';
            } else if (['xls', 'xlsx'].includes(ext)) {
                iconClass = 'bi-file-earmark-excel';
                iconColor = 'text-success';
            } else if (['ppt', 'pptx'].includes(ext)) {
                iconClass = 'bi-file-earmark-slides';
                iconColor = 'text-warning';
            } else if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) {
                iconClass = 'bi-file-earmark-image';
                iconColor = 'text-info';
            } else if (['zip', 'rar', '7z'].includes(ext)) {
                iconClass = 'bi-file-earmark-zip';
                iconColor = 'text-secondary';
            }
            
            // Update preview with icon
            // ===== PERBAIKAN: Menambah text-break pada <p> =====
            filePreviewContainer.innerHTML = `
                <i class="bi ${iconClass} ${iconColor}" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2 text-break">${file.name}</p>
            `;
            
            // If it's an image, show a preview
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // ===== PERBAIKAN: Menambah text-break pada <p> =====
                    filePreviewContainer.innerHTML = `
                        <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;" alt="Preview">
                        <p class="text-muted mt-2 text-break">${file.name}</p>
                    `;
                };
                reader.readAsDataURL(file);
            }
        }
    });
    
    // Format file size function
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>

<?php include '../../includes/footer.php'; ?>