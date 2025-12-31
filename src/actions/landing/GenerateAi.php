<?php

namespace actions\landing;

use actions\ConsoleAction;
use app\client\StableDiffusionClient;
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

    /** @var StableDiffusionClient */
    private $client;

    public function run($landingName = 'all')
    {
        $this->landingName = $landingName;
        $this->client = new StableDiffusionClient();

        $this->stdout("Generating landing sprites using Stable Diffusion API...\n\n");

        $basePath = Yii::getAlias('@app/..');
        $landingDir = $basePath . '/public/assets/tiles/landing';

        // Check if SD is running
        if (!$this->client->isAvailable()) {
            $this->stdout("Error: Stable Diffusion WebUI is not running at {$this->client->getApiUrl()}\n", Console::FG_RED);
            $this->stdout("Please start it first.\n");
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

            $variationsCount = $landing['variations_count'] ?? 5;
            $variations = $variationPrompts[$name] ?? array_fill(0, $variationsCount, '');

            // Generate base image (variation 0)
            $this->stdout("  Generating base image (0/{$variationsCount})...\n");
            $originalPath = $landingPath . '/' . $name . '_0_original.png';

            if (!file_exists($originalPath)) {
                $this->stdout("    Warning: Base image not found, skipping variations\n", Console::FG_YELLOW);
                continue;
            }

            $baseImageBase64 = base64_encode(file_get_contents($originalPath));

            // Generate other variations using img2img with low denoising for seamless edges
            for ($i = 1; $i < $variationsCount; $i++) {
                $this->stdout("  Generating variation {$i}/{$variationsCount}...\n");

                $modifier = $variations[$i] ?? '';
                $varPrompt = $prompts[$name]['positive'] . ($modifier ? ', ' . $modifier : '');

                // Use img2img with low denoising to preserve seamless edges
                $result = $this->client->img2img(
                    $baseImageBase64,
                    $varPrompt,
                    $prompts[$name]['negative'],
                    512,
                    384,
                    ['denoising_strength' => 0.25]
                );

                if (!$result) {
                    $this->stdout("    Warning: Failed to generate variation {$i}, using base instead\n", Console::FG_YELLOW);
                    $varImageBase64 = $baseImageBase64;
                } else {
                    $varImageBase64 = $result->imageBase64;
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

        $this->stdout("\nAI generation complete! Running scale-original...\n\n");

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
     * Get prompts for each landing type
     */
    protected function getPrompts()
    {
        return [
            'grass' => [
                'positive' => 'seamless tileable grass texture, game sprite, 2D game asset, stylized grass, painted style, vibrant green, game terrain texture',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams'
            ],
            'dirt' => [
                'positive' => 'seamless tileable dirt texture, game sprite, 2D game asset, stylized ground, painted style, brown earth, dry soil with small stones, game terrain texture',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, grass, plants, aerial view, birds eye'
            ],
            'sand' => [
                'positive' => 'seamless tileable sand texture, game sprite, 2D game asset, stylized ground, painted style, golden yellow beach sand, game terrain texture, warm tones',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, water, waves, aerial view, birds eye'
            ],
            'water' => [
                'positive' => 'seamless tileable water texture, game sprite, 2D game asset, stylized water, painted style, clear blue water surface, gentle ripples, game terrain texture',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, foam, waves, beach, aerial view, birds eye'
            ],
            'stone' => [
                'positive' => 'seamless tileable stone texture, game sprite, 2D game asset, stylized ground, painted style, gray rocky ground, stone surface with cracks, game terrain texture',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, moss, plants, aerial view, birds eye'
            ],
            'lava' => [
                'positive' => 'seamless tileable lava texture, game sprite, 2D game asset, stylized ground, painted style, molten lava surface, glowing red-orange magma, volcanic terrain, game terrain texture',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, water, aerial view, birds eye'
            ],
            'snow' => [
                'positive' => 'seamless tileable snow texture, game sprite, 2D game asset, stylized ground, painted style, white snow-covered ground, fresh winter snow, game terrain texture',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, footprints, dirty snow, aerial view, birds eye'
            ],
            'swamp' => [
                'positive' => 'seamless tileable swamp texture, game sprite, 2D game asset, stylized ground, painted style, dark green murky marshland, wet muddy ground with moss, game terrain texture',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, clear water, aerial view, birds eye'
            ],
            'island_edge' => [
                'positive' => 'seamless tileable hanging stalactites, game sprite, 2D game asset, stylized, painted style, rocky earth surface at top, stone stalactites hanging downward, cave ceiling texture, side view, transparent background at bottom',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, top-down view, aerial view, birds eye, sky, clouds, ground at bottom'
            ],
            'ship_edge' => [
                'positive' => 'seamless tileable ship hull edge, game sprite, 2D game asset, stylized, painted style, dark metal ship hull side view, rivets and panels, industrial sci-fi starship exterior, side view',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, top-down view, aerial view, birds eye, interior, windows'
            ],
            'ship_floor_wood' => [
                'positive' => 'seamless tileable wooden planks floor, game sprite, 2D game asset, stylized, painted style, brown wood deck planks, ship floor texture, top-down view',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, side view, perspective'
            ],
            'ship_floor_iron' => [
                'positive' => 'seamless tileable iron metal floor, game sprite, 2D game asset, stylized, painted style, dark gray iron plates, industrial floor panels with rivets, top-down view',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, side view, perspective, rust'
            ],
            'ship_floor_steel' => [
                'positive' => 'seamless tileable steel metal floor, game sprite, 2D game asset, stylized, painted style, light gray polished steel plates, clean industrial floor, top-down view',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, side view, perspective, dirty'
            ],
            'ship_floor_titanium' => [
                'positive' => 'seamless tileable titanium metal floor, game sprite, 2D game asset, stylized, painted style, blue-gray titanium alloy plates, futuristic sci-fi floor, top-down view',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, side view, perspective'
            ],
            'ship_floor_crystal' => [
                'positive' => 'seamless tileable crystal floor, game sprite, 2D game asset, stylized, painted style, purple glowing crystals embedded in floor, magical energy floor, sci-fi floor, top-down view',
                'negative' => 'photorealistic, 3d, realistic, photograph, blurry, seams, side view, perspective, rocks'
            ]
        ];
    }

    /**
     * Get variation prompts for img2img
     */
    protected function getVariationPrompts()
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
        $startY = (int)($height * (1 - $transparentHeight));

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
