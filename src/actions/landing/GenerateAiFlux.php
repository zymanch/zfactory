<?php

namespace actions\landing;

use actions\ConsoleAction;
use models\Landing;
use Yii;
use yii\helpers\Console;

/**
 * Generate landing sprites using FLUX.1 Dev via ComfyUI
 * Usage: php yii landing/generate-ai-flux [landing_name]
 * Examples:
 *   php yii landing/generate-ai-flux grass
 *   php yii landing/generate-ai-flux all
 */
class GenerateAiFlux extends ConsoleAction
{
    public $landingName = 'all';

    public function run($landingName = 'all')
    {
        $this->landingName = $landingName;
        $this->stdout("Generating landing sprites using FLUX.1 Dev via ComfyUI...\n\n");

        $apiUrl = 'http://localhost:8188';
        $basePath = Yii::getAlias('@app/..');
        $landingDir = $basePath . '/public/assets/tiles/landing';

        // Check if ComfyUI is running
        if (!$this->checkComfyUIRunning($apiUrl)) {
            $this->stdout("Error: ComfyUI is not running at $apiUrl\n", Console::FG_RED);
            $this->stdout("Please start ComfyUI first: cd ai && start_comfyui.bat\n");
            return 1;
        }

        // Prompts for each landing type
        $prompts = $this->getPrompts();
        $variationPrompts = $this->getVariationPrompts();

        // Get landings to process
        $landingsToProcess = [];
        if ($landingName === 'all') {
            $landings = Landing::find()->asArray()->all();
            foreach ($landings as $landing) {
                $name = $landing['folder'];
                if (isset($prompts[$name])) {
                    $landingsToProcess[$name] = $landing;
                }
            }
        } else {
            $landing = Landing::find()->where(['folder' => $landingName])->asArray()->one();
            if ($landing && isset($prompts[$landingName])) {
                $landingsToProcess[$landingName] = $landing;
            } else {
                $this->stdout("Error: Landing '{$landingName}' not found or no prompt defined.\n");
                return 1;
            }
        }

        if (empty($landingsToProcess)) {
            $this->stdout("No landings to process.\n");
            return 1;
        }

        foreach ($landingsToProcess as $name => $landing) {
            $this->stdout("Generating {$name}...\n");

            $landingPath = $landingDir . '/' . $name;
            if (!is_dir($landingPath)) {
                mkdir($landingPath, 0755, true);
            }

            // Generate base sprite
            $this->stdout("  Generating base sprite...\n");
            $imageData = $this->generateViaComfyUI(
                $apiUrl,
                $prompts[$name]['positive'],
                $prompts[$name]['negative'],
                512,
                384
            );

            if (!$imageData) {
                $this->stdout("  Error: Failed to generate base sprite\n", Console::FG_RED);
                continue;
            }

            $originalPath = $landingPath . '/' . $name . '_0_original.png';
            file_put_contents($originalPath, base64_decode($imageData));
            $this->stdout("  Saved base sprite\n", Console::FG_GREEN);

            // Generate 4 variations
            $this->stdout("  Generating variations (1-4)...\n");
            for ($i = 1; $i <= 4; $i++) {
                $varPrompt = $prompts[$name]['positive'];
                if (isset($variationPrompts[$name][$i - 1])) {
                    $varPrompt .= ', ' . $variationPrompts[$name][$i - 1];
                }

                $varImageData = $this->generateViaComfyUI(
                    $apiUrl,
                    $varPrompt,
                    $prompts[$name]['negative'],
                    512,
                    384
                );

                if (!$varImageData) {
                    $this->stdout("    Warning: Failed to generate variation {$i}\n", Console::FG_YELLOW);
                    continue;
                }

                $varPath = $landingPath . '/' . $name . '_' . $i . '_original.png';
                file_put_contents($varPath, base64_decode($varImageData));
                $this->stdout("  Saved variation {$i}\n", Console::FG_GREEN);
            }

            // Apply transparency for island_edge
            if ($name === 'island_edge') {
                for ($i = 0; $i <= 4; $i++) {
                    $path = $landingPath . '/' . $name . '_' . $i . '_original.png';
                    if (file_exists($path)) {
                        $this->makeBottomTransparent($path, 0.5);
                    }
                }
                $this->stdout("  Applied transparency for island_edge\n");
            }
        }

        $this->stdout("\nFLUX generation complete! Running scale-original...\n\n");

        // Automatically run scale-original
        $result = $this->controller->runAction('scale-original');

        if ($result === 0) {
            $this->stdout("\nDone! Now run:\n");
            $this->stdout("  php yii landing/generate\n");
            $this->stdout("  npm run assets\n");
        }

        return $result;
    }

    /**
     * Check if ComfyUI is running
     */
    private function checkComfyUIRunning($apiUrl)
    {
        $ch = curl_init($apiUrl . '/system_stats');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Generate image via ComfyUI API
     */
    private function generateViaComfyUI($apiUrl, $prompt, $negativePrompt, $width, $height)
    {
        // Load FLUX workflow template
        $workflowPath = Yii::getAlias('@app/../ai/workflow_flux_api.json');
        if (!file_exists($workflowPath)) {
            $this->stdout("Error: Workflow file not found: $workflowPath\n", Console::FG_RED);
            return null;
        }

        // Load workflow (already in API format)
        $workflow = json_decode(file_get_contents($workflowPath), true);

        // Update workflow with our parameters
        // Node structure:
        // 1 = DualCLIPLoader
        // 2 = CLIPTextEncode (positive)
        // 3 = CLIPTextEncode (negative)
        // 4 = UNETLoader
        // 5 = EmptyLatentImage
        // 6 = KSampler
        // 7 = VAELoader
        // 8 = VAEDecode
        // 9 = SaveImage

        // Update prompts
        $workflow['2']['inputs']['text'] = $prompt;
        $workflow['3']['inputs']['text'] = $negativePrompt;

        // Update image size
        $workflow['5']['inputs']['width'] = $width;
        $workflow['5']['inputs']['height'] = $height;

        // Update seed
        $workflow['6']['inputs']['seed'] = rand(0, 2147483647);

        // Queue prompt
        $payload = [
            'prompt' => $workflow
        ];

        $ch = curl_init($apiUrl . '/prompt');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->stdout("Error: ComfyUI API returned HTTP $httpCode\n", Console::FG_RED);
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['prompt_id'])) {
            $this->stdout("Error: No prompt_id in response\n", Console::FG_RED);
            return null;
        }

        $promptId = $data['prompt_id'];

        // Wait for completion and get image
        return $this->waitForCompletion($apiUrl, $promptId);
    }

    /**
     * Wait for prompt completion and return image
     */
    private function waitForCompletion($apiUrl, $promptId)
    {
        $maxAttempts = 120; // 2 minutes max (with 1 sec intervals)
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(1);
            $attempt++;

            // Check history
            $ch = curl_init($apiUrl . '/history/' . $promptId);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $history = json_decode($response, true);
            if (empty($history) || !isset($history[$promptId])) {
                continue;
            }

            $status = $history[$promptId];
            if (isset($status['outputs'])) {
                // Find SaveImage node output
                foreach ($status['outputs'] as $nodeId => $output) {
                    if (isset($output['images'][0])) {
                        $imageInfo = $output['images'][0];
                        $filename = $imageInfo['filename'];
                        $subfolder = $imageInfo['subfolder'] ?? '';

                        // Download image
                        return $this->downloadImage($apiUrl, $filename, $subfolder);
                    }
                }
            }
        }

        $this->stdout("Error: Timeout waiting for generation\n", Console::FG_RED);
        return null;
    }

    /**
     * Download image from ComfyUI
     */
    private function downloadImage($apiUrl, $filename, $subfolder)
    {
        $url = $apiUrl . '/view';
        $url .= '?filename=' . urlencode($filename);
        if ($subfolder) {
            $url .= '&subfolder=' . urlencode($subfolder);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $imageData = curl_exec($ch);
        curl_close($ch);

        return base64_encode($imageData);
    }

    /**
     * Make bottom 50% of image transparent (for island_edge)
     */
    private function makeBottomTransparent($imagePath, $heightPercentage = 0.5)
    {
        $img = imagecreatefrompng($imagePath);
        $width = imagesx($img);
        $height = imagesy($img);

        imagesavealpha($img, true);

        $startY = (int)($height * (1 - $heightPercentage));

        for ($y = $startY; $y < $height; $y++) {
            $alpha = (int)(127 * ($y - $startY) / ($height - $startY));
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($img, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;

                $newColor = imagecolorallocatealpha($img, $r, $g, $b, $alpha);
                imagesetpixel($img, $x, $y, $newColor);
            }
        }

        imagepng($img, $imagePath);
        imagedestroy($img);
    }

    /**
     * Get prompts for each landing type
     */
    private function getPrompts()
    {
        return [
            'grass' => [
                'positive' => 'seamless tileable texture, grass field, lush green grass, top-down view, game texture, clean design, natural lighting',
                'negative' => 'blurry, low quality, ugly, distorted, text, watermark, 3d, perspective'
            ],
            'dirt' => [
                'positive' => 'seamless tileable texture, brown dirt ground, earth soil, top-down view, game texture, natural, simple',
                'negative' => 'blurry, low quality, grass, plants, rocks, text, watermark'
            ],
            'sand' => [
                'positive' => 'seamless tileable texture, sand beach, golden sand, top-down view, game texture, clean, fine grain',
                'negative' => 'blurry, low quality, water, rocks, grass, text, watermark'
            ],
            'water' => [
                'positive' => 'seamless tileable texture, water surface, blue water, ripples, top-down view, game texture, clear',
                'negative' => 'blurry, low quality, land, rocks, text, watermark, foam'
            ],
            'stone' => [
                'positive' => 'seamless tileable texture, stone surface, grey rocks, top-down view, game texture, natural pattern',
                'negative' => 'blurry, low quality, grass, dirt, text, watermark'
            ],
            'lava' => [
                'positive' => 'seamless tileable texture, lava surface, molten rock, orange red glow, cracks, top-down view, game texture, dramatic',
                'negative' => 'blurry, low quality, water, ice, text, watermark'
            ],
            'snow' => [
                'positive' => 'seamless tileable texture, snow surface, white snow, icy, top-down view, game texture, clean, soft',
                'negative' => 'blurry, low quality, dirt, grass, text, watermark'
            ],
            'swamp' => [
                'positive' => 'seamless tileable texture, swamp ground, murky water, mud, dark green, top-down view, game texture',
                'negative' => 'blurry, low quality, clean water, grass, text, watermark'
            ],
            'island_edge' => [
                'positive' => 'seamless tileable texture, rocky cliff edge, stalactites hanging down, stone formations, bottom edge, game texture, dramatic',
                'negative' => 'blurry, low quality, grass, sky, text, watermark'
            ],
        ];
    }

    /**
     * Variation prompts for subtle differences
     */
    private function getVariationPrompts()
    {
        return [
            'grass' => ['small flowers', 'darker shade', 'lighter shade', 'tiny patches'],
            'dirt' => ['small rocks', 'darker', 'lighter', 'cracks'],
            'sand' => ['fine grain', 'coarse grain', 'golden tint', 'white sand'],
            'water' => ['calm', 'ripples', 'darker blue', 'lighter blue'],
            'stone' => ['mossy patches', 'darker grey', 'lighter grey', 'rough texture'],
            'lava' => ['more cracks', 'brighter glow', 'darker cooled areas', 'flowing'],
            'snow' => ['fresh powder', 'icy patches', 'slight footprints', 'pristine'],
            'swamp' => ['more mud', 'darker water', 'algae', 'murky'],
            'island_edge' => ['longer stalactites', 'shorter formations', 'rougher texture', 'smoother edge'],
        ];
    }
}
