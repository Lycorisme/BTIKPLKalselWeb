<?php
/**
 * Public Configuration
 * Includes core config and starts session
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
