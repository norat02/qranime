<?php
require_once __DIR__ . '/../../security_check.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('memory_limit', '1024M');
    set_time_limit(120);
    header('Content-Type: application/json');

    try {
        $qrData = $_POST['qr_image'] ?? '';

        $coords = [
            ['x' => 119, 'y' => 514],
            ['x' => 551, 'y' => 510],
            ['x' => 619, 'y' => 943],
            ['x' => 176, 'y' => 953]
        ];

        if (!$qrData) throw new Exception("Please upload a QR code.");

        if (!file_exists('bg.jpg')) throw new Exception("Background file 'bg.jpg' not found on server.");
        $bgImage = imagecreatefromjpeg('bg.jpg');

        $parts = explode(',', $qrData);
        $qrBinary = base64_decode(count($parts) > 1 ? $parts[1] : $parts[0]);
        $qrImage = imagecreatefromstring($qrBinary);

        if (!$bgImage || !$qrImage) throw new Exception("Failed to process images.");

        $targetR = 0x39;
        $targetG = 0x94;
        $targetB = 0xC3;

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
                    $newCol = imagecolorallocatealpha($qrImage, $targetR, $targetG, $targetB, $a);
                    imagesetpixel($qrImage, $x, $y, $newCol);
                }
            }
        }

        $warper = new PerspectiveWarper();
        $finalImage = $warper->warp($qrImage, $bgImage, $coords);

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

class PerspectiveWarper
{
    public function warp($srcImg, $dstImg, $coords)
    {
        $sw = imagesx($srcImg);
        $sh = imagesy($srcImg);
        $x0 = $coords[0]['x'];
        $y0 = $coords[0]['y'];
        $x1 = $coords[1]['x'];
        $y1 = $coords[1]['y'];
        $x2 = $coords[2]['x'];
        $y2 = $coords[2]['y'];
        $x3 = $coords[3]['x'];
        $y3 = $coords[3]['y'];

        $minX = floor(min($x0, $x1, $x2, $x3));
        $maxX = ceil(max($x0, $x1, $x2, $x3));
        $minY = floor(min($y0, $y1, $y2, $y3));
        $maxY = ceil(max($y0, $y1, $y2, $y3));

        $H_inv = $this->getHomographyMatrix([$x0, $y0], [$x1, $y1], [$x2, $y2], [$x3, $y3], [0, 0], [$sw, 0], [$sw, $sh], [0, $sh]);

        for ($y = $minY; $y <= $maxY; $y++) {
            for ($x = $minX; $x <= $maxX; $x++) {
                $w_val = $H_inv[6] * $x + $H_inv[7] * $y + $H_inv[8];
                if ($w_val == 0) continue;
                $srcX = ($H_inv[0] * $x + $H_inv[1] * $y + $H_inv[2]) / $w_val;
                $srcY = ($H_inv[3] * $x + $H_inv[4] * $y + $H_inv[5]) / $w_val;

                if ($srcX >= 0 && $srcX < $sw && $srcY >= 0 && $srcY < $sh) {
                    $color = imagecolorat($srcImg, (int)$srcX, (int)$srcY);
                    imagesetpixel($dstImg, $x, $y, $color);
                }
            }
        }
        return $dstImg;
    }

    private function getHomographyMatrix($p0, $p1, $p2, $p3, $q0, $q1, $q2, $q3)
    {
        $matrix = [];
        $rhs = [];
        $pts = [$p0, $p1, $p2, $p3];
        $tgs = [$q0, $q1, $q2, $q3];
        for ($i = 0; $i < 4; $i++) {
            $x = $pts[$i][0];
            $y = $pts[$i][1];
            $u = $tgs[$i][0];
            $v = $tgs[$i][1];
            $matrix[] = [$x, $y, 1, 0, 0, 0, -$u * $x, -$u * $y];
            $rhs[] = $u;
            $matrix[] = [0, 0, 0, $x, $y, 1, -$v * $x, -$v * $y];
            $rhs[] = $v;
        }
        $h = $this->solve($matrix, $rhs);
        $h[] = 1.0;
        return $h;
    }

    private function solve($A, $B)
    {
        $n = count($B);
        for ($i = 0; $i < $n; $i++) {
            $pivot = $A[$i][$i] ?: 0.000001;
            for ($j = $i + 1; $j < $n; $j++) {
                $f = $A[$j][$i] / $pivot;
                $B[$j] -= $f * $B[$i];
                for ($k = $i; $k < $n; $k++) $A[$j][$k] -= $f * $A[$i][$k];
            }
        }
        $X = array_fill(0, $n, 0);
        for ($i = $n - 1; $i >= 0; $i--) {
            $sum = 0;
            for ($j = $i + 1; $j < $n; $j++) $sum += $A[$i][$j] * $X[$j];
            $X[$i] = ($B[$i] - $sum) / ($A[$i][$i] ?: 0.000001);
        }
        return $X;
    }
}
