<?php

namespace app\client;

/**
 * Result of AI image generation
 */
class ImageResult
{
    /** @var string Base64-encoded image data */
    public $imageBase64;

    /** @var int|null Seed used for generation */
    public $seed;

    /** @var array Additional info from the AI service */
    public $info = [];

    public function __construct(string $imageBase64, ?int $seed = null, array $info = [])
    {
        $this->imageBase64 = $imageBase64;
        $this->seed = $seed;
        $this->info = $info;
    }

    /**
     * Get raw image bytes
     * @return string
     */
    public function getImageData(): string
    {
        return base64_decode($this->imageBase64);
    }

    /**
     * Save image to file
     * @param string $path
     * @return bool
     */
    public function saveToFile(string $path): bool
    {
        return file_put_contents($path, $this->getImageData()) !== false;
    }
}
