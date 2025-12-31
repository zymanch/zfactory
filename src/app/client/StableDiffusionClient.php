<?php

namespace app\client;

/**
 * Client for Stable Diffusion WebUI API (AUTOMATIC1111)
 *
 * Usage:
 *   $client = new StableDiffusionClient();
 *   if ($client->isAvailable()) {
 *       // Text to image
 *       $result = $client->txt2img('a sunset', 'blurry', 512, 512);
 *
 *       // Image to image (variation)
 *       $result = $client->img2img($baseImageBase64, 'with flowers', 'blurry', 512, 512);
 *   }
 */
class StableDiffusionClient implements ImageGeneratorInterface
{
    /** @var string API URL */
    private $apiUrl;

    /** @var int Timeout in seconds */
    private $timeout;

    /**
     * Default txt2img options
     */
    private const DEFAULT_TXT2IMG_OPTIONS = [
        'steps' => 25,
        'cfg_scale' => 3,
        'sampler_name' => 'Euler a',
        'seed' => -1, // -1 = random
        'tiling' => true, // Seamless mode
    ];

    /**
     * Default img2img options
     */
    private const DEFAULT_IMG2IMG_OPTIONS = [
        'steps' => 20,
        'cfg_scale' => 5,
        'sampler_name' => 'Euler a',
        'denoising_strength' => 0.25,
        'seed' => -1,
        'tiling' => true,
    ];

    public function __construct(string $apiUrl = 'http://localhost:7860', int $timeout = 300)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        $ch = curl_init("{$this->apiUrl}/sdapi/v1/options");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * @inheritDoc
     */
    public function txt2img(
        string $positivePrompt,
        string $negativePrompt,
        int $width,
        int $height,
        array $options = []
    ): ?ImageResult {
        $options = array_merge(self::DEFAULT_TXT2IMG_OPTIONS, $options);

        $payload = [
            'prompt' => $positivePrompt,
            'negative_prompt' => $negativePrompt,
            'width' => $width,
            'height' => $height,
            'steps' => $options['steps'],
            'cfg_scale' => $options['cfg_scale'],
            'sampler_name' => $options['sampler_name'],
            'seed' => $options['seed'],
            'batch_size' => 1,
            'n_iter' => 1,
            'tiling' => $options['tiling'],
        ];

        return $this->sendRequest('/sdapi/v1/txt2img', $payload);
    }

    /**
     * Generate image variation using img2img
     *
     * @param string $baseImageBase64 Base64-encoded source image
     * @param string $positivePrompt
     * @param string $negativePrompt
     * @param int $width
     * @param int $height
     * @param array $options denoising_strength (0.1-0.5 = subtle, 0.5-1.0 = major changes)
     * @return ImageResult|null
     */
    public function img2img(
        string $baseImageBase64,
        string $positivePrompt,
        string $negativePrompt,
        int $width,
        int $height,
        array $options = []
    ): ?ImageResult {
        $options = array_merge(self::DEFAULT_IMG2IMG_OPTIONS, $options);

        $payload = [
            'init_images' => [$baseImageBase64],
            'prompt' => $positivePrompt,
            'negative_prompt' => $negativePrompt,
            'width' => $width,
            'height' => $height,
            'steps' => $options['steps'],
            'cfg_scale' => $options['cfg_scale'],
            'sampler_name' => $options['sampler_name'],
            'denoising_strength' => $options['denoising_strength'],
            'seed' => $options['seed'],
            'batch_size' => 1,
            'n_iter' => 1,
            'tiling' => $options['tiling'],
        ];

        return $this->sendRequest('/sdapi/v1/img2img', $payload);
    }

    /**
     * Send request to SD API
     * @param string $endpoint
     * @param array $payload
     * @return ImageResult|null
     */
    private function sendRequest(string $endpoint, array $payload): ?ImageResult
    {
        $ch = curl_init("{$this->apiUrl}{$endpoint}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("StableDiffusionClient: API error (HTTP {$httpCode})");
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['images'][0])) {
            error_log("StableDiffusionClient: No image in response");
            return null;
        }

        // Parse info
        $info = json_decode($data['info'] ?? '{}', true);
        $seed = $info['seed'] ?? null;

        return new ImageResult($data['images'][0], $seed, $info);
    }

    /**
     * Get API URL
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }
}
