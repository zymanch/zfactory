<?php

namespace actions\entity;

use actions\ConsoleAction;
use models\EntityType;
use Yii;
use yii\helpers\Console;

/**
 * Generate entity sprites using local Stable Diffusion API
 * Usage: php yii entity/generate-ai [entity_name]
 * Examples:
 *   php yii entity/generate-ai tree_pine
 *   php yii entity/generate-ai all
 */
class GenerateAi extends ConsoleAction
{
    private $tileWidth;
    private $tileHeight;

    public function init()
    {
        parent::init();
        $this->tileWidth = Yii::$app->params['tile_width'];
        $this->tileHeight = Yii::$app->params['tile_height'];
    }

    public function run($entityName = 'all')
    {
        $this->stdout("Generating entity sprites using Stable Diffusion API...\n\n");

        $apiUrl = 'http://localhost:7860';
        $basePath = Yii::getAlias('@app/..');
        $entityDir = $basePath . '/public/assets/tiles/entities';

        // Get prompts for each entity type
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

        foreach ($entitiesToProcess as $name => $entity) {
            $this->stdout("Generating {$name}...\n");

            $width = $entity['width'];
            $height = $entity['height'];
            $pixelWidth = $width * $this->tileWidth;
            $pixelHeight = $height * $this->tileHeight;

            $this->stdout("  Entity size: {$width}x{$height} tiles ({$pixelWidth}x{$pixelHeight} pixels)\n");

            // Generate normal.png
            $imageData = $this->generateViaSdApi(
                $apiUrl,
                $prompts[$name]['positive'],
                $prompts[$name]['negative'],
                $pixelWidth,
                $pixelHeight
            );

            if (!$imageData) {
                $this->stdout("  Error: Failed to generate image\n");
                continue;
            }

            $imageBase64 = $imageData['image'];
            $seed = $imageData['seed'];

            // Save normal.png
            $entityPath = $entityDir . '/' . $name;
            if (!is_dir($entityPath)) {
                mkdir($entityPath, 0755, true);
            }

            $normalPath = $entityPath . '/normal.png';
            file_put_contents($normalPath, base64_decode($imageBase64));
            $this->stdout("  Saved: normal.png (seed: {$seed})\n");

            // TODO: Generate other states (damaged, blueprint, selected) if needed
        }

        $this->stdout("\nDone! Generated entity sprites.\n");
        $this->stdout("Note: Only normal.png was generated. Other states need manual creation or separate prompts.\n");

        return 0;
    }

    /**
     * Get prompts for each entity type
     */
    protected function getPrompts()
    {
        return [
            // Trees
            'tree_pine' => [
                'positive' => 'pine tree sprite, game asset, isometric view, 2D game sprite, stylized tree, painted style, green pine needles, brown trunk, forest tree, game terrain object',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, landscape, multiple trees'
            ],
            'tree_oak' => [
                'positive' => 'oak tree sprite, game asset, isometric view, 2D game sprite, stylized tree, painted style, green leafy canopy, brown trunk, forest tree, game terrain object',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, landscape, multiple trees'
            ],
            'tree_dead' => [
                'positive' => 'dead tree sprite, game asset, isometric view, 2D game sprite, stylized tree, painted style, bare branches, gray trunk, withered tree, game terrain object',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, landscape, multiple trees, leaves'
            ],

            // Rocks
            'rock_small' => [
                'positive' => 'small rock sprite, game asset, isometric view, 2D game sprite, stylized stone, painted style, gray rock, boulder, game terrain object',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, landscape, multiple rocks'
            ],
            'rock_medium' => [
                'positive' => 'medium rock sprite, game asset, isometric view, 2D game sprite, stylized stone, painted style, gray rock, large boulder, game terrain object',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, landscape, multiple rocks'
            ],
            'rock_large' => [
                'positive' => 'large rock sprite, game asset, isometric view, 2D game sprite, stylized stone, painted style, gray rock, huge boulder, game terrain object',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, landscape, multiple rocks'
            ],

            // Conveyors
            'conveyor' => [
                'positive' => 'conveyor belt sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, gray metal conveyor, factory equipment, automation game building, horizontal belt',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple objects'
            ],
            'conveyor_up' => [
                'positive' => 'conveyor belt sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, gray metal conveyor, factory equipment, automation game building, upward direction',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple objects'
            ],
            'conveyor_down' => [
                'positive' => 'conveyor belt sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, gray metal conveyor, factory equipment, automation game building, downward direction',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple objects'
            ],
            'conveyor_left' => [
                'positive' => 'conveyor belt sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, gray metal conveyor, factory equipment, automation game building, leftward direction',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple objects'
            ],

            // Buildings
            'furnace' => [
                'positive' => 'furnace building sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, stone furnace, glowing fire inside, factory building, automation game, smelting equipment',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple buildings'
            ],
            'assembler' => [
                'positive' => 'assembly machine sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, complex machinery, factory equipment, automation game building, crafting machine',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple buildings'
            ],
            'chest' => [
                'positive' => 'storage chest sprite, game asset, isometric view, 2D game sprite, stylized, painted style, wooden chest, storage container, game inventory box',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple chests'
            ],
            'power_pole' => [
                'positive' => 'power pole sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, wooden electricity pole, power line tower, factory infrastructure',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple poles'
            ],
            'steam_engine' => [
                'positive' => 'steam engine sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, steam turbine, power generator, factory building, automation game',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple buildings'
            ],
            'boiler' => [
                'positive' => 'boiler sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, water boiler, steam generator, factory equipment, automation game building',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple buildings'
            ],

            // Mining
            'drill' => [
                'positive' => 'mining drill sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, mining equipment, ore extractor, factory machine, automation game',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple drills'
            ],
            'drill_fast' => [
                'positive' => 'fast mining drill sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, advanced mining equipment, faster ore extractor, factory machine, automation game',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple drills'
            ],

            // Manipulators
            'manipulator_short' => [
                'positive' => 'short manipulator arm sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, robotic arm, item inserter, factory equipment, automation game, rightward direction',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple arms'
            ],
            'manipulator_short_up' => [
                'positive' => 'short manipulator arm sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, robotic arm, item inserter, factory equipment, automation game, upward direction',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple arms'
            ],
            'manipulator_short_down' => [
                'positive' => 'short manipulator arm sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, robotic arm, item inserter, factory equipment, automation game, downward direction',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple arms'
            ],
            'manipulator_short_left' => [
                'positive' => 'short manipulator arm sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, robotic arm, item inserter, factory equipment, automation game, leftward direction',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple arms'
            ],
            'manipulator_long' => [
                'positive' => 'long manipulator arm sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, long robotic arm, item inserter, factory equipment, automation game, rightward direction',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple arms'
            ],
            'manipulator_long_up' => [
                'positive' => 'long manipulator arm sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, long robotic arm, item inserter, factory equipment, automation game, upward direction',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple arms'
            ],
            'manipulator_long_down' => [
                'positive' => 'long manipulator arm sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, long robotic arm, item inserter, factory equipment, automation game, downward direction',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple arms'
            ],
            'manipulator_long_left' => [
                'positive' => 'long manipulator arm sprite, game asset, isometric view, 2D game sprite, stylized industrial, painted style, long robotic arm, item inserter, factory equipment, automation game, leftward direction',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple arms'
            ],

            // Resources
            'ore_iron' => [
                'positive' => 'iron ore deposit sprite, game asset, isometric view, 2D game sprite, stylized resource, painted style, iron ore rocks, metallic gray stones, resource deposit, game terrain object',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, refined metal'
            ],
            'ore_copper' => [
                'positive' => 'copper ore deposit sprite, game asset, isometric view, 2D game sprite, stylized resource, painted style, copper ore rocks, orange-brown stones, resource deposit, game terrain object',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, refined metal'
            ],

            // Crystal Towers
            'tower_crystal_small' => [
                'positive' => 'small crystal tower sprite, game asset, isometric view, 2D game sprite, stylized magical, painted style, glowing crystal spire, magical tower, mystical structure, game building',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple towers'
            ],
            'tower_crystal_medium' => [
                'positive' => 'medium crystal tower sprite, game asset, isometric view, 2D game sprite, stylized magical, painted style, glowing crystal spire, tall magical tower, mystical structure, game building',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple towers'
            ],
            'tower_crystal_large' => [
                'positive' => 'large crystal tower sprite, game asset, isometric view, 2D game sprite, stylized magical, painted style, huge glowing crystal spire, massive magical tower, mystical structure, game building',
                'negative' => 'photorealistic, 3d model, realistic, photograph, blurry, background, multiple towers'
            ],
        ];
    }

    /**
     * Generate image using Stable Diffusion API
     * Returns array ['image' => base64, 'seed' => int, 'info' => array] or null
     */
    protected function generateViaSdApi($apiUrl, $positivePrompt, $negativePrompt, $width, $height)
    {
        $payload = [
            'prompt' => $positivePrompt,
            'negative_prompt' => $negativePrompt,
            'width' => $width,
            'height' => $height,
            'steps' => 30,
            'cfg_scale' => 7,
            'sampler_name' => 'DPM++ 2M Karras',  // Better for detailed objects
            'seed' => -1,
            'batch_size' => 1,
            'n_iter' => 1,
            'tiling' => false,  // Entities don't need tiling
        ];

        $ch = curl_init($apiUrl . '/sdapi/v1/txt2img');
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
}
