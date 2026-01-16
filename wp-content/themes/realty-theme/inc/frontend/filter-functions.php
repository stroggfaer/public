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
 * Шорткод для отображения формы фильтрации
 * Использование: [property_filter]
 */
function property_filter_shortcode($atts) {
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
    <div class="property-filter-wrapper">
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
        
        <div id="filter-results" class="filter-results">
            <div class="loading-indicator" style="display: none;">
                <p>Загрузка результатов...</p>
            </div>
            <div class="results-content">
                <?php
                // Показываем все объекты недвижимости по умолчанию
                $default_params = array(
                    'where' => 't.post_status = "publish"',
                    'limit' => 12,
                    'orderby' => 't.post_date DESC'
                );
                    
                $default_pod = pods('property', $default_params);
                    
                if ($default_pod->total() > 0) {
                    echo '<div class="properties-grid">';
                    while ($default_pod->fetch()) {
                        $property_id = $default_pod->id();
                        $property_title = $default_pod->display('post_title');
                        $property_content = $default_pod->display('post_content');
                        $property_price = $default_pod->field('price');
                        $property_address = $default_pod->field('address');
                        $property_lat = $default_pod->field('latitude');
                        $property_lng = $default_pod->field('longitude');
                            
                        // Получаем термины для таксономий
                        $property_locations = $default_pod->field('location');
                        $property_types = $default_pod->field('property_type');
                            
                        // Получаем дополнительные данные фильтрации
                        $max_adults = get_post_meta($property_id, '_max_adults', true);
                        $max_children = get_post_meta($property_id, '_max_children', true);
                            
                        // Получаем изображение
                        $thumbnail_url = get_the_post_thumbnail_url($property_id, 'medium');
                        if (!$thumbnail_url) {
                            $thumbnail_url = get_template_directory_uri() . '/assets/images/no_image.png';
                        }
                        ?>
                        <article class="property-card" data-id="<?php echo $property_id; ?>" 
                            data-lat="<?php echo $property_lat; ?>" data-lng="<?php echo $property_lng; ?>">
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
                                            <?php if ($max_adults) echo $max_adults . ' взросл' . ($max_adults == 1 ? 'ый' : 'ых'); ?>
                                            <?php if ($max_children) echo ', ' . $max_children . ' реб' . ($max_children == 1 ? 'енок' : 'енка'); ?>
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
                        <?php
                    }
                    echo '</div>'; // .properties-grid
                        
                    // Добавляем информацию о количестве результатов
                    echo '<div class="results-info">';
                    echo '<p>Всего объектов: ' . $default_pod->total() . '</p>';
                    echo '</div>';
                        
                } else {
                    echo '<div class="no-results">';
                    echo '<p>Недвижимость не найдена.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <style>
    .property-filter-wrapper {
        margin: 20px 0;
    }
    
    .property-filter-form {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .filter-group {
        flex: 1;
        min-width: 150px;
    }
    
    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    
    .filter-group input,
    .filter-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .filter-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .filter-submit,
    .filter-reset {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .filter-submit {
        background: #007cba;
        color: white;
    }
    
    .filter-reset {
        background: #6c757d;
        color: white;
    }
    
    .loading-indicator {
        text-align: center;
        padding: 20px;
    }
    
    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
        }
        
        .filter-group {
            min-width: 100%;
        }
    }
    </style>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#property-filter-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData + '&action=filter_properties_custom&nonce=<?php echo wp_create_nonce("property_filter_nonce"); ?>',
                beforeSend: function() {
                    $('.results-content').html('<div class="loading-indicator" style="display: block;"><p>Загрузка результатов...</p></div>');
                },
                success: function(data) {
                    if (data.success) {
                        $('.results-content').html(data.data.properties_html);
                    } else {
                        $('.results-content').html('<p>Ошибка при загрузке результатов: ' + data.data + '</p>');
                    }
                },
                error: function() {
                    $('.results-content').html('<p>Произошла ошибка.</p>');
                }
            });
        });

        $('.filter-reset').on('click', function(e) {
            e.preventDefault();
            $('#property-filter-form')[0].reset();
            $('#property-filter-form').trigger('submit');
        });
    });
    </script>
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

    global $wpdb;

    /**
     * ✅ ФИКС ДВОЙНОГО URL ENCODE
     */
    $location_raw = isset($_POST['location']) ? $_POST['location'] : '';
    $location_raw = wp_unslash($location_raw);

    // декодируем до нормального UTF-8
    while (strpos($location_raw, '%') !== false) {
        $decoded = rawurldecode($location_raw);
        if ($decoded === $location_raw) {
            break;
        }
        $location_raw = $decoded;
    }

    $location = sanitize_text_field($location_raw);

    $price_min = isset($_POST['price_min']) ? (int) $_POST['price_min'] : 0;
    $price_max = isset($_POST['price_max']) ? (int) $_POST['price_max'] : 0;
    $adults    = isset($_POST['adults']) ? (int) $_POST['adults'] : 0;
    $children  = isset($_POST['children']) ? (int) $_POST['children'] : 0;

    $where = [];
    $where[] = "t.post_status = 'publish'";

    /**
     * ✅ ФИЛЬТР ПО ГОРОДУ (taxonomy location)
     */
    if ($location !== '') {
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

    /**
     * ✅ ФИЛЬТР ПО ЦЕНЕ
     */
    if ($price_min > 0) {
        $where[] = "price >= {$price_min}";
    }

    if ($price_max > 0) {
        $where[] = "price <= {$price_max}";
    }

    /**
     * ✅ ФИЛЬТР ПО ГОСТЯМ
     */
    if ($adults > 0) {
        $where[] = "
            CAST(
                (SELECT meta_value FROM {$wpdb->postmeta}
                 WHERE post_id = t.ID
                 AND meta_key = '_max_adults'
                 LIMIT 1) AS UNSIGNED
            ) >= {$adults}
        ";
    }

    if ($children > 0) {
        $where[] = "
            CAST(
                (SELECT meta_value FROM {$wpdb->postmeta}
                 WHERE post_id = t.ID
                 AND meta_key = '_max_children'
                 LIMIT 1) AS UNSIGNED
            ) >= {$children}
        ";
    }

    $params = [
        'where'   => implode(' AND ', $where),
        'limit'   => 12,
        'orderby' => 't.post_date DESC'
    ];

    $pod = pods('property', $params);

    ob_start();

    if ($pod->total() > 0) {
        echo '<div class="properties-grid">';
        while ($pod->fetch()) {
            $id = $pod->id();
            ?>
            <article class="property-card">
                <a href="<?php echo esc_url(get_permalink($id)); ?>">
                    <?php echo esc_html($pod->display('post_title')); ?>
                </a>
            </article>
            <?php
        }
        echo '</div>';
    } else {
        echo '<p>По вашему запросу ничего не найдено</p>';
    }

    wp_send_json_success([
        'properties_html' => ob_get_clean(),
        'count'           => $pod->total()
    ]);
}


// Регистрируем новый AJAX обработчик
add_action('wp_ajax_filter_properties_custom', 'filter_properties_custom_ajax_handler');
add_action('wp_ajax_nopriv_filter_properties_custom', 'filter_properties_custom_ajax_handler');

