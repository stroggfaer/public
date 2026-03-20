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
 * Возвращает структурированные данные активных фильтров
 *
 * @return array
 */
function get_current_filters_data(): array {
    $filters = [];

    // Локация (поддерживаем location и location_slug)
    $loc_slug = $_GET['location_slug'] ?? $_GET['location'] ?? '';
    if (!empty($loc_slug)) {
        $loc_slug = urldecode($loc_slug);
        $term = get_term_by('slug', sanitize_text_field($loc_slug), 'location');
      //  dd($loc_slug);
        if ($term && ! is_wp_error($term)) {
            $filters[] = [
                    'key'   => 'location',
                    'label' => 'Город',
                    'value' => $term->name,
                    'raw'   => $loc_slug,
                    'type'  => 'taxonomy',
            ];
        }
    }

    // Даты
    if (! empty($_GET['checkin_date'])) {
        $filters[] = [
                'key'   => 'checkin_date',
                'label' => 'Заезд',
                'value' => esc_html($_GET['checkin_date']),
                'type'  => 'date',
        ];
    }

    if (! empty($_GET['checkout_date'])) {
        $filters[] = [
                'key'   => 'checkout_date',
                'label' => 'Выезд',
                'value' => esc_html($_GET['checkout_date']),
                'type'  => 'date',
        ];
    }

    // Гости
    if (! empty($_GET['adults'])) {
        $filters[] = [
                'key'   => 'adults',
                'label' => 'Взрослые',
                'value' => max(1, (int) $_GET['adults']),
                'type'  => 'guests',
        ];
    }

    if (! empty($_GET['children'])) {
        $filters[] = [
                'key'   => 'children',
                'label' => 'Дети',
                'value' => max(0, (int) $_GET['children']),
                'type'  => 'guests',
        ];
    }

    return $filters;
}

/**
 * Рендер формы фильтрации (Vue 3 компонент)
 */
function property_filter_render_form($context = 'default') {
    // Получаем настройки активных фильтров
    $active_filters = get_option('property_active_filters', array(
        'location', 'checkin_date', 'checkout_date', 'adults', 'children'
    ));

    $price_range = get_option('property_price_range', array(
        'min' => 0,
        'max' => 1000000,
        'step' => 1000
    ));

    // Получаем nonce
    $nonce = wp_create_nonce('property_filter_nonce');

    // Определяем ID контейнера в зависимости от контекста
    $container_id = ($context === 'archive') ? 'property-filter-vue-archive' : 'property-filter-vue';

    // Подготавливаем данные для передачи в JS
    $data_to_localize = [
            'containerId' => $container_id,
            'activeFilters' => $active_filters,
            'initFilters'   => get_current_filters_data(),
            'priceRange'    => $price_range,
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => $nonce,
    ];

    $propertyFilterData = wp_json_encode(
             $data_to_localize,
            JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    ob_start();
    ?>
    <div id="<?php echo esc_attr($container_id); ?>"></div>

    <script type="module">
        const { ref } = Vue;
        const { createAppModule, PropertyFilter } = window.VueAppModule;
        const app = createAppModule({
            components: {
                PropertyFilter
            },
            // language=Vue
            template: `
                <property-filter
                    :context="propertyFilterData.containerId"
                    :active-filters="propertyFilterData.activeFilters"
                    :init-filters="propertyFilterData.initFilters"
                    :price-range="propertyFilterData.priceRange"
                    :ajax-url="propertyFilterData.ajaxUrl"
                    :nonce="propertyFilterData.nonce"
                />
            `,
            setup() {
                const propertyFilterData = ref(<?=$propertyFilterData?>);
                return {
                    propertyFilterData
                };
            }
        });

        app.mount('#<?php echo esc_attr($container_id); ?>');
    </script>
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
                                  ?>" placeholder="В любое время" class="input__com">
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
                        <input type="text" readonly value class="input__com guests-placeholder js-guests-val" autocomplete="nope" placeholder="Гости">
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

    // Получаем номер страницы из GET параметра
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

    // Базовые параметры запроса
    $args = array(
        'post_type'      => 'property',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    // Добавляем фильтр по локации если есть
    if (!empty($filters['location'])) {
        $location = sanitize_text_field($filters['location']);
        $location_term = get_term_by('slug', $location, 'location');
        if (!$location_term) {
            $location_term = get_term_by('name', $location, 'location');
        }
        if ($location_term) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'location',
                    'field'    => 'term_id',
                    'terms'    => $location_term->term_id,
                ),
            );
        }
    }

    // Выполняем запрос через WP_Query
    $query = new WP_Query($args);

    ob_start();

    // Отладочная информация (можно удалить после проверки)
    $debug_info = '';
    if (current_user_can('administrator')) {
        $debug_info = '<!-- Debug: Found ' . $query->found_posts . ' posts, post_type=property -->';
    }

    if ($query->have_posts()) {
        echo $debug_info;
        echo '<div class="properties-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            // Получаем Pods объект для текущего поста
            $pod = pods('property', get_the_ID());
            get_template_part('template-parts/component/property-card', null, array('pod' => $pod));
        }
        echo '</div>';

        echo '<div class="results-info">';
        $total_obj = declension(['объект', 'объекта', 'объектов'], $query->found_posts);
        echo '<p>Всего ' . $query->found_posts . ' ' . $total_obj[1] . '</p>';
        echo '</div>';
        
        // Добавляем пагинацию
        if ($query->max_num_pages > 1) {
            echo '<div class="archive-pagination">';
            echo '<ul class="pods-pagination">';
            
            // Текущая страница
            $current_page = max(1, get_query_var('paged'));
            
            // Предыдущая страница
            if ($current_page > 1) {
                echo '<li><a href="' . esc_url(get_pagenum_link($current_page - 1)) . '">&laquo;</a></li>';
            }
            
            // Номера страниц
            for ($i = 1; $i <= min($query->max_num_pages, 5); $i++) {
                if ($i == $current_page) {
                    echo '<li><span class="current">' . $i . '</span></li>';
                } else {
                    echo '<li><a href="' . esc_url(get_pagenum_link($i)) . '">' . $i . '</a></li>';
                }
            }
            
            // Следующая страница
            if ($current_page < $query->max_num_pages) {
                echo '<li><a href="' . esc_url(get_pagenum_link($current_page + 1)) . '">&raquo;</a></li>';
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        wp_reset_postdata();
    } else {
        echo $debug_info;
        echo '<div class="no-results">';
        echo '<p>Недвижимость не найдена.</p>';
        // Дополнительная отладка для администраторов
        if (current_user_can('administrator')) {
            echo '<p style="color: red; font-size: 12px;">Отладка: post_type=property, статус=publish. Проверьте, есть ли записи этого типа в базе данных.</p>';
        }
        echo '</div>';
    }

    return ob_get_clean();
}

/**
 * Функция склонения числительных
 */
function declension($words, $number) {
    $number = abs($number) % 100;
    $num = $number % 10;
    if ($number > 10 && $number < 20) {
        return array($number, $words[2]);
    } elseif ($num > 1 && $num < 5) {
        return array($number, $words[1]);
    } elseif ($num == 1) {
        return array($number, $words[0]);
    } else {
        return array($number, $words[2]);
    }
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
    global $wpdb;

    // Проверка nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'property_filter_nonce')) {
        wp_send_json_error('Security check failed');
    }

    // Получаем и декодируем location
    $location_raw = isset($_POST['location']) ? wp_unslash($_POST['location']) : '';
    while (strpos($location_raw, '%') !== false) {
        $decoded = rawurldecode($location_raw);
        if ($decoded === $location_raw) break;
        $location_raw = $decoded;
    }
    $location_slug = isset($_POST['location_slug']) ? sanitize_title($_POST['location_slug']) : '';

    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;

    // Фильтры из AJAX
    $filters = array(
            'price_min'     => isset($_POST['price_min']) ? (int) $_POST['price_min'] : 0,
            'price_max'     => isset($_POST['price_max']) ? (int) $_POST['price_max'] : 0,
            'adults'        => isset($_POST['adults']) ? (int) $_POST['adults'] : 0,
            'children'      => isset($_POST['children']) ? (int) $_POST['children'] : 0, // Позже добавим
            'location'      => sanitize_text_field($location_raw),
            'location_slug' => $location_slug,
    );

    // Строим WHERE для Pods
    $where = array();
    $where[] = "t.post_status = 'publish'";

    // Локация
    if (!empty($filters['location']) || !empty($filters['location_slug'])) {
        $where[] = "EXISTS (
            SELECT 1
            FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->terms} tm ON tm.term_id = tt.term_id
            WHERE tr.object_id = t.ID
            AND tt.taxonomy = 'location'
            AND (
                tm.name = '" . esc_sql($filters['location']) . "'
                OR tm.slug = '" . esc_sql($filters['location_slug']) . "'
            )
        )";
    }

    // Цена
    if ($filters['price_min'] > 0) {
        $where[] = "CAST((SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = t.ID AND meta_key = 'price' LIMIT 1) AS UNSIGNED) >= {$filters['price_min']}";
    }
    if ($filters['price_max'] > 0) {
        $where[] = "CAST((SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = t.ID AND meta_key = 'price' LIMIT 1) AS UNSIGNED) <= {$filters['price_max']}";
    }

    // Взрослые (guests_count)
    if ($filters['adults'] > 0) {
        $where[] = "CAST((SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = t.ID AND meta_key = 'guests_count' LIMIT 1) AS UNSIGNED) >= {$filters['adults']}";
    }

    // Дети (если отдельного поля нет, можно игнорировать)
    // $filters['children'] пока не используем, так как нет отдельного поля

    // Параметры Pods запроса
    $params = array(
            'where'   => implode(' AND ', $where),
            'limit'   => 12,
            'page'    => $page,
            'orderby' => 't.post_date DESC',
    );

    $pod = pods('property', $params);

    $coordinates = array();
    ob_start();

    if ($pod->total() > 0) {
        echo '<div class="properties-grid">';
        while ($pod->fetch()) {
            // Координаты для карты
            $lat = $pod->field('latitude');
            $lng = $pod->field('longitude');
            if ($lat && $lng) {
                $coordinates[] = array(
                        'id'    => $pod->id(),
                        'lat'   => $lat,
                        'lng'   => $lng,
                        'title' => $pod->display('post_title'),
                );
            }

            // Шаблон карточки
            get_template_part('template-parts/component/property-card', null, array('pod' => $pod));
        }
        echo '</div>';

        // Пагинация
        $pagination = '';
        if ($pod->total() > 12) {
            $total_pages = ceil($pod->total() / 12);
            $pagination = '<ul class="pods-pagination">';
            for ($i = 1; $i <= min($total_pages, 5); $i++) {
                if ($i == $page) {
                    $pagination .= '<li><span class="current">' . $i . '</span></li>';
                } else {
                    $pagination .= '<li><a href="#" data-page="' . $i . '">' . $i . '</a></li>';
                }
            }
            $pagination .= '</ul>';
        }

        wp_send_json_success(array(
                'html'        => ob_get_clean(),
                'count'       => $pod->total(),
                'total_pages' => isset($total_pages) ? $total_pages : 1,
                'pagination'  => $pagination,
                'coordinates' => $coordinates,
        ));

    } else {
        wp_send_json_success(array(
                'html'        => '<p>Недвижимость не найдена.</p>',
                'count'       => 0,
                'total_pages' => 0,
                'pagination'  => '',
                'coordinates' => array(),
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
