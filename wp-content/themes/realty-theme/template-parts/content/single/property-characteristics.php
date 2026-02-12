<?php
/**
 * Шаблон characteristics
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
<div class="section-property property-key-characteristics">
    <h3 class="title">Популярные услуги и удобства</h3>
    <div class="characteristics-grid__com">
        <div class="characteristic-item">
            <span class="material-symbols-outlined">pets</span>
            <span class="label">Pet Friendly</span>
            <span class="value">Allowed</span>
        </div>
        <div class="characteristic-item">
            <span class="material-symbols-outlined">work</span>
            <span class="label">Workstation</span>
            <span class="value">Ergonomic</span>
        </div>
        <div class="characteristic-item">
            <span class="material-symbols-outlined">thermostat</span>
            <span class="label">Heating</span>
            <span class="value">Geothermal</span>
        </div>
        <div class="characteristic-item">
            <span class="material-symbols-outlined">pets</span>
            <span class="label">Pet Friendly</span>
            <span class="value">Allowed</span>
        </div>
        <div class="characteristic-item">
            <span class="material-symbols-outlined">work</span>
            <span class="label">Workstation</span>
            <span class="value">Ergonomic</span>
        </div>
        <div class="characteristic-item">
            <span class="material-symbols-outlined">thermostat</span>
            <span class="label">Heating</span>
            <span class="value">Geothermal</span>
        </div>
        <div class="characteristic-item">
            <span class="material-symbols-outlined">ac_unit</span>
            <span class="label">Cooling</span>
            <span class="value">Air Purifier</span>
        </div>
        <div class="characteristic-item">
            <span class="material-symbols-outlined">kitchen</span>
            <span class="label">Kitchen</span>
            <span class="value">Fully Equipped</span>
        </div>
    </div>
    <div class="action"><a href="#" class="bold border all">Показыть все</a></div>
</div>