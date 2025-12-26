<?php

namespace commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Conveyor sprite processing commands
 * Handles animation frames, connection variants, and atlas generation
 */
class ConveyorController extends Controller
{
    // Константы для настройки
    const ANIMATION_FRAMES = 8;      // Кадров анимации
    const SHIFT_PER_FRAME = 8;       // Пикселей сдвига на кадр
    const CONNECTION_VARIANTS = 16;  // Вариантов соединений (4-bit = 2^4)
    const TILE_WIDTH = 64;           // Размер тайла в пикселях
    const TILE_HEIGHT = 64;

    private $basePath;
    private $entityDir;

    public function init()
    {
        parent::init();
        $this->basePath = Yii::getAlias('@app/..');
        $this->entityDir = $this->basePath . '/public/assets/tiles/entities';
    }

    /**
     * Делает спрайт конвейера симметричным (зеркалирует нижнюю половину наверх)
     * Usage: php yii conveyor/mirror-normal
     */
    public function actionMirrorNormal()
    {
        $this->stdout("=== Mirror Conveyor Sprite (Make Symmetrical) ===\n\n", Console::FG_CYAN);

        $conveyorPath = $this->entityDir . '/conveyor';
        $normalPath = $conveyorPath . '/normal.png';

        if (!file_exists($normalPath)) {
            $this->stdout("Error: {$normalPath} not found\n", Console::FG_RED);
            return 1;
        }

        $this->stdout("Loading: {$normalPath}\n");

        // Загружаем изображение
        $img = imagecreatefrompng($normalPath);

        // КРИТИЧЕСКИ ВАЖНО: сохраняем прозрачность
        imagealphablending($img, false);
        imagesavealpha($img, true);

        $width = imagesx($img);
        $height = imagesy($img);

        $this->stdout("Image size: {$width}x{$height}px\n");

        if ($height < 32) {
            $this->stdout("Error: Image height must be at least 32px\n", Console::FG_RED);
            return 1;
        }

        // Вычисляем середину
        $halfHeight = intval($height / 2);

        $this->stdout("Half height: {$halfHeight}px\n");
        $this->stdout("Extracting bottom half (Y: {$halfHeight}-{$height})...\n");

        // Вырезаем нижнюю половину
        $bottomHalf = imagecreatetruecolor($width, $halfHeight);
        imagealphablending($bottomHalf, false);
        imagesavealpha($bottomHalf, true);

        imagecopy($bottomHalf, $img, 0, 0, 0, $halfHeight, $width, $halfHeight);

        $this->stdout("Flipping vertically...\n");

        // Отражаем вертикально
        imageflip($bottomHalf, IMG_FLIP_VERTICAL);

        $this->stdout("Copying to top half (Y: 0-{$halfHeight})...\n");

        // Копируем на верхнюю половину
        imagecopy($img, $bottomHalf, 0, 0, 0, 0, $width, $halfHeight);

        // Сохраняем
        imagepng($img, $normalPath, 9);

        $this->stdout("Saved: {$normalPath}\n", Console::FG_GREEN);
        $this->stdout("\n✓ Conveyor sprite is now symmetrical!\n", Console::FG_GREEN);

        imagedestroy($img);
        imagedestroy($bottomHalf);

        return 0;
    }

    /**
     * Generates 8 animation frames for conveyor belt
     * Usage: php yii conveyor/generate-animation-frames
     */
    public function actionGenerateAnimationFrames()
    {
        $this->stdout("=== Generate Conveyor Animation Frames ===\n\n", Console::FG_CYAN);

        $conveyorPath = $this->entityDir . '/conveyor';
        $normalPath = $conveyorPath . '/normal.png';

        if (!file_exists($normalPath)) {
            $this->stdout("Error: {$normalPath} not found\n", Console::FG_RED);
            $this->stdout("Run 'php yii conveyor/mirror-normal' first\n");
            return 1;
        }

        $this->stdout("Loading: {$normalPath}\n");

        // Загружаем спрайт
        $sprite = imagecreatefrompng($normalPath);

        // КРИТИЧЕСКИ ВАЖНО: сохраняем прозрачность
        imagealphablending($sprite, false);
        imagesavealpha($sprite, true);

        $width = imagesx($sprite);
        $height = imagesy($sprite);

        $this->stdout("Sprite size: {$width}x{$height}px\n");

        // Детектируем границы ленты
        $this->stdout("\nDetecting belt edges...\n");
        $beltTop = $this->detectBeltEdge($sprite, 'top');
        $beltBottom = $this->detectBeltEdge($sprite, 'bottom');

        if ($beltTop === false || $beltBottom === false) {
            $this->stdout("Error: Could not detect belt edges\n", Console::FG_RED);
            $this->stdout("Using fallback values: top=20, bottom=44\n", Console::FG_YELLOW);
            $beltTop = 20;
            $beltBottom = 44;
        }

        $beltHeight = $beltBottom - $beltTop;
        $this->stdout("Belt region: Y {$beltTop}-{$beltBottom} (height: {$beltHeight}px)\n", Console::FG_GREEN);

        // Вырезаем ленту
        $belt = imagecreatetruecolor($width, $beltHeight);
        imagealphablending($belt, false);
        imagesavealpha($belt, true);
        imagecopy($belt, $sprite, 0, 0, 0, $beltTop, $width, $beltHeight);

        $this->stdout("Extracted belt region\n");

        // Создаем расширенную ленту (5x ширина)
        $extendedWidth = $width * 5;
        $extendedBelt = imagecreatetruecolor($extendedWidth, $beltHeight);
        imagealphablending($extendedBelt, false);
        imagesavealpha($extendedBelt, true);

        for ($i = 0; $i < 5; $i++) {
            imagecopy($extendedBelt, $belt, $i * $width, 0, 0, 0, $width, $beltHeight);
        }

        $this->stdout("Created extended belt ({$extendedWidth}x{$beltHeight}px)\n");

        // Генерируем 8 кадров анимации
        $this->stdout("\nGenerating " . self::ANIMATION_FRAMES . " animation frames...\n");

        for ($frame = 0; $frame < self::ANIMATION_FRAMES; $frame++) {
            $frameImage = imagecreatetruecolor($width, $height);
            imagealphablending($frameImage, false);
            imagesavealpha($frameImage, true);

            // Копируем верхнюю часть (до ленты)
            imagecopy($frameImage, $sprite, 0, 0, 0, 0, $width, $beltTop);

            // Копируем нижнюю часть (после ленты)
            imagecopy($frameImage, $sprite, 0, $beltBottom, 0, $beltBottom, $width, $height - $beltBottom);

            // Копируем ленту со сдвигом (инвертируем для движения вправо)
            $shiftX = (self::ANIMATION_FRAMES - 1 - $frame) * self::SHIFT_PER_FRAME;
            imagecopy($frameImage, $extendedBelt, 0, $beltTop, $shiftX, 0, $width, $beltHeight);

            // Сохраняем кадр
            $frameNum = str_pad($frame + 1, 3, '0', STR_PAD_LEFT);
            $framePath = $conveyorPath . '/normal_' . $frameNum . '.png';
            imagepng($frameImage, $framePath, 9);

            $this->stdout("  Frame {$frameNum}: shift={$shiftX}px → {$framePath}\n");

            imagedestroy($frameImage);
        }

        $this->stdout("\n✓ Generated " . self::ANIMATION_FRAMES . " animation frames\n", Console::FG_GREEN);

        imagedestroy($sprite);
        imagedestroy($belt);
        imagedestroy($extendedBelt);

        return 0;
    }

    /**
     * Детектирует край ленты конвейера по изменению яркости
     * @param resource $img
     * @param string $edge 'top' или 'bottom'
     * @return int|false Y координата края или false при ошибке
     */
    private function detectBeltEdge($img, $edge)
    {
        $width = imagesx($img);
        $height = imagesy($img);

        if ($edge === 'top') {
            // Сканируем сверху вниз
            for ($y = 0; $y < $height; $y++) {
                $darkPixels = 0;
                for ($x = 0; $x < $width; $x++) {
                    $rgb = imagecolorat($img, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $brightness = ($r + $g + $b) / 3;

                    if ($brightness < 100) {
                        $darkPixels++;
                    }
                }

                // Если >50% пикселей темные, это край ленты
                if ($darkPixels > ($width * 0.5)) {
                    return $y;
                }
            }
        } else { // 'bottom'
            // Сканируем снизу вверх
            for ($y = $height - 1; $y >= 0; $y--) {
                $darkPixels = 0;
                for ($x = 0; $x < $width; $x++) {
                    $rgb = imagecolorat($img, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $brightness = ($r + $g + $b) / 3;

                    if ($brightness < 100) {
                        $darkPixels++;
                    }
                }

                // Если >50% пикселей темные, это край ленты
                if ($darkPixels > ($width * 0.5)) {
                    return $y + 1; // +1 чтобы включить эту строку
                }
            }
        }

        return false;
    }

    /**
     * Rotates animation frames for other orientations (up, down, left)
     * Usage: php yii conveyor/rotate-animation-frames
     */
    public function actionRotateAnimationFrames()
    {
        $this->stdout("=== Rotate Animation Frames for Other Orientations ===\n\n", Console::FG_CYAN);

        $orientations = [
            'conveyor_up' => -90,    // 90° против часовой (CCW): RIGHT -> UP
            'conveyor_down' => 90,   // 90° по часовой (CW): RIGHT -> DOWN
            'conveyor_left' => 180   // 180°: RIGHT -> LEFT
        ];

        $conveyorPath = $this->entityDir . '/conveyor';

        // Проверяем что кадры существуют
        $frame001 = $conveyorPath . '/normal_001.png';
        if (!file_exists($frame001)) {
            $this->stdout("Error: Animation frames not found\n", Console::FG_RED);
            $this->stdout("Run 'php yii conveyor/generate-animation-frames' first\n");
            return 1;
        }

        $totalFrames = 0;

        foreach ($orientations as $folder => $angle) {
            $this->stdout("Processing {$folder} (rotate {$angle}°)...\n");

            $variantPath = $this->entityDir . '/' . $folder;
            if (!is_dir($variantPath)) {
                mkdir($variantPath, 0755, true);
                $this->stdout("  Created directory: {$variantPath}\n");
            }

            // После исправления углов поворота реверс кадров не нужен
            // Поворот спрайта автоматически дает правильное направление анимации

            for ($frame = 1; $frame <= self::ANIMATION_FRAMES; $frame++) {
                $srcFrameNum = $frame;

                $srcFrameStr = str_pad($srcFrameNum, 3, '0', STR_PAD_LEFT);
                $destFrameStr = str_pad($frame, 3, '0', STR_PAD_LEFT);

                $srcPath = $conveyorPath . '/normal_' . $srcFrameStr . '.png';
                $destPath = $variantPath . '/normal_' . $destFrameStr . '.png';

                if (!file_exists($srcPath)) {
                    $this->stdout("  Warning: {$srcPath} not found, skipping\n", Console::FG_YELLOW);
                    continue;
                }

                // Загружаем, поворачиваем, сохраняем
                $src = imagecreatefrompng($srcPath);
                $transparent = imagecolorallocatealpha($src, 0, 0, 0, 127);
                $rotated = imagerotate($src, -$angle, $transparent);
                imagealphablending($rotated, false);
                imagesavealpha($rotated, true);

                imagepng($rotated, $destPath, 9);

                imagedestroy($src);
                imagedestroy($rotated);

                $totalFrames++;
            }

            if ($reverseFrames) {
                $this->stdout("  ✓ Rotated " . self::ANIMATION_FRAMES . " frames (reversed)\n", Console::FG_GREEN);
            } else {
                $this->stdout("  ✓ Rotated " . self::ANIMATION_FRAMES . " frames\n", Console::FG_GREEN);
            }
        }

        $this->stdout("\n✓ Total frames rotated: {$totalFrames}\n", Console::FG_GREEN);

        return 0;
    }

    /**
     * Generates 16 connection variants for conveyors
     * Usage: php yii conveyor/generate-connection-variants
     */
    public function actionGenerateConnectionVariants()
    {
        $this->stdout("=== Generate Connection Variants (16 variants × 8 frames) ===\n\n", Console::FG_CYAN);

        $conveyorPath = $this->entityDir . '/conveyor';

        // Проверяем что кадры существуют
        $frame001 = $conveyorPath . '/normal_001.png';
        if (!file_exists($frame001)) {
            $this->stdout("Error: Animation frames not found\n", Console::FG_RED);
            $this->stdout("Run 'php yii conveyor/generate-animation-frames' first\n");
            return 1;
        }

        $totalVariants = 0;

        // Генерируем для каждого кадра
        for ($frame = 1; $frame <= self::ANIMATION_FRAMES; $frame++) {
            $frameNum = str_pad($frame, 3, '0', STR_PAD_LEFT);
            $baseFramePath = $conveyorPath . '/normal_' . $frameNum . '.png';

            if (!file_exists($baseFramePath)) {
                $this->stdout("Warning: {$baseFramePath} not found, skipping\n", Console::FG_YELLOW);
                continue;
            }

            $this->stdout("Processing frame {$frameNum}...\n");
            $baseFrame = imagecreatefrompng($baseFramePath);

            // Генерируем все 16 вариантов
            for ($variant = 0; $variant < self::CONNECTION_VARIANTS; $variant++) {
                $left  = $variant & 1;
                $right = $variant & 2;
                $up    = $variant & 4;
                $down  = $variant & 8;

                $result = $this->createConnectionVariant($baseFrame, $left, $right, $up, $down);

                $variantPath = $conveyorPath . '/variant_' . str_pad($variant, 2, '0', STR_PAD_LEFT) . '_' . $frameNum . '.png';
                imagepng($result, $variantPath, 9);

                imagedestroy($result);
                $totalVariants++;
            }

            imagedestroy($baseFrame);
            $this->stdout("  Generated 16 variants for frame {$frameNum}\n");
        }

        $this->stdout("\n✓ Total variants generated: {$totalVariants} (16 variants × " . self::ANIMATION_FRAMES . " frames)\n", Console::FG_GREEN);

        return 0;
    }

    /**
     * Создает вариант соединения конвейера на основе маски соединений
     * @param resource $baseFrame Базовый кадр
     * @param int $left Соединение слева (0 или 1)
     * @param int $right Соединение справа (0 или 1)
     * @param int $up Соединение сверху (0 или 1)
     * @param int $down Соединение снизу (0 или 1)
     * @return resource Модифицированное изображение
     */
    private function createConnectionVariant($baseFrame, $left, $right, $up, $down)
    {
        $width = imagesx($baseFrame);
        $height = imagesy($baseFrame);

        // Создаем результирующее изображение
        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        // Заполняем прозрачным
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);

        // Копируем базовый кадр
        imagecopy($result, $baseFrame, 0, 0, 0, 0, $width, $height);

        // Применяем модификации в зависимости от соединений

        // Если нет входящих соединений - добавляем прозрачную полосу слева
        if (!$left && !$up && !$down) {
            $this->addLeftTransparency($result, 10);
        }

        // Если только сверху - отражение по антидиагонали
        if ($up && !$left && !$down) {
            $result = $this->mirrorTriangleTop($result);
        }
        // Если сверху + другие соседи - используем поворот половины
        else if ($up) {
            $this->addTopConnection($result, $baseFrame);
        }

        // Если только снизу - отражение по главной диагонали
        if ($down && !$left && !$up) {
            $result = $this->mirrorTriangleBottom($result);
        }
        // Если снизу + другие соседы - используем поворот половины
        else if ($down) {
            $this->addBottomConnection($result, $baseFrame);
        }

        return $result;
    }

    /**
     * Добавляет прозрачную полосу слева
     */
    private function addLeftTransparency($img, $width)
    {
        $height = imagesy($img);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                imagesetpixel($img, $x, $y, $transparent);
            }
        }
    }

    /**
     * Добавляет соединение сверху (поворачивает левую половину на 90°)
     * Для комбинированных случаев: LEFT + UP
     */
    private function addTopConnection($result, $baseFrame)
    {
        $width = imagesx($baseFrame);
        $height = imagesy($baseFrame);
        $halfWidth = intval($width / 2);

        // Вырезаем левую половину
        $leftHalf = imagecreatetruecolor($halfWidth, $height);
        imagealphablending($leftHalf, false);
        imagesavealpha($leftHalf, true);
        imagecopy($leftHalf, $baseFrame, 0, 0, 0, 0, $halfWidth, $height);

        // Поворачиваем на 90° (левая часть становится верхней)
        $transparent = imagecolorallocatealpha($leftHalf, 0, 0, 0, 127);
        $rotated = imagerotate($leftHalf, -90, $transparent);
        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        // Копируем только на прозрачные области
        $this->overlayOnTransparent($result, $rotated, 0, 0);

        imagedestroy($leftHalf);
        imagedestroy($rotated);
    }

    /**
     * Добавляет соединение снизу (поворачивает левую половину на -90°)
     * Для комбинированных случаев: LEFT + DOWN
     */
    private function addBottomConnection($result, $baseFrame)
    {
        $width = imagesx($baseFrame);
        $height = imagesy($baseFrame);
        $halfWidth = intval($width / 2);

        // Вырезаем левую половину
        $leftHalf = imagecreatetruecolor($halfWidth, $height);
        imagealphablending($leftHalf, false);
        imagesavealpha($leftHalf, true);
        imagecopy($leftHalf, $baseFrame, 0, 0, 0, 0, $halfWidth, $height);

        // Поворачиваем на -90° = 270° (левая часть становится нижней)
        $transparent = imagecolorallocatealpha($leftHalf, 0, 0, 0, 127);
        $rotated = imagerotate($leftHalf, -270, $transparent);
        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        // Копируем только на прозрачные области (размещаем внизу)
        $rotatedHeight = imagesy($rotated);
        $destY = $height - $rotatedHeight;
        $this->overlayOnTransparent($result, $rotated, 0, $destY);

        imagedestroy($leftHalf);
        imagedestroy($rotated);
    }

    /**
     * Копирует изображение только на прозрачные области
     */
    private function overlayOnTransparent($dest, $src, $destX, $destY)
    {
        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);

        for ($y = 0; $y < $srcHeight; $y++) {
            for ($x = 0; $x < $srcWidth; $x++) {
                $destPixelX = $destX + $x;
                $destPixelY = $destY + $y;

                // Проверяем границы
                if ($destPixelX < 0 || $destPixelX >= imagesx($dest) ||
                    $destPixelY < 0 || $destPixelY >= imagesy($dest)) {
                    continue;
                }

                // Проверяем прозрачность целевого пикселя
                $destColor = imagecolorat($dest, $destPixelX, $destPixelY);
                $destAlpha = ($destColor >> 24) & 0x7F;

                // Если целевой пиксель прозрачный (alpha > 100)
                if ($destAlpha > 100) {
                    $srcColor = imagecolorat($src, $x, $y);
                    imagesetpixel($dest, $destPixelX, $destPixelY, $srcColor);
                }
            }
        }
    }

    /**
     * Отражает треугольник для соединения с конвейером СВЕРХУ
     * Использует антидиагональ (из верхнего правого в нижний левый)
     *
     * Логика:
     * - Верхний левый треугольник: отражаем по формуле newX = y, newY = x
     * - Нижний правый треугольник: копируем без изменений
     */
    private function mirrorTriangleTop($img)
    {
        $width = imagesx($img);
        $height = imagesy($img);

        // Создаем новый холст
        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        // Заполняем прозрачным
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);

        // Проходим по всем пикселям
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($img, $x, $y);

                // Антидиагональ: x + y = width - 1
                if ($x + $y < $width) {
                    // Верхний левый треугольник - отражаем (простое x/y swap)
                    $newX = $y;
                    $newY = $x;

                    if ($newX >= 0 && $newX < $width && $newY >= 0 && $newY < $height) {
                        imagesetpixel($result, $newX, $newY, $color);
                    }
                } else {
                    // Нижний правый треугольник - копируем без изменений
                    imagesetpixel($result, $x, $y, $color);
                }
            }
        }

        imagedestroy($img);
        return $result;
    }

    /**
     * Отражает треугольник для соединения с конвейером СНИЗУ
     * Использует главную диагональ y = x (из верхнего левого в нижний правый)
     *
     * Логика:
     * - Нижний левый треугольник (x < y): отражаем по формуле
     * - Верхний правый треугольник (x >= y): копируем без изменений
     */
    private function mirrorTriangleBottom($img)
    {
        $width = imagesx($img);
        $height = imagesy($img);

        // Создаем новый холст
        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        // Заполняем прозрачным
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);

        // Проходим по всем пикселям
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($img, $x, $y);

                if ($x < $y) {
                    // Нижний левый треугольник - отражаем
                    $newX = $width - $y - 1;
                    $newY = $height - $x - 1;

                    if ($newX >= 0 && $newX < $width && $newY >= 0 && $newY < $height) {
                        imagesetpixel($result, $newX, $newY, $color);
                    }
                } else {
                    // Верхний правый треугольник - копируем без изменений
                    imagesetpixel($result, $x, $y, $color);
                }
            }
        }

        imagedestroy($img);
        return $result;
    }

    /**
     * Rotates connection variants for other orientations (up, down, left)
     * Usage: php yii conveyor/rotate-connection-variants
     */
    public function actionRotateConnectionVariants()
    {
        $this->stdout("=== Rotate Connection Variants for Other Orientations ===\n\n", Console::FG_CYAN);

        $orientations = [
            'conveyor_up' => -90,    // 90° против часовой (CCW): RIGHT -> UP
            'conveyor_down' => 90,   // 90° по часовой (CW): RIGHT -> DOWN
            'conveyor_left' => 180   // 180°: RIGHT -> LEFT
        ];

        $conveyorPath = $this->entityDir . '/conveyor';

        // Проверяем что варианты существуют
        $variant00_001 = $conveyorPath . '/variant_00_001.png';
        if (!file_exists($variant00_001)) {
            $this->stdout("Error: Connection variants not found\n", Console::FG_RED);
            $this->stdout("Run 'php yii conveyor/generate-connection-variants' first\n");
            return 1;
        }

        $totalRotated = 0;

        foreach ($orientations as $folder => $angle) {
            $this->stdout("Processing {$folder} (rotate {$angle}°)...\n");

            $variantPath = $this->entityDir . '/' . $folder;
            if (!is_dir($variantPath)) {
                mkdir($variantPath, 0755, true);
                $this->stdout("  Created directory: {$variantPath}\n");
            }

            // После исправления углов поворота реверс кадров не нужен
            // Поворот спрайта автоматически дает правильное направление анимации

            // Для каждого варианта (0-15)
            for ($variant = 0; $variant < self::CONNECTION_VARIANTS; $variant++) {
                // Поворачиваем номер варианта в соответствии с ориентацией
                $rotatedVariant = $this->rotateVariantBits($variant, $folder);

                $srcVariantNum = str_pad($variant, 2, '0', STR_PAD_LEFT);
                $destVariantNum = str_pad($rotatedVariant, 2, '0', STR_PAD_LEFT);

                // Для каждого кадра (1-8)
                for ($frame = 1; $frame <= self::ANIMATION_FRAMES; $frame++) {
                    $srcFrameNum = $frame;

                    $srcFrameStr = str_pad($srcFrameNum, 3, '0', STR_PAD_LEFT);
                    $destFrameStr = str_pad($frame, 3, '0', STR_PAD_LEFT);

                    $srcPath = $conveyorPath . '/variant_' . $srcVariantNum . '_' . $srcFrameStr . '.png';
                    $destPath = $variantPath . '/variant_' . $destVariantNum . '_' . $destFrameStr . '.png';

                    if (!file_exists($srcPath)) {
                        $this->stdout("  Warning: {$srcPath} not found, skipping\n", Console::FG_YELLOW);
                        continue;
                    }

                    // Загружаем, поворачиваем, сохраняем
                    $src = imagecreatefrompng($srcPath);
                    $transparent = imagecolorallocatealpha($src, 0, 0, 0, 127);
                    $rotated = imagerotate($src, -$angle, $transparent);
                    imagealphablending($rotated, false);
                    imagesavealpha($rotated, true);

                    imagepng($rotated, $destPath, 9);

                    imagedestroy($src);
                    imagedestroy($rotated);

                    $totalRotated++;
                }
            }

            $suffix = $reverseFrames ? " (reversed)" : "";
            $this->stdout("  ✓ Rotated " . (self::CONNECTION_VARIANTS * self::ANIMATION_FRAMES) . " variants{$suffix}\n", Console::FG_GREEN);
        }

        $this->stdout("\n✓ Total variants rotated: {$totalRotated}\n", Console::FG_GREEN);

        return 0;
    }

    /**
     * Поворачивает биты варианта в соответствии с ориентацией
     * Когда спрайт поворачивается, соединения также поворачиваются
     *
     * @param int $variant Исходный variant (0-15)
     * @param string $orientation Целевая ориентация
     * @return int Повернутый variant
     */
    private function rotateVariantBits($variant, $orientation)
    {
        // Bit mask: [DOWN][UP][RIGHT][LEFT] = [3][2][1][0]

        if ($orientation === 'conveyor_up') {
            // Поворот на 90° против часовой (RIGHT -> UP)
            // LEFT->DOWN, RIGHT->UP, UP->LEFT, DOWN->RIGHT
            return (($variant & 0x1) << 3) |  // bit0 -> bit3 (LEFT -> DOWN)
                   (($variant & 0x2) << 1) |  // bit1 -> bit2 (RIGHT -> UP)
                   (($variant & 0x4) >> 2) |  // bit2 -> bit0 (UP -> LEFT)
                   (($variant & 0x8) >> 2);   // bit3 -> bit1 (DOWN -> RIGHT)
        }

        if ($orientation === 'conveyor_down') {
            // Поворот на 270° против часовой (RIGHT -> DOWN)
            // LEFT->UP, RIGHT->DOWN, UP->RIGHT, DOWN->LEFT
            return (($variant & 0x1) << 2) |  // bit0 -> bit2 (LEFT -> UP)
                   (($variant & 0x2) << 2) |  // bit1 -> bit3 (RIGHT -> DOWN)
                   (($variant & 0x4) >> 1) |  // bit2 -> bit1 (UP -> RIGHT)
                   (($variant & 0x8) >> 3);   // bit3 -> bit0 (DOWN -> LEFT)
        }

        if ($orientation === 'conveyor_left') {
            // Поворот на 180°
            // LEFT<->RIGHT, UP<->DOWN
            return (($variant & 0x1) << 1) |  // bit0 -> bit1 (LEFT -> RIGHT)
                   (($variant & 0x2) >> 1) |  // bit1 -> bit0 (RIGHT -> LEFT)
                   (($variant & 0x4) << 1) |  // bit2 -> bit3 (UP -> DOWN)
                   (($variant & 0x8) >> 1);   // bit3 -> bit2 (DOWN -> UP)
        }

        // conveyor (RIGHT) - без изменений
        return $variant;
    }

    /**
     * Добавляет отладочный текст на спрайты конвейеров (ориентация + вариант)
     * Usage: php yii conveyor/add-debug-text
     */
    public function actionAddDebugText()
    {
        $this->stdout("=== Add Debug Text to Conveyor Sprites ===\n\n", Console::FG_CYAN);

        $orientations = [
            'conveyor' => 'R',       // Right
            'conveyor_up' => 'U',    // Up
            'conveyor_down' => 'D',  // Down
            'conveyor_left' => 'L'   // Left
        ];

        $totalProcessed = 0;

        foreach ($orientations as $folder => $letter) {
            $this->stdout("Processing {$folder} ({$letter})...\n");

            $variantPath = $this->entityDir . '/' . $folder;
            if (!is_dir($variantPath)) {
                $this->stdout("  Warning: {$variantPath} not found, skipping\n", Console::FG_YELLOW);
                continue;
            }

            // Для каждого варианта (0-15)
            for ($variant = 0; $variant < self::CONNECTION_VARIANTS; $variant++) {
                $variantNum = str_pad($variant, 2, '0', STR_PAD_LEFT);

                // Для каждого кадра (1-8)
                for ($frame = 1; $frame <= self::ANIMATION_FRAMES; $frame++) {
                    $frameNum = str_pad($frame, 3, '0', STR_PAD_LEFT);
                    $filePath = $variantPath . '/variant_' . $variantNum . '_' . $frameNum . '.png';

                    if (!file_exists($filePath)) {
                        continue;
                    }

                    // Загружаем изображение
                    $img = imagecreatefrompng($filePath);
                    imagealphablending($img, true);
                    imagesavealpha($img, true);

                    // Рисуем текст
                    $text = "{$letter}{$variant}";
                    $this->drawDebugText($img, $text);

                    // Сохраняем
                    imagepng($img, $filePath, 9);
                    imagedestroy($img);

                    $totalProcessed++;
                }
            }

            $this->stdout("  ✓ Processed {$folder}\n", Console::FG_GREEN);
        }

        $this->stdout("\n✓ Total sprites processed: {$totalProcessed}\n", Console::FG_GREEN);
        $this->stdout("Run 'php yii conveyor/generate-atlases' to update atlases\n");

        return 0;
    }

    /**
     * Рисует отладочный текст на изображении
     * @param resource $img
     * @param string $text
     */
    private function drawDebugText($img, $text)
    {
        $width = imagesx($img);
        $height = imagesy($img);

        // Цвет текста - яркий желтый с черным контуром для читабельности
        $yellow = imagecolorallocate($img, 255, 255, 0);
        $black = imagecolorallocate($img, 0, 0, 0);

        // Позиция - в центре спрайта
        $fontSize = 5; // Встроенный шрифт GD (1-5)
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);

        $x = intval(($width - $textWidth) / 2);
        $y = intval(($height - $textHeight) / 2);

        // Рисуем контур (черный) для лучшей видимости
        imagestring($img, $fontSize, $x - 1, $y - 1, $text, $black);
        imagestring($img, $fontSize, $x + 1, $y - 1, $text, $black);
        imagestring($img, $fontSize, $x - 1, $y + 1, $text, $black);
        imagestring($img, $fontSize, $x + 1, $y + 1, $text, $black);

        // Рисуем основной текст (желтый)
        imagestring($img, $fontSize, $x, $y, $text, $yellow);
    }

    /**
     * Удаляет отладочный текст путем перегенерации вариантов
     * Usage: php yii conveyor/remove-debug-text
     */
    public function actionRemoveDebugText()
    {
        $this->stdout("=== Remove Debug Text ===\n\n", Console::FG_CYAN);
        $this->stdout("To remove debug text, regenerate variants:\n");
        $this->stdout("  php yii conveyor/rotate-connection-variants\n");
        $this->stdout("  php yii conveyor/generate-atlases\n");

        return 0;
    }

    /**
     * Generates texture atlases for conveyors
     * Creates 5 atlases per orientation (normal, damaged, blueprint, selected)
     * Usage: php yii conveyor/generate-atlases
     */
    public function actionGenerateAtlases()
    {
        $this->stdout("=== Generate Texture Atlases (512×192px) ===\n\n", Console::FG_CYAN);

        $orientations = ['conveyor', 'conveyor_up', 'conveyor_down', 'conveyor_left'];

        $atlasGenerator = new \helpers\ConveyorAtlasGenerator($this->basePath);

        $totalAtlases = 0;

        foreach ($orientations as $orientation) {
            $this->stdout("Processing {$orientation}...\n");

            $count = $atlasGenerator->generateAtlasesForOrientation($orientation);
            $totalAtlases += $count;

            $this->stdout("  ✓ Generated {$count} atlases for {$orientation}\n\n", Console::FG_GREEN);
        }

        $this->stdout("✓ Total atlases generated: {$totalAtlases}\n", Console::FG_GREEN);
        $this->stdout("\nAtlas structure:\n");
        $atlasWidth = self::TILE_WIDTH * self::CONNECTION_VARIANTS;
        $atlasHeight = self::TILE_HEIGHT * self::ANIMATION_FRAMES;
        $this->stdout("  Size: {$atlasWidth}×{$atlasHeight}px (16 variants × 8 frames)\n");
        $this->stdout("  Tile: " . self::TILE_WIDTH . "×" . self::TILE_HEIGHT . "px\n");
        $this->stdout("  States: normal, damaged, blueprint, normal_selected, damaged_selected\n");

        return 0;
    }
}
