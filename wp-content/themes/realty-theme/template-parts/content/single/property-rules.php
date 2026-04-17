<?php
/**
 * Шаблон rules
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
$all_chars = realty_get_property_characteristics($property_id);

$text_chars = [];
$prohibited_chars = [];
$group_name = '';
foreach ($all_chars as $char) {
    $style = $char['display_style'] ?? 'standard';
    if ($style === 'text') {
        $text_chars[] = $char;
        $group_name = $char['group_name'] ?? '';
    } elseif ($style === 'prohibited') {
        $prohibited_chars[] = $char;
    }
}

?>

<div class="section-property property-rules">
    <?php if (!empty($text_chars)): ?>
        <h3 class="title"><?php echo $group_name?></h3>
        <div class="characteristics-grid__com">
            <?php foreach ($text_chars as $char): ?>
            <div class="characteristic-item">
                <div class="label-text"><?php echo esc_html($char['label']); ?></div>
                <div class="desc"><?php echo esc_html($char['title']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($prohibited_chars)): ?>
        <div class="characteristics-grid__com">
            <?php foreach ($prohibited_chars as $char): ?>
            <div class="characteristic-item">
                <?php echo realty_render_characteristic_icon($char, 'diagonal-strike'); ?>
                <span class="label"><?php echo esc_html($char['title']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
