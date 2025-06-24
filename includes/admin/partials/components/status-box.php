<?php
/**
 * Status Box Component
 */
if(!defined('WPINC'))die;
?>

<div id="sspu-status-box" class="notice" style="display:none;">
    <h3 id="sspu-status-heading"></h3>
    <div id="sspu-progress-bar" style="display:none;">
        <div class="progress-bar">
            <div class="progress-fill" id="sspu-progress-fill"></div>
        </div>
        <div class="progress-text" id="sspu-progress-text">0%</div>
    </div>
    <pre id="sspu-status-log"></pre>
</div>