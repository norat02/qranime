<?php
require_once __DIR__ . '/../../security_check.php';
require_once __DIR__ . '/../../TextStyler.php';
$targetHex = "#aae5fc";
$config = [
    'r' => hexdec(substr($targetHex, 1, 2)),
    'g' => hexdec(substr($targetHex, 3, 2)),
    'b' => hexdec(substr($targetHex, 5, 2)),
    'coords' => [
        ['x' => 935, 'y' => 746], // Góc trên trái
        ['x' => 1862, 'y' => 741], // Góc trên phải
        ['x' => 1859, 'y' => 1720], // Góc dưới phải
        ['x' => 937, 'y' => 1716], // Góc dưới trái
    ]
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

        $warper = new Warper();
        $finalImage = $warper->warp($qrImage, $bgImage, $config['coords']);

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
