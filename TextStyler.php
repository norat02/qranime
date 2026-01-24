<?php
class TextStyler
{
    /**
     * Draws text with stroke (outline) and shadow effects.
     */
    public static function draw($image, $size, $angle, $x, $y, $color, $font, $text, $strokeColor = null, $strokeSize = 0, $shadowColor = null, $shadowOffsetX = 2, $shadowOffsetY = 2)
    {
        // 1. Draw Shadow
        if ($shadowColor !== null) {
            imagettftext($image, $size, $angle, (int)($x + $shadowOffsetX), (int)($y + $shadowOffsetY), $shadowColor, $font, $text);
        }

        // 2. Draw Stroke
        if ($strokeColor !== null && $strokeSize > 0) {
            for ($c1 = $x - $strokeSize; $c1 <= $x + $strokeSize; $c1++) {
                for ($c2 = $y - $strokeSize; $c2 <= $y + $strokeSize; $c2++) {
                    imagettftext($image, $size, $angle, (int)$c1, (int)$c2, $strokeColor, $font, $text);
                }
            }
        }

        // 3. Draw Main Text
        imagettftext($image, $size, $angle, (int)$x, (int)$y, $color, $font, $text);
    }

    public static function centerTextX($image, $size, $angle, $font, $text)
    {
        $box = imagettfbbox($size, $angle, $font, $text);
        // bbox returns: lower-left X, lower-left Y, lower-right X, lower-right Y, upper-right X, upper-right Y, upper-left X, upper-left Y
        // Width is roughly lower-right X - lower-left X 
        // OR using min/max for more precision
        $minX = min($box[0], $box[2], $box[4], $box[6]);
        $maxX = max($box[0], $box[2], $box[4], $box[6]);
        $textWidth = abs($maxX - $minX);

        $width = imagesx($image);
        return ($width - $textWidth) / 2;
    }

    /**
     * Calculates the optimal font size to fit text within a box.
     */
    public static function fitText($maxFontSize, $angle, $font, $text, $maxWidth, $maxHeight)
    {
        $fontSize = $maxFontSize;
        do {
            $box = imagettfbbox($fontSize, $angle, $font, $text);
            $minX = min($box[0], $box[2], $box[4], $box[6]);
            $maxX = max($box[0], $box[2], $box[4], $box[6]);
            $minY = min($box[1], $box[3], $box[5], $box[7]);
            $maxY = max($box[1], $box[3], $box[5], $box[7]);

            $width = abs($maxX - $minX);
            $height = abs($maxY - $minY);

            if ($width <= $maxWidth && $height <= $maxHeight) {
                return [
                    'fontSize' => $fontSize,
                    'width' => $width,
                    'height' => $height,
                    'box' => $box
                ];
            }
            $fontSize--;
        } while ($fontSize > 5);

        return [
            'fontSize' => 5,
            'width' => 0,
            'height' => 0,
            'box' => []
        ];
    }
}
