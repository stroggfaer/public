<?php
/**
 * Фича Недвижимости
 * Объединяет: список, фильтрацию, AJAX, шорткоды
 */

if (!defined('ABSPATH')) {
    exit;
}

class PropertyFeature {

    private static $PER_LIMIT = 10;

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
            'posts_per_page' => self::$PER_LIMIT,
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

        // Фильтрация по характеристикам (после основного запроса)
        // Логика OR: объект должен иметь ХОТЯ БЫ ОДНУ из выбранных характеристик
        $filtered_posts = $query->posts;
        
        if (!empty($params['characteristics']) && is_array($params['characteristics'])) {
            $characteristics = array_map('intval', $params['characteristics']);
            
            $filtered_posts = array_filter($filtered_posts, function($post) use ($characteristics) {
                $saved = get_post_meta($post->ID, 'property_characteristics', true);
                
                if (!is_array($saved) || empty($saved)) {
                    return false;
                }
                
                $property_char_ids = array_column($saved, 'characteristic_id');
                $property_char_ids = array_map('intval', $property_char_ids);
                
                // ХОТЯ БЫ ОДНА характеристика должна совпадать
                foreach ($characteristics as $char_id) {
                    if (in_array($char_id, $property_char_ids, true)) {
                        return true;
                    }
                }
                return false;
            });
            
            // Пересчитываем после фильтрации
            $filtered_posts = array_values($filtered_posts);
        }

        // Подготавливаем координаты для карты
        $coordinates = [];
        foreach ($filtered_posts as $post) {
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
            'posts' => $filtered_posts,
            'total' => count($filtered_posts),
            'max_pages' => ceil(count($filtered_posts) / $params['posts_per_page']),
            'coordinates' => $coordinates,
        ];
    }

    /**
     * Единая точка получения и нормализации всех фильтров
     *
     * @param string $context 'get' | 'post' | 'request'  — откуда брать данные
     * @param array  $overrides Дополнительные/принудительные значения (удобно для AJAX)
     * @return array Нормализованные фильтры + служебные поля
     */
    public function formatRequestParams(string $context = 'request', array $overrides = []): array
    {
        // 1. Определяем источник данных
        $source = match (strtolower($context)) {
            'post'    => $_POST,
            'get'     => $_GET,
            default   => $_REQUEST,
        };

        // 2. Базовые значения + overrides
        $filters = array_merge([
                'location'      => '',
                'location_slug' => '',
                'checkin_date'  => '',
                'checkout_date' => '',
                'adults'        => ($context === 'post') ? 0 : 1,
                'children'      => 0,
                'price_min'     => 0,
                'price_max'     => 0,
                'paged'         => 1,
                'characteristics' => [],
        ], $overrides);

        // 3. Локация — самая сложная часть, делаем аккуратно
        $loc_raw = $source['location']      ?? $filters['location'];
        $loc_slug = $source['location_slug'] ?? $source['location'] ?? $filters['location_slug'];

        if ($loc_raw !== '') {
            $filters['location'] = sanitize_text_field(wp_unslash($loc_raw));
        }
        if ($loc_slug !== '') {
            $filters['location_slug'] = sanitize_title($loc_slug);
            if (empty($filters['location'])) {
                $filters['location'] = $filters['location_slug'];
            }
        }

        // 4. Даты — простая проверка формата
        foreach (['checkin_date', 'checkout_date'] as $key) {
            $val = $source[$key] ?? $filters[$key];
            if ($val && preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                $filters[$key] = sanitize_text_field($val);
            }
        }

        // 5. Числовые поля с минимальными значениями
        $numeric_fields = [
                'adults'    => ['min' => ($context === 'get' ? 1 : 0), 'default' => ($context === 'get' ? 1 : 0)],
                'children'  => ['min' => 0, 'default' => 0],
                'price_min' => ['min' => 0],
                'price_max' => ['min' => 0],
                'paged'     => ['min' => 1],
        ];

        foreach ($numeric_fields as $key => $rules) {
            $val = $source[$key] ?? $filters[$key];
            if (isset($source['page']) && $key === 'paged') {  // поддержка AJAX page
                $val = $source['page'];
            }
            $filters[$key] = max($rules['min'] ?? 0, intval($val ?: $rules['default'] ?? 0));
        }

        // 6. Защита от мусора
        $filters = array_filter($filters, function ($v, $k) {
            if (in_array($k, ['paged', 'adults', 'children', 'price_min', 'price_max'])) {
                return $v > 0; // убираем нулевые числовые, если не нужны
            }
            // Не удаляем массивы (characteristics)
            if (is_array($v)) {
                return true;
            }
            return $v !== '';
        }, ARRAY_FILTER_USE_BOTH);

        // 7. Обработка характеристик
        if (isset($source['characteristics'])) {
            $chars = $source['characteristics'];
            
            // Если пришла строка JSON, декодируем
            if (is_string($chars)) {
                $decoded = json_decode($chars, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $chars = $decoded;
                }
            }
            
            // Преобразуем в массив целых чисел
            if (is_array($chars)) {
                $chars = array_map('intval', array_filter($chars, 'is_numeric'));
                // Сохраняем только если массив не пустой
                if (!empty($chars)) {
                    $filters['characteristics'] = $chars;
                }
            }
        }

        return $filters;
    }

    /**
     * Получение списка недвижимости из GET параметров
     * Используется для рендеринга на странице архива
     * 
     * @return array
     */
    public function getListFromGet(): array {
        $filters = $this->formatRequestParams('GET');
        return $this->getList($filters);
    }

    /**
     * Получение активных фильтров из GET
     * 
     * @return array
     */
    public function getCurrentFilters(): array {
        $params = $this->formatRequestParams('GET');
        $filters = [];

        // Локация
        if (!empty($params['location'])) {
            $loc_slug = urldecode($params['location']);
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
        if (!empty($params['checkin_date'])) {
            $filters[] = [
                'key' => 'checkin_date',
                'label' => 'Заезд',
                'value' => esc_html($params['checkin_date']),
                'type' => 'date',
            ];
        }

        if (!empty($params['checkout_date'])) {
            $filters[] = [
                'key' => 'checkout_date',
                'label' => 'Выезд',
                'value' => esc_html($params['checkout_date']),
                'type' => 'date',
            ];
        }

        // Гости
        if (!empty($params['adults'])) {
            $filters[] = [
                'key' => 'adults',
                'label' => 'Взрослые',
                'value' => $params['adults'],
                'type' => 'guests',
            ];
        }

        if (isset($params['children'])) {
            $filters[] = [
                'key' => 'children',
                'label' => 'Дети',
                'value' => $params['children'],
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
    public function ajaxFilterOld() {
        // Проверка nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $this->nonceAction)) {
            wp_send_json_error('Security check failed');
        }

        // Получаем параметры через унифицированный метод
        $params = $this->formatRequestParams('POST');
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $params['page'] = $page;

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
    public function ajaxFilter() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $this->nonceAction)) {
            wp_send_json_error('Security check failed');
        }

        $params = $this->formatRequestParams('POST');

        $page     = max(1, intval($_POST['page'] ?? 1));
        $per_page = max(self::$PER_LIMIT, intval($_POST['per_page'] ?? self::$PER_LIMIT));

        $params['paged']          = $page;
        $params['posts_per_page'] = $per_page;

        $result = $this->getList($params);

        ob_start();
        if (!empty($result['posts'])) {
            foreach ($result['posts'] as $post) {
                setup_postdata($post);
                $pod = pods('property', $post->ID);
                get_template_part('template-parts/component/property-card', null, ['pod' => $pod]);
            }
            $html = ob_get_clean();
        } else {
            $html = '';
            ob_end_clean();
        }

        $total_pages = $result['max_pages'] ?? ceil($result['total'] / $per_page);

        wp_send_json_success([
                'html' => $html,
                'meta' => [
                        'current_page' => (int)$page,
                        'page_count'   => (int)$total_pages,
                        'per_page'     => (int)$per_page,
                        'total'        => (int)$result['total']
                ],
                'coordinates' => $result['coordinates'] ?? []
        ]);
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

        $config = [
                'price_range' => $price_range,
                'guests' => ['adults'=> ['min'=>1,'max'=>10],'children' => ['min'=>0,'max'=>8]],
                'filter_characteristics' => realty_get_filter_characteristics()
        ];

        $data = [
                'containerId' => $container_id,
                'activeFilters' => $active_filters,
                'initFilters' => $this->getCurrentFilters(),
                'priceRange' => $price_range,
                'config' => $config,
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
                        :config="propertyFilterData.config"
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

        $config = [
            'guests' => [
                'adults' => ['min' => 1, 'max' => 10],
                'children' => ['min' => 0, 'max' => 8]
            ]
        ];

        $data = [
            'activeFilters' => $active_filters,
            'config' => $config,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->nonceAction),
        ];

        $propertyFilterData = wp_json_encode(
            $data,
            JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        ob_start();
        ?>
        <div id="property-home-filter-vue"></div>

        <script type="module">
            (function() {
                const { ref } = Vue;
                const { createAppModule, HomePropertyFilter } = window.VueAppModule;
                
                const homeApp = createAppModule({
                    components: {
                        HomePropertyFilter
                    },
                    template: `
                        <home-property-filter
                            :active-filters="propertyFilterData.activeFilters"
                            :config="propertyFilterData.config"
                            :ajax-url="propertyFilterData.ajaxUrl"
                            :nonce="propertyFilterData.nonce"
                        />
                    `,
                    setup() {
                        const propertyFilterData = ref(<?php echo $propertyFilterData; ?>);
                        return {
                            propertyFilterData
                        };
                    }
                });

                homeApp.mount('#property-home-filter-vue');
            })();
        </script>
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
            $total_obj = declension(['объект', 'объекта', 'объектов'], $result['total']);
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
     *
     * @param array $params Параметры фильтрации. Если пустой - использует GET-параметры
     */
    public function property_filter_render_list() {
        $result = $this->getListFromGet();

        ob_start();
        if (!empty($result['posts'])) {
            foreach ($result['posts'] as $post) {
                setup_postdata($post);
                $pod = pods('property', $post->ID);
                get_template_part('template-parts/component/property-card', null, ['pod' => $pod]);
            }
            $html = ob_get_clean();
        } else {
            $html = '<div class="no-results">Недвижимость не найдена.</div>';
            ob_end_clean();
        }

        wp_reset_postdata();

        $page = max(1, get_query_var('paged', 1));
        $per_page = get_option('posts_per_page', self::$PER_LIMIT);
        $total_pages = $result['max_pages'] ?? ceil($result['total'] / $per_page);

        return [
            'html' => $html,
            'meta' => [
                'current_page' => (int)$page,
                'page_count'   => (int)$total_pages,
                'per_page'     => (int)$per_page,
                'total'        => (int)$result['total']
            ],
            'coordinates' => $result['coordinates'] ?? []
        ];
    }

    /**
     * Обёртка для обратной совместимости - get_current_filters_data()
     */
    public function get_current_filters_data() {
        return $this->getCurrentFilters();
    }
}
