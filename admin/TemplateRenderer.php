<?php
// Core Renderer for Smart Templates

class TemplateRenderer
{
    private $dir;
    private $config;

    public function __construct($dir, $config)
    {
        $this->dir = $dir;
        $this->config = $config;
    }

    public function render()
    {
        // Prevent any HTML output from errors
        error_reporting(0);
        ini_set('display_errors', 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // 1. Load Background
                $bgPath = $this->dir . '/bg.jpg';
                if (!file_exists($bgPath)) throw new Exception("Background not found at: " . $bgPath);

                // Use generic loader
                $bgContent = file_get_contents($bgPath);
                if (!$bgContent) throw new Exception("Failed to read background file");

                $bg = imagecreatefromstring($bgContent);
                if (!$bg) throw new Exception("Failed to process background image. Format might be unsupported.");

                // Ensure we have a valid image resource
                if (!($bg instanceof \GdImage) && !is_resource($bg)) {
                    throw new Exception("GD Image creation failed");
                }

                // 2. Load QR Image (Base64 or Uploaded)
                if (isset($_POST['qr_image'])) {
                    $qrData = $_POST['qr_image'];
                    if (strpos($qrData, ',') !== false) {
                        $qrData = explode(',', $qrData)[1];
                    }
                    $qrDecoded = base64_decode($qrData);
                    $qrImg = imagecreatefromstring($qrDecoded);

                    if ($qrImg) {
                        $qrW = imagesx($qrImg);
                        $qrH = imagesy($qrImg);
                        $targetSize = $this->config['qr']['size'];

                        imagecopyresampled(
                            $bg,
                            $qrImg,
                            $this->config['qr']['x'],
                            $this->config['qr']['y'],
                            0,
                            0,
                            $targetSize,
                            $targetSize,
                            $qrW,
                            $qrH
                        );
                        imagedestroy($qrImg);
                    }
                }

                // 3. Draw Text
                $fontPath = __DIR__ . '/../font/Inter-Bold.ttf';
                if (!file_exists($fontPath)) {
                    $fonts = glob(__DIR__ . '/../font/*.ttf');
                    if (!empty($fonts)) $fontPath = $fonts[0];
                }

                // Verify Font Exists before drawing
                if (file_exists($fontPath)) {
                    if (isset($_POST['bank_name']) && isset($this->config['bank_name'])) {
                        $this->drawText($bg, $_POST['bank_name'], $this->config['bank_name'], $fontPath);
                    }
                    if (isset($_POST['acc_num']) && isset($this->config['acc_num'])) {
                        $this->drawText($bg, $_POST['acc_num'], $this->config['acc_num'], $fontPath);
                    }
                }

                // Output
                ob_clean(); // Clear any previous output buffer
                header('Content-Type: application/json');

                ob_start();
                imagejpeg($bg);
                $imgData = ob_get_clean();
                imagedestroy($bg);

                echo json_encode([
                    'success' => true,
                    'image' => 'data:image/jpeg;base64,' . base64_encode($imgData)
                ]);
            } catch (Throwable $e) {
                ob_clean(); // Clean buffer
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
        }
    }

    private function drawText($img, $text, $cfg, $font)
    {
        try {
            $colorHex = $cfg['color'];
            $r = hexdec(substr($colorHex, 1, 2));
            $g = hexdec(substr($colorHex, 3, 2));
            $b = hexdec(substr($colorHex, 5, 2));
            $color = imagecolorallocate($img, $r, $g, $b);

            $size = $cfg['size'];
            $x = $cfg['x'];
            $y = $cfg['y'];

            imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
        } catch (Throwable $t) {
            // Ignore text drawing errors
        }
    }
}
