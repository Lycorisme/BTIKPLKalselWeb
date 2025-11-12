<?php
/**
 * View Post Page
 * Modern design using Mazer components
 * (Refactored to load live counts and new stats card design)
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/Post.php';
require_once '../../../models/Comment.php'; // <-- 1. DIMUAT

 $pageTitle = 'Detail Post';
 $currentPage = 'posts';

 $postModel = new Post();
 $commentModel = new Comment(); // <-- 2. INISIALISASI

// Get post ID
 $postId = $_GET['id'] ?? 0;
 $post = $postModel->getById($postId);

if (!$post) {
    setAlert('danger', 'Post tidak ditemukan');
    redirect(ADMIN_URL . 'modules/posts/posts_list.php');
}

// Get tags
 $postTags = $postModel->getTags($postId);

// 3. AMBIL DATA COUNT LANGSUNG (LIVE)
// Ini lebih akurat daripada $post['comment_count'] yang mungkin datanya lama/salah
 $liveCommentCount = $commentModel->getCommentCount('post', $postId);

// (Catatan: $post['like_count'] Anda sepertinya sudah berfungsi, jadi kita tetap pakai itu)

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 mb-3 mb-md-0">
                <h3><i class=""></i> <?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="posts_list.php">Post</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h2 class="card-title mb-3 text-break"><?= htmlspecialchars($post['title']) ?></h2>
                                
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge bg-primary">
                                        <i class="bi bi-folder me-1"></i> <?= htmlspecialchars($post['category_name']) ?>
                                    </span>
                                    
                                    <?= getStatusBadge($post['status']) ?>
                                    
                                    <?php if ($post['is_featured']): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-star-fill me-1"></i> Featured
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex flex-wrap gap-3 text-muted small border-top pt-3">
                            <div>
                                <i class="bi bi-person-circle me-1"></i>
                                <strong><?= htmlspecialchars($post['author_name']) ?></strong>
                            </div>
                            <div>
                                <i class="bi bi-calendar-event me-1"></i>
                                <?= formatTanggal($post['published_at'] ?: $post['created_at'], 'd M Y') ?>
                            </div>
                            <div>
                                <i class="bi bi-clock me-1"></i>
                                <?= formatTanggal($post['published_at'] ?: $post['created_at'], 'H:i') ?>
                            </div>
                            <div>
                                <i class="bi bi-eye me-1"></i>
                                <?= formatNumber($post['view_count']) ?> views
                            </div>
                            <div>
                                <a href="posts_comment_view.php?id=<?= $post['id'] ?>" class="text-decoration-none text-muted">
                                    <i class="bi bi-chat-dots me-1"></i>
                                    <?= formatNumber($liveCommentCount) ?> komentar
                                </a>
                            </div>
                            <div>
                                <a href="posts_like_view.php?id=<?= $post['id'] ?>" class="text-decoration-none text-muted">
                                    <i class="bi bi-heart me-1"></i>
                                    <?= formatNumber($post['like_count']) ?> likes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($post['featured_image']): ?>
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <img src="<?= uploadUrl($post['featured_image']) ?>" 
                                 alt="<?= htmlspecialchars($post['title']) ?>" 
                                 class="img-fluid rounded">
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($post['excerpt']): ?>
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="alert alert-light-primary mb-0">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <i class="bi bi-info-circle fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="alert-heading mb-2">Ringkasan</h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($post['excerpt'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-file-text me-2"></i>Konten
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="post-content">
                            <?= $post['content'] ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($postTags)): ?>
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="bi bi-tags me-2"></i>Tags
                            </h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($postTags as $tag): ?>
                                    <span class="badge bg-light-secondary">
                                        <i class="bi bi-hash"></i><?= htmlspecialchars($tag['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($post['meta_title'] || $post['meta_description'] || $post['meta_keywords']): ?>
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-search me-2"></i>SEO Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($post['meta_title']): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted small">Meta Title</label>
                                    <p class="mb-0 text-break"><?= htmlspecialchars($post['meta_title']) ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($post['meta_description']): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted small">Meta Description</label>
                                    <p class="mb-0 text-break"><?= htmlspecialchars($post['meta_description']) ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($post['meta_keywords']): ?>
                                <div class="mb-0">
                                    <label class="form-label fw-bold text-muted small">Meta Keywords</label>
                                    <p class="mb-0 text-break"><?= htmlspecialchars($post['meta_keywords']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-gear me-2"></i>Aksi
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if (hasRole(['super_admin', 'admin', 'editor'])): ?>
                                <a href="posts_edit.php?id=<?= $post['id'] ?>" 
                                   class="btn btn-warning btn-block">
                                    <i class="bi bi-pencil-square me-1"></i> Edit Post
                                </a>
                            <?php endif; ?>
                            
                            <?php if (hasRole(['super_admin', 'admin'])): ?>
                                <a href="posts_delete.php?id=<?= $post['id'] ?>" 
                                   class="btn btn-danger btn-block"
                                   data-confirm-delete
                                   data-title="Hapus Post?"
                                   data-message="Post &quot;<?= htmlspecialchars($post['title']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                   data-loading-text="Menghapus post...">
                                    <i class="bi bi-trash3 me-1"></i> Hapus Post
                                </a>
                            <?php endif; ?>
                            
                            <a href="posts_list.php" class="btn btn-secondary btn-block">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-graph-up me-2"></i>Statistik
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon purple me-3">
                                        <i class="iconly-boldShow"></i>
                                    </div>
                                    <span class="font-semibold text-muted">Views</span>
                                </div>
                                <h6 class="font-extrabold mb-0"><?= formatNumber($post['view_count']) ?></h6>
                            </li>
                            
                            <a href="<?= ADMIN_URL ?>modules/posts/posts_comment_view.php?id=<?= $post['id'] ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon blue me-3">
                                        <i class="iconly-boldChat"></i>
                                    </div>
                                    <span class="font-semibold">Komentar</span>
                                </div>
                                <h6 class="font-extrabold mb-0"><?= formatNumber($liveCommentCount) ?></h6>
                            </a>

                            <a href="<?= ADMIN_URL ?>modules/posts/posts_like_view.php?id=<?= $post['id'] ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon red me-3">
                                        <i class="iconly-boldHeart"></i>
                                    </div>
                                    <span class="font-semibold">Likes</span>
                                </div>
                                <h6 class="font-extrabold mb-0"><?= formatNumber($post['like_count']) ?></h6>
                            </a>
                        </ul>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Informasi Post
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td style="width: 40%;" class="text-muted">
                                            <i class="bi bi-hash me-1"></i> ID
                                        </td>
                                        <td class="fw-bold"><?= $post['id'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">
                                            <i class="bi bi-link-45deg me-1"></i> Slug
                                        </td>
                                        <td>
                                            <code class="small text-break"><?= htmlspecialchars($post['slug']) ?></code>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">
                                            <i class="bi bi-folder me-1"></i> Kategori
                                        </td>
                                        <td class="fw-bold"><?= htmlspecialchars($post['category_name']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">
                                            <i class="bi bi-circle-fill me-1"></i> Status
                                        </td>
                                        <td><?= getStatusBadge($post['status']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">
                                            <i class="bi bi-person me-1"></i> Penulis
                                        </td>
                                        <td class="fw-bold"><?= htmlspecialchars($post['author_name']) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history me-2"></i>Timeline
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex align-items-center">
                                <div class="avatar avatar-sm bg-light-success me-3">
                                    <i class="bi bi-plus-circle"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 small">Dibuat</h6>
                                    <small class="text-muted">
                                        <?= formatTanggal($post['created_at'], 'd M Y, H:i') ?> WIB
                                    </small>
                                </div>
                            </li>
                            
                            <?php if ($post['updated_at'] && $post['updated_at'] != $post['created_at']): ?>
                                <li class="list-group-item d-flex align-items-center">
                                    <div class="avatar avatar-sm bg-light-warning me-3">
                                        <i class="bi bi-pencil"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 small">Terakhir Diupdate</h6>
                                        <small class="text-muted">
                                            <?= formatTanggal($post['updated_at'], 'd M Y, H:i') ?> WIB
                                        </small>
                                    </div>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($post['published_at']): ?>
                                <li class="list-group-item d-flex align-items-center">
                                    <div class="avatar avatar-sm bg-light-primary me-3">
                                        <i class="bi bi-send-check"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 small">Dipublikasikan</h6>
                                        <small class="text-muted">
                                            <?= formatTanggal($post['published_at'], 'd M Y, H:i') ?> WIB
                                        </small>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>