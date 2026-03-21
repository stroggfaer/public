<?php
/**
 * Фича Недвижимости
 * Объединяет: список, фильтрацию, AJAX, шорткоды
 */

if (!defined('ABSPATH')) {
    exit;
}

class PropertyFeature {
    private static $instance = null;
    private $nonceAction = 'property_filter_nonce';

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Инициализация всех хуков
     */
    public function init() {
        $this->initAjax();
        $this->initShortcodes();
    }

    // ============================================================
    // CORE: Бизнес-логика
    // ============================================================

    /**
     * Получение списка недвижимости с фильтрацией
     * Основная функция - заменяет properties_list()
     * 
     * @param array $params Параметры фильтрации
     * @return array
     */
    public function getList(array $params = []): array {
        $defaults = [
            'posts_per_page' => 12,
            'paged' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $params = wp_parse_args($params, $defaults);

        // Базовые параметры запроса
        $args = [
            'post_type' => 'property',
            'post_status' => 'publish',
            'posts_per_page' => $params['posts_per_page'],
            'paged' => $params['paged'],
            'orderby' => $params['orderby'],
            'order' => $params['order'],
        ];

        // Локация
        $location = $params['location'] ?? $params['location_slug'] ?? '';
        if (!empty($location)) {
            $location_term = get_term_by('slug', sanitize_title($location), 'location');
            if (!$location_term) {
                $location_term = get_term_by('name', sanitize_text_field($location), 'location');
            }
            if ($location_term) {
                $args['tax_query'] = [[
                    'taxonomy' => 'location',
                    'field' => 'term_id',
                    'terms' => $location_term->term_id,
                ]];
            }
        }

        // Цена
        $meta_query = [];
        if (!empty($params['price_min'])) {
            $meta_query[] = [
                'key' => 'price',
                'value' => intval($params['price_min']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
        if (!empty($params['price_max'])) {
            $meta_query[] = [
                'key' => 'price',
                'value' => intval($params['price_max']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        // Гости
        if (!empty($params['adults'])) {
            $meta_query[] = [
                'key' => 'guests_count',
                'value' => intval($params['adults']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        // Выполняем запрос
        $query = new WP_Query($args);

        // Подготавливаем координаты для карты
        $coordinates = [];
        foreach ($query->posts as $post) {
            $lat = get_post_meta($post->ID, 'latitude', true);
            $lng = get_post_meta($post->ID, 'longitude', true);
            if ($lat && $lng) {
                $coordinates[] = [
                    'id' => $post->ID,
                    'lat' => $lat,
                    'lng' => $lng,
                    'title' => $post->post_title,
                ];
            }
        }

        return [
            'posts' => $query->posts,
            'total' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
            'coordinates' => $coordinates,
        ];
    }

    /**
     * Получение списка недвижимости из GET параметров
     * Используется для рендеринга на странице архива
     * 
     * @return array
     */
    public function getListFromGet(): array {
        $filters = [];

        // Локация
        $loc_slug = $_GET['location_slug'] ?? $_GET['location'] ?? '';
        if (!empty($loc_slug)) {
            $filters['location'] = sanitize_text_field($loc_slug);
        }

        // Даты
        if (!empty($_GET['checkin_date'])) {
            $filters['checkin_date'] = sanitize_text_field($_GET['checkin_date']);
        }
        if (!empty($_GET['checkout_date'])) {
            $filters['checkout_date'] = sanitize_text_field($_GET['checkout_date']);
        }

        // Гости
        if (!empty($_GET['adults'])) {
            $filters['adults'] = intval($_GET['adults']);
        }
        if (!empty($_GET['children'])) {
            $filters['children'] = intval($_GET['children']);
        }

        // Пагинация
        $filters['paged'] = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

        return $this->getList($filters);
    }

    /**
     * Получение активных фильтров из GET
     * 
     * @return array
     */
    public function getCurrentFilters(): array {
        $filters = [];

        // Локация
        $loc_slug = $_GET['location_slug'] ?? $_GET['location'] ?? '';
        if (!empty($loc_slug)) {
            $loc_slug = urldecode($loc_slug);
            $term = get_term_by('slug', sanitize_text_field($loc_slug), 'location');
            if ($term && !is_wp_error($term)) {
                $filters[] = [
                    'key' => 'location',
                    'label' => 'Город',
                    'value' => $term->name,
                    'raw' => $loc_slug,
                    'type' => 'taxonomy',
                ];
            }
        }

        // Даты
        if (!empty($_GET['checkin_date'])) {
            $filters[] = [
                'key' => 'checkin_date',
                'label' => 'Заезд',
                'value' => esc_html($_GET['checkin_date']),
                'type' => 'date',
            ];
        }

        if (!empty($_GET['checkout_date'])) {
            $filters[] = [
                'key' => 'checkout_date',
                'label' => 'Выезд',
                'value' => esc_html($_GET['checkout_date']),
                'type' => 'date',
            ];
        }

        // Гости
        if (!empty($_GET['adults'])) {
            $filters[] = [
                'key' => 'adults',
                'label' => 'Взрослые',
                'value' => max(1, (int)$_GET['adults']),
                'type' => 'guests',
            ];
        }

        if (!empty($_GET['children'])) {
            $filters[] = [
                'key' => 'children',
                'label' => 'Дети',
                'value' => max(0, (int)$_GET['children']),
                'type' => 'guests',
            ];
        }

        return $filters;
    }

    // ============================================================
    // AJAX: Обработчики
    // ============================================================

    private function initAjax() {
        // Новый action
        add_action('wp_ajax_property_filter', [$this, 'ajaxFilter']);
        add_action('wp_ajax_nopriv_property_filter', [$this, 'ajaxFilter']);
        
        // Обратная совместимость - старый action
        add_action('wp_ajax_filter_properties_custom', [$this, 'ajaxFilter']);
        add_action('wp_ajax_nopriv_filter_properties_custom', [$this, 'ajaxFilter']);
        
        // Автодополнение локаций
        add_action('wp_ajax_location_autocomplete', [$this, 'ajaxLocationAutocomplete']);
        add_action('wp_ajax_nopriv_location_autocomplete', [$this, 'ajaxLocationAutocomplete']);
    }

    /**
     * AJAX обработчик фильтрации
     */
    public function ajaxFilter() {
        // Проверка nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $this->nonceAction)) {
            wp_send_json_error('Security check failed');
        }

        // Получаем и декодируем location
        $location_raw = isset($_POST['location']) ? wp_unslash($_POST['location']) : '';
        $location_slug = isset($_POST['location_slug']) ? sanitize_title($_POST['location_slug']) : '';

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;

        // Параметры
        $params = [
            'page' => $page,
            'price_min' => isset($_POST['price_min']) ? (int)$_POST['price_min'] : 0,
            'price_max' => isset($_POST['price_max']) ? (int)$_POST['price_max'] : 0,
            'adults' => isset($_POST['adults']) ? (int)$_POST['adults'] : 0,
            'children' => isset($_POST['children']) ? (int)$_POST['children'] : 0,
            'location' => sanitize_text_field($location_raw),
            'location_slug' => $location_slug,
        ];

        // Получаем результаты
        $result = $this->getList($params);

        ob_start();

        if (!empty($result['posts'])) {
            echo '<div class="properties-grid">';
            foreach ($result['posts'] as $post) {
                setup_postdata($post);
                $pod = pods('property', $post->ID);
                get_template_part('template-parts/component/property-card', null, ['pod' => $pod]);
            }
            echo '</div>';

            // Пагинация
            $pagination = '';
            $posts_per_page = $params['posts_per_page'] ?? 12;
            $total_pages = ceil($result['total'] / $posts_per_page);
            if ($total_pages > 1) {
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

            wp_send_json_success([
                'html' => ob_get_clean(),
                'count' => $result['total'],
                'total_pages' => $total_pages,
                'pagination' => $pagination,
                'coordinates' => $result['coordinates'],
            ]);
        } else {
            wp_send_json_success([
                'html' => '<p>Недвижимость не найдена.</p>',
                'count' => 0,
                'total_pages' => 0,
                'pagination' => '',
                'coordinates' => [],
            ]);
        }
    }

    /**
     * AJAX автодополнение локаций
     */
    public function ajaxLocationAutocomplete() {
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], $this->nonceAction)) {
            wp_send_json_error('Security check failed');
        }

        $search_term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        if (empty($search_term)) {
            wp_send_json_error('Empty search term');
        }

        $locations = get_terms([
            'taxonomy' => 'location',
            'name__like' => $search_term,
            'hide_empty' => false,
            'number' => 10,
        ]);

        $results = [];
        foreach ($locations as $location) {
            $results[] = [
                'label' => $location->name,
                'value' => $location->slug,
            ];
        }

        wp_send_json($results);
    }

    // ============================================================
    // RENDER: HTML вывод
    // ============================================================

    private function initShortcodes() {
        add_shortcode('property_filter', [$this, 'shortcodeFilter']);
    }

    /**
     * Шорткод [property_filter]
     */
    public function shortcodeFilter($atts): string {
        $context = $atts['context'] ?? 'default';

        $active_filters = get_option('property_active_filters', [
            'location', 'checkin_date', 'checkout_date', 'adults', 'children',
        ]);

        $price_range = get_option('property_price_range', [
            'min' => 0,
            'max' => 1000000,
            'step' => 0,
        ]);

        $container_id = ($context === 'archive') ? 'property-filter-vue-archive' : 'property-filter-vue';

        $data = [
            'containerId' => $container_id,
            'activeFilters' => $active_filters,
            'initFilters' => $this->getCurrentFilters(),
            'priceRange' => $price_range,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->nonceAction),
        ];

        $propertyFilterData = wp_json_encode(
            $data,
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
     * Рендер формы для главной страницы (GET форма)
     */
    public function renderHomeForm(): string {
        $active_filters = get_option('property_active_filters', [
            'location', 'checkin_date', 'checkout_date', 'adults', 'children',
        ]);

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
                                       $location_term = get_term_by('slug', $location_slug, 'location');
                                       if ($location_term) {
                                           echo esc_attr($location_term->name);
                                       } else {
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
                                        <input type="text" name="adults" value="<?php echo isset($_GET['adults']) ? intval($_GET['adults']) : 1; ?>" readonly>
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
     * Рендер списка недвижимости (для страницы архива)
     */
    public function renderList(): string {
        $result = $this->getListFromGet();

        ob_start();

        if (!empty($result['posts'])) {
            echo '<div class="properties-grid">';
            while (have_posts()) {
                the_post();
                $pod = pods('property', get_the_ID());
                get_template_part('template-parts/component/property-card', null, ['pod' => $pod]);
            }
            echo '</div>';

            echo '<div class="results-info">';
            $total_obj = $this->declension(['объект', 'объекта', 'объектов'], $result['total']);
            echo '<p>Всего ' . $result['total'] . ' ' . $total_obj[1] . '</p>';
            echo '</div>';

            // Пагинация
            global $wp_query;
            if ($wp_query->max_num_pages > 1) {
                echo '<div class="archive-pagination">';
                echo '<ul class="pods-pagination">';

                $current_page = max(1, get_query_var('paged'));

                if ($current_page > 1) {
                    echo '<li><a href="' . esc_url(get_pagenum_link($current_page - 1)) . '">&laquo;</a></li>';
                }

                for ($i = 1; $i <= min($wp_query->max_num_pages, 5); $i++) {
                    if ($i == $current_page) {
                        echo '<li><span class="current">' . $i . '</span></li>';
                    } else {
                        echo '<li><a href="' . esc_url(get_pagenum_link($i)) . '">' . $i . '</a></li>';
                    }
                }

                if ($current_page < $wp_query->max_num_pages) {
                    echo '<li><a href="' . esc_url(get_pagenum_link($current_page + 1)) . '">&raquo;</a></li>';
                }

                echo '</ul>';
                echo '</div>';
            }

            wp_reset_postdata();
        } else {
            echo '<div class="no-results">';
            echo '<p>Недвижимость не найдена.</p>';
            if (current_user_can('administrator')) {
                echo '<p style="color: red; font-size: 12px;">Отладка: post_type=property, статус=publish.</p>';
            }
            echo '</div>';
        }

        return ob_get_clean();
    }

    /**
     * Склонение числительных
     */
    private function declension($words, $number) {
        $number = abs($number) % 100;
        $num = $number % 10;
        if ($number > 10 && $number < 20) {
            return [$number, $words[2]];
        } elseif ($num > 1 && $num < 5) {
            return [$number, $words[1]];
        } elseif ($num == 1) {
            return [$number, $words[0]];
        } else {
            return [$number, $words[2]];
        }
    }

    // ============================================================
    // Для обратной совместимости - функции-обёртки
    // ============================================================

    /**
     * Обёртка для обратной совместимости - заменяет properties_list()
     */
    public function properties_list($params = array()) {
        return $this->getList($params);
    }

    /**
     * Обёртка для обратной совместимости
     */
    public function property_filter_render_form($context = 'default') {
        return $this->shortcodeFilter(['context' => $context]);
    }

    /**
     * Обёртка для обратной совместимости
     */
    public function property_filter_render_home_form() {
        return $this->renderHomeForm();
    }

    /**
     * Обёртка для обратной совместимости
     */
    public function property_filter_render_list($params = array()) {
        if (!empty($params)) {
            $result = $this->getList($params);
        } else {
            $result = $this->getListFromGet();
        }

        ob_start();

        if (!empty($result['posts'])) {
            echo '<div class="properties-grid">';
            foreach ($result['posts'] as $post) {
                setup_postdata($post);
                $pod = pods('property', $post->ID);
                get_template_part('template-parts/component/property-card', null, ['pod' => $pod]);
            }
            echo '</div>';

            echo '<div class="results-info">';
            $total_obj = $this->declension(['объект', 'объекта', 'объектов'], $result['total']);
            echo '<p>Всего ' . $result['total'] . ' ' . $total_obj[1] . '</p>';
            echo '</div>';
        } else {
            echo '<div class="no-results">';
            echo '<p>Недвижимость не найдена.</p>';
            echo '</div>';
        }

        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Обёртка для обратной совместимости - get_current_filters_data()
     */
    public function get_current_filters_data() {
        return $this->getCurrentFilters();
    }
}
