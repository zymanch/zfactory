<?php

namespace helpers;

/**
 * Генератор текстурных атласов для конвейеров
 * Создает атласы (TILE_WIDTH * VARIANTS) × (TILE_HEIGHT * FRAMES) для каждого состояния
 * По умолчанию: 1024×512px (16 вариантов × 8 кадров)
 */
class ConveyorAtlasGenerator
{
    const TILE_WIDTH = 64;
    const TILE_HEIGHT = 64;
    const VARIANTS = 16;
    const FRAMES = 8;

    private $basePath;
    private $entityDir;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
        $this->entityDir = $basePath . '/public/assets/tiles/entities';
    }

    /**
     * Генерирует атласы для всех ориентаций и состояний
     * @param string $orientation Папка ориентации ('conveyor', 'conveyor_up', etc.)
     * @return int Количество созданных атласов
     */
    public function generateAtlasesForOrientation($orientation)
    {
        $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
        $generatedCount = 0;

        foreach ($states as $state) {
            if ($this->generateAtlas($orientation, $state)) {
                $generatedCount++;
            }
        }

        return $generatedCount;
    }

    /**
     * Генерирует один атлас для указанной ориентации и состояния
     * @param string $orientation Папка ориентации
     * @param string $state Состояние ('normal', 'damaged', etc.)
     * @return bool Успех генерации
     */
    private function generateAtlas($orientation, $state)
    {
        $orientationPath = $this->entityDir . '/' . $orientation;
        $atlasPath = $orientationPath . '/' . $state . '_atlas.png';

        echo "  Generating {$state}_atlas.png for {$orientation}...\n";

        // Создаем атлас
        $atlasWidth = self::TILE_WIDTH * self::VARIANTS;
        $atlasHeight = self::TILE_HEIGHT * self::FRAMES;
        $atlas = imagecreatetruecolor($atlasWidth, $atlasHeight);
        imagealphablending($atlas, false);
        imagesavealpha($atlas, true);

        // Заполняем прозрачным
        $transparent = imagecolorallocatealpha($atlas, 0, 0, 0, 127);
        imagefill($atlas, 0, 0, $transparent);

        $missingSprites = 0;

        // Для каждого кадра (Y ось)
        for ($frame = 0; $frame < self::FRAMES; $frame++) {
            $frameNum = str_pad($frame + 1, 3, '0', STR_PAD_LEFT);

            // Для каждого варианта (X ось)
            for ($variant = 0; $variant < self::VARIANTS; $variant++) {
                $variantNum = str_pad($variant, 2, '0', STR_PAD_LEFT);

                // Загружаем спрайт варианта
                $sprite = $this->loadVariantSprite($orientation, $variantNum, $frameNum, $state);

                if ($sprite === false) {
                    $missingSprites++;
                    continue;
                }

                // Координаты в атласе
                $destX = $variant * self::TILE_WIDTH;
                $destY = $frame * self::TILE_HEIGHT;

                // Копируем в атлас
                imagecopy($atlas, $sprite, $destX, $destY, 0, 0, self::TILE_WIDTH, self::TILE_HEIGHT);

                imagedestroy($sprite);
            }
        }

        // Сохраняем атлас
        imagepng($atlas, $atlasPath, 9);
        imagedestroy($atlas);

        if ($missingSprites > 0) {
            echo "    Warning: {$missingSprites} sprites were missing\n";
        }

        echo "    Saved: {$atlasPath}\n";

        return true;
    }

    /**
     * Загружает спрайт варианта с преобразованием в нужное состояние
     * @param string $orientation Папка ориентации
     * @param string $variantNum Номер варианта (00-15)
     * @param string $frameNum Номер кадра (001-008)
     * @param string $state Целевое состояние
     * @return resource|false Изображение или false при ошибке
     */
    private function loadVariantSprite($orientation, $variantNum, $frameNum, $state)
    {
        $orientationPath = $this->entityDir . '/' . $orientation;

        // Базовый спрайт варианта (всегда normal)
        $variantPath = $orientationPath . '/variant_' . $variantNum . '_' . $frameNum . '.png';

        if (!file_exists($variantPath)) {
            return false;
        }

        $img = imagecreatefrompng($variantPath);

        // Если нужно состояние normal - возвращаем как есть
        if ($state === 'normal') {
            return $img;
        }

        // Применяем преобразование в зависимости от состояния
        switch ($state) {
            case 'damaged':
                $result = $this->createDamaged($img);
                break;
            case 'blueprint':
                $result = $this->createBlueprint($img);
                break;
            case 'normal_selected':
                $result = $this->createSelected($img);
                break;
            case 'damaged_selected':
                $damaged = $this->createDamaged($img);
                $result = $this->createSelected($damaged);
                imagedestroy($damaged);
                break;
            default:
                $result = $img;
        }

        if ($result !== $img) {
            imagedestroy($img);
        }

        return $result;
    }

    /**
     * Создает damaged версию (затемнение + грязь)
     */
    private function createDamaged($src)
    {
        $width = imagesx($src);
        $height = imagesy($src);

        $dest = imagecreatetruecolor($width, $height);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        // Копируем изображение
        imagecopy($dest, $src, 0, 0, 0, 0, $width, $height);

        // Затемняем на 30%
        imagefilter($dest, IMG_FILTER_BRIGHTNESS, -77); // -255 * 0.3 ≈ -77

        // Добавляем случайные темные пятна
        $dirtColor = imagecolorallocatealpha($dest, 40, 30, 20, 30);
        for ($i = 0; $i < 15; $i++) {
            $x = rand(0, $width - 1);
            $y = rand(0, $height - 1);
            imagefilledellipse($dest, $x, $y, rand(2, 4), rand(2, 4), $dirtColor);
        }

        // Усиливаем контраст
        imagefilter($dest, IMG_FILTER_CONTRAST, -20);

        return $dest;
    }

    /**
     * Создает blueprint версию (синий оттенок)
     */
    private function createBlueprint($src)
    {
        $width = imagesx($src);
        $height = imagesy($src);

        $dest = imagecreatetruecolor($width, $height);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($src, $x, $y);
                $a = ($rgb >> 24) & 0xFF;

                if ($a < 127) {
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    // Конвертируем в синий оттенок
                    $gray = ($r + $g + $b) / 3;
                    $newR = intval($gray * 0.4);
                    $newG = intval($gray * 0.6);
                    $newB = intval($gray * 1.0);

                    // Увеличиваем прозрачность
                    $newA = min(127, $a + 64);

                    $newColor = imagecolorallocatealpha($dest, $newR, $newG, $newB, $newA);
                    imagesetpixel($dest, $x, $y, $newColor);
                } else {
                    $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
                    imagesetpixel($dest, $x, $y, $transparent);
                }
            }
        }

        return $dest;
    }

    /**
     * Создает selected версию (желтый контур)
     */
    private function createSelected($src)
    {
        $width = imagesx($src);
        $height = imagesy($src);

        $dest = imagecreatetruecolor($width, $height);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        // Копируем оригинал
        imagecopy($dest, $src, 0, 0, 0, 0, $width, $height);

        // Желтый цвет для контура
        $yellow = imagecolorallocatealpha($dest, 255, 255, 0, 0);

        // Детектируем края и рисуем контур
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($src, $x, $y);
                $alpha = ($rgb >> 24) & 0xFF;

                // Если пиксель не прозрачный
                if ($alpha < 100) {
                    // Проверяем соседей
                    $isEdge = false;

                    // Проверяем 8 соседей
                    for ($dy = -1; $dy <= 1; $dy++) {
                        for ($dx = -1; $dx <= 1; $dx++) {
                            if ($dx == 0 && $dy == 0) continue;

                            $nx = $x + $dx;
                            $ny = $y + $dy;

                            if ($nx >= 0 && $nx < $width && $ny >= 0 && $ny < $height) {
                                $neighborRgb = imagecolorat($src, $nx, $ny);
                                $neighborAlpha = ($neighborRgb >> 24) & 0xFF;

                                // Если сосед прозрачный - это край
                                if ($neighborAlpha > 100) {
                                    $isEdge = true;
                                    break 2;
                                }
                            } else {
                                // Если за границей - тоже край
                                $isEdge = true;
                                break 2;
                            }
                        }
                    }

                    // Рисуем желтый контур
                    if ($isEdge) {
                        imagesetpixel($dest, $x, $y, $yellow);
                    }
                }
            }
        }

        return $dest;
    }
}
