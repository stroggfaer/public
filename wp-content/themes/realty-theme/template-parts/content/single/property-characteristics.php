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

$property_id = $pod->id();
$characteristics = realty_get_property_characteristics($property_id);

if (empty($characteristics)) {
    return;
}

$grouped = [];
foreach ($characteristics as $char) {
    $gid = $char['group_id'];
    if (!isset($grouped[$gid])) {
        $grouped[$gid] = [
            'name' => $char['group_name'],
            'style' => $char['display_style'],
            'items' => []
        ];
    }
    $grouped[$gid]['items'][] = $char;
}

$limit = 8;
$all_count = count($characteristics);
$has_more = $all_count > $limit;
?>
<div class="section-property property-key-characteristics">
    <?php foreach ($grouped as $group): ?>
    <h3 class="title"><?php echo esc_html($group['name']); ?></h3>
    <div class="characteristics-grid__com">
        <?php foreach ($group['items'] as $index => $char): ?>
        <?php if ($index >= $limit) break; ?>
        <div class="characteristic-item">
            <?php echo realty_render_characteristic_icon($char); ?>
            <span class="label"><?php echo esc_html($char['label'] ?: $char['title']); ?></span>
            <span class="value"><?php echo esc_html($char['value'] ?: $char['hint']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    
    <?php if ($has_more): ?>
    <div class="action">
        <a href="#" class="bold border all" data-property-id="<?php echo esc_attr($property_id); ?>">
            Показать все (<?php echo esc_html($all_count); ?>)
        </a>
    </div>
    <?php endif; ?>
</div>