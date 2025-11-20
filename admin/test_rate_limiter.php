<?php
require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/RateLimiter.php';

$rateLimiter = new RateLimiter();

// Test 1: Check rate limit
echo "<h3>Test 1: Check Rate Limit</h3>";
$result = $rateLimiter->check('192.168.1.1', 'contact', 5, 15);
echo "<pre>";
print_r($result);
echo "</pre>";

// Test 2: Record action
echo "<h3>Test 2: Record Action</h3>";
$recorded = $rateLimiter->record('192.168.1.1', 'contact', 15);
echo $recorded ? "✓ Action recorded" : "✗ Failed to record";
echo "<br><br>";

// Test 3: Check again after record
echo "<h3>Test 3: Check After Record</h3>";
$result2 = $rateLimiter->check('192.168.1.1', 'contact', 5, 15);
echo "<pre>";
print_r($result2);
echo "</pre>";

// Test 4: Get stats
echo "<h3>Test 4: Get Stats</h3>";
$stats = $rateLimiter->getStats('192.168.1.1', 'contact');
echo "<pre>";
print_r($stats);
echo "</pre>";
