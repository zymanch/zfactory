<?php

namespace app\client;

use Yii;

/**
 * Client for ComfyUI API (FLUX.1 Dev model)
 *
 * Usage:
 *   $client = new ComfyUIClient();
 *   if ($client->isAvailable()) {
 *       $result = $client->txt2img('a beautiful sunset', 'blurry', 512, 512);
 *       if ($result) {
 *           $result->saveToFile('/path/to/image.png');
 *       }
 *   }
 */
class ComfyUIClient implements ImageGeneratorInterface
{
    /** @var string ComfyUI API URL */
    private $apiUrl;

    /** @var int Timeout in seconds */
    private $timeout;

    /** @var array|null Cached workflow */
    private $workflow = null;

    /** @var string Path to workflow JSON file */
    private $workflowPath;

    /**
     * Default generation options
     */
    private const DEFAULT_OPTIONS = [
        'steps' => 30,
        'cfg' => 1.5,
        'seed' => null, // null = random
    ];

    public function __construct(
        string $apiUrl = 'http://localhost:8188',
        int $timeout = 600,
        ?string $workflowPath = null
    ) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->timeout = $timeout;
        $this->workflowPath = $workflowPath ?? Yii::getAlias('@app/../workflow_flux_api.json');
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        $ch = curl_init("{$this->apiUrl}/system_stats");
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
        // Load workflow
        if ($this->workflow === null) {
            $this->workflow = $this->loadWorkflow();
            if ($this->workflow === null) {
                return null;
            }
        }

        // Merge options with defaults
        $options = array_merge(self::DEFAULT_OPTIONS, $options);

        // Prepare workflow with our parameters
        $workflow = $this->workflow;

        // Node 2: Positive prompt (CLIP Text Encode)
        $workflow['2']['inputs']['text'] = $positivePrompt;

        // Node 3: Negative prompt (CLIP Text Encode)
        $workflow['3']['inputs']['text'] = $negativePrompt;

        // Node 5: Empty Latent Image (dimensions)
        $workflow['5']['inputs']['width'] = $width;
        $workflow['5']['inputs']['height'] = $height;

        // Node 6: KSampler (seed, steps, cfg)
        $seed = $options['seed'] ?? rand(0, 2147483647);
        $workflow['6']['inputs']['seed'] = $seed;
        $workflow['6']['inputs']['steps'] = $options['steps'];
        $workflow['6']['inputs']['cfg'] = $options['cfg'];

        // Submit workflow
        $promptId = $this->submitWorkflow($workflow);
        if ($promptId === null) {
            return null;
        }

        // Wait for completion
        $outputs = $this->waitForCompletion($promptId);
        if ($outputs === null) {
            return null;
        }

        // Download image
        $imageBase64 = $this->downloadImage($outputs);
        if ($imageBase64 === null) {
            return null;
        }

        return new ImageResult($imageBase64, $seed, [
            'prompt_id' => $promptId,
            'width' => $width,
            'height' => $height,
        ]);
    }

    /**
     * Load workflow from JSON file
     * @return array|null
     */
    private function loadWorkflow(): ?array
    {
        if (!file_exists($this->workflowPath)) {
            error_log("ComfyUIClient: Workflow file not found: {$this->workflowPath}");
            return null;
        }

        $json = file_get_contents($this->workflowPath);
        $workflow = json_decode($json, true);

        if ($workflow === null) {
            error_log("ComfyUIClient: Failed to parse workflow JSON");
            return null;
        }

        return $workflow;
    }

    /**
     * Submit workflow to ComfyUI
     * @param array $workflow
     * @return string|null Prompt ID
     */
    private function submitWorkflow(array $workflow): ?string
    {
        $ch = curl_init("{$this->apiUrl}/prompt");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['prompt' => $workflow]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("ComfyUIClient: Failed to submit workflow (HTTP {$httpCode})");
            return null;
        }

        $result = json_decode($response, true);
        return $result['prompt_id'] ?? null;
    }

    /**
     * Wait for generation to complete
     * @param string $promptId
     * @return array|null Outputs array
     */
    private function waitForCompletion(string $promptId): ?array
    {
        $startTime = time();

        while (true) {
            if ((time() - $startTime) > $this->timeout) {
                error_log("ComfyUIClient: Timeout waiting for completion");
                return null;
            }

            $ch = curl_init("{$this->apiUrl}/history/{$promptId}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            curl_close($ch);

            $history = json_decode($response, true);

            if (isset($history[$promptId])) {
                $status = $history[$promptId];

                // Check for error
                if (isset($status['status']['status_str']) && $status['status']['status_str'] === 'error') {
                    error_log("ComfyUIClient: Generation error");
                    return null;
                }

                // Check for completion
                if (isset($status['status']['completed']) && $status['status']['completed'] === true) {
                    return $status['outputs'] ?? null;
                }
            }

            sleep(2);
        }
    }

    /**
     * Download generated image from ComfyUI
     * @param array $outputs
     * @return string|null Base64-encoded image
     */
    private function downloadImage(array $outputs): ?string
    {
        foreach ($outputs as $nodeId => $output) {
            if (isset($output['images']) && is_array($output['images'])) {
                $image = $output['images'][0];
                $filename = $image['filename'];
                $subfolder = $image['subfolder'] ?? '';

                $url = "{$this->apiUrl}/view?filename=" . urlencode($filename);
                if ($subfolder) {
                    $url .= "&subfolder=" . urlencode($subfolder);
                }

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);

                $imageData = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $imageData !== false) {
                    return base64_encode($imageData);
                }
            }
        }

        error_log("ComfyUIClient: No images found in outputs");
        return null;
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
