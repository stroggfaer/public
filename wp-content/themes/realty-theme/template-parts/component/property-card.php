<?php
/**
 * Шаблон карточки недвижимости
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
$property_title = $pod->display('post_title');
$property_content = $pod->display('post_content');
$property_price = $pod->field('price');
$property_address = $pod->field('address');
$property_lat = $pod->field('latitude');
$property_lng = $pod->field('longitude');

// Получаем термины для таксономий
$property_locations = $pod->field('location');
$property_types = $pod->field('property_type');

// Получаем дополнительные данные фильтрации
$max_adults = get_post_meta($property_id, '_max_adults', true);
$max_children = get_post_meta($property_id, '_max_children', true);

// Получаем изображение
$thumbnail_url = get_the_post_thumbnail_url($property_id, 'medium');
if (!$thumbnail_url) {
    $thumbnail_url = get_template_directory_uri() . '/assets/images/no_image.png';
}
?>
<article class="property-card" data-id="<?php echo esc_attr($property_id); ?>"
    data-lat="<?php echo esc_attr($property_lat); ?>" data-lng="<?php echo esc_attr($property_lng); ?>">
    <div class="property-thumbnail">
        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($property_title); ?>">
    </div>

    <div class="property-content">
        <header class="entry-header">
            <h3 class="entry-title">
                <a href="<?php echo esc_url(get_permalink($property_id)); ?>"><?php echo esc_html($property_title); ?></a>
            </h3>
        </header>

        <div class="property-meta">
            <?php if ($property_price) : ?>
                <span class="property-price">Цена: <?php echo number_format($property_price, 0, '', ' '); ?> ₽/сутки</span>
            <?php endif; ?>

            <?php if ($property_address) : ?>
                <div class="property-address">Адрес: <?php echo esc_html($property_address); ?></div>
            <?php endif; ?>

            <?php if ($max_adults || $max_children) : ?>
                <div class="property-capacity">
                    Вместимость:
                    <?php if ($max_adults) echo esc_html($max_adults) . ' взросл' . ($max_adults == 1 ? 'ый' : 'ых'); ?>
                    <?php if ($max_children) echo ', ' . esc_html($max_children) . ' реб' . ($max_children == 1 ? 'енок' : 'енка'); ?>
                </div>
            <?php endif; ?>

            <?php if ($property_locations) : ?>
                <div class="property-location">Локация:
                    <?php
                    $location_names = array();
                    if (is_array($property_locations)) {
                        foreach ($property_locations as $location) {
                            $location_names[] = $location['name'];
                        }
                    } else {
                        $location_names[] = $property_locations['name'];
                    }
                    echo esc_html(implode(', ', $location_names));
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($property_types) : ?>
                <div class="property-type">Тип:
                    <?php
                    $type_names = array();
                    if (is_array($property_types)) {
                        foreach ($property_types as $type) {
                            $type_names[] = $type['name'];
                        }
                    } else {
                        $type_names[] = $property_types['name'];
                    }
                    echo esc_html(implode(', ', $type_names));
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="entry-summary">
            <?php echo wp_kses_post(wp_trim_words($property_content, 20)); ?>
        </div>

        <footer class="entry-footer">
            <a href="<?php echo esc_url(get_permalink($property_id)); ?>" class="more-link">Подробнее</a>
        </footer>
    </div>
</article>