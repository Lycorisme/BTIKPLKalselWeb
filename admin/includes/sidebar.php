<?php
// Ambil status dan badge count
$currentFile = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['PHP_SELF'];
$currentModule = '';
$currentPage = '';
$currentReportType = '';

// Ekstrak module dari path
if (preg_match('/\/modules\/([^\/]+)\//', $currentPath, $matches)) {
    $currentModule = $matches[1];
}

if (strpos($currentPath, '/posts/') !== false) $currentPage = 'posts';
elseif (strpos($currentPath, '/services/') !== false) $currentPage = 'services';
elseif (strpos($currentPath, '/users/') !== false) $currentPage = 'users';
elseif (strpos($currentPath, '/categories/') !== false) $currentPage = 'categories';
elseif (strpos($currentPath, '/tags/') !== false) $currentPage = 'tags';
elseif (strpos($currentPath, '/files/') !== false) $currentPage = 'files';
elseif (strpos($currentPath, '/banners/') !== false) $currentPage = 'banners';
elseif (strpos($currentPath, '/settings/') !== false) $currentPage = 'settings';
elseif (strpos($currentPath, '/gallery/') !== false) $currentPage = 'gallery';
elseif (strpos($currentPath, '/contact/') !== false) $currentPage = 'contact';
elseif (strpos($currentPath, '/trash/') !== false) $currentPage = 'trash';
elseif (strpos($currentPath, '/pages/') !== false) $currentPage = 'pages';
elseif (strpos($currentPath, '/logs/') !== false) $currentPage = 'activity_logs'; // PERBAIKAN DI SINI
elseif (strpos($currentPath, '/reports/') !== false) {
    $currentPage = 'reports';
    if (strpos($currentPath, 'report_activities') !== false) $currentReportType = 'report_activities';
    elseif (strpos($currentPath, 'report_kegiatan') !== false) $currentReportType = 'report_kegiatan';
    elseif (strpos($currentPath, 'report_posts') !== false) $currentReportType = 'report_posts';
    elseif (strpos($currentPath, 'report_tags') !== false) $currentReportType = 'report_tags';
    elseif (strpos($currentPath, 'report_categories') !== false) $currentReportType = 'report_categories';
    elseif (strpos($currentPath, 'report_users') !== false) $currentReportType = 'report_users';
    elseif (strpos($currentPath, 'report_engagement') !== false) $currentReportType = 'report_engagement';
    elseif (strpos($currentPath, 'report_downloads') !== false) $currentReportType = 'report_downloads';
    elseif (strpos($currentPath, 'report_contacts') !== false) $currentReportType = 'report_contacts';
    elseif (strpos($currentPath, 'report_executive') !== false) $currentReportType = 'report_executive';
    elseif (strpos($currentPath, 'report_security') !== false) $currentReportType = 'report_security';
}
elseif ($currentFile === 'index.php' && strpos($currentPath, '/admin/index.php') !== false) $currentPage = 'dashboard';

// Badge counts
$db = Database::getInstance()->getConnection();

// Unread contact messages
$unreadContactStmt = $db->query("SELECT COUNT(*) as unread FROM contact_messages WHERE status = 'unread'");
$unreadContactData = $unreadContactStmt->fetch();
$unreadContactCount = $unreadContactData['unread'] ?? 0;

// Pending users (is_active = 3)
$pendingUsersStmt = $db->query("SELECT COUNT(*) as pending FROM users WHERE is_active = 3 AND deleted_at IS NULL");
$pendingUsersData = $pendingUsersStmt->fetch();
$pendingUsersCount = $pendingUsersData['pending'] ?? 0;

// Trash count
$trashCountStmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM services WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM downloadable_files WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM gallery_albums WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM gallery_photos WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM post_categories WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM tags WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM pages WHERE deleted_at IS NOT NULL) as total
");
$trashCountData = $trashCountStmt->fetch();
$trashCount = $trashCountData['total'] ?? 0;

// Pending comments
$pendingCommentsStmt = $db->query("SELECT COUNT(*) as pending FROM comments WHERE status = 'pending'");
$pendingCommentsData = $pendingCommentsStmt->fetch();
$pendingCommentsCount = $pendingCommentsData['pending'] ?? 0;
?>

<div id="sidebar">
    <div class="sidebar-wrapper active">

        <!-- Sidebar Header Logo & Text -->
        <div class="sidebar-header position-relative">
            <div class="d-flex align-items-center justify-content-center">
                <a href="<?= ADMIN_URL ?>" class="d-flex flex-column align-items-center text-decoration-none w-100 py-3"> 
                    <!-- Logo Image - DIPERBESAR -->
                    <?php if ($adminLogo = getSetting('site_logo')): ?>
                      <img src="<?= uploadUrl($adminLogo) ?>" alt="Logo BTIKP" style="height:60px; width:auto; display:block;">
                    <?php else: ?>
                      <img src="<?= BASE_URL ?>path/to/default/logo.png" alt="Logo" style="height:70px; width:auto; display:block;">
                    <?php endif; ?>

                    <!-- Logo Text -->
                    <?php if (getSetting('site_logo_show_text', '1') == '1'): ?>
                        <div class="text-center">
                          <span style="font-size:1.5rem; font-weight:800; color:var(--bs-primary); text-align:center;"> 
                            <?= getSetting('site_logo_text', 'BTIKP KALSEL') ?>
                          </span>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
            <hr class="sidebar-divider my-0">
        </div>

        <!-- Sidebar Menu -->
        <div class="sidebar-menu">
            <ul class="menu">
                
                <!-- SECTION: UTAMA -->
                <li class="sidebar-title">Menu Utama</li>
                
                <!-- Dashboard -->
                <li class="sidebar-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>" class="sidebar-link">
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- SECTION: KONTEN -->
                <li class="sidebar-title">Konten</li>

                <!-- Berita & Artikel -->
                <li class="sidebar-item has-sub <?= $currentPage === 'posts' || $currentPage === 'categories' || $currentPage === 'tags' ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-newspaper"></i>
                        <span>Berita & Artikel</span>
                        <?php if ($pendingCommentsCount > 0): ?>
                            <span class="badge bg-warning badge-sm"><?= $pendingCommentsCount ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="submenu <?= $currentPage === 'posts' || $currentPage === 'categories' || $currentPage === 'tags' ? 'active' : '' ?>">
                        <li class="submenu-item <?= $currentPage === 'posts' && !in_array($currentFile, ['posts_add.php', 'posts_edit.php']) ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/posts/posts_list.php" class="submenu-link">
                                <i class="bi bi-circle"></i>
                                <span>Semua Post</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentFile === 'posts_add.php' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/posts/posts_add.php" class="submenu-link">
                                <i class="bi bi-plus-circle"></i>
                                <span>Tambah Baru</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentPage === 'categories' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/categories/categories_list.php" class="submenu-link">
                                <i class="bi bi-folder"></i>
                                <span>Kategori</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentPage === 'tags' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/tags/tags_list.php" class="submenu-link">
                                <i class="bi bi-tags"></i>
                                <span>Tags</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Halaman -->
                <li class="sidebar-item <?= $currentPage === 'pages' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/pages/pages_list.php" class="sidebar-link">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Halaman</span>
                    </a>
                </li>

                <!-- Layanan -->
                <li class="sidebar-item <?= $currentPage === 'services' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/services/services_list.php" class="sidebar-link">
                        <i class="bi bi-gear-fill"></i>
                        <span>Layanan</span>
                    </a>
                </li>

                <!-- Gallery -->
                <li class="sidebar-item has-sub <?= $currentPage === 'gallery' ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-images"></i>
                        <span>Gallery</span>
                    </a>
                    <ul class="submenu <?= $currentPage === 'gallery' ? 'active' : '' ?>">
                        <li class="submenu-item <?= strpos($currentFile, 'albums_list') !== false ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/gallery/albums_list.php" class="submenu-link">
                                <i class="bi bi-collection"></i>
                                <span>Semua Album</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentFile === 'albums_add.php' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/gallery/albums_add.php" class="submenu-link">
                                <i class="bi bi-plus-circle"></i>
                                <span>Tambah Album</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- File Download -->
                <li class="sidebar-item <?= $currentPage === 'files' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/files/files_list.php" class="sidebar-link">
                        <i class="bi bi-download"></i>
                        <span>File Download</span>
                    </a>
                </li>

                <!-- Banner -->
                <li class="sidebar-item <?= $currentPage === 'banners' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/banners/banners_list.php" class="sidebar-link">
                        <i class="bi bi-card-image"></i>
                        <span>Banner</span>
                    </a>
                </li>

                <!-- Pesan Kontak -->
                <li class="sidebar-item <?= $currentPage === 'contact' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/contact/messages_list.php" class="sidebar-link">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Pesan Kontak</span>
                        <?php if ($unreadContactCount > 0): ?>
                            <span class="badge bg-danger badge-pill"><?= $unreadContactCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- SECTION: MANAJEMEN -->
                <li class="sidebar-title">Manajemen</li>

                <!-- Users -->
                <li class="sidebar-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/users/users_list.php" class="sidebar-link">
                        <i class="bi bi-people-fill"></i>
                        <span>Pengguna</span>
                        <?php if ($pendingUsersCount > 0): ?>
                            <span class="badge bg-warning badge-pill"><?= $pendingUsersCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- Activity Logs -->
                <li class="sidebar-item <?= $currentPage === 'activity_logs' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/logs/activity_logs.php" class="sidebar-link">
                        <i class="bi bi-clock-history"></i>
                        <span>Activity Logs</span>
                    </a>
                </li>

                <!-- Trash -->
                <li class="sidebar-item <?= $currentPage === 'trash' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/trash/trash_list.php" class="sidebar-link">
                        <i class="bi bi-trash-fill"></i>
                        <span>Trash</span>
                        <?php if ($trashCount > 0): ?>
                            <span class="badge bg-secondary badge-pill"><?= $trashCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- Pengaturan -->
                <li class="sidebar-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/settings/settings.php" class="sidebar-link">
                        <i class="bi bi-gear-fill"></i>
                        <span>Pengaturan</span>
                    </a>
                </li>

                <!-- SECTION: LAPORAN -->
                <li class="sidebar-title">Laporan & Analisis</li>

                <!-- Laporan Utama (Dropdown) -->
                <li class="sidebar-item has-sub <?= in_array($currentReportType, ['report_executive', 'report_posts', 'report_engagement']) ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-bar-chart-fill"></i>
                        <span>Laporan Utama</span>
                    </a>
                    <ul class="submenu <?= in_array($currentReportType, ['report_executive', 'report_posts', 'report_engagement']) ? 'active' : '' ?>">
                        <li class="submenu-item <?= $currentReportType === 'report_executive' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_executive.php" class="submenu-link">
                                <i class="bi bi-speedometer2"></i>
                                <span>Laporan harian</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_posts' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_posts.php" class="submenu-link">
                                <i class="bi bi-journal-text"></i>
                                <span>Postingan</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_engagement' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_engagement.php" class="submenu-link">
                                <i class="bi bi-heart-fill"></i>
                                <span>Engagement</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Laporan Konten (Dropdown) -->
                <li class="sidebar-item has-sub <?= in_array($currentReportType, ['report_categories', 'report_tags', 'report_services', 'report_downloads']) ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-folder-fill"></i>
                        <span>Laporan Konten</span>
                    </a>
                    <ul class="submenu <?= in_array($currentReportType, ['report_categories', 'report_tags', 'report_services', 'report_downloads']) ? 'active' : '' ?>">
                        <li class="submenu-item <?= $currentReportType === 'report_categories' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_categories.php" class="submenu-link">
                                <i class="bi bi-folder2"></i>
                                <span>Kategori</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_tags' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_tags.php" class="submenu-link">
                                <i class="bi bi-tag-fill"></i>
                                <span>Tags</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_services' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_services.php" class="submenu-link">
                                <i class="bi bi-briefcase-fill"></i>
                                <span>Layanan</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_downloads' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_files.php" class="submenu-link">
                                <i class="bi bi-cloud-download"></i>
                                <span>File Download</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Laporan Manajemen (Dropdown) -->
                <li class="sidebar-item has-sub <?= in_array($currentReportType, ['report_users', 'report_activities', 'report_contacts', 'report_security']) ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-shield-check"></i>
                        <span>Laporan Manajemen</span>
                    </a>
                    <ul class="submenu <?= in_array($currentReportType, ['report_users', 'report_activities', 'report_contacts', 'report_security']) ? 'active' : '' ?>">
                        <li class="submenu-item <?= $currentReportType === 'report_users' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_users.php" class="submenu-link">
                                <i class="bi bi-person-badge"></i>
                                <span>Pengguna</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_activities' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_activities.php" class="submenu-link">
                                <i class="bi bi-activity"></i>
                                <span>Aktivitas Sistem</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_contacts' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_contact.php" class="submenu-link">
                                <i class="bi bi-envelope-paper"></i>
                                <span>Pesan Kontak</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_security' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_security.php" class="submenu-link">
                                <i class="bi bi-shield-lock"></i>
                                <span>Keamanan</span>
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
        </div>
    </div>
</div>