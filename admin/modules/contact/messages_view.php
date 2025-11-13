<?php
/**
 * Contact Messages - View Detail
 * Simplified: No archive feature
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

 $pageTitle = 'Detail Pesan';
 $currentPage = 'contact';

 $db = Database::getInstance()->getConnection();

// Get message ID
 $messageId = (int)($_GET['id'] ?? 0);

if (!$messageId) {
    setAlert('danger', 'Pesan tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/contact/messages_list.php');
    exit;
}

// Get message data
 $stmt = $db->prepare("SELECT * FROM contact_messages WHERE id = ?");
 $stmt->execute([$messageId]);
 $message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    setAlert('danger', 'Pesan tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/contact/messages_list.php');
    exit;
}

// Mark as read if unread
if ($message['status'] === 'unread') {
    $updateStmt = $db->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
    $updateStmt->execute([$messageId]);
    $message['status'] = 'read';
    
    // Log activity
    logActivity('UPDATE', "Membaca pesan kontak #{$messageId}", 'contact_messages', $messageId);
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 mb-3 mb-md-0">
                <h3><i class=""></i><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted mb-0">Detail pesan dari pengunjung</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="messages_list.php">Pesan Kontak</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-12">
                <!-- Message Header Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php
                                $statusBadges = [
                                    'unread' => '<span class="badge bg-danger"><i class="bi bi-envelope-fill me-1"></i> Belum Dibaca</span>',
                                    'read' => '<span class="badge bg-primary"><i class="bi bi-envelope-open me-1"></i> Sudah Dibaca</span>'
                                ];
                                echo $statusBadges[$message['status']] ?? '';
                                ?>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="messages_list.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i>
                                    <span class="d-none d-md-inline ms-1">Kembali</span>
                                </a>
                                <a href="messages_delete.php?id=<?= $message['id'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   data-confirm-delete
                                   data-title="Hapus Pesan"
                                   data-message="Apakah Anda yakin ingin menghapus pesan ini? Tindakan ini tidak dapat dibatalkan."
                                   data-loading-text="Menghapus...">
                                    <i class="bi bi-trash"></i>
                                    <span class="d-none d-md-inline ms-1">Hapus</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Message Content -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <!-- Subject -->
                        <h4 class="mb-4"><?= htmlspecialchars($message['subject']) ?></h4>
                        
                        <!-- Sender Info -->
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center mb-4 pb-4 border-bottom">
                            <div class="d-flex align-items-center flex-grow-1 mb-3 mb-md-0">
                                <div class="avatar avatar-xl me-3">
                                    <div class="avatar-content bg-primary rounded-circle">
                                        <?= strtoupper(substr($message['name'], 0, 2)) ?>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-1"><?= htmlspecialchars($message['name']) ?></h5>
                                    <div class="d-flex flex-column flex-md-row gap-2 text-muted small">
                                        <span><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($message['email']) ?></span>
                                        <?php if ($message['phone']): ?>
                                            <span><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($message['phone']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-start text-md-end">
                                <div class="small text-muted">
                                    <div><i class="bi bi-calendar me-1"></i> <?= formatTanggal($message['created_at'], 'd F Y') ?></div>
                                    <div><i class="bi bi-clock me-1"></i> <?= formatTanggal($message['created_at'], 'H:i') ?> WIB</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Message Content -->
                        <div class="message-content p-3 rounded">
                            <?= nl2br(htmlspecialchars($message['message'])) ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Informasi Pesan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Status</span>
                                <?php
                                $statusText = [
                                    'unread' => '<span class="badge bg-danger">Belum Dibaca</span>',
                                    'read' => '<span class="badge bg-primary">Sudah Dibaca</span>'
                                ];
                                echo $statusText[$message['status']] ?? '-';
                                ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Diterima</span>
                                <span class="small"><?= formatTanggal($message['created_at'], 'd F Y') ?></span>
                            </div>
                            <div class="text-end">
                                <span class="small text-muted"><?= formatTanggal($message['created_at'], 'H:i') ?> WIB</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">IP Address</span>
                                <code class="small"><?= htmlspecialchars($message['ip_address'] ?? '-') ?></code>
                            </div>
                        </div>
                        
                        <?php if (!empty($message['user_agent'])): ?>
                            <hr>
                            <div>
                                <h6 class="text-muted small mb-2">User Agent</h6>
                                <div class="small text-muted p-2 rounded" style="word-break: break-all; font-size: 0.75rem;">
                                    <?= htmlspecialchars($message['user_agent']) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightning me-2"></i>Aksi Cepat
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="mailto:<?= htmlspecialchars($message['email']) ?>" class="btn btn-outline-primary">
                                <i class="bi bi-reply me-2"></i>Balas via Email
                            </a>
                            <button class="btn btn-outline-secondary" onclick="window.print()">
                                <i class="bi bi-printer me-2"></i>Cetak Pesan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>