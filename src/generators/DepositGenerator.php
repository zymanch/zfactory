<?php

namespace generators;

use generators\base\FluxAiGenerator;
use models\DepositType;
use Yii;

/**
 * Генератор спрайтов для deposits (руды, деревья, камни)
 * Генерирует только normal.png (deposits не имеют состояний)
 */
class DepositGenerator
{
    protected $fluxAi;
    protected $basePath;

    public function __construct(FluxAiGenerator $fluxAi, $basePath = null)
    {
        $this->fluxAi = $fluxAi;
        $this->basePath = $basePath ?: Yii::getAlias('@app/..');
    }

    /**
     * Промпты для deposits
     */
    public function getPrompts(): array
    {
        return [
            // ORES - россыпь руды, максимальное заполнение пространства
            'ore_iron' => [
                'positive' => 'scattered iron ore rocks, pile of iron ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, metallic gray stones, iron ore cluster, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art',
                'negative' => 'photorealistic, 3d model, blurry, background, landscape, refined metal, single rock, cropped edges, white background, empty space, text, watermark'
            ],
            'ore_copper' => [
                'positive' => 'scattered copper ore rocks, pile of copper ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, orange-brown stones, copper ore cluster, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art',
                'negative' => 'photorealistic, 3d model, blurry, background, landscape, refined metal, single rock, cropped edges, white background, empty space, text, watermark'
            ],
            'ore_aluminum' => [
                'positive' => 'scattered aluminum ore rocks, pile of aluminum ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, light silver-gray stones, bauxite rocks, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art',
                'negative' => 'photorealistic, 3d model, blurry, background, landscape, refined metal, single rock, cropped edges, white background, empty space, text, watermark'
            ],
            'ore_titanium' => [
                'positive' => 'scattered titanium ore rocks, pile of titanium ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, dark metallic gray stones, ilmenite rocks, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art',
                'negative' => 'photorealistic, 3d model, blurry, background, landscape, refined metal, single rock, cropped edges, white background, empty space, text, watermark'
            ],
            'ore_silver' => [
                'positive' => 'scattered silver ore rocks, pile of silver ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, shiny silver-white stones, precious metal ore, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art',
                'negative' => 'photorealistic, 3d model, blurry, background, landscape, refined metal bars, single rock, cropped edges, white background, empty space, text, watermark'
            ],
            'ore_gold' => [
                'positive' => 'scattered gold ore rocks, pile of gold ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, golden-yellow stones, precious metal ore, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art',
                'negative' => 'photorealistic, 3d model, blurry, background, landscape, refined gold bars, single rock, cropped edges, white background, empty space, text, watermark'
            ],
        ];
    }

    /**
     * Генерирует normal.png для deposit
     */
    public function generate(DepositType $deposit): bool
    {
        $imageUrl = $deposit->image_url;
        $prompts = $this->getPrompts();

        if (!isset($prompts[$imageUrl])) {
            echo "  No prompt found for deposit: {$imageUrl}\n";
            return false;
        }

        $prompt = $prompts[$imageUrl];

        // Все deposits 1x1 tile, генерируем в 4x разрешении
        $pixelWidth = Yii::$app->params['tile_width'];
        $pixelHeight = Yii::$app->params['tile_height'];
        $genWidth = $pixelWidth * 4;
        $genHeight = $pixelHeight * 4;

        echo "  Generating {$imageUrl} ({$pixelWidth}x{$pixelHeight}px, gen: {$genWidth}x{$genHeight}px)...\n";

        $imageData = $this->fluxAi->generate(
            $prompt['positive'],
            $prompt['negative'],
            $genWidth,
            $genHeight
        );

        if ($imageData === false) {
            echo "  Failed to generate image\n";
            return false;
        }

        $depositDir = $this->basePath . '/public/assets/tiles/deposits/' . $imageUrl;
        if (!is_dir($depositDir)) {
            mkdir($depositDir, 0755, true);
        }

        $normalPath = $depositDir . '/normal.png';
        file_put_contents($normalPath, base64_decode($imageData));

        // Удаляем фон и масштабируем
        $this->removeBackground($normalPath);
        $this->scaleImage($normalPath, $pixelWidth, $pixelHeight);

        echo "  ✓ Generated normal.png\n";

        return true;
    }

    /**
     * Удаляет белый фон (flood fill из углов)
     */
    protected function removeBackground($imagePath)
    {
        $img = imagecreatefrompng($imagePath);
        $width = imagesx($img);
        $height = imagesy($img);

        imagealphablending($img, false);
        imagesavealpha($img, true);

        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

        // Flood fill из углов
        $toProcess = [];
        $processed = [];

        $corners = [
            [0, 0],
            [$width - 1, 0],
            [0, $height - 1],
            [$width - 1, $height - 1],
        ];

        foreach ($corners as $corner) {
            $toProcess[] = $corner;
        }

        while (!empty($toProcess)) {
            $point = array_shift($toProcess);
            [$x, $y] = $point;

            $key = "{$x},{$y}";
            if (isset($processed[$key]) || $x < 0 || $x >= $width || $y < 0 || $y >= $height) {
                continue;
            }

            $processed[$key] = true;

            $rgb = imagecolorat($img, $x, $y);
            $colors = imagecolorsforindex($img, $rgb);
            $brightness = ($colors['red'] + $colors['green'] + $colors['blue']) / 3;

            // Убираем светлые пиксели (фон)
            if ($brightness > 240) {
                imagesetpixel($img, $x, $y, $transparent);

                // Добавляем соседей
                $toProcess[] = [$x + 1, $y];
                $toProcess[] = [$x - 1, $y];
                $toProcess[] = [$x, $y + 1];
                $toProcess[] = [$x, $y - 1];
            }
        }

        imagepng($img, $imagePath);
        imagedestroy($img);
    }

    /**
     * Масштабирует изображение до целевого размера
     */
    protected function scaleImage($imagePath, $targetWidth, $targetHeight)
    {
        $source = imagecreatefrompng($imagePath);
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        $scaled = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);

        $transparent = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
        imagefill($scaled, 0, 0, $transparent);

        imagecopyresampled(
            $scaled, $source,
            0, 0, 0, 0,
            $targetWidth, $targetHeight,
            $srcWidth, $srcHeight
        );

        imagepng($scaled, $imagePath);
        imagedestroy($source);
        imagedestroy($scaled);
    }
}
