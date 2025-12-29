<?php

namespace generators\base;

use Yii;

/**
 * Обертка над ComfyUI API для генерации изображений через FLUX.1 Dev
 */
class FluxAiGenerator
{
    private $comfyUrl = 'http://localhost:8188';
    private $workflow = null;
    private $timeout = 600; // 10 минут

    public function __construct($comfyUrl = null)
    {
        if ($comfyUrl) {
            $this->comfyUrl = $comfyUrl;
        }
    }

    /**
     * Проверяет, запущен ли ComfyUI
     * @return bool
     */
    public function checkRunning()
    {
        try {
            $ch = curl_init("{$this->comfyUrl}/system_stats");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Генерирует изображение через ComfyUI
     * @param string $positivePrompt
     * @param string $negativePrompt
     * @param int $width
     * @param int $height
     * @param int|null $seed Если null, используется случайный
     * @return string|false Base64-encoded PNG или false при ошибке
     */
    public function generate($positivePrompt, $negativePrompt, $width, $height, $seed = null)
    {
        // Загружаем workflow если еще не загружен
        if ($this->workflow === null) {
            $this->workflow = $this->loadWorkflow();
            if ($this->workflow === false) {
                return false;
            }
        }

        // Устанавливаем параметры в workflow
        $workflow = $this->workflow;

        // Node 2: Positive prompt (CLIP Text Encode)
        $workflow['2']['inputs']['text'] = $positivePrompt;

        // Node 3: Negative prompt (CLIP Text Encode)
        $workflow['3']['inputs']['text'] = $negativePrompt;

        // Node 5: Empty Latent Image (размеры)
        $workflow['5']['inputs']['width'] = $width;
        $workflow['5']['inputs']['height'] = $height;

        // Node 6: KSampler (seed, steps, cfg)
        $workflow['6']['inputs']['seed'] = $seed ?: rand(0, 2147483647);
        $workflow['6']['inputs']['steps'] = 30;  // Оптимальное качество для FLUX
        $workflow['6']['inputs']['cfg'] = 1.5;   // Оптимальный CFG для FLUX (1-3)

        // Отправляем workflow в ComfyUI
        $promptId = $this->submitWorkflow($workflow);
        if ($promptId === false) {
            return false;
        }

        // Ждем завершения
        $result = $this->waitForCompletion($promptId);
        if ($result === false) {
            return false;
        }

        // Скачиваем изображение
        $imageData = $this->downloadImage($result);
        return $imageData;
    }

    /**
     * Загружает workflow из файла
     * @return array|false
     */
    private function loadWorkflow()
    {
        $workflowPath = Yii::getAlias('@app/../workflow_flux_api.json');
        if (!file_exists($workflowPath)) {
            echo "Error: workflow_flux_api.json not found at {$workflowPath}\n";
            return false;
        }

        $workflowJson = file_get_contents($workflowPath);
        $workflow = json_decode($workflowJson, true);

        if ($workflow === null) {
            echo "Error: Failed to parse workflow_flux_api.json\n";
            return false;
        }

        return $workflow;
    }

    /**
     * Отправляет workflow в ComfyUI
     * @param array $workflow
     * @return string|false Prompt ID или false при ошибке
     */
    private function submitWorkflow($workflow)
    {
        $data = json_encode(['prompt' => $workflow]);

        $ch = curl_init("{$this->comfyUrl}/prompt");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo "Error: Failed to submit workflow to ComfyUI (HTTP {$httpCode})\n";
            return false;
        }

        $result = json_decode($response, true);
        if (!isset($result['prompt_id'])) {
            echo "Error: No prompt_id in ComfyUI response\n";
            return false;
        }

        return $result['prompt_id'];
    }

    /**
     * Ждет завершения генерации
     * @param string $promptId
     * @return array|false Информация о результате или false при ошибке
     */
    private function waitForCompletion($promptId)
    {
        $startTime = time();

        while (true) {
            if ((time() - $startTime) > $this->timeout) {
                echo "Error: Timeout waiting for ComfyUI to complete\n";
                return false;
            }

            $ch = curl_init("{$this->comfyUrl}/history/{$promptId}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $history = json_decode($response, true);

            if (isset($history[$promptId])) {
                $status = $history[$promptId];

                if (isset($status['status']['completed']) && $status['status']['completed'] === true) {
                    // Генерация завершена
                    if (isset($status['outputs'])) {
                        return $status['outputs'];
                    } else {
                        echo "Error: No outputs in completed prompt\n";
                        return false;
                    }
                }

                if (isset($status['status']['status_str']) && $status['status']['status_str'] === 'error') {
                    echo "Error: ComfyUI reported an error\n";
                    return false;
                }
            }

            // Ждем 2 секунды перед следующей проверкой
            sleep(2);
        }
    }

    /**
     * Скачивает сгенерированное изображение
     * @param array $outputs
     * @return string|false Base64-encoded PNG или false при ошибке
     */
    private function downloadImage($outputs)
    {
        // Ищем SaveImage node (обычно node 9)
        foreach ($outputs as $nodeId => $output) {
            if (isset($output['images']) && is_array($output['images'])) {
                $image = $output['images'][0];
                $filename = $image['filename'];
                $subfolder = isset($image['subfolder']) ? $image['subfolder'] : '';

                // Формируем URL для скачивания
                $url = "{$this->comfyUrl}/view?filename=" . urlencode($filename);
                if ($subfolder) {
                    $url .= "&subfolder=" . urlencode($subfolder);
                }

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $imageData = curl_exec($ch);
                curl_close($ch);

                if ($imageData === false) {
                    echo "Error: Failed to download image from ComfyUI\n";
                    return false;
                }

                return base64_encode($imageData);
            }
        }

        echo "Error: No images found in ComfyUI outputs\n";
        return false;
    }
}
