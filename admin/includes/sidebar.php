<?php
// Ambil status dan badge count (kode PHP tetap seperti sebelumnya)
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
elseif (strpos($currentPath, '/reports/') !== false) {
    $currentPage = 'reports';
    if (strpos($currentPath, 'report_posts') !== false) $currentReportType = 'report_posts';
    elseif (strpos($currentPath, 'report_services') !== false) $currentReportType = 'report_services';
    elseif (strpos($currentPath, 'report_users') !== false) $currentReportType = 'report_users';
    elseif (strpos($currentPath, 'report_categories') !== false) $currentReportType = 'report_categories';
    elseif (strpos($currentPath, 'report_overview') !== false) $currentReportType = 'report_overview';
    elseif (strpos($currentPath, 'report_activities') !== false) $currentReportType = 'report_activities';
    elseif (strpos($currentPath, 'report_file_download') !== false) $currentReportType = 'report_file_download';
    elseif (strpos($currentPath, 'report_kegiatan') !== false) $currentReportType = 'report_kegiatan';
    elseif (strpos($currentPath, 'report_contact') !== false) $currentReportType = 'report_contact';
}
elseif (strpos($currentPath, '/activity-logs/') !== false) $currentPage = 'activity_logs';
elseif ($currentFile === 'index.php' && strpos($currentPath, '/admin/index.php') !== false) $currentPage = 'dashboard';

// Badge counts
$db = Database::getInstance()->getConnection();
$unreadContactStmt = $db->query("SELECT COUNT(*) as unread FROM contact_messages WHERE status = 'unread'");
$unreadContactData = $unreadContactStmt->fetch();
$unreadContactCount = $unreadContactData['unread'] ?? 0;

$trashCountStmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM services WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM downloadable_files WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM gallery_albums WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM gallery_photos WHERE deleted_at IS NOT NULL) as total
");
$trashCountData = $trashCountStmt->fetch();
$trashCount = $trashCountData['total'] ?? 0;
?>

<div id="sidebar">
  <div class="sidebar-wrapper active">

    <!-- Sidebar Header Logo & Text -->
    <div class="sidebar-header position-relative pb-2 pt-4 mb-4">
      <div class="d-flex flex-column align-items-center justify-content-center" style="gap:8px;">
        <!-- LOGO --> 
        <?php if ($adminLogo = getSetting('site_logo')): ?>
          <img src="<?= uploadUrl($adminLogo) ?>" alt="Logo BTIKP" style="height:70px; width:auto; display:block;">
        <?php else: ?>
          <img src="<?= BASE_URL ?>path/to/default/logo.png" alt="Logo" style="height:70px; width:auto; display:block;">
        <?php endif; ?>

        <!-- Logo Text --> 
        <?php if (getSetting('site_logo_show_text', '1') == '1'): ?>
          <span style="font-size:1.5rem; font-weight:800; color:var(--bs-primary); text-align:center;"> 
            <?= getSetting('site_logo_text', 'BTIKP KALSEL') ?>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sidebar Menu -->
    <div class="sidebar-menu">
      <ul class="menu">
        <li class="sidebar-title">Menu</li>
        <li class="sidebar-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>" class="sidebar-link">
            <i class="bi bi-grid-fill"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li class="sidebar-item has-sub <?= $currentPage === 'posts' || $currentPage === 'categories' || $currentPage === 'tags' ? 'active' : '' ?>">
          <a href="#" class="sidebar-link">
            <i class="bi bi-newspaper"></i>
            <span>Berita & Artikel</span>
          </a>
          <ul class="submenu">
            <li class="submenu-item <?= $currentPage === 'posts' ? 'active' : '' ?>">
              <a href="<?= ADMIN_URL ?>modules/posts/posts_list.php">Semua Post</a>
            </li>
            <li class="submenu-item <?= $currentPage === 'posts' && $currentFile === 'posts_add.php' ? 'active' : '' ?>">
              <a href="<?= ADMIN_URL ?>modules/posts/posts_add.php">Tambah Baru</a>
            </li>
            <li class="submenu-item <?= $currentPage === 'categories' ? 'active' : '' ?>">
              <a href="<?= ADMIN_URL ?>modules/categories/categories_list.php">Kategori</a>
            </li>
            <li class="submenu-item <?= $currentPage === 'tags' ? 'active' : '' ?>">
              <a href="<?= ADMIN_URL ?>modules/tags/tags_list.php">Tags</a>
            </li>
          </ul>
        </li>
        <li class="sidebar-item <?= $currentPage === 'services' ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/services/services_list.php" class="sidebar-link">
            <i class="bi bi-gear-fill"></i>
            <span>Layanan</span>
          </a>
        </li>
        <li class="sidebar-item has-sub <?= $currentPage === 'gallery' ? 'active' : '' ?>">
          <a href="#" class="sidebar-link">
            <i class="bi bi-images"></i>
            <span>Gallery</span>
          </a>
          <ul class="submenu">
            <li class="submenu-item <?= strpos($currentFile, 'albums_') !== false ? 'active' : '' ?>">
              <a href="<?= ADMIN_URL ?>modules/gallery/albums_list.php">Semua Album</a>
            </li>
            <li class="submenu-item <?= $currentFile === 'albums_add.php' ? 'active' : '' ?>">
              <a href="<?= ADMIN_URL ?>modules/gallery/albums_add.php">Tambah Album</a>
            </li>
          </ul>
        </li>
        <li class="sidebar-item <?= $currentPage === 'pages' ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/pages/pages_list.php" class="sidebar-link">
            <i class="bi bi-file-earmark-text"></i>
            <span>Halaman</span>
          </a>
        </li>
        <li class="sidebar-item <?= $currentPage === 'files' ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/files/files_list.php" class="sidebar-link">
            <i class="bi bi-download"></i>
            <span>File Download</span>
          </a>
        </li>
        <li class="sidebar-item <?= $currentPage === 'banners' ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/banners/banners_list.php" class="sidebar-link">
            <i class="bi bi-card-image"></i>
            <span>Banner</span>
          </a>
        </li>
        <li class="sidebar-item <?= $currentPage === 'contact' ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/contact/messages_list.php" class="sidebar-link">
            <i class="bi bi-envelope"></i>
            <span>Pesan Kontak</span>
            <?php if ($unreadContactCount > 0): ?>
              <span class="badge bg-danger ms-auto"><?= $unreadContactCount ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="sidebar-title">Manajemen</li>
        <li class="sidebar-item <?= $currentPage === 'users' ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/users/users_list.php" class="sidebar-link">
            <i class="bi bi-people-fill"></i>
            <span>Users</span>
          </a>
        </li>
        <li class="sidebar-item <?= $currentPage === 'activity_logs' ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/logs/activity_logs.php" class="sidebar-link">
            <i class="bi bi-clock-history"></i>
            <span>Activity Logs</span>
          </a>
        </li>
        <li class="sidebar-item <?= $currentPage === 'trash' ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/trash/trash_list.php" class="sidebar-link">
            <i class="bi bi-trash"></i>
            <span>Trash</span>
            <?php if ($trashCount > 0): ?>
              <span class="badge bg-secondary ms-auto"><?= $trashCount ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="sidebar-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/settings/settings.php" class="sidebar-link">
            <i class="bi bi-gear"></i>
            <span>Pengaturan</span>
          </a>
        </li>
        <li class="sidebar-title">Laporan</li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_overview') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_overview.php" class="sidebar-link">
            <i class="bi bi-bar-chart-steps"></i>
            <span>Laporan Overview</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_activities') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_activities.php" class="sidebar-link">
            <i class="bi bi-diagram-3"></i>
            <span>Laporan Sistem</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_kegiatan') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_kegiatan.php" class="sidebar-link">
            <i class="bi bi-calendar-event"></i>
            <span>Laporan Kegiatan</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_posts') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_posts.php" class="sidebar-link">
            <i class="bi bi-journal-text"></i>
            <span>Laporan Post</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_services') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_services.php" class="sidebar-link">
            <i class="bi bi-briefcase-fill"></i>
            <span>Laporan Layanan</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_categories') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_categories.php" class="sidebar-link">
            <i class="bi bi-tags"></i>
            <span>Laporan Kategori & Tag</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_users') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_users.php" class="sidebar-link">
            <i class="bi bi-person-lines-fill"></i>
            <span>Laporan User</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_file_download') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_files.php" class="sidebar-link">
            <i class="bi bi-download"></i>
            <span>Laporan File Download</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_contact') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_contact.php" class="sidebar-link">
            <i class="bi bi-envelope"></i>
            <span>Laporan Pesan Kontak</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>

<!-- Script Animasi & Expand Menu Mazer -->
<script src="<?= BASE_URL ?>assets/static/js/sidebar.js"></script>
<script>
  // Script utama handling sidebar: collapse-expand, animation, responsive, dan toggle theme
  document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.querySelector('.sidebar-wrapper');
    var toggler = document.querySelector('.sidebar-toggler');
    var submenuLinks = document.querySelectorAll('.sidebar-item.has-sub > .sidebar-link');
    var menuActive = document.querySelectorAll('.sidebar-item.active.has-sub > .submenu');

    // Dropdown expand/collapse
    submenuLinks.forEach(function(link) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        var parent = link.parentElement;
        var subMenu = parent.querySelector('.submenu');
        if (subMenu) {
          parent.classList.toggle('show');
          subMenu.classList.toggle('show');
          setTimeout(function() {
            if (subMenu.classList.contains('show')) {
              subMenu.style.maxHeight = subMenu.scrollHeight + "px";
            } else {
              subMenu.style.maxHeight = null;
            }
          }, 10);
        }
      });
    });

    // Sidebar hide (close animation)
    toggler && toggler.addEventListener('click', function(e) {
      e.preventDefault();
      sidebar.classList.toggle('hide');
    });

    // Theme toggle (dark/light)
    var themeSwitch = document.getElementById('toggle-dark');
    themeSwitch && themeSwitch.addEventListener('change', function() {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });

    // Inisialisasi submenu yang aktif
    menuActive.forEach(function(submenu) {
      submenu.classList.add('show');
      submenu.style.maxHeight = submenu.scrollHeight + "px";
      submenu.parentElement.classList.add('show');
    });
    // Responsif pada mobile: sidebar hide di klik luar pada layar kecil
    window.addEventListener('click', function(e) {
      var isSidebar = e.target.closest('.sidebar-wrapper');
      var isToggler = e.target.closest('.sidebar-toggler');
      if (!isSidebar && !isToggler && window.innerWidth <= 991) {
        sidebar.classList.add('hide');
      }
    });
  });
</script>
