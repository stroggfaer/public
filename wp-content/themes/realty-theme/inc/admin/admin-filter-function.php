<?php
/**
 * Административная часть системы фильтрации недвижимости
 * Реализует базовую инфраструктуру для настройки фильтров в админке (MVP)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация страницы настроек фильтрации в админке
 */
function property_filter_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=property',
        'Настройки фильтрации',
        'Фильтрация',
        'manage_options',
        'property-filter-settings',
        'property_filter_settings_page'
    );
}
add_action('admin_menu', 'property_filter_admin_menu');

/**
 * Отображение страницы настроек фильтрации
 */
function property_filter_settings_page() {
    // Сохранение настроек
    if (isset($_POST['save_filter_settings']) && wp_verify_nonce($_POST['filter_settings_nonce'], 'save_filter_settings')) {
        // Сохраняем активные фильтры
        $active_filters = isset($_POST['active_filters']) ? array_map('sanitize_text_field', $_POST['active_filters']) : array();
        update_option('property_active_filters', $active_filters);
        
        // Сохраняем настройки диапазонов
        $price_range = array(
            'min' => isset($_POST['price_min']) ? intval($_POST['price_min']) : 0,
            'max' => isset($_POST['price_max']) ? intval($_POST['price_max']) : 1000000,
            'step' => isset($_POST['price_step']) ? intval($_POST['price_step']) : 1000
        );
        update_option('property_price_range', $price_range);
        
        // Сообщение об успешном сохранении
        echo '<div class="notice notice-success"><p>Настройки фильтрации успешно сохранены!</p></div>';
    }
    
    $active_filters = get_option('property_active_filters', array(
        'location', 'checkin_date', 'checkout_date', 'adults', 'children'
    ));
    
    $price_range = get_option('property_price_range', array(
        'min' => 0,
        'max' => 1000000,
        'step' => 1000
    ));
    
    ?>
    <div class="wrap">
        <h1>Настройки фильтрации недвижимости</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('save_filter_settings', 'filter_settings_nonce'); ?>
            
            <h2>Активные фильтры</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Выберите активные фильтры:</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Активные фильтры</span></legend>
                            
                            <label>
                                <input type="checkbox" name="active_filters[]" value="location" 
                                    <?php checked(in_array('location', $active_filters)); ?> />
                                Город (таксономия location)
                            </label><br/>
                            
                            <label>
                                <input type="checkbox" name="active_filters[]" value="checkin_date" 
                                    <?php checked(in_array('checkin_date', $active_filters)); ?> />
                                Дата заезда
                            </label><br/>
                            
                            <label>
                                <input type="checkbox" name="active_filters[]" value="checkout_date" 
                                    <?php checked(in_array('checkout_date', $active_filters)); ?> />
                                Дата выезда
                            </label><br/>
                            
                            <label>
                                <input type="checkbox" name="active_filters[]" value="adults" 
                                    <?php checked(in_array('adults', $active_filters)); ?> />
                                Количество взрослых
                            </label><br/>
                            
                            <label>
                                <input type="checkbox" name="active_filters[]" value="children" 
                                    <?php checked(in_array('children', $active_filters)); ?> />
                                Количество детей
                            </label><br/>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <h2>Настройки диапазонов цен</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="price_min">Минимальная цена:</label></th>
                    <td>
                        <input name="price_min" type="number" id="price_min" 
                            value="<?php echo esc_attr($price_range['min']); ?>" 
                            class="small-text" /> ₽
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="price_max">Максимальная цена:</label></th>
                    <td>
                        <input name="price_max" type="number" id="price_max" 
                            value="<?php echo esc_attr($price_range['max']); ?>" 
                            class="small-text" /> ₽
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="price_step">Шаг цены:</label></th>
                    <td>
                        <input name="price_step" type="number" id="price_step" 
                            value="<?php echo esc_attr($price_range['step']); ?>" 
                            class="small-text" /> ₽
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Сохранить настройки', 'primary', 'save_filter_settings'); ?>
        </form>
    </div>
    <?php
}

/**
 * Регистрация AJAX обработчиков для админки
 */
function property_filter_admin_ajax_handlers() {
    // Получение статистики использования фильтров
    add_action('wp_ajax_get_filter_statistics', 'get_filter_statistics_callback');
}
add_action('admin_init', 'property_filter_admin_ajax_handlers');

/**
 * Callback для получения статистики фильтров
 */
function get_filter_statistics_callback() {
    // Проверка прав доступа
    if (!current_user_can('manage_options')) {
        wp_die('Недостаточно прав');
    }
    
    // Здесь можно реализовать сбор статистики использования фильтров
    $statistics = array(
        'total_filtered_requests' => 1247,
        'most_popular_filters' => array(
            'location' => 892,
            'price' => 756,
            'checkin_date' => 432
        ),
        'average_results_per_filter' => 15
    );
    
    wp_send_json_success($statistics);
}

/**
 * Добавление метабокса с информацией о фильтрации на странице редактирования property
 */
function add_property_filter_metabox() {
    add_meta_box(
        'property-filter-info',
        'Информация о фильтрации',
        'property_filter_metabox_callback',
        'property',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_property_filter_metabox');

/**
 * Callback для метабокса информации о фильтрации
 */
function property_filter_metabox_callback($post) {
    // Получаем текущие значения полей
    $checkin_date = get_post_meta($post->ID, '_checkin_date', true);
    $checkout_date = get_post_meta($post->ID, '_checkout_date', true);
    $max_adults = get_post_meta($post->ID, '_max_adults', true);
    $max_children = get_post_meta($post->ID, '_max_children', true);
    
    ?>
    <p>
        <label for="checkin_date">Дата заезда:</label><br/>
        <input type="date" id="checkin_date" name="checkin_date" 
            value="<?php echo esc_attr($checkin_date); ?>" />
    </p>
    
    <p>
        <label for="checkout_date">Дата выезда:</label><br/>
        <input type="date" id="checkout_date" name="checkout_date" 
            value="<?php echo esc_attr($checkout_date); ?>" />
    </p>
    
    <p>
        <label for="max_adults">Максимум взрослых:</label><br/>
        <input type="number" id="max_adults" name="max_adults" 
            value="<?php echo esc_attr($max_adults); ?>" min="1" max="20" />
    </p>
    
    <p>
        <label for="max_children">Максимум детей:</label><br/>
        <input type="number" id="max_children" name="max_children" 
            value="<?php echo esc_attr($max_children); ?>" min="0" max="10" />
    </p>
    
    <p class="description">
        Эти данные используются для фильтрации недвижимости на фронтенде.
    </p>
    <?php
}

/**
 * Сохранение метаданных фильтрации при сохранении property
 */
function save_property_filter_meta($post_id) {
    // Проверка автосохранения
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Проверка прав пользователя
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Проверка типа поста
    if ('property' !== get_post_type($post_id)) {
        return;
    }
    
    // Сохраняем даты
    if (isset($_POST['checkin_date'])) {
        update_post_meta($post_id, '_checkin_date', sanitize_text_field($_POST['checkin_date']));
    }
    
    if (isset($_POST['checkout_date'])) {
        update_post_meta($post_id, '_checkout_date', sanitize_text_field($_POST['checkout_date']));
    }
    
    // Сохраняем количество гостей
    if (isset($_POST['max_adults'])) {
        update_post_meta($post_id, '_max_adults', intval($_POST['max_adults']));
    }
    
    if (isset($_POST['max_children'])) {
        update_post_meta($post_id, '_max_children', intval($_POST['max_children']));
    }
}
add_action('save_post', 'save_property_filter_meta');