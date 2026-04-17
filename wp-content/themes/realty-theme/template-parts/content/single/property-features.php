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

$property_id = $pod->id();

// options
$options = [
        'type' => 'one',
        'group_key' => 'kvartira',
        'limit' => 25
];



$group = realty_get_property_characteristics($property_id, 'circle', $options);

if (empty($group) || empty($group['characteristics'])) {
    return;
}
?>

<div class="property-features-title">
    <?php echo esc_html($group['group_name']); ?>
</div>

<div class="section-property property-features">
    <?php foreach ($group['characteristics'] as $feature): ?>
        <div class="feature-item__com">
            <div class="icon-circle">
                <?php echo realty_render_characteristic_icon($feature); ?>
            </div>
            <div class="feature-text">
                <span class="label">
                    <?php echo esc_html($feature['label'] ?: $feature['title']); ?>
                </span>
                <span class="value">
                    <?php echo esc_html($feature['value']); ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
</div>