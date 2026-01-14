<?php
/**
 * Функции фильтрации недвижимости
 * Используются для AJAX-обработки фильтров и сортировки
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX обработчик для фильтрации и сортировки недвижимости
 */
function filter_properties_ajax_handler() {
    // Проверяем nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'filter_properties_nonce')) {
        wp_die('Security check failed');
    }

    // Получаем параметры фильтрации
    $price_min = isset($_POST['price_min']) ? intval($_POST['price_min']) : 0;
    $price_max = isset($_POST['price_max']) ? intval($_POST['price_max']) : 0;
    $property_type = isset($_POST['property_type']) ? sanitize_text_field($_POST['property_type']) : '';
    $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
    $sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'date_desc';
    
    // Подготовим параметры для запроса через Pods
    $params = array(
        'where' => 't.post_status = "publish"',
        'limit' => 9, // Количество элементов на странице
    );

    // Добавляем условия фильтрации по цене
    if ($price_min > 0) {
        $params['where'] .= ' AND price >= ' . $price_min;
    }
    if ($price_max > 0) {
        $params['where'] .= ' AND price <= ' . $price_max;
    }

    // Добавляем сортировку
    switch ($sort_order) {
        case 'date_asc':
            $params['orderby'] = 't.post_date ASC';
            break;
        case 'date_desc':
            $params['orderby'] = 't.post_date DESC';
            break;
        case 'price_asc':
            $params['orderby'] = 'CAST(price.meta_value AS DECIMAL) ASC';
            break;
        case 'price_desc':
            $params['orderby'] = 'CAST(price.meta_value AS DECIMAL) DESC';
            break;
        case 'area_asc':
            $params['orderby'] = 'CAST(area.meta_value AS DECIMAL) ASC';
            break;
        case 'area_desc':
            $params['orderby'] = 'CAST(area.meta_value AS DECIMAL) DESC';
            break;
        default:
            $params['orderby'] = 't.post_date DESC';
    }

    // Создаем экземпляр Pods для получения недвижимости
    $property_pod = pods('property', $params);
    
    ob_start();
    
    if ($property_pod->total() > 0) {
        while ($property_pod->fetch()) {
            $property_id = $property_pod->id();
            $property_title = $property_pod->display('post_title');
            $property_content = $property_pod->display('post_content');
            $property_price = $property_pod->field('price');
            $property_address = $property_pod->field('address');
            $property_guest_count = $property_pod->field('guest_count');
            $property_lat = $property_pod->field('latitude');
            $property_lng = $property_pod->field('longitude');
            
            // Получаем термины для таксономий
            $property_locations = $property_pod->field('location');
            $property_types = $property_pod->field('property_type');
            
            // Получаем изображение
            $thumbnail_url = get_the_post_thumbnail_url($property_id, 'medium');
            if (!$thumbnail_url) {
                $thumbnail_url = get_template_directory_uri() . '/assets/images/no_image.png';
            }
            ?>
            <article id="post-<?php echo $property_id; ?>" class="property-card" data-id="<?php echo $property_id; ?>" 
                data-lat="<?php echo $property_lat; ?>" data-lng="<?php echo $property_lng; ?>">
                <div class="property-thumbnail">
                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($property_title); ?>">
                </div>
                
                <div class="property-content">
                    <header class="entry-header">
                        <h2 class="entry-title">
                            <a href="<?php echo esc_url(get_permalink($property_id)); ?>"><?php echo esc_html($property_title); ?></a>
                        </h2>
                    </header>
                    
                    <div class="property-meta">
                        <?php if ($property_price) : ?>
                            <span class="property-price">Цена: <?php echo number_format($property_price, 0, '', ' '); ?> ₽/сутки</span>
                        <?php endif; ?>
                        
                        <?php if ($property_address) : ?>
                            <div class="property-address">Адрес: <?php echo esc_html($property_address); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($property_guest_count) : ?>
                            <div class="property-guests">Гостей: <?php echo intval($property_guest_count); ?></div>
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
    } else {
        echo '<p>Недвижимость не найдена.</p>';
    }
    
    $properties_html = ob_get_clean();
    
    wp_send_json_success(array(
        'properties_html' => $properties_html,
        'count' => $property_pod->total()
    ));
}

add_action('wp_ajax_filter_properties', 'filter_properties_ajax_handler');
add_action('wp_ajax_nopriv_filter_properties', 'filter_properties_ajax_handler');

/**
 * Функция для локализации JavaScript переменных
 */
function localize_property_filter_script() {
    wp_localize_script('naked', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('filter_properties_nonce')
    ));
}

add_action('wp_enqueue_scripts', 'localize_property_filter_script');