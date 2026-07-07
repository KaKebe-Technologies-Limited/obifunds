<?php
// Serve as og-default.png via .htaccess or call directly
// Generates the default OG image dynamically for pages without a campaign photo
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

$W = 1200; $H = 630;
$img = imagecreatetruecolor($W, $H);

// Background gradient (green)
for ($y = 0; $y < $H; $y++) {
    $r = (int)(20  + (0   - 20)  * ($y/$H));
    $g = (int)(100 + (70  - 100) * ($y/$H));
    $b = (int)(50  + (30  - 50)  * ($y/$H));
    imageline($img, 0, $y, $W, $y, imagecolorallocate($img, $r, $g, $b));
}

// Yellow accent circle top-right
imagefilledellipse($img, $W+80, -80, 480, 480, imagecolorallocate($img, 245, 197, 24));

// Logo box
$logoBox = imagecolorallocate($img, 245, 197, 24);
imagefilledrectangle($img, 80, 80, 180, 160, $logoBox);

// Text
$white = imagecolorallocate($img, 255, 255, 255);
$yellow= imagecolorallocate($img, 245, 197, 24);

// Try system fonts
$font = null;
foreach (['/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf','/Library/Fonts/Arial Bold.ttf','C:/Windows/Fonts/arialbd.ttf'] as $f) {
    if (file_exists($f)) { $font = $f; break; }
}

if ($font) {
    imagettftext($img, 40, 0, 80, 280, $yellow, $font, 'ObiFunds');
    imagettftext($img, 22, 0, 80, 330, $white,  $font, 'Africa\'s Mobile-Money Crowdfunding Platform');
    imagettftext($img, 18, 0, 80, 390, imagecolorallocate($img,200,240,215), $font, 'obifunds.com  ·  Free to start  ·  Same-day payout');
} else {
    imagestring($img, 5, 80, 260, 'ObiFunds', $yellow);
    imagestring($img, 4, 80, 300, 'Africa\'s Mobile-Money Crowdfunding Platform', $white);
}

imagepng($img);
imagedestroy($img);
