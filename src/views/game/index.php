<?php

/* @var $this yii\web\View */

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'ZFactory';

// Register compiled CSS
$this->registerCssFile('/css/game.css');

// Pass only config URL to JavaScript (other config loaded via AJAX)
$this->registerJs("
    window.gameConfig = {
        configUrl: '" . Url::to(['game/config']) . "'
    };
", \yii\web\View::POS_HEAD);

// Register compiled game JS
$this->registerJsFile('/js/game.js', ['position' => \yii\web\View::POS_END]);

?>

<div id="game-container"></div>

<div id="loading">Loading ZFactory...</div>

<div id="debug-info">
    Mode: <span id="debug-mode">Normal</span><br>
    Camera: <span id="debug-camera">0, 0</span><br>
    Tiles: <span id="debug-tiles">0</span><br>
    Entities: <span id="debug-entities">0</span><br>
    FPS: <span id="debug-fps">0</span>
</div>

<div id="controls-hint">
    <span class="hint-row"><kbd>W</kbd><kbd>A</kbd><kbd>S</kbd><kbd>D</kbd> move</span>
    <span class="hint-row"><kbd>Wheel</kbd> zoom</span>
    <span class="hint-row"><kbd>B</kbd> buildings</span>
    <span class="hint-row"><kbd>1</kbd>-<kbd>0</kbd> build</span>
    <span class="hint-row"><kbd>Esc</kbd> cancel</span>
</div>
