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
 * Рендер формы фильтрации для главной страницы (отправляет GET-запрос на /property/)
 */
function property_filter_render_home_form() {
    // Получаем настройки активных фильтров
    $active_filters = get_option('property_active_filters', array(
        'location', 'checkin_date', 'checkout_date', 'adults', 'children'
    ));

    ob_start();
    ?>
    <form id="property-home-filter-form" class="property-filter-form home-filter" method="GET" action="<?php echo esc_url(home_url('/property/')); ?>">
        <div class="filter-row">
                   <?php if (in_array('location', $active_filters)): ?>
                         <div class="input-group__com location-group" data-name-icon="location_on">
                             <div class="input-with-icon">
                                 <span class="icon material-symbols-outlined">location_on</span>
                                 <input type="text" name="location" id="filter-location" class="location-autocomplete input__com"
                                        autocomplete="nope"
                                        value="<?php
                                        if (isset($_GET['location']) && !empty($_GET['location'])) {
                                            $location_slug = sanitize_text_field($_GET['location']);
                                            // Пытаемся найти термин по slug
                                            $location_term = get_term_by('slug', $location_slug, 'location');
                                            if ($location_term) {
                                                // Если нашли, выводим имя
                                                echo esc_attr($location_term->name);
                                            } else {
                                                // Если не нашли, выводим то, что ввели
                                                echo esc_attr($location_slug);
                                            }
                                        }
                                        ?>" placeholder="Город или регион">
                             </div>
                             <input type="hidden" name="location_slug" id="filter-location-slug"
                                    value="<?php echo isset($_GET['location']) ? esc_attr(sanitize_text_field($_GET['location'])) : ''; ?>">
                         </div>
                   <?php endif; ?>
       
                   <?php if (in_array('checkin_date', $active_filters) && in_array('checkout_date', $active_filters)): ?>
                     <div class="input-group__com date-group">
                       <div class="input-with-icon">
                         <span class="icon material-symbols-outlined">date_range</span>
                           <input type="text" id="daterange" name="daterange" readonly
                                  value="<?php
                                  $checkin = isset($_GET['checkin_date']) ? $_GET['checkin_date'] : '';
                                  $checkout = isset($_GET['checkout_date']) ? $_GET['checkout_date'] : '';
                                  if ($checkin && $checkout) {
                                      echo esc_attr($checkin . ' - ' . $checkout);
                                  } elseif ($checkin) {
                                      echo esc_attr($checkin . ' - ');
                                  } elseif ($checkout) {
                                      echo esc_attr(' - ' . $checkout);
                                  }
                                  ?>" placeholder="Даты" class="input__com">
                       </div>
                       <input type="hidden" name="checkin_date" id="filter-checkin"
                              value="<?php echo isset($_GET['checkin_date']) ? esc_attr($_GET['checkin_date']) : ''; ?>">
                       <input type="hidden" name="checkout_date" id="filter-checkout"
                              value="<?php echo isset($_GET['checkout_date']) ? esc_attr($_GET['checkout_date']) : ''; ?>">
                   </div>
                   <?php endif; ?>

            <?php if (in_array('adults', $active_filters) || in_array('children', $active_filters)): ?>
                <div class="input-group__com guests-group">
                    <div class="input-with-icon">
                        <div class="icon material-symbols-outlined">group</div>
                        <input type="text" readonly name="location" value class="input__com guests-placeholder js-guests-val" autocomplete="nope" placeholder="Гости">
                    </div>
                    <div class="guest-controls-wrapper">
                        <div class="guest-controls">
                            <div class="guest-control">
                                <div class="flex-label">
                                    <span class="guest-label">Взрослые</span>
                                    <span class="guest-desc">от 13 лет</span>
                                </div>
                                <div class="guest-input">
                                    <button type="button" class="guest-decrease" data-target="adults">
                                        <span class="material-symbols-outlined">remove</span>
                                    </button>
                                    <input type="text" name="adults"  value="<?php echo isset($_GET['adults']) ? intval($_GET['adults']) : 1; ?>" readonly>
                                    <button type="button" class="guest-increase" data-target="adults">
                                         <span class="material-symbols-outlined">add_2</span>
                                    </button>
                                </div>
                            </div>
                            <?php if (in_array('children', $active_filters)): ?>
                                <div class="guest-control">
                                    <div class="flex-label">
                                        <span class="guest-label">Дети</span>
                                        <span class="guest-desc">от 2 до 12 лет</span>
                                    </div>
                                    <div class="guest-input">
                                        <button type="button" class="guest-decrease" data-target="children">
                                            <span class="material-symbols-outlined">remove</span>
                                        </button>
                                        <input type="text" name="children" value="<?php echo isset($_GET['children']) ? intval($_GET['children']) : 0; ?>" readonly>
                                        <button type="button" class="guest-increase" data-target="children">
                                            <span class="material-symbols-outlined">add_2</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="filter-actions">
            <button type="submit" class="button__com lg">
                <div class="icon material-symbols-outlined">search</div>
                Искать
            </button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Рендер списка недвижимости
 */
function property_filter_render_list($params = array()) {
    // Если переданы фильтры (а не только параметры для pods), обрабатываем их отдельно
    $filters = array();
    
    // Проверяем, содержит ли $params фильтры (а не только параметры для pods)
    if (isset($params['location']) || isset($params['checkin_date']) ||
        isset($params['checkout_date']) || isset($params['adults']) ||
        isset($params['children'])) {
        $filters = $params;
        $params = array();
    }
    
    // Если в GET есть фильтры, используем их
    if (isset($_GET['location_slug']) && !empty($_GET['location_slug'])) {
        // Используем slug если он доступен
        $filters['location'] = sanitize_text_field($_GET['location_slug']);
    } else if (isset($_GET['location']) && !empty($_GET['location'])) {
        // Обратная совместимость - получаем slug из имени, если возможно
        $location_input = sanitize_text_field($_GET['location']);
        $location_term = get_term_by('name', $location_input, 'location');
        if ($location_term) {
            // Если нашли термин по имени, используем его slug
            $filters['location'] = $location_term->slug;
        } else {
            // Иначе используем введенное значение (может быть slug или просто строка)
            $filters['location'] = $location_input;
        }
    }
    if (isset($_GET['checkin_date']) && !empty($_GET['checkin_date'])) {
        $filters['checkin_date'] = sanitize_text_field($_GET['checkin_date']);
    }
    if (isset($_GET['checkout_date']) && !empty($_GET['checkout_date'])) {
        $filters['checkout_date'] = sanitize_text_field($_GET['checkout_date']);
    }
    if (isset($_GET['adults']) && !empty($_GET['adults'])) {
        $filters['adults'] = intval($_GET['adults']);
    }
    if (isset($_GET['children']) && !empty($_GET['children'])) {
        $filters['children'] = intval($_GET['children']);
    }

    $default_params = array(
        'limit' => 12,
        'orderby' => 't.post_date DESC'
    );

    $params = array_merge($default_params, $params);
    
    // Формируем условие WHERE на основе фильтров
    if (!empty($filters)) {
        $where = property_filter_build_where($filters);
        $params['where'] = $where;
    } else {
        $params['where'] = 't.post_status = "publish"';
    }

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
        
        // Проверяем, является ли переданное значение slug или именем
        $location_term = get_term_by('slug', $location, 'location');
        if (!$location_term) {
            // Если не нашли по slug, ищем по имени
            $location_term = get_term_by('name', $location, 'location');
        }
        
        if ($location_term) {
            // Используем ID термина для более точной фильтрации
            $location_id = (int) $location_term->term_id;
            $where[] = "
                EXISTS (
                    SELECT 1
                    FROM {$wpdb->term_relationships} tr
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                    WHERE tr.object_id = t.ID
                    AND tt.taxonomy = 'location'
                    AND tt.term_id = {$location_id}
                )
            ";
        } else {
            // Если термин не найден, добавляем условие, которое ничего не вернет
            $where[] = "1=0";
        }
    }

    // Фильтр по дате заезда и выезда
    if (!empty($filters['checkin_date']) && !empty($filters['checkout_date'])) {
        $checkin_date = sanitize_text_field($filters['checkin_date']);
        $checkout_date = sanitize_text_field($filters['checkout_date']);
        
        // Проверяем формат дат
        $checkin_ts = strtotime($checkin_date);
        $checkout_ts = strtotime($checkout_date);
        
        if ($checkin_ts !== false && $checkout_ts !== false && $checkin_ts <= $checkout_ts) {
            // Примечание: В будущем здесь должна быть реализована проверка на бронирования
            // для исключения объектов, которые уже забронированы на указанный период
            // Пример логики (псевдокод):
            // Исключить объекты недвижимости, у которых есть подтвержденные бронирования,
            // пересекающиеся с диапазоном checkin_date - checkout_date
        }
    }
    
    // Фильтр по отдельной дате заезда
    if (!empty($filters['checkin_date']) && empty($filters['checkout_date'])) {
        $checkin_date = sanitize_text_field($filters['checkin_date']);
        $checkin_ts = strtotime($checkin_date);
        
        if ($checkin_ts === false) {
            // Неверный формат даты
            $where[] = "1=0"; // Это условие никогда не выполнится, эффективно исключая все результаты
        }
    }
    
    // Фильтр по отдельной дате выезд��
    if (empty($filters['checkin_date']) && !empty($filters['checkout_date'])) {
        $checkout_date = sanitize_text_field($filters['checkout_date']);
        $checkout_ts = strtotime($checkout_date);
        
        if ($checkout_ts === false) {
            // Неверный формат даты
            $where[] = "1=0"; // Это условие никогда не выполнится, эффективно исключая все результаты
        }
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

    // Обработка локации - если это имя, а не slug, пытаемся получить slug
    $location_value = sanitize_text_field($location_raw);
    $location_term = get_term_by('name', $location_value, 'location');
    if ($location_term) {
        // Если нашли по имени, используем slug
        $location_filter_value = $location_term->slug;
    } else {
        // Иначе используем введенное значение (предполагаем, что это slug)
        $location_filter_value = $location_value;
    }

    $filters = array(
        'location'  => $location_filter_value,
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

    if ($pod->total() > 0) {
        ob_start();
        echo '<div class="properties-grid">';
        while ($pod->fetch()) {
            get_template_part('template-parts/component/property-card', null, array('pod' => $pod));
        }
        echo '</div>';
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'total' => $pod->total(),
            'total_pages' => $pod->total_pages(),
            'pagination' => $pod->pagination(array('type' => 'list'))
        ));
    } else {
        wp_send_json_success(array(
            'html' => '<p>Недвижимость не найдена.</p>',
            'total' => 0
        ));
    }
}
add_action('wp_ajax_filter_properties_custom', 'filter_properties_custom_ajax_handler');
add_action('wp_ajax_nopriv_filter_properties_custom', 'filter_properties_custom_ajax_handler');

/**
 * AJAX обработчик для автодополнения местоположений
 */
function location_autocomplete_ajax_handler() {
    if (
        !isset($_GET['nonce']) ||
        !wp_verify_nonce($_GET['nonce'], 'property_filter_nonce')
    ) {
        wp_send_json_error('Security check failed');
    }
    
    $search_term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

    if (empty($search_term)) {
        wp_send_json_error('Empty search term');
    }

    $locations = get_terms(array(
        'taxonomy' => 'location',
        'name__like' => $search_term,
        'hide_empty' => false,
        'number' => 10
    ));

    $results = array();
    foreach ($locations as $location) {
        $results[] = array(
            'label' => $location->name,
            'value' => $location->slug
        );
    }

    wp_send_json($results);
}
add_action('wp_ajax_location_autocomplete', 'location_autocomplete_ajax_handler');
add_action('wp_ajax_nopriv_location_autocomplete', 'location_autocomplete_ajax_handler');
