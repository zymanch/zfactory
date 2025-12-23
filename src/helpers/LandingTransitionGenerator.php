<?php

namespace helpers;

use Yii;

/**
 * LandingTransitionGenerator - Generates texture atlases for landing transitions
 *
 * Creates single atlas PNG for each landing type containing all variations and transitions.
 * Uses formula: Row = top_z + 1, Column = right_z for atlas coordinates.
 */
class LandingTransitionGenerator
{
    private $sourceDir;
    private $outputDir;
    private $tileWidth = 32;
    private $tileHeight = 24;

    /** @var int Padding between sprites in atlas (to prevent bleeding) */
    private $padding = 0;

    /** @var float Amplitude of the wavy line */
    private $waveAmplitude = 1;

    /** @var float Frequency of the wavy line (waves per tile) */
    private $waveFrequency = 2.0;

    /** @var int Outline width in pixels */
    private $outlineWidth = 1;

    public function __construct($basePath)
    {
        $this->sourceDir = $basePath . '/public/assets/tiles/landing';
        $this->outputDir = $basePath . '/public/assets/tiles/landing/atlases';

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Генерирует атласы для всех типов лендингов
     */
    public function generateAllAtlases()
    {
        $landings = $this->getLandings();

        foreach ($landings as $landing) {
            echo "Generating atlas for landing {$landing['landing_id']} ({$landing['name']})...\n";
            $this->generateAtlas($landing);
        }

        echo "All atlases generated successfully!\n";
    }

    /**
     * Генерирует один атлас для конкретного лендинга
     */
    private function generateAtlas($landing)
    {
        $landingId = $landing['landing_id'];
        $variationsCount = $landing['variations_count'];
        $imageName = str_replace('.png', '', $landing['image_url']);

        // Загружаем базовое изображение
        $baseImage = $this->loadImage($landing['image_url']);

        // Загружаем готовые вариации из папки
        $variations = $this->loadVariations($imageName, $variationsCount);

        // Получаем все типы лендингов для генерации всех комбинаций
        $allLandings = $this->getLandings();
        $maxLandingId = 0;
        foreach ($allLandings as $l) {
            if ($l['landing_id'] > $maxLandingId) {
                $maxLandingId = $l['landing_id'];
            }
        }

        // Размеры атласа
        $tileWithPadding = $this->tileWidth + $this->padding;
        $tileHeightWithPadding = $this->tileHeight + $this->padding;

        // Колонки: от 0 до maxLandingId (0 = самоссылка, 1-10 = другие лендинги)
        $atlasWidth = ($maxLandingId + 1) * $tileWithPadding;
        // Строки: строка 0 (вариации) + строки 1-11 (переходы для каждого лендинга сверху)
        $atlasHeight = ($maxLandingId + 2) * $tileHeightWithPadding;

        // Создаем пустой атлас
        $atlas = imagecreatetruecolor($atlasWidth, $atlasHeight);
        imagealphablending($atlas, false);
        imagesavealpha($atlas, true);

        // Заливаем прозрачностью
        $transparent = imagecolorallocatealpha($atlas, 0, 0, 0, 127);
        imagefill($atlas, 0, 0, $transparent);

        // Строка 0: Вариации базового тайла (для рандомизации)
        // Заполняем только первые N колонок вариациями
        foreach ($variations as $colIdx => $variation) {
            imagecopy(
                $atlas,
                $variation,
                $colIdx * $tileWithPadding,
                0,
                0, 0,
                $this->tileWidth,
                $this->tileHeight
            );
        }

        // Загружаем изображения всех лендингов (для генерации всех комбинаций)
        $landingImages = [];
        $landingImages[0] = $baseImage; // 0 = самоссылка

        foreach ($allLandings as $l) {
            $lId = $l['landing_id'];
            $lImageName = str_replace('.png', '', $l['image_url']);

            // Загружаем первую вариацию каждого лендинга
            $landingImages[$lId] = $this->loadVariationImage($lImageName, 0);
        }

        // Генерируем ВСЕ комбинации: каждый лендинг сверху × каждый лендинг справа
        for ($topId = 0; $topId <= $maxLandingId; $topId++) {
            if (!isset($landingImages[$topId])) {
                continue; // Пропускаем несуществующие ID
            }

            $topImage = $landingImages[$topId];
            $row = $topId + 1;  // +1 потому что строка 0 занята вариациями

            for ($rightId = 0; $rightId <= $maxLandingId; $rightId++) {
                if (!isset($landingImages[$rightId])) {
                    continue; // Пропускаем несуществующие ID
                }

                $rightImage = $landingImages[$rightId];

                // Special rules for Island Edge (ID=10) transitions:

                // 1. For Sky: if top is Island Edge, treat as Sky
                if ($landingId == 9 && $topId == 10) {
                    $topImage = $landingImages[9];
                }

                // 2. For Island Edge: if right is Sky, treat as Island Edge
                if ($landingId == 10 && $rightId == 9) {
                    $rightImage = $landingImages[10];
                }

                // Генерируем переход: сверху topImage, справа rightImage
                $transition = $this->createTransition($baseImage, $topImage, $rightImage);

                imagecopy(
                    $atlas,
                    $transition,
                    $rightId * $tileWithPadding,
                    $row * $tileHeightWithPadding,
                    0, 0,
                    $this->tileWidth,
                    $this->tileHeight
                );

                imagedestroy($transition);
            }
        }

        // Освобождаем память
        foreach ($landingImages as $lId => $img) {
            if ($lId !== 0 && $img !== $baseImage) {
                imagedestroy($img);
            }
        }

        // Сохраняем атлас
        $atlasPath = $this->outputDir . "/{$imageName}_atlas.png";
        imagepng($atlas, $atlasPath, 9);
        imagedestroy($atlas);

        foreach ($variations as $variation) {
            imagedestroy($variation);
        }
        imagedestroy($baseImage);

        echo "  Atlas saved: {$atlasPath} ({$atlasWidth}x{$atlasHeight})\n";
    }

    /**
     * Создает переход между тайлами с волнистыми линиями
     * @param resource $base - Базовый тайл
     * @param resource $top - Тайл сверху
     * @param resource $right - Тайл справа
     * @return resource
     */
    private function createTransition($base, $top, $right)
    {
        // Получаем цвет outline (затемненная версия самого темного цвета в базовом тайле)
        $outlineColor = $this->getDarkenedColor($base, 0.5);

        // Определяем, совпадают ли соседи с базой
        $topIsSame = $this->imagesEqual($base, $top);
        $rightIsSame = $this->imagesEqual($base, $right);

        if ($topIsSame && $rightIsSame) {
            // Оба соседа совпадают - просто возвращаем копию базы
            return $this->cloneImage($base);
        } elseif (!$topIsSame && $rightIsSame) {
            // Только сверху другой - TOP transition
            return $this->generateTopTransition($base, $top, $outlineColor);
        } elseif ($topIsSame && !$rightIsSame) {
            // Только справа другой - RIGHT transition
            return $this->generateRightTransition($base, $right, $outlineColor);
        } else {
            // Оба соседа разные - CORNER transition
            return $this->generateCornerTransition($base, $top, $right, $outlineColor);
        }
    }

    /**
     * Проверяет, совпадают ли два изображения (сравнивает несколько пикселей)
     */
    private function imagesEqual($img1, $img2)
    {
        // Сравниваем несколько точек для быстрой проверки
        $points = [
            [0, 0], [$this->tileWidth - 1, 0],
            [0, $this->tileHeight - 1], [$this->tileWidth - 1, $this->tileHeight - 1],
            [$this->tileWidth / 2, $this->tileHeight / 2]
        ];

        foreach ($points as [$x, $y]) {
            if (imagecolorat($img1, $x, $y) !== imagecolorat($img2, $x, $y)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate RIGHT transition - Wavy line from bottom-right to top-right
     */
    private function generateRightTransition($baseImage, $rightImage, $outlineColor)
    {
        $result = $this->cloneImage($baseImage);

        // Generate wavy boundary X positions for each Y
        $wavyX = [];
        for ($y = 0; $y < $this->tileHeight; $y++) {
            $t = $y / ($this->tileHeight - 1);
            $wave = cos($t * 2 * M_PI * $this->waveFrequency) * $this->waveAmplitude;
            $wavyX[$y] = (int)round($this->tileWidth - 1 - $this->waveAmplitude + $wave);
        }

        // Copy pixels from right image for positions right of wavy line
        for ($y = 0; $y < $this->tileHeight; $y++) {
            $boundaryX = $wavyX[$y];
            for ($x = $boundaryX; $x < $this->tileWidth; $x++) {
                $color = imagecolorat($rightImage, $x, $y);
                imagesetpixel($result, $x, $y, $color);
            }
        }

        // Draw outline on the wavy line
        $this->drawOutline($result, $wavyX, 'vertical', $outlineColor);

        return $result;
    }

    /**
     * Generate TOP transition - Wavy line from top-left to top-right
     */
    private function generateTopTransition($baseImage, $topImage, $outlineColor)
    {
        $result = $this->cloneImage($baseImage);

        // Generate wavy boundary Y positions for each X
        $wavyY = [];
        for ($x = 0; $x < $this->tileWidth; $x++) {
            $t = $x / ($this->tileWidth - 1);
            $wave = cos($t * 2 * M_PI * $this->waveFrequency) * $this->waveAmplitude;
            $wavyY[$x] = (int)round($this->waveAmplitude - $wave);
        }

        // Copy pixels from top image for positions above wavy line
        for ($x = 0; $x < $this->tileWidth; $x++) {
            $boundaryY = $wavyY[$x];
            for ($y = 0; $y < $boundaryY; $y++) {
                $color = imagecolorat($topImage, $x, $y);
                imagesetpixel($result, $x, $y, $color);
            }
        }

        // Draw outline on the wavy line
        $this->drawOutline($result, $wavyY, 'horizontal', $outlineColor);

        return $result;
    }

    /**
     * Generate CORNER transition - L-shaped wavy line
     */
    private function generateCornerTransition($baseImage, $topImage, $rightImage, $outlineColor)
    {
        $result = $this->cloneImage($baseImage);

        // Top edge wavy Y positions
        $topWavyY = [];
        for ($x = 0; $x < $this->tileWidth; $x++) {
            $t = $x / ($this->tileWidth - 1);
            $wave = cos($t * 2 * M_PI * $this->waveFrequency) * $this->waveAmplitude;
            $topWavyY[$x] = (int)round($this->waveAmplitude - $wave);
        }

        // Right edge wavy X positions
        $rightWavyX = [];
        for ($y = 0; $y < $this->tileHeight; $y++) {
            $t = $y / ($this->tileHeight - 1);
            $wave = cos($t * 2 * M_PI * $this->waveFrequency) * $this->waveAmplitude;
            $rightWavyX[$y] = (int)round($this->tileWidth - 1 - $this->waveAmplitude + $wave);
        }

        // Apply regions based on L-shaped boundary
        for ($x = 0; $x < $this->tileWidth; $x++) {
            for ($y = 0; $y < $this->tileHeight; $y++) {
                $isAboveTopLine = isset($topWavyY[$x]) && $y < $topWavyY[$x];
                $isRightOfRightLine = isset($rightWavyX[$y]) && $x >= $rightWavyX[$y];

                if ($isAboveTopLine) {
                    $color = imagecolorat($topImage, $x, $y);
                    imagesetpixel($result, $x, $y, $color);
                } elseif ($isRightOfRightLine) {
                    $color = imagecolorat($rightImage, $x, $y);
                    imagesetpixel($result, $x, $y, $color);
                }
            }
        }

        // Draw L-shaped outline
        $this->drawLShapedOutline($result, $topWavyY, $rightWavyX, $outlineColor);

        return $result;
    }

    /**
     * Draw outline along wavy line
     */
    private function drawOutline($image, $wavyPositions, $direction, $color)
    {
        if ($direction === 'vertical') {
            for ($y = 0; $y < $this->tileHeight; $y++) {
                $x = $wavyPositions[$y];
                for ($w = 0; $w < $this->outlineWidth; $w++) {
                    $drawX = $x - $w;
                    if ($drawX >= 0 && $drawX < $this->tileWidth) {
                        imagesetpixel($image, $drawX, $y, $color);
                    }
                }
            }
        } else {
            for ($x = 0; $x < $this->tileWidth; $x++) {
                $y = $wavyPositions[$x];
                for ($w = 0; $w < $this->outlineWidth; $w++) {
                    $drawY = $y + $w;
                    if ($drawY >= 0 && $drawY < $this->tileHeight) {
                        imagesetpixel($image, $x, $drawY, $color);
                    }
                }
            }
        }
    }

    /**
     * Draw L-shaped outline
     */
    private function drawLShapedOutline($image, $topWavyY, $rightWavyX, $color)
    {
        // Top edge outline
        for ($x = 0; $x < $this->tileWidth; $x++) {
            if (isset($topWavyY[$x])) {
                $y = $topWavyY[$x];
                for ($w = 0; $w < $this->outlineWidth; $w++) {
                    $drawY = $y + $w;
                    if ($drawY >= 0 && $drawY < $this->tileHeight) {
                        imagesetpixel($image, $x, $drawY, $color);
                    }
                }
            }
        }

        // Right edge outline
        for ($y = 0; $y < $this->tileHeight; $y++) {
            if (isset($rightWavyX[$y])) {
                $x = $rightWavyX[$y];
                for ($w = 0; $w < $this->outlineWidth; $w++) {
                    $drawX = $x - $w;
                    if ($drawX >= 0 && $drawX < $this->tileWidth) {
                        imagesetpixel($image, $drawX, $y, $color);
                    }
                }
            }
        }
    }

    /**
     * Clone an image
     */
    private function cloneImage($source)
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $clone = imagecreatetruecolor($width, $height);
        imagealphablending($clone, false);
        imagesavealpha($clone, true);
        imagecopy($clone, $source, 0, 0, 0, 0, $width, $height);
        return $clone;
    }

    /**
     * Get darkened color from darkest pixel in image
     */
    private function getDarkenedColor($image, $darkenFactor)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $darkestR = 255;
        $darkestG = 255;
        $darkestB = 255;
        $darkestBrightness = 255 * 3;

        // Sample pixels to find darkest
        for ($x = 0; $x < $width; $x += 2) {
            for ($y = 0; $y < $height; $y += 2) {
                $color = imagecolorat($image, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;
                $brightness = $r + $g + $b;

                if ($brightness < $darkestBrightness) {
                    $darkestBrightness = $brightness;
                    $darkestR = $r;
                    $darkestG = $g;
                    $darkestB = $b;
                }
            }
        }

        // Darken the color
        $darkR = (int)round($darkestR * $darkenFactor);
        $darkG = (int)round($darkestG * $darkenFactor);
        $darkB = (int)round($darkestB * $darkenFactor);

        return imagecolorallocate($image, $darkR, $darkG, $darkB);
    }

    /**
     * Загружает изображение (первую вариацию из папки)
     */
    private function loadImage($filename)
    {
        // Файлы теперь находятся в папках вариаций
        $landingName = str_replace('.png', '', $filename);
        return $this->loadVariationImage($landingName, 0);
    }

    /**
     * Загружает готовые вариации из папки
     * @param string $landingName - Имя лендинга (например, 'grass')
     * @param int $count - Количество вариаций
     * @return array - Массив GD image resources
     */
    private function loadVariations($landingName, $count)
    {
        $variations = [];
        for ($i = 0; $i < $count; $i++) {
            $variations[] = $this->loadVariationImage($landingName, $i);
        }
        return $variations;
    }

    /**
     * Загружает одну вариацию
     * @param string $landingName - Имя лендинга
     * @param int $index - Индекс вариации
     * @return resource - GD image resource
     */
    private function loadVariationImage($landingName, $index)
    {
        $path = $this->sourceDir . '/' . $landingName . '/' . $landingName . '_' . $index . '.png';
        $image = imagecreatefrompng($path);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        return $image;
    }

    /**
     * Получает все лендинги
     */
    private function getLandings()
    {
        return Yii::$app->db->createCommand(
            'SELECT landing_id, name, image_url, variations_count FROM {{%landing}} ORDER BY landing_id'
        )->queryAll();
    }

    /**
     * Получает лендинг по ID
     */
    private function getLandingById($id)
    {
        return Yii::$app->db->createCommand(
            'SELECT landing_id, image_url FROM {{%landing}} WHERE landing_id = :id'
        )->bindValue(':id', $id)->queryOne();
    }

    /**
     * Получает смежности для лендинга
     */
    private function getAdjacencies($landingId)
    {
        return Yii::$app->db->createCommand(
            'SELECT landing_id_2, atlas_z
             FROM {{%landing_adjacency}}
             WHERE landing_id_1 = :id
             ORDER BY atlas_z'
        )->bindValue(':id', $landingId)->queryAll();
    }
}
