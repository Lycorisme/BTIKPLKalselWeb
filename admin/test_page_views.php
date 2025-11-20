<?php
session_start();

require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/PageViewTracker.php';

header('Content-Type: text/plain');

$tracker = new PageViewTracker();

echo "=== PAGE VIEW TRACKER TEST ===\n\n";

// Test 1: Track view
echo "1. Track Post ID 1:\n";
$result = $tracker->track('post', 1);
echo "   Result: " . ($result ? 'SUCCESS' : 'FAILED or DUPLICATE') . "\n\n";

// Test 2: Track again (should be duplicate)
echo "2. Track Post ID 1 Again (should be duplicate):\n";
$result = $tracker->track('post', 1);
echo "   Result: " . ($result ? 'SUCCESS' : 'DUPLICATE (expected)') . "\n\n";

// Test 3: Get stats
echo "3. Get Stats for Post ID 1:\n";
$stats = $tracker->getStats('post', 1, 30);
print_r($stats);
echo "\n";

// Test 4: Get trend
echo "4. Get View Trend (last 7 days):\n";
$trend = $tracker->getViewTrend('post', 1, 7);
print_r($trend);
echo "\n";

// Test 5: Database check
echo "5. Database Records:\n";
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM page_views ORDER BY created_at DESC LIMIT 5");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($records);
