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
// options
$options = [
        'type' => 'all',
        'system_temp' => 'system_popular_services',
];
$property_id = $pod->id();

$group_characteristics = realty_get_property_characteristics($property_id, 'standard', $options);

if (empty($group_characteristics)) {
    return;
}

?>
<div class="section-property property-key-characteristics">
    <?php foreach ($group_characteristics as $group): ?>
        <h3 class="title"><?php echo esc_html($group['group_name']); ?></h3>
        <div class="characteristics-grid__com">
            <?php foreach ($group['characteristics'] as $index => $characteristics): ?>
                <div class="characteristic-item">
                    <?php echo realty_render_characteristic_icon($characteristics); ?>
                    <span class="label"><?php echo esc_html($characteristics['title']); ?></span>
                    <span class="value"><?php echo esc_html($characteristics['value']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="action">
            <a href="#" class="bold border all" data-property-id="<?php echo esc_attr($group['group_id']); ?>">
                Показать все (<?php echo esc_html($group['total']); ?>)
            </a>
        </div>
    <?php endforeach; ?>
</div>