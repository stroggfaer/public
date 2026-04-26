<?php
/**
 * WP entry: детальная города — /locations/{slug}
 *
 * @package Realty Theme
 */

$term = get_queried_object();
if (!$term || is_wp_error($term)) {
    get_header();
    get_template_part('template-parts/content/content-none');
    get_footer();
    return;
}

// Получаем настройки карты из админки
$map_zoom = get_option('realty_map_zoom', 6);
$map_center_lat = get_option('realty_default_lat', 55.7558);
$map_center_lng = get_option('realty_default_lng', 37.6173);

// Меняем слот в шапке - форма поиска с предзаполненным городом (город нельзя изменить)
// Меняем слот ставка
add_filter('realty_header_slot', function ($content) use ($term) {
    return '<div class="main-search-form-container">'.property_filter_render_home_form(array('location'=> $term) ).'</div>';
});



get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main-map col-full" role="main">
        <?php get_template_part('template-parts/component/error-valid'); ?>
        
        <!-- Баннер города с изображением и описанием -->
        <div class="location-banner">
            <?php
            get_template_part('template-parts/component/breadcrumbs', null, array(
                'term' => $term,
                'taxonomy' => 'location',
                'taxonomy_archive_url' => home_url('/locations/'),
                'taxonomy_archive_label' => 'Города'
            ));
            
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
            <div class="location-banner__content">
                <?php if ($thumbnail) : ?>
                    <div class="location-banner__thumbnail">
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($term->name); ?>">
                    </div>
                <?php endif; ?>
                <div class="location-banner__info">
                    <h1 class="location-banner__title"><?php echo esc_html($term->name); ?></h1>
                    <?php if (!empty($term->description)) : ?>
                        <div class="location-banner__description"><?php echo wp_kses_post(wpautop($term->description)); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Контейнер для фильтров, списка и карты (переиспользуем структуру archive-property) -->
        <div class="property-archive-container location-page">
            <!-- Сайдбар с фильтрами -->
            <aside class="property-filters-sidebar">
                <?php get_template_part('template-parts/content/list/property-filters'); ?>
            </aside>
            
            <!-- Основной контент: только список (без карты) -->
            <div class="property-content-area">
                <div class="property-layout property-layout--no-map">
                    <div class="content-card-list">
                        <?php get_template_part('template-parts/content/list/property-card-list', null, [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'nonce' => wp_create_nonce('property_filter_nonce'),
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
get_footer();
?>
