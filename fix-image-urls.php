<?php
// ============================================================
// fix-image-urls.php — ONE-TIME script
// Fixes image URLs stored as absolute localhost/full-domain
// paths → converts them to relative /uploads/campaigns/...
// Run once, then DELETE this file.
// ============================================================
require_once __DIR__ . '/includes/config.php';

// Patterns to strip from the start of image_url values
$stripPatterns = [
    'http://localhost/sedufunds/chama',
    'http://localhost/sedufunds',
    'http://localhost/chama',
    'https://localhost/sedufunds/chama',
    'https://localhost/chama',
    'http://127.0.0.1/sedufunds/chama',
    'https://undpconnect.org/chama',
    'http://undpconnect.org/chama',
    'https://chama.kakebeshop.com',
    'http://chama.kakebeshop.com',
    'https://obifunds.com',
    'http://obifunds.com',
    'https://www.obifunds.com',
    BASE, // current resolved base — catches anything not listed above
];

$fixed = 0;
$skipped = 0;

// Fix campaigns.image_url
$result = $conn->query("SELECT campaign_id, image_url FROM campaigns WHERE image_url != '' AND image_url IS NOT NULL");
while ($row = $result->fetch_assoc()) {
    $url = $row['image_url'];
    $newUrl = $url;
    foreach ($stripPatterns as $p) {
        if ($p && strpos($url, $p) === 0) {
            $newUrl = substr($url, strlen($p));
            break;
        }
    }
    if ($newUrl !== $url) {
        $esc = $conn->real_escape_string($newUrl);
        $conn->query("UPDATE campaigns SET image_url='$esc' WHERE campaign_id={$row['campaign_id']}");
        echo "✅ campaigns #{$row['campaign_id']}: {$url} → {$newUrl}<br>";
        $fixed++;
    } else {
        $skipped++;
    }
}

// Fix campaign_images.image_url
$result2 = $conn->query("SELECT image_id, image_url FROM campaign_images WHERE image_url != '' AND image_url IS NOT NULL");
while ($row = $result2->fetch_assoc()) {
    $url = $row['image_url'];
    $newUrl = $url;
    foreach ($stripPatterns as $p) {
        if ($p && strpos($url, $p) === 0) {
            $newUrl = substr($url, strlen($p));
            break;
        }
    }
    if ($newUrl !== $url) {
        $esc = $conn->real_escape_string($newUrl);
        $conn->query("UPDATE campaign_images SET image_url='$esc' WHERE image_id={$row['image_id']}");
        echo "✅ campaign_images #{$row['image_id']}: {$url} → {$newUrl}<br>";
        $fixed++;
    } else {
        $skipped++;
    }
}

echo "<br><strong>Done. Fixed: {$fixed} | Already OK: {$skipped}</strong>";
echo "<br><br><strong style='color:red'>DELETE this file from your server now!</strong>";
?>
