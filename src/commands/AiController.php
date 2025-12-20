<?php

namespace commands;

use models\Landing;

class AiController extends \yii\console\Controller
{
    /**
     * Build all landing tiles
     * Usage: php yii ai/build-landing
     */
    public function actionBuildLanding()
    {
        $landings = Landing::find()->all();
        $count = count($landings);

        $this->stdout("Processing {$count} landing types...\n");

        foreach ($landings as $landing) {
            $this->stdout("  Building landing #{$landing->landing_id}: {$landing->name}...\n");
            $this->_buildLanding($landing);
            break;
        }

        $this->stdout("Done!\n");
    }

    /**
     * @param Landing $landing
     */
    private function _buildLanding(Landing $landing)
    {
        $token = \Yii::$app->params['replicate_ai_api_key'];
        $width = \Yii::$app->params['tile_width'];
        $height = \Yii::$app->params['tile_height'];
        $prompt = "{$landing->name} seamless texture, trending on artstation, base color, albedo, 4k, width $width, height $height";

        $data = [
            'version' => 'a42692c54c0f407f803a0a8a9066160976baedb77c91171a01730f9b0d7beeff',
            'input' => [
                'prompt' => $prompt,
            ],
        ];

        $ch = curl_init('https://api.replicate.com/v1/predictions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Prefer: wait',
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);

        $this->stdout("    Prompt: {$prompt}\n");
        $this->stdout("    Waiting for API response...\n");

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->stdout("    HTTP {$httpCode}\n");
        $this->stdout("    Response: {$response}\n");
    }
}
