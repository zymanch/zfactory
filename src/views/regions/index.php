<?php
/**
 * @var $this \yii\web\View
 */

$this->title = 'Regions Map';

// Register compiled JS and CSS
$this->registerJsFile('/js/regions.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerCssFile('/css/regions.css');
?>

<div id="regions-app">
    <canvas id="regions-map"></canvas>

    <div id="regions-sidebar">
        <h2>Regions</h2>
        <div id="ship-info">
            <div class="ship-stat">
                <span class="label">View Radius:</span>
                <span id="ship-view-radius" class="value">300</span>
            </div>
            <div class="ship-stat">
                <span class="label">Jump Distance:</span>
                <span id="ship-jump-distance" class="value">500</span>
            </div>
        </div>

        <div id="regions-list"></div>
    </div>

    <div id="region-tooltip" class="hidden">
        <h3 id="tooltip-name"></h3>
        <div id="tooltip-distance"></div>
        <div id="tooltip-difficulty"></div>
        <div id="tooltip-resources"></div>
        <div id="tooltip-status"></div>
    </div>
</div>
