<?php
require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/plain');

echo "BASE = " . BASE . "\n";
echo "DOCUMENT_ROOT = " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "__DIR__ (project root) = " . dirname(__DIR__) . "\n\n";

echo "=== campaigns.image_url ===\n";
$result = $conn->query("SELECT campaign_id, title, image_url FROM campaigns ORDER BY campaign_id DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $url = $row['image_url'];
    // Check if physical file exists on disk
    $localPath = $_SERVER['DOCUMENT_ROOT'] . $url;
    $exists = file_exists($localPath) ? 'FILE EXISTS' : 'FILE MISSING at: ' . $localPath;
    echo "ID={$row['campaign_id']} | stored_url=[{$url}] | {$exists}\n";
}

echo "\n=== campaign_images.image_url ===\n";
$result2 = $conn->query("SELECT image_id, campaign_id, image_url FROM campaign_images ORDER BY image_id DESC LIMIT 10");
while ($row = $result2->fetch_assoc()) {
    $url = $row['image_url'];
    $localPath = $_SERVER['DOCUMENT_ROOT'] . $url;
    $exists = file_exists($localPath) ? 'FILE EXISTS' : 'FILE MISSING at: ' . $localPath;
    echo "img_id={$row['image_id']} camp={$row['campaign_id']} | stored_url=[{$url}] | {$exists}\n";
}

echo "\n=== Physical files in uploads/campaigns/ ===\n";
$dir = __DIR__ . '/../uploads/campaigns/';
if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        echo $f . "\n";
    }
} else {
    echo "Directory not found: " . $dir . "\n";
}
?>
