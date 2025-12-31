<?php

namespace actions\landing;

use actions\ConsoleAction;
use app\client\ComfyUIClient;
use models\Landing;
use Yii;
use yii\helpers\Console;

/**
 * Generate landing sprites using FLUX.1 Dev via ComfyUI
 * Usage: php yii landing/generate-ai-flux [landing_name] [testMode]
 * Examples:
 *   php yii landing/generate-ai-flux grass --testMode=1  (quick test, only base sprite)
 *   php yii landing/generate-ai-flux grass              (full generation with 4 variations)
 *   php yii landing/generate-ai-flux all
 */
class GenerateAiFlux extends ConsoleAction
{
    public $landingName = 'all';
    public $testMode = false;

    /** @var ComfyUIClient */
    private $client;

    public function run($landingName = 'all', $testMode = false)
    {
        $this->landingName = $landingName;
        $this->testMode = $testMode;
        $this->client = new ComfyUIClient();

        $this->stdout("Generating landing sprites using FLUX.1 Dev via ComfyUI...\n");
        if ($testMode) {
            $this->stdout("TEST MODE: Generating only base sprite (no variations)\n");
        }
        $this->stdout("\n");

        $basePath = Yii::getAlias('@app/..');
        $landingDir = $basePath . '/public/assets/tiles/landing';

        // Check if ComfyUI is running
        if (!$this->client->isAvailable()) {
            $this->stdout("Error: ComfyUI is not running at {$this->client->getApiUrl()}\n", Console::FG_RED);
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
            $result = $this->client->txt2img(
                $prompts[$name]['positive'],
                $prompts[$name]['negative'],
                512,
                384,
                ['steps' => 28, 'cfg' => 1.5]
            );

            if (!$result) {
                $this->stdout("  Error: Failed to generate base sprite\n", Console::FG_RED);
                continue;
            }

            $originalPath = $landingPath . '/' . $name . '_0_original.png';
            $result->saveToFile($originalPath);

            // Make seamless tileable
            $this->makeSeamless($originalPath);
            $this->stdout("  Saved base sprite (seamless)\n", Console::FG_GREEN);

            // Generate 4 variations (skip in test mode)
            if (!$this->testMode) {
                $this->stdout("  Generating variations (1-4)...\n");
            }
            for ($i = 1; $i <= ($this->testMode ? 0 : 4); $i++) {
                $varPrompt = $prompts[$name]['positive'];
                if (isset($variationPrompts[$name][$i - 1])) {
                    $varPrompt .= ', ' . $variationPrompts[$name][$i - 1];
                }

                $varResult = $this->client->txt2img(
                    $varPrompt,
                    $prompts[$name]['negative'],
                    512,
                    384,
                    ['steps' => 28, 'cfg' => 1.5]
                );

                if (!$varResult) {
                    $this->stdout("    Warning: Failed to generate variation {$i}\n", Console::FG_YELLOW);
                    continue;
                }

                $varPath = $landingPath . '/' . $name . '_' . $i . '_original.png';
                $varResult->saveToFile($varPath);

                // Make seamless tileable
                $this->makeSeamless($varPath);
                $this->stdout("  Saved variation {$i} (seamless)\n", Console::FG_GREEN);
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
     * Make texture seamless tileable using offset+blend method
     */
    private function makeSeamless($imagePath)
    {
        $img = imagecreatefrompng($imagePath);
        $width = imagesx($img);
        $height = imagesy($img);

        imagesavealpha($img, true);

        // Create new image
        $seamless = imagecreatetruecolor($width, $height);
        imagesavealpha($seamless, true);

        // Offset by half width and height
        $offsetX = (int)($width / 2);
        $offsetY = (int)($height / 2);

        // Copy in 4 quadrants (offset method)
        imagecopy($seamless, $img, 0, 0, $offsetX, $offsetY, $width - $offsetX, $height - $offsetY);
        imagecopy($seamless, $img, $width - $offsetX, 0, 0, $offsetY, $offsetX, $height - $offsetY);
        imagecopy($seamless, $img, 0, $height - $offsetY, $offsetX, 0, $width - $offsetX, $offsetY);
        imagecopy($seamless, $img, $width - $offsetX, $height - $offsetY, 0, 0, $offsetX, $offsetY);

        imagedestroy($img);

        // Apply Gaussian blur to hide seams
        $blurRadius = 5;
        for ($i = 0; $i < $blurRadius; $i++) {
            imagefilter($seamless, IMG_FILTER_GAUSSIAN_BLUR);
        }

        imagepng($seamless, $imagePath);
        imagedestroy($seamless);
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
                'positive' => 'seamless tileable texture, grass field texture from directly above, overhead shot, satellite view, flat surface, no perspective, short grass, natural green tones, varied grass pattern, game texture, top-down orthographic, photorealistic, high detail',
                'negative' => 'solid color, flat color, uniform, neon, oversaturated, side view, perspective, 3d, depth, horizon line, camera angle, tall grass, blurry, low quality, text, watermark'
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
                'positive' => 'seamless tileable texture, detailed snow surface, snow crystals, subtle shadows and highlights, varied white and light blue tones, natural snow texture, icy patches, top-down orthographic view, game asset, photorealistic, high detail',
                'negative' => 'solid white, pure white, flat color, monochrome, uniform, blurry, low quality, dirt, grass, text, watermark, 3d perspective'
            ],
            'swamp' => [
                'positive' => 'seamless tileable texture, swamp ground, murky water, mud, dark green, top-down view, game texture',
                'negative' => 'blurry, low quality, clean water, grass, text, watermark'
            ],
            'island_edge' => [
                'positive' => 'seamless tileable texture, rocky cliff edge, stalactites hanging down, stone formations, bottom edge, game texture, dramatic',
                'negative' => 'blurry, low quality, grass, sky, text, watermark'
            ],
            'ship_edge' => [
                'positive' => 'seamless tileable texture, dark metal ship hull side, industrial panels with rivets, sci-fi starship exterior, side view, metallic surface, game texture',
                'negative' => 'blurry, low quality, top-down view, windows, interior, grass, text, watermark'
            ],
            'ship_floor_wood' => [
                'positive' => 'seamless tileable texture, wooden deck planks, brown wood floor, ship deck, top-down orthographic view, game texture, natural wood grain',
                'negative' => 'blurry, low quality, side view, perspective, 3d, text, watermark'
            ],
            'ship_floor_iron' => [
                'positive' => 'seamless tileable texture, dark gray iron metal plates, industrial floor panels with rivets, heavy duty metal surface, top-down orthographic view, game texture',
                'negative' => 'blurry, low quality, side view, perspective, rust, damaged, text, watermark'
            ],
            'ship_floor_steel' => [
                'positive' => 'seamless tileable texture, light gray polished steel plates, clean industrial metal floor, smooth metallic surface, top-down orthographic view, game texture',
                'negative' => 'blurry, low quality, side view, perspective, dirty, rust, text, watermark'
            ],
            'ship_floor_titanium' => [
                'positive' => 'seamless tileable texture, blue-gray titanium alloy plates, futuristic sci-fi floor, advanced metal surface, top-down orthographic view, game texture',
                'negative' => 'blurry, low quality, side view, perspective, damaged, text, watermark'
            ],
            'ship_floor_crystal' => [
                'positive' => 'seamless tileable texture, purple glowing crystals embedded in metal floor, magical energy floor, sci-fi mystical surface, luminescent crystals, top-down orthographic view, game texture',
                'negative' => 'blurry, low quality, side view, perspective, rocks, dirt, text, watermark'
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
            'ship_edge' => ['more rivets', 'darker metal', 'lighter panels', 'weathered surface'],
            'ship_floor_wood' => ['lighter wood', 'darker wood', 'worn planks', 'polished finish'],
            'ship_floor_iron' => ['more rivets', 'darker plates', 'lighter gray', 'heavy industrial'],
            'ship_floor_steel' => ['polished shine', 'brushed finish', 'light scratches', 'pristine clean'],
            'ship_floor_titanium' => ['blue tint', 'purple tint', 'futuristic glow', 'advanced alloy'],
            'ship_floor_crystal' => ['bright glow', 'dim crystals', 'large crystals', 'small crystals'],
        ];
    }
}
