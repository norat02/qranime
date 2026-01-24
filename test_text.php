<?php
require_once __DIR__ . '/TextStyler.php';

// 1. Create a dummy background image (e.g., 800x600 white or grey)
$width = 800;
$height = 600;
$image = imagecreatetruecolor($width, $height);

// Fill with a background color (Dark Grey to show white outline)
$bgColor = imagecolorallocate($image, 50, 50, 50);
imagefill($image, 0, 0, $bgColor);

// 2. Setup Font and Text
$fontPath = __DIR__ . '/font/nautical_prestige/Nautical Prestige.ttf';
$text1 = "Nguyen Trong Thao";
$text2 = "066775";
$fontSize = 60;
$angle = 0;

// 3. Define Colors (Based on User's Image)
// Main Text: Light Blue (#aae5fc)
$textColor = imagecolorallocate($image, 0xaa, 0xe5, 0xfc);
// Stroke: White
$strokeColor = imagecolorallocate($image, 255, 255, 255);
// Shadow: Soft Gray
$shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 60); // Darker shadow for visibility

// 4. Draw
if (file_exists($fontPath)) {

    // Line 1
    $textX1 = TextStyler::centerTextX($image, $fontSize, $angle, $fontPath, $text1);
    $textY1 = ($height / 2) - 40;

    TextStyler::draw(
        $image,
        $fontSize,
        $angle,
        $textX1,
        $textY1,
        $textColor,
        $fontPath,
        $text1,
        $strokeColor,
        5,      // Thicker stroke for that sticker look
        $shadowColor,
        6,
        6    // Offset shadow
    );

    // Line 2
    $textX2 = TextStyler::centerTextX($image, $fontSize, $angle, $fontPath, $text2);
    $textY2 = ($height / 2) + 60;

    TextStyler::draw(
        $image,
        $fontSize,
        $angle,
        $textX2,
        $textY2,
        $textColor,
        $fontPath,
        $text2,
        $strokeColor,
        5,
        $shadowColor,
        6,
        6
    );
} else {
    // Fallback if font missing
    $red = imagecolorallocate($image, 255, 0, 0);
    imagestring($image, 5, 10, 10, "Font not found: $fontPath", $red);
}

// 6. Output
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
