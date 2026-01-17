<?php
/**
 * Функции фильтрации недвижимости
 * Используются для AJAX-обработки фильтров и сортировки
 *
 * Поддерживаемые фильтры (MVP):
 * - Город (таксономия location)
 * - Дата заезда (meta-поле)
 * - Дата выезда (meta-поле)
 * - Количество гостей (взрослые / дети)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Рендер формы фильтрации
 */
function property_filter_render_form() {
    // Получаем настройки активных фильтров
    $active_filters = get_option('property_active_filters', array(
        'location', 'checkin_date', 'checkout_date', 'adults', 'children'
    ));

    $price_range = get_option('property_price_range', array(
        'min' => 0,
        'max' => 1000000,
        'step' => 1000
    ));

    ob_start();
    ?>
    <form id="property-filter-form" class="property-filter-form">
        <?php wp_nonce_field('property_filter_nonce', 'property_filter_nonce'); ?>

        <div class="filter-row">
            <?php if (in_array('location', $active_filters)): ?>
            <div class="filter-group">
                <label for="filter-location">Город:</label>
                <select name="location" id="filter-location">
                    <option value="">Все города</option>
                    <?php
                    $locations = get_terms(array(
                        'taxonomy' => 'location',
                        'hide_empty' => false,
                    ));
                    foreach ($locations as $location) {
                        echo '<option value="' . esc_attr($location->slug) . '">' . esc_html($location->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="filter-group">
                <label for="filter-price-min">Цена от:</label>
                <input type="number" name="price_min" id="filter-price-min"
                       min="<?php echo esc_attr($price_range['min']); ?>"
                       max="<?php echo esc_attr($price_range['max']); ?>"
                       step="<?php echo esc_attr($price_range['step']); ?>"
                       placeholder="<?php echo esc_attr($price_range['min']); ?>">
            </div>

            <div class="filter-group">
                <label for="filter-price-max">до:</label>
                <input type="number" name="price_max" id="filter-price-max"
                       min="<?php echo esc_attr($price_range['min']); ?>"
                       max="<?php echo esc_attr($price_range['max']); ?>"
                       step="<?php echo esc_attr($price_range['step']); ?>"
                       placeholder="<?php echo esc_attr($price_range['max']); ?>">
            </div>
        </div>

        <div class="filter-row">
            <?php if (in_array('checkin_date', $active_filters)): ?>
            <div class="filter-group">
                <label for="filter-checkin">Дата заезда:</label>
                <input type="date" name="checkin_date" id="filter-checkin">
            </div>
            <?php endif; ?>

            <?php if (in_array('checkout_date', $active_filters)): ?>
            <div class="filter-group">
                <label for="filter-checkout">Дата выезда:</label>
                <input type="date" name="checkout_date" id="filter-checkout">
            </div>
            <?php endif; ?>

            <?php if (in_array('adults', $active_filters)): ?>
            <div class="filter-group">
                <label for="filter-adults">Взрослые:</label>
                <select name="adults" id="filter-adults">
                    <option value="">Любое количество</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (in_array('children', $active_filters)): ?>
            <div class="filter-group">
                <label for="filter-children">Дети:</label>
                <select name="children" id="filter-children">
                    <option value="">Любое количество</option>
                    <option value="0">Без детей</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <div class="filter-actions">
            <button type="submit" class="filter-submit">Применить фильтры</button>
            <button type="button" class="filter-reset">Сбросить</button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Рендер списка недвижимости
 */
function property_filter_render_list($params = array()) {
    $default_params = array(
        'where' => 't.post_status = "publish"',
        'limit' => 12,
        'orderby' => 't.post_date DESC'
    );

    $params = array_merge($default_params, $params);

    $pod = pods('property', $params);

    ob_start();

    if ($pod->total() > 0) {
        echo '<div class="properties-grid">';
        while ($pod->fetch()) {
            get_template_part('template-parts/component/property-card', null, array('pod' => $pod));
        }
        echo '</div>';

        echo '<div class="results-info">';
        echo '<p>Всего объектов: ' . $pod->total() . '</p>';
        echo '</div>';
    } else {
        echo '<div class="no-results">';
        echo '<p>Недвижимость не найдена.</p>';
        echo '</div>';
    }

    return ob_get_clean();
}

/**
 * Подготовка WHERE условия для фильтрации
 */
function property_filter_build_where($filters = array()) {
    global $wpdb;

    $where = array();
    $where[] = "t.post_status = 'publish'";

    // Фильтр по городу
    if (!empty($filters['location'])) {
        $location = sanitize_text_field($filters['location']);
        $location_sql = esc_sql($location);

        $where[] = "
            EXISTS (
                SELECT 1
                FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                INNER JOIN {$wpdb->terms} tm ON tm.term_id = tt.term_id
                WHERE tr.object_id = t.ID
                AND tt.taxonomy = 'location'
                AND (
                    tm.name = '{$location_sql}'
                    OR tm.slug = '{$location_sql}'
                )
            )
        ";
    }

    // Фильтр по цене
    if (!empty($filters['price_min'])) {
        $price_min = (int) $filters['price_min'];
        $where[] = "
            CAST(
                (SELECT meta_value FROM {$wpdb->postmeta}
                 WHERE post_id = t.ID
                 AND meta_key = 'price'
                 LIMIT 1) AS UNSIGNED
            ) >= {$price_min}
        ";
    }

    if (!empty($filters['price_max'])) {
        $price_max = (int) $filters['price_max'];
        $where[] = "
            CAST(
                (SELECT meta_value FROM {$wpdb->postmeta}
                 WHERE post_id = t.ID
                 AND meta_key = 'price'
                 LIMIT 1) AS UNSIGNED
            ) <= {$price_max}
        ";
    }

    // Фильтр по гостям
    if (!empty($filters['adults'])) {
        $adults = (int) $filters['adults'];
        $where[] = "
            CAST(
                (SELECT meta_value FROM {$wpdb->postmeta}
                 WHERE post_id = t.ID
                 AND meta_key = '_max_adults'
                 LIMIT 1) AS UNSIGNED
            ) >= {$adults}
        ";
    }

    if (!empty($filters['children'])) {
        $children = (int) $filters['children'];
        $where[] = "
            CAST(
                (SELECT meta_value FROM {$wpdb->postmeta}
                 WHERE post_id = t.ID
                 AND meta_key = '_max_children'
                 LIMIT 1) AS UNSIGNED
            ) >= {$children}
        ";
    }

    return implode(' AND ', $where);
}

/**
 * Шорткод для отображения формы фильтрации
 * Использование: [property_filter]
 */
function property_filter_shortcode($atts) {
    ob_start();
    ?>
    <div class="property-filter-wrapper">
        <?php echo property_filter_render_form(); ?>

        <div id="filter-results" class="filter-results">
            <div class="loading-indicator" style="display: none;">
                <p>Загрузка результатов...</p>
            </div>
            <div class="results-content">
                <?php echo property_filter_render_list(); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('property_filter', 'property_filter_shortcode');

/**
 * AJAX обработчик для кастомной фильтрации через шорткод
 */
function filter_properties_custom_ajax_handler() {
    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'property_filter_nonce')
    ) {
        wp_send_json_error('Security check failed');
    }

    // Нормализация входных данных
    $location_raw = isset($_POST['location']) ? wp_unslash($_POST['location']) : '';

    // Фикс двойного URL encode
    while (strpos($location_raw, '%') !== false) {
        $decoded = rawurldecode($location_raw);
        if ($decoded === $location_raw) {
            break;
        }
        $location_raw = $decoded;
    }

    $filters = array(
        'location'  => sanitize_text_field($location_raw),
        'price_min' => isset($_POST['price_min']) ? (int) $_POST['price_min'] : 0,
        'price_max' => isset($_POST['price_max']) ? (int) $_POST['price_max'] : 0,
        'adults'    => isset($_POST['adults']) ? (int) $_POST['adults'] : 0,
        'children'  => isset($_POST['children']) ? (int) $_POST['children'] : 0,
    );

    $where = property_filter_build_where($filters);

    $params = array(
        'where'   => $where,
        'limit'   => 12,
        'orderby' => 't.post_date DESC'
    );

    $pod = pods('property', $params);

    ob_start();

    if ($pod->total() > 0) {
        echo '<div class="properties-grid">';
        while ($pod->fetch()) {
            get_template_part('template-parts/component/property-card', null, array('pod' => $pod));
        }
        echo '</div>';

        echo '<div class="results-info">';
        echo '<p>Всего объектов: ' . $pod->total() . '</p>';
        echo '</div>';
    } else {
        echo '<div class="no-results">';
        echo '<p>Недвижимость не найдена.</p>';
        echo '</div>';
    }

    wp_send_json_success(array(
        'properties_html' => ob_get_clean(),
        'count'           => $pod->total()
    ));
}


// Регистрируем новый AJAX обработчик
add_action('wp_ajax_filter_properties_custom', 'filter_properties_custom_ajax_handler');
add_action('wp_ajax_nopriv_filter_properties_custom', 'filter_properties_custom_ajax_handler');

