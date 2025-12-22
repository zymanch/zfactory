<?php

namespace helpers;

/**
 * LandingTransitionGenerator - Generates transition sprites between landing types
 *
 * Creates JPG images with wavy borders for smooth terrain transitions.
 * Transitions are generated for RIGHT, TOP, and CORNER (RT) cases.
 */
class LandingTransitionGenerator
{
    /** @var int Tile width in pixels */
    private $tileWidth = 32;

    /** @var int Tile height in pixels */
    private $tileHeight = 24;

    /** @var float Amplitude of the wavy line */
    private $waveAmplitude = 1.5;

    /** @var float Frequency of the wavy line (waves per tile) */
    private $waveFrequency = 2.0;

    /** @var int Outline width in pixels */
    private $outlineWidth = 1;

    /** @var string Source directory for landing tiles */
    private $sourceDir;

    /** @var string Output directory for transition tiles */
    private $outputDir;

    /**
     * Constructor
     * @param string $basePath - Project base path
     */
    public function __construct($basePath)
    {
        $this->sourceDir = $basePath . '/public/assets/tiles/landing';
        $this->outputDir = $basePath . '/public/assets/tiles/landing/transitions';

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Generate all transition sprites for a pair of landing types
     *
     * @param string $baseName - Base landing name (e.g., 'grass')
     * @param string $adjacentName - Adjacent landing name (e.g., 'dirt')
     * @return array - List of generated file paths
     */
    public function generatePair($baseName, $adjacentName)
    {
        $generated = [];

        // Load source images
        $baseImage = $this->loadImage($baseName);
        $adjacentImage = $this->loadImage($adjacentName);

        if (!$baseImage || !$adjacentImage) {
            return $generated;
        }

        // Get outline color (darkened version of the darkest color in base image)
        $outlineColor = $this->getDarkenedColor($baseImage, 0.5);

        // Generate RIGHT transition (wavy line on right edge)
        $rightPath = pathinfo($baseName, PATHINFO_FILENAME).'_'.pathinfo($adjacentName, PATHINFO_FILENAME)."_r.jpg";
        $rightImage = $this->generateRightTransition($baseImage, $adjacentImage, $outlineColor);
        if ($rightImage && $this->saveImage($rightImage, $rightPath)) {
            $generated[] = $rightPath;
            imagedestroy($rightImage);
        }

        // Generate TOP transition (wavy line on top edge)
        $topPath = pathinfo($baseName, PATHINFO_FILENAME).'_'.pathinfo($adjacentName, PATHINFO_FILENAME)."_t.jpg";
        $topImage = $this->generateTopTransition($baseImage, $adjacentImage, $outlineColor);
        if ($topImage && $this->saveImage($topImage, $topPath)) {
            $generated[] = $topPath;
            imagedestroy($topImage);
        }

        // Generate CORNER transition (both right and top different)
        $cornerPath = pathinfo($baseName, PATHINFO_FILENAME).'_'.pathinfo($adjacentName, PATHINFO_FILENAME)."_rt.jpg";
        $cornerImage = $this->generateCornerTransition($baseImage, $adjacentImage, $adjacentImage, $outlineColor);
        if ($cornerImage && $this->saveImage($cornerImage, $cornerPath)) {
            $generated[] = $cornerPath;
            imagedestroy($cornerImage);
        }

        imagedestroy($baseImage);
        imagedestroy($adjacentImage);

        return $generated;
    }

    /**
     * Generate only TOP transition for a pair
     * Used for island_edge which only needs top transitions
     *
     * @param string $baseName - Base landing name (e.g., 'island_edge')
     * @param string $adjacentName - Adjacent landing name (e.g., 'grass')
     * @return array - List of generated file paths
     */
    public function generateTopOnly($baseName, $adjacentName)
    {
        $generated = [];

        $baseImage = $this->loadImage($baseName);
        $adjacentImage = $this->loadImage($adjacentName);

        if (!$baseImage || !$adjacentImage) {
            return $generated;
        }

        $outlineColor = $this->getDarkenedColor($baseImage, 0.5);

        $topPath = pathinfo($baseName, PATHINFO_FILENAME).'_'.pathinfo($adjacentName, PATHINFO_FILENAME)."_t.jpg";
        $topImage = $this->generateTopTransition($baseImage, $adjacentImage, $outlineColor);
        if ($topImage && $this->saveImage($topImage, $topPath)) {
            $generated[] = $topPath;
            imagedestroy($topImage);
        }

        imagedestroy($baseImage);
        imagedestroy($adjacentImage);

        return $generated;
    }

    /**
     * Generate only RIGHT transition for a pair
     * Used for sky which only needs right transitions
     *
     * @param string $baseName - Base landing name (e.g., 'sky')
     * @param string $adjacentName - Adjacent landing name (e.g., 'grass')
     * @return array - List of generated file paths
     */
    public function generateRightOnly($baseName, $adjacentName)
    {
        $generated = [];

        $baseImage = $this->loadImage($baseName);
        $adjacentImage = $this->loadImage($adjacentName);

        if (!$baseImage || !$adjacentImage) {
            return $generated;
        }

        $outlineColor = $this->getDarkenedColor($baseImage, 0.5);

        $rightPath = pathinfo($baseName, PATHINFO_FILENAME).'_'.pathinfo($adjacentName, PATHINFO_FILENAME)."_r.jpg";
        $rightImage = $this->generateRightTransition($baseImage, $adjacentImage, $outlineColor);
        if ($rightImage && $this->saveImage($rightImage, $rightPath)) {
            $generated[] = $rightPath;
            imagedestroy($rightImage);
        }

        imagedestroy($baseImage);
        imagedestroy($adjacentImage);

        return $generated;
    }

    /**
     * Generate RIGHT transition
     * Wavy line from bottom-right to top-right, adjacent pixels on right side
     */
    private function generateRightTransition($baseImage, $rightImage, $outlineColor)
    {
        $result = $this->cloneImage($baseImage);

        // Generate wavy boundary X positions for each Y
        $wavyX = [];
        for ($y = 0; $y < $this->tileHeight; $y++) {
            // Normalize y to 0-1 range
            $t = $y / ($this->tileHeight - 1);
            // Wavy offset from right edge
            $wave = sin($t * 2 * M_PI * $this->waveFrequency) * $this->waveAmplitude;
            // X position: hugging right edge
            $wavyX[$y] = (int)round($this->tileWidth - 4 + $wave);
        }

        // Copy pixels from right image for positions right of wavy line
        for ($y = 0; $y < $this->tileHeight; $y++) {
            $boundaryX = $wavyX[$y];
            for ($x = $boundaryX; $x < $this->tileWidth; $x++) {
                $color = imagecolorat($rightImage, $x, $y);
                imagesetpixel($result, $x, $y, $color);
            }
        }

        // Draw outline on the wavy line
        $this->drawOutline($result, $wavyX, 'vertical', $outlineColor);

        return $result;
    }

    /**
     * Generate TOP transition
     * Wavy line from top-left to top-right, adjacent pixels above
     */
    private function generateTopTransition($baseImage, $topImage, $outlineColor)
    {
        $result = $this->cloneImage($baseImage);

        // Generate wavy boundary Y positions for each X
        $wavyY = [];
        for ($x = 0; $x < $this->tileWidth; $x++) {
            $t = $x / ($this->tileWidth - 1);
            $wave = sin($t * 2 * M_PI * $this->waveFrequency) * $this->waveAmplitude;
            $wavyY[$x] = (int)round(4 - $wave);
        }

        // Copy pixels from top image for positions above wavy line
        for ($x = 0; $x < $this->tileWidth; $x++) {
            $boundaryY = $wavyY[$x];
            for ($y = 0; $y < $boundaryY; $y++) {
                $color = imagecolorat($topImage, $x, $y);
                imagesetpixel($result, $x, $y, $color);
            }
        }

        // Draw outline on the wavy line
        $this->drawOutline($result, $wavyY, 'horizontal', $outlineColor);

        return $result;
    }

    /**
     * Generate CORNER transition (both right and top different)
     * Wavy line from bottom-right to top-left, hugging top-right corner
     */
    private function generateCornerTransition($baseImage, $topImage, $rightImage, $outlineColor)
    {
        $result = $this->cloneImage($baseImage);

        // Generate wavy diagonal from bottom-right to top-left
        // The line curves around the top-right corner
        $wavyPoints = [];
        $steps = $this->tileWidth + $this->tileHeight;

        for ($i = 0; $i <= $steps; $i++) {
            $t = $i / $steps;
            // Base diagonal: start at bottom-right corner (width-1, height-1) end at top-left (0, 0)
            $baseX = ($this->tileWidth - 1) * (1 - $t);
            $baseY = ($this->tileHeight - 1) * (1 - $t);

            // Add wave perpendicular to diagonal
            $wave = sin($t * 2 * M_PI * $this->waveFrequency * 1.5) * $this->waveAmplitude;
            // Perpendicular direction is (1,1) normalized = (0.707, 0.707)
            $perpX = $wave * 0.707;
            $perpY = $wave * 0.707;

            $wavyPoints[] = [
                'x' => (int)round($baseX + $perpX),
                'y' => (int)round($baseY + $perpY)
            ];
        }

        // For each pixel, determine which region it's in
        for ($x = 0; $x < $this->tileWidth; $x++) {
            for ($y = 0; $y < $this->tileHeight; $y++) {
                // Check if pixel is above the wavy diagonal
                $isAboveDiagonal = $this->isPointAboveWavyDiagonal($x, $y, $wavyPoints);

                if ($isAboveDiagonal) {
                    // Check if above or below 45 degree line from top-right corner
                    // 45 degree line: y = x - width + height
                    $diag45Y = $x - $this->tileWidth + $this->tileHeight;

                    if ($y < $diag45Y) {
                        // Above 45 line -> use top image
                        $color = imagecolorat($topImage, $x, $y);
                    } else {
                        // Below 45 line -> use right image
                        $color = imagecolorat($rightImage, $x, $y);
                    }
                    imagesetpixel($result, $x, $y, $color);
                }
            }
        }

        // Draw outline along the wavy diagonal
        $this->drawDiagonalOutline($result, $wavyPoints, $outlineColor);

        return $result;
    }

    /**
     * Check if a point is above the wavy diagonal line
     */
    private function isPointAboveWavyDiagonal($x, $y, $wavyPoints)
    {
        // Find the closest point on the wavy line and check if we're above it
        $minDist = PHP_FLOAT_MAX;
        $closestY = 0;

        foreach ($wavyPoints as $point) {
            if ($point['x'] === $x) {
                return $y < $point['y'];
            }
            $dist = abs($point['x'] - $x);
            if ($dist < $minDist) {
                $minDist = $dist;
                $closestY = $point['y'];
            }
        }

        // Interpolate between points for more accurate check
        // For simplicity, use the basic diagonal check with wavy offset
        $baseY = $this->tileHeight - ($x / $this->tileWidth) * $this->tileHeight;
        $t = $x / $this->tileWidth;
        $wave = sin($t * 2 * M_PI * $this->waveFrequency * 1.5) * $this->waveAmplitude * 0.707;

        return $y < ($baseY + $wave);
    }

    /**
     * Draw outline along vertical wavy line
     */
    private function drawOutline($image, $wavyPositions, $direction, $color)
    {
        if ($direction === 'vertical') {
            // Vertical wavy line (for right transition)
            for ($y = 0; $y < $this->tileHeight; $y++) {
                $x = $wavyPositions[$y];
                for ($w = 0; $w < $this->outlineWidth; $w++) {
                    $drawX = $x - $w;
                    if ($drawX >= 0 && $drawX < $this->tileWidth) {
                        imagesetpixel($image, $drawX, $y, $color);
                    }
                }
            }
        } else {
            // Horizontal wavy line (for top transition)
            for ($x = 0; $x < $this->tileWidth; $x++) {
                $y = $wavyPositions[$x];
                for ($w = 0; $w < $this->outlineWidth; $w++) {
                    $drawY = $y + $w;
                    if ($drawY >= 0 && $drawY < $this->tileHeight) {
                        imagesetpixel($image, $x, $drawY, $color);
                    }
                }
            }
        }
    }

    /**
     * Draw outline along diagonal wavy line
     */
    private function drawDiagonalOutline($image, $wavyPoints, $color)
    {
        foreach ($wavyPoints as $point) {
            $x = $point['x'];
            $y = $point['y'];

            if ($x >= 0 && $x < $this->tileWidth && $y >= 0 && $y < $this->tileHeight) {
                imagesetpixel($image, $x, $y, $color);
            }
        }
    }

    /**
     * Load JPG image from source directory
     */
    private function loadImage($filename)
    {
        $path = $this->sourceDir . '/' . $filename;
        if (!file_exists($path)) {
            echo "Warning: Image not found: {$path}\n";
            return null;
        }
        return imagecreatefromjpeg($path);
    }

    /**
     * Save JPG image to output directory
     */
    private function saveImage($image, $filename)
    {
        $path = $this->outputDir . '/' . $filename;
        return imagejpeg($image, $path, 90);
    }

    /**
     * Clone an image
     */
    private function cloneImage($source)
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $clone = imagecreatetruecolor($width, $height);
        imagecopy($clone, $source, 0, 0, 0, 0, $width, $height);
        return $clone;
    }

    /**
     * Get darkened color from darkest pixel in image
     */
    private function getDarkenedColor($image, $darkenFactor)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $darkestR = 255;
        $darkestG = 255;
        $darkestB = 255;
        $darkestBrightness = 255 * 3;

        // Sample pixels to find darkest
        for ($x = 0; $x < $width; $x += 2) {
            for ($y = 0; $y < $height; $y += 2) {
                $color = imagecolorat($image, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;
                $brightness = $r + $g + $b;

                if ($brightness < $darkestBrightness) {
                    $darkestBrightness = $brightness;
                    $darkestR = $r;
                    $darkestG = $g;
                    $darkestB = $b;
                }
            }
        }

        // Darken the color
        $darkR = (int)round($darkestR * $darkenFactor);
        $darkG = (int)round($darkestG * $darkenFactor);
        $darkB = (int)round($darkestB * $darkenFactor);

        return imagecolorallocate($image, $darkR, $darkG, $darkB);
    }
}
