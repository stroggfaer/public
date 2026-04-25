<?php
/**
 * Шаблон карточки города
 *
 * @param object $term WP_Term объект локации
 */
if (!defined('ABSPATH')) {
    exit;
}

$term = $args['term'] ?? null;
if (!$term || is_wp_error($term)) {
    return;
}

$term_link = get_term_link($term);
if (is_wp_error($term_link)) {
    return;
}

$thumbnail = '';
if (function_exists('pods')) {
    $pod = pods('location', $term->term_id);
    if ($pod) {
        $thumbnail_field = $pod->field('thumbnail');
        if (!empty($thumbnail_field)) {
            $thumbnail = is_array($thumbnail_field) ? ($thumbnail_field['guid'] ?? '') : $thumbnail_field;
        }
    }
}
?>
<article class="location-card">
    <a class="property-thumbnail" href="<?php echo esc_url($term_link); ?>">
        <img src="<?php echo esc_url($thumbnail ?: get_template_directory_uri() . '/assets/images/no_city.png'); ?>" alt="<?php echo esc_attr($term->name); ?>" loading="lazy">
        <div class="entry-header">
            <h3 class="entry-title"><?php echo esc_html($term->name); ?></h3>
            <?php if (isset($term->count)) : ?>
                <div class="variations"> <?php echo esc_html(sprintf(_n('%d объект', '%d объектов', $term->count, 'realty-theme'), $term->count)); ?></div>
            <?php endif; ?>
        </div>
    </a>
</article>
