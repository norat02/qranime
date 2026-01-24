<?php
require_once __DIR__ . '/../../security_check.php';

$targetHex = "#aae5fc";
// Image Processing Config
$config = [
    'r' => hexdec(substr($targetHex, 1, 2)),
    'g' => hexdec(substr($targetHex, 3, 2)),
    'b' => hexdec(substr($targetHex, 5, 2)),
    'coords' => [
        ['x' => 721, 'y' => 610], // Góc trên trái
        ['x' => 1284, 'y' => 613], // Góc trên phải
        ['x' => 1288, 'y' => 1174], // Góc dưới phải
        ['x' => 732, 'y' => 1166], // Góc dưới trái
    ]
];

// Text Drawing Config (Move to top for easier updates)
$textConfig = [
    'fontPath' => __DIR__ . '/../../font/nautical_prestige/Nautical Prestige.ttf',
    'fontSizeMax' => 80,
    'colorHex' => '#00a0dfff', // Text Color
    'strokeHex' => '#ffffff', // Stroke Color
    'shadowAlpha' => 115, // Very transparent for soft accumulation
    'box' => [
        'x' => 693,
        'y' => 286,
        'w' => 405,
        'h' => 130
    ],
    // Offsets for 2nd line or spacing
    'lineSpacing' => 10
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('memory_limit', '1024M');
    set_time_limit(120);
    header('Content-Type: application/json');

    try {
        $qrData = $_POST['qr_image'] ?? '';
        if (!$qrData) throw new Exception("No QR provided.");
        if (!file_exists('bg.jpg')) throw new Exception("bg.jpg missing.");

        $bgImage = imagecreatefromjpeg('bg.jpg');
        $parts = explode(',', $qrData);
        $qrBinary = base64_decode(count($parts) > 1 ? $parts[1] : $parts[0]);
        $qrImage = imagecreatefromstring($qrBinary);

        if (!$bgImage || !$qrImage) throw new Exception("Process failed.");

        // Recoloring Logic
        imagealphablending($qrImage, false);
        imagesavealpha($qrImage, true);
        $qw = imagesx($qrImage);
        $qh = imagesy($qrImage);
        for ($x = 0; $x < $qw; $x++) {
            for ($y = 0; $y < $qh; $y++) {
                $rgba = imagecolorat($qrImage, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $a = ($rgba & 0x7F000000) >> 24;
                if ($r < 110 && $g < 110 && $b < 110) {
                    $newCol = imagecolorallocatealpha($qrImage, $config['r'], $config['g'], $config['b'], $a);
                    imagesetpixel($qrImage, $x, $y, $newCol);
                }
            }
        }

        // Warp QR onto Background
        $warper = new Warper();
        $finalImage = $warper->warp($qrImage, $bgImage, $config['coords']);

        // --- Text Drawing Section (Using Global Config) ---
        $bankName = $_POST['bank_name'] ?? 'MB BANK';
        $accNum = $_POST['acc_num'] ?? '0333 444 555';

        if (file_exists($textConfig['fontPath'])) {
            $drawer = new TextDrawer($finalImage, $textConfig['fontPath']);

            // Calculate Center from Config Box
            $centerX = $textConfig['box']['x'] + ($textConfig['box']['w'] / 2);
            $centerY = $textConfig['box']['y'] + ($textConfig['box']['h'] / 2);

            // Convert Hex Config to Colors
            // Helper to parse hex
            sscanf($textConfig['colorHex'], "#%02x%02x%02x", $tr, $tg, $tb);
            $textColor = imagecolorallocate($finalImage, $tr, $tg, $tb);

            sscanf($textConfig['strokeHex'], "#%02x%02x%02x", $sr, $sg, $sb);
            $strokeColor = imagecolorallocate($finalImage, $sr, $sg, $sb);

            $shadowColor = imagecolorallocatealpha($finalImage, 0, 0, 0, $textConfig['shadowAlpha']);

            // Calculate height available for each line
            $maxLineHeight = ($textConfig['box']['h'] - $textConfig['lineSpacing']) / 2;

            // Draw Bank Name (Top Half)
            $drawer->drawFitText(
                $bankName,
                $centerX,
                $centerY - 40, // More spacing (was 25)
                $textConfig['box']['w'],
                $maxLineHeight,
                $textColor,
                $strokeColor,
                $shadowColor,
                $textConfig['fontSizeMax']
            );

            // Draw Account Number (Bottom Half)
            $drawer->drawFitText(
                $accNum,
                $centerX,
                $centerY + 45, // More spacing (was 35)
                $textConfig['box']['w'],
                $maxLineHeight,
                $textColor,
                $strokeColor,
                $shadowColor,
                $textConfig['fontSizeMax']
            );
        }
        // ---------------------------------------------------

        ob_start();
        imagejpeg($finalImage, null, 95);
        $imageData = ob_get_clean();

        echo json_encode(['success' => true, 'image' => 'data:image/jpeg;base64,' . base64_encode($imageData)]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Local Text Drawer Class
class TextDrawer
{
    private $image;
    private $font;

    public function __construct($image, $font)
    {
        $this->image = $image;
        $this->font = $font;
    }

    public function drawFitText($text, $centerX, $centerY, $maxWidth, $maxHeight, $color, $strokeColor, $shadowColor, $maxFontSize)
    {
        // 1. Calculate Font Size
        $fontSize = $maxFontSize;
        do {
            $box = imagettfbbox($fontSize, 0, $this->font, $text);
            $width = abs($box[4] - $box[0]);
            $height = abs($box[5] - $box[1]);

            if ($width <= $maxWidth && $height <= $maxHeight) {
                break;
            }
            $fontSize--;
        } while ($fontSize > 5);

        // 2. Locate Drawing Position (Centered)
        $box = imagettfbbox($fontSize, 0, $this->font, $text);
        $textW = abs($box[4] - $box[0]);
        $textH = abs($box[5] - $box[1]);

        // Calculate X, Y for imagettftext (which uses bottom-left of first char)
        $drawX = $centerX - ($textW / 2);
        $drawY = $centerY + ($textH / 3); // Approximate vertical centering correction

        // 3. Draw Soft/Blurred Shadow
        // We simulate a blur by drawing the text multiple times around the shadow offset
        $shadowOffsetX = 6;
        $shadowOffsetY = 6;
        $blurRadius = 3;

        for ($ox = -$blurRadius; $ox <= $blurRadius; $ox++) {
            for ($oy = -$blurRadius; $oy <= $blurRadius; $oy++) {
                // Determine distance from center to feather edges slightly?
                // For simplicity, just draw everywhere in the box to create a "glow/blur" block
                if (abs($ox) + abs($oy) <= $blurRadius + 1) {
                    imagettftext($this->image, $fontSize, 0, (int)($drawX + $shadowOffsetX + $ox), (int)($drawY + $shadowOffsetY + $oy), $shadowColor, $this->font, $text);
                }
            }
        }

        // 4. Draw Stroke
        $strokeSize = 4;
        for ($c1 = $drawX - $strokeSize; $c1 <= $drawX + $strokeSize; $c1++) {
            for ($c2 = $drawY - $strokeSize; $c2 <= $drawY + $strokeSize; $c2++) {
                imagettftext($this->image, $fontSize, 0, (int)$c1, (int)$c2, $strokeColor, $this->font, $text);
            }
        }

        // 5. Draw Main Text
        imagettftext($this->image, $fontSize, 0, (int)$drawX, (int)$drawY, $color, $this->font, $text);
    }
}

class Warper
{
    public function warp($src, $dst, $c)
    {
        $sw = imagesx($src);
        $sh = imagesy($src);
        $x0 = $c[0]['x'];
        $y0 = $c[0]['y'];
        $x1 = $c[1]['x'];
        $y1 = $c[1]['y'];
        $x2 = $c[2]['x'];
        $y2 = $c[2]['y'];
        $x3 = $c[3]['x'];
        $y3 = $c[3]['y'];

        $minX = floor(min($x0, $x1, $x2, $x3));
        $maxX = ceil(max($x0, $x1, $x2, $x3));
        $minY = floor(min($y0, $y1, $y2, $y3));
        $maxY = ceil(max($y0, $y1, $y2, $y3));

        $H = $this->getH([$x0, $y0], [$x1, $y1], [$x2, $y2], [$x3, $y3], [0, 0], [$sw, 0], [$sw, $sh], [0, $sh]);

        for ($y = $minY; $y <= $maxY; $y++) {
            for ($x = $minX; $x <= $maxX; $x++) {
                $w = $H[6] * $x + $H[7] * $y + $H[8];
                if ($w == 0) continue;
                $sx = ($H[0] * $x + $H[1] * $y + $H[2]) / $w;
                $sy = ($H[3] * $x + $H[4] * $y + $H[5]) / $w;

                if ($sx >= 0 && $sx < $sw && $sy >= 0 && $sy < $sh) {
                    imagesetpixel($dst, $x, $y, imagecolorat($src, (int)$sx, (int)$sy));
                }
            }
        }
        return $dst;
    }

    private function getH($p0, $p1, $p2, $p3, $q0, $q1, $q2, $q3)
    {
        $m = [];
        $r = [];
        $pts = [$p0, $p1, $p2, $p3];
        $tgs = [$q0, $q1, $q2, $q3];
        for ($i = 0; $i < 4; $i++) {
            $x = $pts[$i][0];
            $y = $pts[$i][1];
            $u = $tgs[$i][0];
            $v = $tgs[$i][1];
            $m[] = [$x, $y, 1, 0, 0, 0, -$u * $x, -$u * $y];
            $r[] = $u;
            $m[] = [0, 0, 0, $x, $y, 1, -$v * $x, -$v * $y];
            $r[] = $v;
        }
        $res = $this->solve($m, $r);
        $res[] = 1.0;
        return $res;
    }

    private function solve($A, $B)
    {
        $n = count($B);
        for ($i = 0; $i < $n; $i++) {
            $p = $A[$i][$i] ?: 1e-6;
            for ($j = $i + 1; $j < $n; $j++) {
                $f = $A[$j][$i] / $p;
                $B[$j] -= $f * $B[$i];
                for ($k = $i; $k < $n; $k++) $A[$j][$k] -= $f * $A[$i][$k];
            }
        }
        $X = array_fill(0, $n, 0);
        for ($i = $n - 1; $i >= 0; $i--) {
            $s = 0;
            for ($j = $i + 1; $j < $n; $j++) $s += $A[$i][$j] * $X[$j];
            $X[$i] = ($B[$i] - $s) / ($A[$i][$i] ?: 1e-6);
        }
        return $X;
    }
}
