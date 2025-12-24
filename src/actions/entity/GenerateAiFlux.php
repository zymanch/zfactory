<?php

namespace actions\entity;

use actions\ConsoleAction;
use models\EntityType;
use Yii;
use yii\helpers\Console;

/**
 * Generate entity sprites using FLUX.1 Dev via ComfyUI (HIGH QUALITY)
 * Usage: php yii entity/generate-ai-flux [entity_name] [testMode]
 * Examples:
 *   php yii entity/generate-ai-flux tree_pine 1   (test mode, only normal.png)
 *   php yii entity/generate-ai-flux tree_pine     (full generation, all 5 states)
 *   php yii entity/generate-ai-flux all
 */
class GenerateAiFlux extends ConsoleAction
{
    private $tileWidth;
    private $tileHeight;
    public $testMode = false;
    public $variationsOnly = false;

    public function init()
    {
        parent::init();
        $this->tileWidth = Yii::$app->params['tile_width'];
        $this->tileHeight = Yii::$app->params['tile_height'];
    }

    public function run($entityName = 'all', $testMode = false, $variationsOnly = false)
    {
        $this->testMode = $testMode;
        $this->variationsOnly = $variationsOnly;

        $this->stdout("Generating entity sprites using FLUX.1 Dev via ComfyUI (HIGH QUALITY)...\n");
        if ($variationsOnly) {
            $this->stdout("VARIATIONS ONLY MODE: Using existing normal.png, generating variations only\n");
        } elseif ($testMode) {
            $this->stdout("TEST MODE: Generating only normal.png (no variations)\n");
        } else {
            $this->stdout("FULL MODE: Generating normal.png + all variations\n");
        }
        $this->stdout("\n");

        $apiUrl = 'http://localhost:8188';
        $basePath = Yii::getAlias('@app/..');
        $entityDir = $basePath . '/public/assets/tiles/entities';

        // Check if ComfyUI is running
        if (!$this->checkComfyUIRunning($apiUrl)) {
            $this->stdout("Error: ComfyUI is not running at $apiUrl\n", Console::FG_RED);
            $this->stdout("Please start ComfyUI first: cd ai && start_comfyui.bat\n");
            return 1;
        }

        // Get prompts from parent class
        $prompts = $this->getPrompts();

        // Get entities to process
        $entitiesToProcess = [];
        if ($entityName === 'all') {
            $entities = EntityType::find()->asArray()->all();
            foreach ($entities as $entity) {
                $name = $entity['image_url'];
                if (isset($prompts[$name])) {
                    $entitiesToProcess[$name] = $entity;
                }
            }
        } else {
            $entity = EntityType::find()->where(['image_url' => $entityName])->asArray()->one();
            if ($entity && isset($prompts[$entityName])) {
                $entitiesToProcess[$entityName] = $entity;
            } else {
                $this->stdout("Error: Entity '{$entityName}' not found or no prompt defined.\n");
                return 1;
            }
        }

        if (empty($entitiesToProcess)) {
            $this->stdout("No entities to process.\n");
            return 1;
        }

        // Process entities, handling rotational variants
        foreach ($entitiesToProcess as $name => $entity) {
            // Skip rotational variants - they'll be generated from base
            if ($this->isRotationalVariant($name)) {
                continue;
            }

            $this->stdout("Processing {$name}...\n");

            $width = $entity['width'];
            $height = $entity['height'];
            $pixelWidth = $width * $this->tileWidth;
            $pixelHeight = $height * $this->tileHeight;

            // Use higher resolution for better quality (2x)
            $genWidth = $pixelWidth * 2;
            $genHeight = $pixelHeight * 2;

            $this->stdout("  Entity size: {$width}x{$height} tiles ({$pixelWidth}x{$pixelHeight} pixels)\n");
            $this->stdout("  Generation size: {$genWidth}x{$genHeight} pixels (2x upscale)\n");

            $entityPath = $entityDir . '/' . $name;
            if (!is_dir($entityPath)) {
                mkdir($entityPath, 0755, true);
            }

            $normalPath = $entityPath . '/normal.png';

            // Generate normal.png (skip if variationsOnly mode)
            if (!$this->variationsOnly) {
                $this->stdout("  Generating normal.png...\n");
                $imageData = $this->generateViaComfyUI(
                    $apiUrl,
                    $prompts[$name]['positive'],
                    $prompts[$name]['negative'],
                    $genWidth,
                    $genHeight
                );

                if (!$imageData) {
                    $this->stdout("  Error: Failed to generate normal.png\n", Console::FG_RED);
                    continue;
                }

                file_put_contents($normalPath, base64_decode($imageData));

                // Remove background and scale down
                $this->removeBackground($normalPath);
                $this->scaleImage($normalPath, $pixelWidth, $pixelHeight);
                $this->stdout("  Saved: normal.png\n", Console::FG_GREEN);
            } else {
                // Variations only mode - check if normal.png exists
                if (!file_exists($normalPath)) {
                    $this->stdout("  Warning: normal.png not found, skipping {$name}\n", Console::FG_YELLOW);
                    continue;
                }
                $this->stdout("  Using existing normal.png\n", Console::FG_CYAN);
            }

            // Generate other states (skip in test mode)
            if (!$this->testMode) {
                // damaged.png - PHP post-processing
                $this->stdout("  Creating damaged.png...\n");
                $this->createDamaged($normalPath, $entityPath . '/damaged.png');
                $this->stdout("  Saved: damaged.png\n", Console::FG_GREEN);

                // blueprint.png - PHP post-processing
                $this->stdout("  Creating blueprint.png...\n");
                $this->createBlueprint($normalPath, $entityPath . '/blueprint.png');
                $this->stdout("  Saved: blueprint.png\n", Console::FG_GREEN);

                // normal_selected.png - PHP post-processing
                $this->stdout("  Creating normal_selected.png...\n");
                $this->createSelected($normalPath, $entityPath . '/normal_selected.png');
                $this->stdout("  Saved: normal_selected.png\n", Console::FG_GREEN);

                // damaged_selected.png - PHP post-processing
                $this->stdout("  Creating damaged_selected.png...\n");
                $this->createSelected($entityPath . '/damaged.png', $entityPath . '/damaged_selected.png');
                $this->stdout("  Saved: damaged_selected.png\n", Console::FG_GREEN);
            }
        }

        // Generate rotational variants from base images
        $this->stdout("\nGenerating rotational variants...\n");
        $this->generateRotationalVariants($entityDir, $entitiesToProcess);

        $this->stdout("\nDone! Generated entity sprites.\n");
        return 0;
    }

    /**
     * Check if entity is a rotational variant
     */
    private function isRotationalVariant($name)
    {
        $rotationalSuffixes = ['_up', '_down', '_left'];
        foreach ($rotationalSuffixes as $suffix) {
            if (substr($name, -strlen($suffix)) === $suffix) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get base entity name for rotational variant
     */
    private function getBaseName($name)
    {
        $rotationalSuffixes = ['_up', '_down', '_left'];
        foreach ($rotationalSuffixes as $suffix) {
            if (substr($name, -strlen($suffix)) === $suffix) {
                return substr($name, 0, -strlen($suffix));
            }
        }
        return $name;
    }

    /**
     * Generate rotational variants from base images
     */
    private function generateRotationalVariants($entityDir, $entitiesToProcess)
    {
        $rotationMap = [
            '_up' => 270,    // Rotate 270° CCW (or 90° CW)
            '_down' => 90,   // Rotate 90° CCW (or 270° CW)
            '_left' => 180,  // Rotate 180°
        ];

        foreach ($entitiesToProcess as $name => $entity) {
            // Check if this entity has rotational variants
            foreach ($rotationMap as $suffix => $angle) {
                $variantName = $name . $suffix;

                // Check if variant exists in database (not just in current processing list)
                $variantEntity = \models\EntityType::find()
                    ->where(['image_url' => $variantName])
                    ->one();

                if (!$variantEntity) {
                    continue;
                }

                $this->stdout("  Creating {$variantName} from {$name} (rotate {$angle}°)...\n");

                $basePath = $entityDir . '/' . $name;
                $variantPath = $entityDir . '/' . $variantName;

                if (!is_dir($variantPath)) {
                    mkdir($variantPath, 0755, true);
                }

                // Rotate normal.png
                if (file_exists($basePath . '/normal.png')) {
                    $this->rotateImage($basePath . '/normal.png', $variantPath . '/normal.png', $angle);
                }

                // Rotate other states if they exist (not in test mode)
                if (!$this->testMode) {
                    $states = ['damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
                    foreach ($states as $state) {
                        if (file_exists($basePath . '/' . $state . '.png')) {
                            $this->rotateImage($basePath . '/' . $state . '.png', $variantPath . '/' . $state . '.png', $angle);
                        }
                    }
                }

                $this->stdout("  Created {$variantName}\n", Console::FG_GREEN);
            }
        }
    }

    /**
     * Rotate image by angle (90, 180, 270)
     */
    private function rotateImage($sourcePath, $destPath, $angle)
    {
        $src = imagecreatefrompng($sourcePath);
        imagesavealpha($src, true);

        // Rotate image
        $rotated = imagerotate($src, $angle, imagecolorallocatealpha($src, 0, 0, 0, 127));
        imagesavealpha($rotated, true);

        imagepng($rotated, $destPath);
        imagedestroy($src);
        imagedestroy($rotated);
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

        $workflow = json_decode(file_get_contents($workflowPath), true);

        // Update workflow with our parameters
        $workflow['2']['inputs']['text'] = $prompt;
        $workflow['3']['inputs']['text'] = $negativePrompt;
        $workflow['5']['inputs']['width'] = $width;
        $workflow['5']['inputs']['height'] = $height;

        // High quality settings - realistic and detailed
        $workflow['6']['inputs']['seed'] = rand(0, 2147483647);
        $workflow['6']['inputs']['steps'] = 45; // Higher for maximum detail
        $workflow['6']['inputs']['cfg'] = 6.0; // Strong guidance for realism

        // Queue prompt
        $payload = ['prompt' => $workflow];

        $ch = curl_init($apiUrl . '/prompt');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10 minutes for high quality

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
        $maxAttempts = 300; // 5 minutes max
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
     * Remove background using smart flood fill algorithm
     * Detects background from corners and removes it
     */
    private function removeBackground($imagePath)
    {
        $img = imagecreatefrompng($imagePath);
        $width = imagesx($img);
        $height = imagesy($img);

        // CRITICAL: Disable alpha blending so we write alpha channel directly
        imagealphablending($img, false);
        imagesavealpha($img, true);

        // Allocate transparent color once
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

        // Smart flood fill: only remove background pixels connected to edges
        // This prevents removing light pixels in the middle of the sprite
        $toProcess = [];
        $processed = [];

        // Start from all 4 corners
        $corners = [
            [0, 0],
            [$width - 1, 0],
            [0, $height - 1],
            [$width - 1, $height - 1]
        ];

        foreach ($corners as [$x, $y]) {
            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $brightness = ($r + $g + $b) / 3;

            if ($brightness > 150) {
                $toProcess[] = [$x, $y];
            }
        }

        // Flood fill from corners
        while (!empty($toProcess)) {
            [$x, $y] = array_shift($toProcess);
            $key = "$x,$y";

            if (isset($processed[$key]) || $x < 0 || $x >= $width || $y < 0 || $y >= $height) {
                continue;
            }

            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $brightness = ($r + $g + $b) / 3;

            // Only process if bright enough
            if ($brightness <= 150) {
                continue;
            }

            $processed[$key] = true;
            imagesetpixel($img, $x, $y, $transparent);

            // Add neighbors to queue (4-way connectivity)
            $toProcess[] = [$x + 1, $y];
            $toProcess[] = [$x - 1, $y];
            $toProcess[] = [$x, $y + 1];
            $toProcess[] = [$x, $y - 1];
        }

        imagepng($img, $imagePath, 9);
        imagedestroy($img);
    }

    /**
     * Scale image to target size
     */
    private function scaleImage($imagePath, $targetWidth, $targetHeight)
    {
        $src = imagecreatefrompng($imagePath);
        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);

        $dst = imagecreatetruecolor($targetWidth, $targetHeight);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcWidth, $srcHeight);

        imagepng($dst, $imagePath);
        imagedestroy($src);
        imagedestroy($dst);
    }

    /**
     * Create damaged version (darker, dirt, wear)
     * IMPORTANT: Keep transparent background intact!
     */
    private function createDamaged($sourcePath, $destPath)
    {
        $img = imagecreatefrompng($sourcePath);
        $width = imagesx($img);
        $height = imagesy($img);

        imagesavealpha($img, true);

        // Apply damage effect only to visible pixels
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $a = ($rgb >> 24) & 0x7F;
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Skip transparent or mostly transparent pixels (don't touch background!)
                if ($a > 100) continue;

                // Darken (reduce brightness by 30%)
                $r = (int)($r * 0.7);
                $g = (int)($g * 0.7);
                $b = (int)($b * 0.7);

                // Add random dirt/wear spots (10% chance per pixel, only on solid pixels)
                if ($a < 20 && rand(0, 100) < 10) {
                    $r = max(0, $r - rand(20, 40));
                    $g = max(0, $g - rand(20, 40));
                    $b = max(0, $b - rand(20, 40));
                }

                // Increase contrast slightly
                $r = max(0, min(255, ($r - 128) * 1.2 + 128));
                $g = max(0, min(255, ($g - 128) * 1.2 + 128));
                $b = max(0, min(255, ($b - 128) * 1.2 + 128));

                $color = imagecolorallocatealpha($img, $r, $g, $b, $a);
                imagesetpixel($img, $x, $y, $color);
            }
        }

        imagepng($img, $destPath);
        imagedestroy($img);
    }

    /**
     * Create blueprint version (blue tint + semi-transparent)
     */
    private function createBlueprint($sourcePath, $destPath)
    {
        $img = imagecreatefrompng($sourcePath);
        $width = imagesx($img);
        $height = imagesy($img);

        imagesavealpha($img, true);

        // Apply blue tint and reduce opacity
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $a = ($rgb >> 24) & 0x7F;
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Skip fully transparent pixels
                if ($a == 127) continue;

                // Blue tint + semi-transparent
                $newR = (int)($r * 0.3 + $b * 0.3);
                $newG = (int)($g * 0.3 + $b * 0.3);
                $newB = (int)($b * 0.8 + 100);
                $newA = min(127, $a + 64); // More transparent

                $color = imagecolorallocatealpha($img, $newR, $newG, $newB, $newA);
                imagesetpixel($img, $x, $y, $color);
            }
        }

        imagepng($img, $destPath);
        imagedestroy($img);
    }

    /**
     * Create selected version (add glow/outline)
     */
    private function createSelected($sourcePath, $destPath)
    {
        $src = imagecreatefrompng($sourcePath);
        $width = imagesx($src);
        $height = imagesy($src);

        // Create new image with glow
        $dst = imagecreatetruecolor($width, $height);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));

        // Copy original
        imagecopy($dst, $src, 0, 0, 0, 0, $width, $height);

        // Add yellow outline
        $yellow = imagecolorallocatealpha($dst, 255, 255, 0, 30);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($src, $x, $y);
                $a = ($rgb >> 24) & 0x7F;

                // If pixel is not transparent, check neighbors
                if ($a < 100) {
                    // Check if any neighbor is transparent (edge detection)
                    $isEdge = false;
                    for ($dy = -2; $dy <= 2; $dy++) {
                        for ($dx = -2; $dx <= 2; $dx++) {
                            $nx = $x + $dx;
                            $ny = $y + $dy;
                            if ($nx >= 0 && $nx < $width && $ny >= 0 && $ny < $height) {
                                $nrgb = imagecolorat($src, $nx, $ny);
                                $na = ($nrgb >> 24) & 0x7F;
                                if ($na > 100) {
                                    $isEdge = true;
                                    break 2;
                                }
                            }
                        }
                    }

                    if ($isEdge) {
                        imagesetpixel($dst, $x, $y, $yellow);
                    }
                }
            }
        }

        imagepng($dst, $destPath);
        imagedestroy($src);
        imagedestroy($dst);
    }

    /**
     * Get prompts for each entity type
     */
    protected function getPrompts()
    {
        return [
            // Trees
            'tree_pine' => [
                'positive' => 'pine tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, green pine needles, brown trunk, highly detailed bark, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality'
            ],
            'tree_oak' => [
                'positive' => 'oak tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, green leafy canopy, brown trunk, highly detailed bark, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality'
            ],
            'tree_dead' => [
                'positive' => 'dead tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, bare branches, gray trunk, withered, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, leaves, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality'
            ],

            // Rocks
            'rock_small' => [
                'positive' => 'small rock boulder, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic stone texture, gray rock, highly detailed surface, cracks and weathering, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, sky, blurry, low quality'
            ],
            'rock_medium' => [
                'positive' => 'medium rock boulder, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic stone texture, gray rock, large boulder, highly detailed surface, cracks and weathering, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, sky, blurry, low quality'
            ],
            'rock_large' => [
                'positive' => 'large rock boulder, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic stone texture, gray rock, huge boulder, highly detailed surface, cracks and weathering, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, sky, blurry, low quality'
            ],

            // Conveyors
            'conveyor' => [
                'positive' => 'flat conveyor belt, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal texture, gray metallic belt, detailed mechanical parts, no legs, no stand, no support structure, PERFECTLY HORIZONTAL ORIENTATION, straight from left to right, NOT diagonal, NOT angled, belt running horizontally across image, flat on surface, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, legs, stand, support structure, elevated, platform, base, diagonal angle, angled view, tilted, rotated, multiple objects, landscape, ground, blurry, low quality'
            ],
            'conveyor_up' => [
                'positive' => 'flat conveyor belt laying on ground, game sprite, isometric view, single object, clean white background, stylized industrial, gray metal belt, no legs, no stand, flat on surface, game asset, no shadows',
                'negative' => 'legs, stand, support structure, elevated, platform, base, multiple objects, landscape, ground, realistic photo, blurry, low quality'
            ],
            'conveyor_down' => [
                'positive' => 'flat conveyor belt laying on ground, game sprite, isometric view, single object, clean white background, stylized industrial, gray metal belt, no legs, no stand, flat on surface, game asset, no shadows',
                'negative' => 'legs, stand, support structure, elevated, platform, base, multiple objects, landscape, ground, realistic photo, blurry, low quality'
            ],
            'conveyor_left' => [
                'positive' => 'flat conveyor belt laying on ground, game sprite, isometric view, single object, clean white background, stylized industrial, gray metal belt, no legs, no stand, flat on surface, game asset, no shadows',
                'negative' => 'legs, stand, support structure, elevated, platform, base, multiple objects, landscape, ground, realistic photo, blurry, low quality'
            ],

            // Buildings
            'furnace' => [
                'positive' => 'stone furnace, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic stone texture, glowing fire inside, detailed metallic parts, smelting equipment, NOT tilted, NOT angled, straight top-down perspective, flat orientation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, multiple objects, landscape, ground, blurry, low quality'
            ],
            'assembler' => [
                'positive' => 'assembly machine, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, complex machinery, detailed mechanical parts, crafting machine, NOT tilted, NOT angled, straight top-down perspective, flat orientation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, multiple objects, landscape, ground, blurry, low quality'
            ],
            'chest' => [
                'positive' => 'wooden storage chest, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic wood texture, detailed wood grain, metal fittings, storage container, inventory box, NOT tilted, NOT angled, straight top-down perspective, flat orientation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, multiple objects, landscape, ground, blurry, low quality'
            ],
            'power_pole' => [
                'positive' => 'wooden power pole, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic wood texture, detailed wood grain, electricity pole with insulators, power tower, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, blurry, low quality'
            ],
            'steam_engine' => [
                'positive' => 'steam engine turbine, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed mechanical parts, power generator, factory building, NOT tilted, NOT angled, straight top-down perspective, flat orientation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, multiple objects, landscape, ground, blurry, low quality'
            ],
            'boiler' => [
                'positive' => 'water boiler, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed rivets and pipes, steam generator, factory equipment, NOT tilted, NOT angled, straight top-down perspective, flat orientation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, multiple objects, landscape, ground, blurry, low quality'
            ],

            // Mining
            'drill' => [
                'positive' => 'mining drill, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed mechanical parts, ore extractor, mining equipment, NOT tilted, NOT angled, straight top-down perspective, flat orientation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, multiple objects, landscape, ground, blurry, low quality'
            ],
            'drill_fast' => [
                'positive' => 'advanced mining drill, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed mechanical parts, faster ore extractor, advanced technology, NOT tilted, NOT angled, straight top-down perspective, flat orientation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, multiple objects, landscape, ground, blurry, low quality'
            ],

            // Manipulators
            'manipulator_short' => [
                'positive' => 'short robotic arm, game sprite, isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed mechanical joints, item inserter, factory equipment, rightward direction, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, blurry, low quality'
            ],
            'manipulator_short_up' => [
                'positive' => 'short robotic arm, game sprite, isometric view, single object, clean white background, stylized industrial, item inserter, factory equipment, upward direction, game asset, no shadows',
                'negative' => 'multiple objects, landscape, ground, realistic photo, blurry, low quality'
            ],
            'manipulator_short_down' => [
                'positive' => 'short robotic arm, game sprite, isometric view, single object, clean white background, stylized industrial, item inserter, factory equipment, downward direction, game asset, no shadows',
                'negative' => 'multiple objects, landscape, ground, realistic photo, blurry, low quality'
            ],
            'manipulator_short_left' => [
                'positive' => 'short robotic arm, game sprite, isometric view, single object, clean white background, stylized industrial, item inserter, factory equipment, leftward direction, game asset, no shadows',
                'negative' => 'multiple objects, landscape, ground, realistic photo, blurry, low quality'
            ],
            'manipulator_long' => [
                'positive' => 'long robotic arm, game sprite, isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed mechanical joints, long item inserter, factory equipment, rightward direction, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, blurry, low quality'
            ],
            'manipulator_long_up' => [
                'positive' => 'long robotic arm, game sprite, isometric view, single object, clean white background, stylized industrial, long item inserter, factory equipment, upward direction, game asset, no shadows',
                'negative' => 'multiple objects, landscape, ground, realistic photo, blurry, low quality'
            ],
            'manipulator_long_down' => [
                'positive' => 'long robotic arm, game sprite, isometric view, single object, clean white background, stylized industrial, long item inserter, factory equipment, downward direction, game asset, no shadows',
                'negative' => 'multiple objects, landscape, ground, realistic photo, blurry, low quality'
            ],
            'manipulator_long_left' => [
                'positive' => 'long robotic arm, game sprite, isometric view, single object, clean white background, stylized industrial, long item inserter, factory equipment, leftward direction, game asset, no shadows',
                'negative' => 'multiple objects, landscape, ground, realistic photo, blurry, low quality'
            ],

            // Resources
            'ore_iron' => [
                'positive' => 'iron ore deposit, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic stone texture, metallic gray rocks, iron ore stones, detailed mineral surface, resource deposit, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, refined metal, multiple objects, landscape, ground, blurry, low quality'
            ],
            'ore_copper' => [
                'positive' => 'copper ore deposit, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic stone texture, orange-brown copper rocks, detailed mineral surface, resource deposit, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, refined metal, multiple objects, landscape, ground, blurry, low quality'
            ],

            // Crystal Towers
            'tower_crystal_small' => [
                'positive' => 'small crystal tower, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic crystal material, glowing translucent crystal spire, detailed facets, light refraction, mystical structure, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, blurry, low quality'
            ],
            'tower_crystal_medium' => [
                'positive' => 'medium crystal tower, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic crystal material, tall glowing translucent crystal spire, detailed facets, light refraction, mystical structure, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, blurry, low quality'
            ],
            'tower_crystal_large' => [
                'positive' => 'large crystal tower, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic crystal material, huge glowing translucent crystal spire, detailed facets, light refraction, mystical structure, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, blurry, low quality'
            ],
        ];
    }
}
