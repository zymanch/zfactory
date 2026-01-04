<?php

namespace bl\landing\generators\island;

use bl\landing\generators\base\AbstractLandingGenerator;

/**
 * Abstract base class for island terrain landing generators
 * Island landings are buildable terrains on the floating island (grass, dirt, sand, etc.)
 */
abstract class AbstractIslandLandingGenerator extends AbstractLandingGenerator
{
    /**
     * @inheritDoc
     */
    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, text, watermark, 3d perspective, side view';
    }

    /**
     * @inheritDoc
     * Island landings always use seamless tiling
     */
    public function shouldMakeSeamless(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     * Island landings are never transparent
     */
    public function shouldMakeBottomTransparent(): bool
    {
        return false;
    }

    /**
     * Generate right transition with wavy line
     * @param resource $baseImage Base tile image
     * @param resource $rightImage Right neighbor tile image
     * @param int $outlineColor Outline color (allocated in base image)
     * @param int $tileWidth Tile width
     * @param int $tileHeight Tile height
     * @param float $waveAmplitude Wave amplitude
     * @param float $waveFrequency Wave frequency
     * @param int $outlineWidth Outline width in pixels
     * @param callable $cloneImage Function to clone image
     * @param callable $drawOutline Function to draw outline
     * @return resource Result image
     */
    public static function generateTransitionRight($baseImage, $rightImage, $outlineColor, $tileWidth, $tileHeight, $waveAmplitude, $waveFrequency, $outlineWidth, $cloneImage, $drawOutline)
    {
        $result = $cloneImage($baseImage);

        // Generate wavy boundary X positions for each Y
        $wavyX = [];
        for ($y = 0; $y < $tileHeight; $y++) {
            $t = $y / ($tileHeight - 1);
            $wave = cos($t * 2 * M_PI * $waveFrequency) * $waveAmplitude;
            $wavyX[$y] = (int)round($tileWidth - 1 - $waveAmplitude + $wave);
        }

        // Copy pixels from right image for positions right of wavy line
        for ($y = 0; $y < $tileHeight; $y++) {
            $boundaryX = $wavyX[$y];
            for ($x = $boundaryX; $x < $tileWidth; $x++) {
                $color = imagecolorat($rightImage, $x, $y);
                imagesetpixel($result, $x, $y, $color);
            }
        }

        // Draw outline on the wavy line
        $drawOutline($result, $wavyX, 'vertical', $outlineColor);

        return $result;
    }

    /**
     * Generate top transition with wavy line
     * @param resource $baseImage Base tile image
     * @param resource $topImage Top neighbor tile image
     * @param int $outlineColor Outline color (allocated in base image)
     * @param int $tileWidth Tile width
     * @param int $tileHeight Tile height
     * @param float $waveAmplitude Wave amplitude
     * @param float $waveFrequency Wave frequency
     * @param int $outlineWidth Outline width in pixels
     * @param callable $cloneImage Function to clone image
     * @param callable $drawOutline Function to draw outline
     * @return resource Result image
     */
    public static function generateTransitionTop($baseImage, $topImage, $outlineColor, $tileWidth, $tileHeight, $waveAmplitude, $waveFrequency, $outlineWidth, $cloneImage, $drawOutline)
    {
        $result = $cloneImage($baseImage);

        // Generate wavy boundary Y positions for each X
        $wavyY = [];
        for ($x = 0; $x < $tileWidth; $x++) {
            $t = $x / ($tileWidth - 1);
            $wave = cos($t * 2 * M_PI * $waveFrequency) * $waveAmplitude;
            $wavyY[$x] = (int)round($waveAmplitude - $wave);
        }

        // Copy pixels from top image for positions above wavy line
        for ($x = 0; $x < $tileWidth; $x++) {
            $boundaryY = $wavyY[$x];
            for ($y = 0; $y < $boundaryY; $y++) {
                $color = imagecolorat($topImage, $x, $y);
                imagesetpixel($result, $x, $y, $color);
            }
        }

        // Draw outline on the wavy line
        $drawOutline($result, $wavyY, 'horizontal', $outlineColor);

        return $result;
    }

    /**
     * Generate corner transition with L-shaped wavy line
     * @param resource $baseImage Base tile image
     * @param resource $topImage Top neighbor tile image
     * @param resource $rightImage Right neighbor tile image
     * @param int $outlineColor Outline color (allocated in base image)
     * @param int $tileWidth Tile width
     * @param int $tileHeight Tile height
     * @param float $waveAmplitude Wave amplitude
     * @param float $waveFrequency Wave frequency
     * @param int $outlineWidth Outline width in pixels
     * @param callable $cloneImage Function to clone image
     * @param callable $drawLShapedOutline Function to draw L-shaped outline
     * @return resource Result image
     */
    public static function generateTransitionCorner($baseImage, $topImage, $rightImage, $outlineColor, $tileWidth, $tileHeight, $waveAmplitude, $waveFrequency, $outlineWidth, $cloneImage, $drawLShapedOutline)
    {
        $result = $cloneImage($baseImage);

        // Top edge wavy Y positions
        $topWavyY = [];
        for ($x = 0; $x < $tileWidth; $x++) {
            $t = $x / ($tileWidth - 1);
            $wave = cos($t * 2 * M_PI * $waveFrequency) * $waveAmplitude;
            $topWavyY[$x] = (int)round($waveAmplitude - $wave);
        }

        // Right edge wavy X positions
        $rightWavyX = [];
        for ($y = 0; $y < $tileHeight; $y++) {
            $t = $y / ($tileHeight - 1);
            $wave = cos($t * 2 * M_PI * $waveFrequency) * $waveAmplitude;
            $rightWavyX[$y] = (int)round($tileWidth - 1 - $waveAmplitude + $wave);
        }

        // Apply regions based on L-shaped boundary
        for ($x = 0; $x < $tileWidth; $x++) {
            for ($y = 0; $y < $tileHeight; $y++) {
                $isAboveTopLine = isset($topWavyY[$x]) && $y < $topWavyY[$x];
                $isRightOfRightLine = isset($rightWavyX[$y]) && $x >= $rightWavyX[$y];

                if ($isAboveTopLine) {
                    $color = imagecolorat($topImage, $x, $y);
                    imagesetpixel($result, $x, $y, $color);
                } elseif ($isRightOfRightLine) {
                    $color = imagecolorat($rightImage, $x, $y);
                    imagesetpixel($result, $x, $y, $color);
                }
            }
        }

        // Draw L-shaped outline
        $drawLShapedOutline($result, $topWavyY, $rightWavyX, $outlineColor);

        return $result;
    }
}
