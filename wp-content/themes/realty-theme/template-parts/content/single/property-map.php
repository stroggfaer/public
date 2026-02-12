<?php
/**
 * Шабло map
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
<div class="section-property property-map">
    <h3 class="title">Местоположение</h3>
    <?php
    // Получаем координаты для отображения карты
    // Используем Pods для получения значений полей
    $pod = pods('property', get_the_ID());
    $latitude = $pod->field('latitude');
    $longitude = $pod->field('longitude');

    if (!empty($latitude) && !empty($longitude)) {
        echo '<div id="property-map" style="width: 100%; height: 400px;" data-latitude="' . esc_attr($latitude) . '" data-longitude="' . esc_attr($longitude) . '"></div>';
    } else {
        echo '<p>Местоположение не указано</p>';
    }
    ?>
</div>