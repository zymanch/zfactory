<?php

namespace actions\landing;

use actions\ConsoleAction;
use models\Landing;
use Yii;
use yii\helpers\Console;

/**
 * Generate landing sprites using local Stable Diffusion API
 * Usage: php yii landing/generate-ai [landing_name]
 * Examples:
 *   php yii landing/generate-ai grass
 *   php yii landing/generate-ai all
 */
class GenerateAi extends ConsoleAction
{
    public $landingName = 'all';

    public function run($landingName = 'all')
    {
        $this->landingName = $landingName;
        $this->stdout("Generating landing sprites using Stable Diffusion API...\n\n");

        $apiUrl = 'http://localhost:7860';
        $basePath = Yii::getAlias('@app/..');
        $landingDir = $basePath . '/public/assets/tiles/landing';

        // Prompts for each landing type
        $prompts = $this->getPrompts();
        $variationPrompts = $this->getVariationPrompts();

        // Get landings to process
        $landingsToProcess = [];
        if ($landingName === 'all') {
            $landings = Landing::find()->asArray()->all();
            foreach ($landings as $landing) {
                $name = str_replace('.png', '', $landing['image_url']);
                if (isset($prompts[$name])) {
                    $landingsToProcess[$name] = $landing;
                }
            }
        } else {
            $landing = Landing::find()->where(['image_url' => $landingName . '.png'])->asArray()->one();
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

            $variationsCount = $landing['variations_count'] ?? 5;
            $variations = $variationPrompts[$name] ?? array_fill(0, $variationsCount, '');

            // Generate base image (variation 0)
            $this->stdout("  Generating base image (0/{$variationsCount})...\n");
            $baseImageData = $this->generateViaSdApi(
                $apiUrl,
                $prompts[$name]['positive'],
                $prompts[$name]['negative'],
                512,
                384
            );

            if (!$baseImageData) {
                $this->stdout("  Error: Failed to generate base image\n");
                continue;
            }

            $baseImageBase64 = $baseImageData['image'];
            $baseSeed = $baseImageData['seed'];

            // Save variation 0
            $originalPath = $landingPath . '/' . $name . '_0_original.png';
            file_put_contents($originalPath, base64_decode($baseImageBase64));
            $this->stdout("  Saved variation 0 (seed: {$baseSeed})\n");

            // Save seed to database
            Landing::updateAll(['ai_seed' => $baseSeed], ['image_url' => $name . '.png']);

            // Generate other variations using img2img
            for ($i = 1; $i < $variationsCount; $i++) {
                $this->stdout("  Generating variation {$i}/{$variationsCount}...\n");

                $modifier = $variations[$i] ?? '';
                $varPrompt = $prompts[$name]['positive'] . ($modifier ? ', ' . $modifier : '');

                $varImageData = $this->generateImg2ImgViaSdApi(
                    $apiUrl,
                    $baseImageBase64,
                    $varPrompt,
                    $prompts[$name]['negative'],
                    512,
                    384,
                    0.4  // Denoising strength (subtle changes)
                );

                if (!$varImageData) {
                    $this->stdout("    Warning: Failed to generate variation {$i}, using base instead\n");
                    $varImageBase64 = $baseImageBase64;
                } else {
                    $varImageBase64 = $varImageData['image'];
                }

                $varPath = $landingPath . '/' . $name . '_' . $i . '_original.png';
                file_put_contents($varPath, base64_decode($varImageBase64));
                $this->stdout("  Saved variation {$i}\n");

                // Apply transparency for island_edge
                if ($name === 'island_edge') {
                    $this->makeBottomTransparent($varPath, 0.5);
                    $this->stdout("  Applied transparency to variation {$i}\n");
                }
            }

            // Apply transparency to base image if island_edge
            if ($name === 'island_edge') {
                $this->makeBottomTransparent($originalPath, 0.5);
                $this->stdout("  Applied transparency to base image\n");
            }
        }

        $this->stdout("\nDone! Now run:\n");
        $this->stdout("  php yii landing/scale-original\n");
        $this->stdout("  php yii landing/generate\n");
        $this->stdout("  npm run assets\n");

        return 0;
    }

    /**
     * Get prompts for each landing type
     */
    private function getPrompts()
    {
        return [
            'grass' => [
                'positive' => 'seamless tileable grass texture, aerial top-down view, vibrant green grass field, natural outdoor ground, photorealistic, high detail, 4k quality',
                'negative' => 'borders, seams, blurry, low quality, text, watermark, people, animals, isometric, 3d perspective'
            ],
            'dirt' => [
                'positive' => 'seamless tileable dirt texture, aerial top-down view, brown earth ground, dry soil with small stones, natural terrain, photorealistic, high detail',
                'negative' => 'borders, seams, blurry, low quality, text, watermark, grass, plants, isometric'
            ],
            'sand' => [
                'positive' => 'seamless tileable sand texture, aerial top-down view, golden yellow beach sand, fine grain texture, desert terrain, photorealistic, high detail',
                'negative' => 'borders, seams, blurry, low quality, text, watermark, water, waves, isometric'
            ],
            'water' => [
                'positive' => 'seamless tileable water texture, aerial top-down view, clear blue water surface, gentle ripples, lake water, photorealistic, high detail',
                'negative' => 'borders, seams, blurry, low quality, text, watermark, foam, waves, beach, isometric'
            ],
            'stone' => [
                'positive' => 'seamless tileable stone texture, aerial top-down view, gray rocky ground, natural stone surface with cracks, mountain terrain, photorealistic, high detail',
                'negative' => 'borders, seams, blurry, low quality, text, watermark, moss, plants, isometric'
            ],
            'lava' => [
                'positive' => 'seamless tileable lava texture, aerial top-down view, molten lava surface, glowing red-orange magma, volcanic terrain with dark crust and bright cracks, photorealistic, high detail',
                'negative' => 'borders, seams, blurry, low quality, text, watermark, water, isometric'
            ],
            'snow' => [
                'positive' => 'seamless tileable snow texture, aerial top-down view, white snow-covered ground, fresh winter snow, cold terrain, photorealistic, high detail',
                'negative' => 'borders, seams, blurry, low quality, text, watermark, footprints, dirty snow, isometric'
            ],
            'swamp' => [
                'positive' => 'seamless tileable swamp texture, aerial top-down view, dark green murky marshland, wet muddy ground with moss, wetland terrain, photorealistic, high detail',
                'negative' => 'borders, seams, blurry, low quality, text, watermark, clear water, isometric'
            ],
            'island_edge' => [
                'positive' => 'seamless tileable hanging stalactites, rocky earth surface at top, stone stalactites hanging downward, cave ceiling texture, side view, photorealistic, high detail, transparent background at bottom',
                'negative' => 'borders, seams, blurry, low quality, text, watermark, isometric, top-down view, sky, clouds, ground at bottom'
            ]
        ];
    }

    /**
     * Get variation prompts for img2img
     */
    private function getVariationPrompts()
    {
        return [
            'grass' => [
                'original' => '',
                'with red flowers scattered',
                'with blue flowers scattered',
                'sparse thin grass patches',
                'dense thick lush grass'
            ],
            'dirt' => [
                'original' => '',
                'with small pebbles',
                'cracked dry earth',
                'with moss patches',
                'with small rocks'
            ],
            'sand' => [
                'original' => '',
                'with shell fragments',
                'fine smooth sand',
                'coarse rough sand texture',
                'with small dunes pattern'
            ],
            'water' => [
                'original' => '',
                'with lily pads',
                'dark deep water',
                'light shallow water',
                'with gentle waves'
            ],
            'stone' => [
                'original' => '',
                'with moss covering',
                'weathered cracked stones',
                'smooth polished surface',
                'rough jagged texture'
            ],
            'lava' => [
                'original' => '',
                'bright glowing cracks',
                'dark cooling surface',
                'intense bright magma',
                'bubbling lava surface'
            ],
            'snow' => [
                'original' => '',
                'fresh powdery snow',
                'icy frozen surface',
                'with sparkles',
                'deep thick snow'
            ],
            'swamp' => [
                'original' => '',
                'with algae patches',
                'dark murky water',
                'with reeds scattered',
                'thick mud surface'
            ],
            'island_edge' => [
                'original' => '',
                'long sharp stalactites',
                'short thick stalactites',
                'weathered eroded edge',
                'dramatic cliff face'
            ]
        ];
    }

    /**
     * Generate image using Stable Diffusion API
     * Returns array ['image' => base64, 'seed' => int, 'info' => array] or null
     */
    private function generateViaSdApi($apiUrl, $positivePrompt, $negativePrompt, $width, $height)
    {
        $payload = [
            'prompt' => $positivePrompt,
            'negative_prompt' => $negativePrompt,
            'width' => $width,
            'height' => $height,
            'steps' => 25,
            'cfg_scale' => 5,  // Lower = softer, less sharp (was 7)
            'sampler_name' => 'Euler a',  // Softer sampler (was DPM++ 2M Karras)
            'seed' => -1,  // Random seed
            'batch_size' => 1,
            'n_iter' => 1,
            'tiling' => true,  // Enable seamless mode
        ];

        $ch = curl_init($apiUrl . '/sdapi/v1/txt2img');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->stdout("  API Error: HTTP {$httpCode}\n");
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['images'][0])) {
            return null;
        }

        // Extract seed from info
        $info = json_decode($data['info'] ?? '{}', true);
        $seed = $info['seed'] ?? null;

        return [
            'image' => $data['images'][0],
            'seed' => $seed,
            'info' => $info
        ];
    }

    /**
     * Generate image variation using Stable Diffusion img2img API
     * Returns array ['image' => base64, 'seed' => int, 'info' => array] or null
     */
    private function generateImg2ImgViaSdApi($apiUrl, $baseImageBase64, $positivePrompt, $negativePrompt, $width, $height, $denoisingStrength = 0.4)
    {
        $payload = [
            'init_images' => [$baseImageBase64],
            'prompt' => $positivePrompt,
            'negative_prompt' => $negativePrompt,
            'width' => $width,
            'height' => $height,
            'steps' => 20,  // Fewer steps for img2img
            'cfg_scale' => 5,
            'sampler_name' => 'Euler a',
            'denoising_strength' => $denoisingStrength,  // 0.3-0.5 = subtle changes, keep structure
            'seed' => -1,
            'batch_size' => 1,
            'n_iter' => 1,
            'tiling' => true,
        ];

        $ch = curl_init($apiUrl . '/sdapi/v1/img2img');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->stdout("  API Error: HTTP {$httpCode}\n");
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['images'][0])) {
            return null;
        }

        // Extract seed from info
        $info = json_decode($data['info'] ?? '{}', true);
        $seed = $info['seed'] ?? null;

        return [
            'image' => $data['images'][0],
            'seed' => $seed,
            'info' => $info
        ];
    }

    /**
     * Make bottom portion of image transparent (for island_edge stalactites)
     */
    private function makeBottomTransparent($imagePath, $transparentHeight = 0.4)
    {
        $image = $this->loadImageAny($imagePath);
        if (!$image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $startY = (int)($height * (1 - $transparentHeight)); // Start transparency from 60% down

        // Make bottom portion transparent
        for ($y = $startY; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($image, $x, $y);
                $alpha = ($color >> 24) & 0xFF;

                // Gradually increase transparency towards bottom
                $progress = ($y - $startY) / ($height - $startY);
                $newAlpha = (int)($progress * 127);

                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;

                $transparentColor = imagecolorallocatealpha($image, $r, $g, $b, $newAlpha);
                imagesetpixel($image, $x, $y, $transparentColor);
            }
        }

        // Save modified image
        imagepng($image, $imagePath, 9);
        imagedestroy($image);

        return true;
    }

    /**
     * Load image from any format
     */
    private function loadImageAny($path)
    {
        // Определяем тип изображения
        $imageInfo = getimagesize($path);
        $mimeType = $imageInfo['mime'] ?? '';

        switch ($mimeType) {
            case 'image/png':
                $image = imagecreatefrompng($path);
                break;
            case 'image/jpeg':
                $image = imagecreatefromjpeg($path);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($path);
                break;
            default:
                $this->stdout("  Unsupported image format: {$mimeType}\n", Console::FG_RED);
                return null;
        }

        if ($image) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image;
    }
}
