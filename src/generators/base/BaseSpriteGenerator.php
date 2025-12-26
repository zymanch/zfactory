<?php

namespace generators\base;

use models\EntityType;
use Yii;

/**
 * Базовый класс для генераторов спрайтов entity
 * Содержит общие методы обработки изображений
 */
abstract class BaseSpriteGenerator
{
    protected $fluxAi;
    protected $basePath;
    protected $testMode = false;

    // Размеры тайлов
    const TILE_WIDTH = 32;   // пикселей на 1 тайл ширины
    const TILE_HEIGHT = 24;  // пикселей на 1 тайл высоты

    public function __construct(FluxAiGenerator $fluxAi, $basePath = null)
    {
        $this->fluxAi = $fluxAi;
        $this->basePath = $basePath ?: Yii::getAlias('@app/..');
    }

    /**
     * Возвращает массив промптов для данной категории
     * @return array ['entity_name' => ['positive' => '...', 'negative' => '...']]
     */
    abstract public function getPrompts(): array;

    /**
     * Генерирует спрайт для entity
     * @param EntityType $entity
     * @param bool $testMode Если true, генерируется только normal.png
     * @return bool Success
     */
    abstract public function generate(EntityType $entity, bool $testMode = false): bool;

    /**
     * Генерирует только состояния (damaged, blueprint, selected) для существующего normal.png
     * @param EntityType $entity
     * @return bool Success
     */
    public function generateStates(EntityType $entity): bool
    {
        $imageUrl = $entity->image_url;
        $entityDir = $this->basePath . '/public/assets/tiles/entities/' . $imageUrl;
        $normalPath = $entityDir . '/normal.png';

        if (!file_exists($normalPath)) {
            echo "Error: normal.png not found at {$normalPath}\n";
            return false;
        }

        echo "  Generating states for {$imageUrl}...\n";

        // Damaged
        $this->createDamaged($normalPath, $entityDir . '/damaged.png');
        echo "  Created damaged.png\n";

        // Blueprint (with direction arrow for conveyors and manipulators)
        $this->createBlueprint($normalPath, $entityDir . '/blueprint.png', $imageUrl);
        echo "  Created blueprint.png\n";

        // Selected variants
        $this->createSelected($normalPath, $entityDir . '/normal_selected.png');
        echo "  Created normal_selected.png\n";

        $this->createSelected($entityDir . '/damaged.png', $entityDir . '/damaged_selected.png');
        echo "  Created damaged_selected.png\n";

        return true;
    }

    /**
     * Удаляет фон используя flood-fill алгоритм
     * @param string $imagePath
     */
    protected function removeBackground($imagePath)
    {
        $img = imagecreatefrompng($imagePath);
        $width = imagesx($img);
        $height = imagesy($img);

        // КРИТИЧНО: Отключить alpha blending чтобы писать alpha канал напрямую
        imagealphablending($img, false);
        imagesavealpha($img, true);

        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

        // Умный flood fill из углов - удаляет только фон соединенный с краями
        $toProcess = [];
        $processed = [];

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

            if ($brightness > 200) {  // Threshold для определения фона
                $toProcess[] = [$x, $y];
            }
        }

        // Итеративный flood fill (без рекурсии)
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

            if ($brightness <= 200) {
                continue;
            }

            $processed[$key] = true;
            imagesetpixel($img, $x, $y, $transparent);

            $toProcess[] = [$x + 1, $y];
            $toProcess[] = [$x - 1, $y];
            $toProcess[] = [$x, $y + 1];
            $toProcess[] = [$x, $y - 1];
        }

        imagepng($img, $imagePath, 9);
        imagedestroy($img);
    }

    /**
     * Масштабирует изображение с сохранением прозрачности
     * @param string $imagePath
     * @param int $targetWidth
     * @param int $targetHeight
     */
    protected function scaleImage($imagePath, $targetWidth, $targetHeight)
    {
        $src = imagecreatefrompng($imagePath);
        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);

        $dest = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);

        imagecopyresampled($dest, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcWidth, $srcHeight);

        imagepng($dest, $imagePath, 9);
        imagedestroy($src);
        imagedestroy($dest);
    }

    /**
     * Создает поврежденную версию спрайта
     * @param string $srcPath
     * @param string $destPath
     */
    protected function createDamaged($srcPath, $destPath)
    {
        $src = imagecreatefrompng($srcPath);
        $width = imagesx($src);
        $height = imagesy($src);

        $dest = imagecreatetruecolor($width, $height);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($src, $x, $y);
                $alpha = ($rgb >> 24) & 0x7F;

                if ($alpha == 127) {
                    continue; // Пропускаем полностью прозрачные пиксели
                }

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Затемнение на 30%
                $r = intval($r * 0.7);
                $g = intval($g * 0.7);
                $b = intval($b * 0.7);

                // Добавляем случайные пятна грязи
                if (rand(0, 100) < 10) {
                    $dirtFactor = rand(80, 100) / 100;
                    $r = intval($r * $dirtFactor);
                    $g = intval($g * $dirtFactor);
                    $b = intval($b * $dirtFactor);
                }

                // Увеличиваем контраст
                $r = max(0, min(255, intval(($r - 128) * 1.2 + 128)));
                $g = max(0, min(255, intval(($g - 128) * 1.2 + 128)));
                $b = max(0, min(255, intval(($b - 128) * 1.2 + 128)));

                $newColor = imagecolorallocatealpha($dest, $r, $g, $b, $alpha);
                imagesetpixel($dest, $x, $y, $newColor);
            }
        }

        imagepng($dest, $destPath, 9);
        imagedestroy($src);
        imagedestroy($dest);
    }

    /**
     * Создает blueprint версию спрайта (синий полупрозрачный)
     * @param string $srcPath
     * @param string $destPath
     * @param string|null $orientation Ориентация (conveyor, conveyor_up, manipulator_short_left, etc.) для стрелки направления
     */
    protected function createBlueprint($srcPath, $destPath, $orientation = null)
    {
        $src = imagecreatefrompng($srcPath);
        $width = imagesx($src);
        $height = imagesy($src);

        $dest = imagecreatetruecolor($width, $height);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($src, $x, $y);
                $alpha = ($rgb >> 24) & 0x7F;

                if ($alpha == 127) {
                    continue; // Пропускаем полностью прозрачные пиксели
                }

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = ($rgb) & 0xFF;

                // Синий оттенок
                $r = intval($r * 0.3);
                $g = intval($g * 0.5);
                $b = intval(min(255, $b * 0.8 + 100));

                // Увеличиваем прозрачность
                $alpha = min(127, $alpha + 64);

                $newColor = imagecolorallocatealpha($dest, $r, $g, $b, $alpha);
                imagesetpixel($dest, $x, $y, $newColor);
            }
        }

        // Рисуем стрелку направления для конвейеров и манипуляторов
        if ($orientation && $this->needsDirectionArrow($orientation)) {
            $this->drawDirectionArrow($dest, $orientation);
        }

        imagepng($dest, $destPath, 9);
        imagedestroy($src);
        imagedestroy($dest);
    }

    /**
     * Проверяет, нужна ли стрелка направления для данной ориентации
     * @param string $orientation
     * @return bool
     */
    protected function needsDirectionArrow($orientation)
    {
        return strpos($orientation, 'conveyor') === 0 || strpos($orientation, 'manipulator') === 0;
    }

    /**
     * Рисует стрелку направления на blueprint спрайте
     * @param resource $img
     * @param string $orientation
     */
    protected function drawDirectionArrow($img, $orientation)
    {
        $width = imagesx($img);
        $height = imagesy($img);

        // Определяем направление стрелки
        $direction = 'right'; // По умолчанию (conveyor, manipulator_short, etc.)

        if (strpos($orientation, '_up') !== false) {
            $direction = 'up';
        } elseif (strpos($orientation, '_down') !== false) {
            $direction = 'down';
        } elseif (strpos($orientation, '_left') !== false) {
            $direction = 'left';
        }

        // Цвет стрелки - яркий желтый, полупрозрачный
        $arrowColor = imagecolorallocatealpha($img, 255, 220, 0, 30);

        // Центр спрайта
        $centerX = intval($width / 2);
        $centerY = intval($height / 2);

        // Размер стрелки (относительно размера спрайта)
        $arrowLength = intval(min($width, $height) * 0.4);
        $arrowWidth = intval($arrowLength * 0.6);

        // Рисуем стрелку в зависимости от направления
        switch ($direction) {
            case 'right':
                // Стрелка вправо: треугольник
                $points = [
                    $centerX - intval($arrowLength / 2), $centerY - intval($arrowWidth / 2), // Левый верх
                    $centerX - intval($arrowLength / 2), $centerY + intval($arrowWidth / 2), // Левый низ
                    $centerX + intval($arrowLength / 2), $centerY,                          // Правая точка
                ];
                imagefilledpolygon($img, $points, 3, $arrowColor);
                break;

            case 'left':
                // Стрелка влево
                $points = [
                    $centerX + intval($arrowLength / 2), $centerY - intval($arrowWidth / 2), // Правый верх
                    $centerX + intval($arrowLength / 2), $centerY + intval($arrowWidth / 2), // Правый низ
                    $centerX - intval($arrowLength / 2), $centerY,                          // Левая точка
                ];
                imagefilledpolygon($img, $points, 3, $arrowColor);
                break;

            case 'up':
                // Стрелка вверх
                $points = [
                    $centerX - intval($arrowWidth / 2), $centerY + intval($arrowLength / 2), // Левый низ
                    $centerX + intval($arrowWidth / 2), $centerY + intval($arrowLength / 2), // Правый низ
                    $centerX, $centerY - intval($arrowLength / 2),                          // Верхняя точка
                ];
                imagefilledpolygon($img, $points, 3, $arrowColor);
                break;

            case 'down':
                // Стрелка вниз
                $points = [
                    $centerX - intval($arrowWidth / 2), $centerY - intval($arrowLength / 2), // Левый верх
                    $centerX + intval($arrowWidth / 2), $centerY - intval($arrowLength / 2), // Правый верх
                    $centerX, $centerY + intval($arrowLength / 2),                          // Нижняя точка
                ];
                imagefilledpolygon($img, $points, 3, $arrowColor);
                break;
        }
    }

    /**
     * Создает selected версию спрайта (с желтым контуром)
     * @param string $srcPath
     * @param string $destPath
     */
    protected function createSelected($srcPath, $destPath)
    {
        $src = imagecreatefrompng($srcPath);
        $width = imagesx($src);
        $height = imagesy($src);

        $dest = imagecreatetruecolor($width, $height);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        // Копируем исходное изображение
        imagecopy($dest, $src, 0, 0, 0, 0, $width, $height);

        // Добавляем желтый контур вокруг краев
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($src, $x, $y);
                $alpha = ($rgb >> 24) & 0x7F;

                if ($alpha == 127) {
                    continue; // Пропускаем полностью прозрачные пиксели
                }

                // Проверяем, есть ли прозрачные соседи (край спрайта)
                $isEdge = false;
                for ($dx = -2; $dx <= 2; $dx++) {
                    for ($dy = -2; $dy <= 2; $dy++) {
                        if ($dx == 0 && $dy == 0) continue;

                        $nx = $x + $dx;
                        $ny = $y + $dy;

                        if ($nx < 0 || $nx >= $width || $ny < 0 || $ny >= $height) {
                            $isEdge = true;
                            break 2;
                        }

                        $neighborRgb = imagecolorat($src, $nx, $ny);
                        $neighborAlpha = ($neighborRgb >> 24) & 0x7F;

                        if ($neighborAlpha == 127) {
                            $isEdge = true;
                            break 2;
                        }
                    }
                }

                if ($isEdge) {
                    // Добавляем желтое свечение
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    $r = min(255, $r + 100);
                    $g = min(255, $g + 100);
                    $b = max(0, $b - 20);

                    $newAlpha = max(0, $alpha - 30);

                    $newColor = imagecolorallocatealpha($dest, $r, $g, $b, $newAlpha);
                    imagesetpixel($dest, $x, $y, $newColor);
                }
            }
        }

        imagepng($dest, $destPath, 9);
        imagedestroy($src);
        imagedestroy($dest);
    }

    /**
     * Поворачивает изображение на заданный угол
     * @param string $srcPath
     * @param string $destPath
     * @param int $angle Угол поворота (90, 180, 270)
     */
    protected function rotateImage($srcPath, $destPath, $angle)
    {
        $src = imagecreatefrompng($srcPath);

        $transparent = imagecolorallocatealpha($src, 0, 0, 0, 127);
        $rotated = imagerotate($src, $angle, $transparent);

        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        imagepng($rotated, $destPath, 9);
        imagedestroy($src);
        imagedestroy($rotated);
    }

    /**
     * Проверяет, является ли entity ротационным вариантом
     * @param string $imageUrl
     * @return bool
     */
    protected function isRotationalVariant($imageUrl)
    {
        $rotationalSuffixes = ['_up', '_down', '_left'];
        foreach ($rotationalSuffixes as $suffix) {
            if (substr($imageUrl, -strlen($suffix)) === $suffix) {
                return true;
            }
        }
        return false;
    }
}
