<?php
/**
 * Public Configuration
 * Includes core config, starts session, and checks maintenance mode
 */

// Start session
session_start();

// Include core configuration
require_once __DIR__ . '/../config/config.php';

// Include core classes
require_once ROOT_PATH . 'core/Database.php';
require_once ROOT_PATH . 'core/Helper.php';
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'core/Pagination.php';
require_once ROOT_PATH . 'core/MetaTags.php';

// Include models (only if file exists)
$models = [
    'Post',
    'PostCategory',
    'Service',
    'User',
    'Banner',
    'Page',
    'Gallery',
    'File',
    'Tag',
    'Comment',
    'Setting'
];

foreach ($models as $model) {
    $modelPath = ROOT_PATH . 'models/' . $model . '.php';
    if (file_exists($modelPath)) {
        require_once $modelPath;
    }
}

// Include public functions
require_once __DIR__ . '/functions.php';

// Initialize database connection
$db = Database::getInstance()->getConnection();

// ===================================================================
// MAINTENANCE MODE CHECK
// ===================================================================

/**
 * Check if maintenance mode is active
 * Redirects to maintenance.php if active and user is not admin
 */
function checkMaintenanceMode() {
    // Get maintenance mode status from settings
    $maintenanceMode = getSetting('site_maintenance_mode', '0');
    
    // If maintenance mode is OFF, continue normally
    if ($maintenanceMode !== '1') {
        return;
    }
    
    // Check if user is admin (bypass maintenance mode for admins)
    $isAdmin = false;
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        $isAdmin = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');
    }
    
    // If admin, allow access (show notice but don't block)
    if ($isAdmin) {
        // Optional: Set a session variable to show admin notice
        $_SESSION['maintenance_mode_active'] = true;
        return;
    }
    
    // Non-admin users: Redirect to maintenance page
    // Check if we're not already on maintenance.php to prevent redirect loop
    $currentScript = basename($_SERVER['SCRIPT_NAME']);
    if ($currentScript !== 'maintenance.php') {
        header('Location: ' . BASE_URL . 'maintenance.php');
        exit;
    }
}

// Execute maintenance mode check
checkMaintenanceMode();

// If we reach here, site is not in maintenance mode or user is admin
// Continue with normal page execution