<?php
/**
 * Edit Post Page
 * Update existing post with all features
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Validator.php';
require_once '../../../core/Upload.php';
require_once '../../../models/Post.php';
require_once '../../../models/PostCategory.php';
require_once '../../../models/Tag.php';

// Only admin and editor can edit posts
if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Edit Post';

$postModel = new Post();
$categoryModel = new PostCategory();
$tagModel = new Tag();
$validator = null;

// Get post ID
$postId = $_GET['id'] ?? 0;
$post = $postModel->getById($postId);

if (!$post) {
    setAlert('danger', 'Post tidak ditemukan');
    redirect(ADMIN_URL . 'modules/posts/posts_list.php');
}

// Get categories and tags
$categories = $categoryModel->getActive();
$allTags = $tagModel->getAll();
$postTags = $postModel->getTags($postId);
// Ambil nama tag, pastikan unik (walaupun di DB duplikat, di input harus unik)
$postTagNames = array_unique(array_column($postTags, 'name'));
$postTagsString = implode(', ', $postTagNames);

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validator($_POST);
    
    // Validation rules
    $validator->required('title', 'Judul');
    $validator->required('content', 'Konten');
    $validator->required('category_id', 'Kategori');
    $validator->in('status', ['draft', 'published', 'archived'], 'Status');
    
    if ($validator->passes()) {
        try {
            $upload = new Upload();
            $featuredImage = $post['featured_image']; // Keep existing
            
            // Handle image deletion
            if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
                if ($post['featured_image']) {
                    $upload->delete($post['featured_image']);
                }
                $featuredImage = null;
            }
            // Handle image upload
            elseif (!empty($_FILES['featured_image']['name'])) {
                $newImage = $upload->upload($_FILES['featured_image'], 'posts');
                if ($newImage) {
                    // Delete old image
                    if ($post['featured_image']) {
                        $upload->delete($post['featured_image']);
                    }
                    $featuredImage = $newImage;
                } else {
                    $validator->addError('featured_image', $upload->getError());
                }
            }
            
            if ($validator->passes()) {
                // Check if title changed, regenerate slug
                $slug = $post['slug'];
                if ($_POST['title'] != $post['title']) {
                    $slug = $postModel->generateSlug($_POST['title'], $postId);
                }
                
                // Handle published_at
                $publishedAt = $post['published_at'];
                if ($_POST['status'] === 'published' && !$publishedAt) {
                    $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : date('Y-m-d H:i:s');
                } elseif (!empty($_POST['published_at'])) {
                    $publishedAt = $_POST['published_at'];
                }
                
                // Prepare data
                $data = [
                    'title' => clean($_POST['title']),
                    'slug' => $slug,
                    'content' => $_POST['content'], // Don't clean HTML
                    'excerpt' => clean($_POST['excerpt'] ?? ''),
                    'featured_image' => $featuredImage,
                    'category_id' => (int)$_POST['category_id'],
                    'status' => clean($_POST['status']),
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'published_at' => $publishedAt,
                    'meta_title' => clean($_POST['meta_title'] ?? ''),
                    'meta_description' => clean($_POST['meta_description'] ?? ''),
                    'meta_keywords' => clean($_POST['meta_keywords'] ?? '')
                ];
                
                if ($postModel->update($postId, $data)) {
                    
                    // ==========================================
                    // START: FINAL TAG HANDLING (FIXED)
                    // ==========================================
                    if (isset($_POST['tags'])) {
                        $tagNames = array_map('trim', explode(',', $_POST['tags']));
                        $tagNames = array_filter($tagNames);
                        $tagNames = array_unique($tagNames);
                        $tagIds = [];
                        
                        foreach ($tagNames as $tagName) {
                            $normalizedName = strtolower(trim($tagName));
                            if (empty($normalizedName)) continue;
                            
                            // ** INI PERBAIKANNYA **
                            // Cek berdasarkan NAMA, bukan slug
                            $existingTag = $tagModel->findByName($normalizedName);
                            
                            if ($existingTag) {
                                // Tag SUDAH ADA - Gunakan ID yang existing
                                $tagIds[] = $existingTag['id'];
                            } else {
                                // Tag TIDAK ADA - Buat tag baru
                                $newTagId = $tagModel->insert([
                                    'name' => $normalizedName,
                                    'slug' => $tagModel->generateSlug($normalizedName) // Biarkan generateSlug handle slug
                                ]);
                                $tagIds[] = $newTagId;
                            }
                        }
                        
                        $tagIds = array_unique($tagIds);
                        
                        // Sync tags
                        $postModel->syncTags($postId, $tagIds);
                    }
                    // ==========================================
                    // END: FINAL TAG HANDLING
                    // ==========================================
                    
                    logActivity('UPDATE', "Mengupdate post: {$data['title']}", 'posts', $postId);
                    
                    setAlert('success', 'Post berhasil diupdate');
                    redirect(ADMIN_URL . 'modules/posts/posts_list.php');
                } else {
                    $validator->addError('general', 'Gagal menyimpan data');
                }
            }
            
        } catch (PDOException $e) {
            error_log("PDO Error: " . $e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan database: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log("General Error: " . $e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}

include '../../includes/header.php';
?>

<style>
.slug-code {
    word-break: break-all; /* Memaksa slug yang panjang untuk pindah baris */
}
</style>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="posts_list.php">Post</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Konten Post</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($validator && $validator->getError('general')): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <strong>Error:</strong> <?= $validator->getError('general') ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Judul Post <span class="text-danger">*</span></label>
                                <input type="text" name="title" 
                                       class="form-control <?= $validator && $validator->getError('title') ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['title'] ?? $post['title']) ?>" required>
                                <?php if ($validator && $validator->getError('title')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('title') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Slug</label>
                                <input type="text" class="form-control" value="<?= $post['slug'] ?>" readonly>
                                <small class="text-muted">Slug akan diupdate otomatis jika judul diubah</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Ringkasan/Excerpt</label>
                                <textarea name="excerpt" rows="3" class="form-control"><?= htmlspecialchars($_POST['excerpt'] ?? $post['excerpt'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Konten <span class="text-danger">*</span></label>
                                <textarea name="content" id="content" rows="15" class="form-control" required><?= $_POST['content'] ?? $post['content'] ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">SEO Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['meta_title'] ?? $post['meta_title'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Meta Description</label>
                                <textarea name="meta_description" rows="2" class="form-control"><?= htmlspecialchars($_POST['meta_description'] ?? $post['meta_description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group mb-0">
                                <label class="form-label">Meta Keywords</label>
                                <input type="text" name="meta_keywords" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['meta_keywords'] ?? $post['meta_keywords'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Publikasi</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                                <?= ($_POST['category_id'] ?? $post['category_id']) == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="draft" <?= ($_POST['status'] ?? $post['status']) == 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="published" <?= ($_POST['status'] ?? $post['status']) == 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="archived" <?= ($_POST['status'] ?? $post['status']) == 'archived' ? 'selected' : '' ?>>Archived</option>
                                </select>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Tanggal Publish</label>
                                <input type="datetime-local" name="published_at" class="form-control"
                                       value="<?= $_POST['published_at'] ?? ($post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '') ?>">
                            </div>
                            
                            <div class="form-group mb-0">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_featured" value="1" 
                                           class="form-check-input" id="is_featured"
                                           <?= ($_POST['is_featured'] ?? $post['is_featured']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_featured">
                                        Post Unggulan/Featured
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Gambar Utama</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($post['featured_image']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Gambar Saat Ini</label>
                                    <div class="position-relative">
                                        <img src="<?= uploadUrl($post['featured_image']) ?>" 
                                             alt="<?= htmlspecialchars($post['title']) ?>" 
                                             class="img-fluid rounded">
                                        
                                        <div class="mt-2">
                                            <div class="form-check">
                                                <input type="checkbox" name="delete_image" value="1" 
                                                       class="form-check-input" id="deleteImage">
                                                <label class="form-check-label text-danger" for="deleteImage">
                                                    <i class="bi bi-trash"></i> Hapus gambar ini
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group mb-0">
                                <label class="form-label">
                                    <?= $post['featured_image'] ? 'Upload Gambar Baru' : 'Upload Gambar' ?>
                                </label>
                                <input type="file" name="featured_image" class="form-control" accept="image/*">
                                <?php if ($post['featured_image']): ?>
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Tags</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Tags</label>
                                <input type="text" name="tags" id="tagsInput" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['tags'] ?? $postTagsString) ?>"
                                       placeholder="tag1, tag2, tag3">
                                <small class="text-muted">Pisahkan dengan koma</small>
                            </div>
                            
                            <?php if (!empty($allTags)): ?>
                                <div>
                                    <small class="text-muted d-block mb-2">Tag populer:</small>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach (array_slice($allTags, 0, 10) as $tag): ?>
                                            <span class="badge bg-secondary" style="cursor: pointer;" 
                                                  onclick="addTag('<?= htmlspecialchars($tag['name']) ?>')">
                                                #<?= htmlspecialchars($tag['name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <small>
                                <strong>No:</strong> <?= $post['id'] ?><br>
                                <strong>Views:</strong> <?= formatNumber($post['view_count']) ?><br>
                                <strong>Dibuat:</strong> <?= formatTanggal($post['created_at'], 'd M Y H:i') ?><br>
                                <?php if ($post['updated_at']): ?>
                                    <strong>Diupdate:</strong> <?= formatTanggal($post['updated_at'], 'd M Y H:i') ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Post
                                </button>
                                <a href="posts_list.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>

<style>
.slug-code {
    word-break: break-all;
}
</style>
<div class="card">
    <div class="card-body">
        <small>
            <strong>No:</strong> <?= $post['id'] ?><br>
            <strong>Views:</strong> <?= formatNumber($post['view_count']) ?><br>
            
            <strong>Slug:</strong> <code class="slug-code"><?= $post['slug'] ?></code><br>
            
            <strong>Dibuat:</strong> <?= formatTanggal($post['created_at'], 'd M Y H:i') ?><br>
            <?php if ($post['updated_at']): ?>
                <strong>Diupdate:</strong> <?= formatTanggal($post['updated_at'], 'd M Y H:i') ?>
            <?php endif; ?>
        </small>
    </div>
</div>
<script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
<script>
    ClassicEditor
        .create(document.querySelector('#content'), {
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'link', 'bulletedList', 'numberedList', '|',
                    'alignment', 'indent', 'outdent', '|',
                    'blockQuote', 'insertTable', '|',
                    'undo', 'redo'
                ],
                shouldNotGroupWhenFull: true
            },
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                    { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
                ]
            },
            language: 'en'
        })
        .catch(error => {
            console.error('CKEditor initialization error:', error);
        });
    
    function addTag(tagName) {
        const tagsInput = document.getElementById('tagsInput');
        const currentTags = tagsInput.value.split(',').map(t => t.trim()).filter(t => t);
        
        const normalizedTagName = tagName.toLowerCase();
        const normalizedCurrentTags = currentTags.map(t => t.toLowerCase());

        if (!normalizedCurrentTags.includes(normalizedTagName)) {
            currentTags.push(tagName);
            tagsInput.value = currentTags.join(', ');
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>