<?php
/**
 * Шаблон features
 *
 * @param object $pod Pods объект недвижимости
 */
if (!defined('ABSPATH')) {
    exit;
}

$pod = $args['pod'] ?? null;
if (!$pod) {
    return;
}
?>
<div class="section-property property-features">
    <div class="feature-item__com">
        <div class="icon-circle">
            <span class="material-symbols-outlined">group</span>
        </div>
        <div class="feature-text">
            <span class="label">GUESTS</span>
            <span class="value">Up to 4</span>
        </div>
    </div>
    <div class="feature-item__com">
        <div class="icon-circle">
            <span class="material-symbols-outlined">bed</span>
        </div>
        <div class="feature-text">
            <span class="label">BEDROOMS</span>
            <span class="value">2 Queen</span>
        </div>
    </div>
    <div class="feature-item__com">
        <div class="icon-circle">
            <span class="material-symbols-outlined">bathtub</span>
        </div>
        <div class="feature-text">
            <span class="label">BATHS</span>
            <span class="value">1.5 Baths</span>
        </div>
    </div>
    <div class="feature-item__com">
        <div class="icon-circle">
            <span class="material-symbols-outlined">wifi</span>
        </div>
        <div class="feature-text">
            <span class="label">FIBER</span>
            <span class="value">500 Mbps</span>
        </div>
    </div>
</div>