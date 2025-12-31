<?php

namespace app\client;

/**
 * Interface for AI image generation clients
 */
interface ImageGeneratorInterface
{
    /**
     * Check if the AI service is running and available
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Generate image from text prompt
     * @param string $positivePrompt
     * @param string $negativePrompt
     * @param int $width
     * @param int $height
     * @param array $options Additional options (seed, steps, cfg, etc.)
     * @return ImageResult|null
     */
    public function txt2img(
        string $positivePrompt,
        string $negativePrompt,
        int $width,
        int $height,
        array $options = []
    ): ?ImageResult;
}
