<?php

namespace bl\landing\generators\ship;

use bl\landing\generators\base\AbstractLandingGenerator;

/**
 * Abstract base class for ship landing generators
 * Ship landings are terrains on the spaceship (metal floors, edges)
 */
abstract class AbstractShipLandingGenerator extends AbstractLandingGenerator
{
    /**
     * @inheritDoc
     */
    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, text, watermark, side view, perspective, 3d';
    }

    /**
     * @inheritDoc
     * Ship landings always use seamless tiling
     */
    public function shouldMakeSeamless(): bool
    {
        return true;
    }

    /**
     * Generate right transition with straight line (outline only, no pixel copying)
     * @param resource $baseImage Base tile image
     * @param int $outlineColor Outline color (allocated in base image)
     * @param int $tileWidth Tile width
     * @param int $tileHeight Tile height
     * @param float $waveAmplitude Wave amplitude (used for offset)
     * @param callable $cloneImage Function to clone image
     * @param callable $drawOutline Function to draw outline
     * @return resource Result image
     */
    public static function generateTransitionRight($baseImage, $outlineColor, $tileWidth, $tileHeight, $waveAmplitude, $cloneImage, $drawOutline)
    {
        $result = $cloneImage($baseImage);

        // Straight line positions (constant X at right edge - amplitude)
        $straightX = [];
        for ($y = 0; $y < $tileHeight; $y++) {
            $straightX[$y] = $tileWidth - 1 - $waveAmplitude;
        }

        // Draw outline only (no pixel copying for ships)
        $drawOutline($result, $straightX, 'vertical', $outlineColor);

        return $result;
    }

    /**
     * Generate top transition with straight line (outline only, no pixel copying)
     * @param resource $baseImage Base tile image
     * @param int $outlineColor Outline color (allocated in base image)
     * @param int $tileWidth Tile width
     * @param int $tileHeight Tile height
     * @param float $waveAmplitude Wave amplitude (used for offset)
     * @param callable $cloneImage Function to clone image
     * @param callable $drawOutline Function to draw outline
     * @return resource Result image
     */
    public static function generateTransitionTop($baseImage, $outlineColor, $tileWidth, $tileHeight, $waveAmplitude, $cloneImage, $drawOutline)
    {
        $result = $cloneImage($baseImage);

        // Straight line positions (constant Y at top edge + amplitude)
        $straightY = [];
        for ($x = 0; $x < $tileWidth; $x++) {
            $straightY[$x] = $waveAmplitude;
        }

        // Draw outline only (no pixel copying for ships)
        $drawOutline($result, $straightY, 'horizontal', $outlineColor);

        return $result;
    }

    /**
     * Generate corner transition with L-shaped straight lines (outline only, no pixel copying)
     * @param resource $baseImage Base tile image
     * @param int $outlineColor Outline color (allocated in base image)
     * @param int $tileWidth Tile width
     * @param int $tileHeight Tile height
     * @param float $waveAmplitude Wave amplitude (used for offset)
     * @param callable $cloneImage Function to clone image
     * @param callable $drawLShapedOutline Function to draw L-shaped outline
     * @return resource Result image
     */
    public static function generateTransitionCorner($baseImage, $outlineColor, $tileWidth, $tileHeight, $waveAmplitude, $cloneImage, $drawLShapedOutline)
    {
        $result = $cloneImage($baseImage);

        // Top edge straight Y positions
        $topStraightY = [];
        for ($x = 0; $x < $tileWidth; $x++) {
            $topStraightY[$x] = $waveAmplitude;
        }

        // Right edge straight X positions
        $rightStraightX = [];
        for ($y = 0; $y < $tileHeight; $y++) {
            $rightStraightX[$y] = $tileWidth - 1 - $waveAmplitude;
        }

        // Draw L-shaped outline (no pixel copying for ships)
        $drawLShapedOutline($result, $topStraightY, $rightStraightX, $outlineColor);

        return $result;
    }
}
