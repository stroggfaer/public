<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
	/*-----------------------------------------------------------------------------------*/
	/* This file will be referenced every time a template/page loads on your Wordpress site
	/* This is the place to define custom fxns and specialty code
	/*-----------------------------------------------------------------------------------*/

// Define the version so we can easily replace it throughout the theme
define( 'NAKED_VERSION', 1.0 );
/*-----------------------------------------------------------------------------------*/
	/*  Set the maximum allowed width for any content in the theme
	/*-----------------------------------------------------------------------------------*/
if ( ! isset( $content_width ) ) $content_width = 900;

/*-----------------------------------------------------------------------------------*/
	/* Add Rss feed support to Head section
	/*-----------------------------------------------------------------------------------*/
add_theme_support( 'automatic-feed-links' );

/*-----------------------------------------------------------------------------------*/
	/* Add post thumbnail/featured image support
	/*-----------------------------------------------------------------------------------*/
add_theme_support( 'post-thumbnails' );

/*-----------------------------------------------------------------------------------*/
	/* Register main menu for Wordpress use
	/*-----------------------------------------------------------------------------------*/
register_nav_menus(
	array(
		'primary'	=>	__( 'Primary Menu', 'naked' ), // Register the Primary menu
		// Copy and paste the line above right here if you want to make another menu,
		// just change the 'primary' to another name
	)
);

/*-----------------------------------------------------------------------------------*/
	/* Activate sidebar for Wordpress use
	/*-----------------------------------------------------------------------------------*/
function naked_register_sidebars() {
	register_sidebar(array(				// Start a series of sidebars to register
		'id' => 'sidebar', 					// Make an ID
		'name' => 'Sidebar',				// Name it
		'description' => 'Take it on the side...', // Dumb description for the admin side
		'before_widget' => '<div>',	// What to display before each widget
		'after_widget' => '</div>',	// What to display following each widget
		'before_title' => '<h3 class="side-title">',	// What to display before each widget's title
		'after_title' => '</h3>',		// What to display following each widget's title
		'empty_title'=> '',					// What to display in the case of no title defined for a widget
		// Copy and paste the lines above right here if you want to make another menu,
		// just change the values of id and name to another word/name
	));
}
// adding sidebars to Wordpress (these are created in functions.php)
add_action( 'widgets_init', 'naked_register_sidebars' );

/*-----------------------------------------------------------------------------------*/
	/* Enqueue Styles and Scripts
	/*-----------------------------------------------------------------------------------*/
function naked_scripts()  {
    /*----Start CSS----*/
    // get the theme directory style.css and link to it in the header
	wp_enqueue_style('style.css', get_stylesheet_directory_uri() . '/style.css');
    wp_enqueue_style('font-awesome.min.css', get_stylesheet_directory_uri() . '/assets/font-awesome/css/font-awesome.min.css');
    wp_enqueue_style('main.min.css', get_stylesheet_directory_uri() . '/dist/css/main.min.css');
    // стили Element Plus
    wp_enqueue_style('element-plus', get_template_directory_uri() . '/assets/vue_module/cdn/element-plus/index.css', array(), NAKED_VERSION, 'all');

    // Подключаем стили Splide для галереи на странице недвижимости
    if (is_singular('property')) {
        wp_enqueue_style('splide-core', get_template_directory_uri() . '/js/slider/splide.min.css', array(), NAKED_VERSION, 'all');
    }
//    wp_enqueue_style('main.css', get_stylesheet_directory_uri() . '/assets/css/main.css');
    /*----End CSS----*/
    wp_enqueue_script( 'naked-jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js', array( 'jquery' ), NAKED_VERSION, true );
	// Подключаем Vue.js и Vuex после jQuery
    wp_enqueue_script( 'vue-js', get_template_directory_uri() . '/assets/vue_module/cdn/vue.global.min.js', array( 'naked-jquery' ), NAKED_VERSION, true );
    wp_enqueue_script( 'vuex-js', get_template_directory_uri() . '/assets/vue_module/cdn/vuex.global.min.js', array( 'vue-js' ), NAKED_VERSION, true );
        // add theme scripts vue
	wp_enqueue_script( 'vue-build', get_template_directory_uri() . '/assets/vue_module/dish/appVueBuild.js', array('vue-js', 'vuex-js'), NAKED_VERSION, true );
    // add theme scripts
    wp_enqueue_script( 'naked', get_template_directory_uri() . '/js/site.js', array(), NAKED_VERSION, true );

    
    // Подключаем скрипт Splide для галереи на странице недвижимости
    if (is_singular('property')) {
        wp_enqueue_script('splide-core', get_template_directory_uri() . '/js/slider/splide.min.js', array('jquery'), NAKED_VERSION, true);
    }

    /*----End JS----*/

}
add_action( 'wp_enqueue_scripts', 'naked_scripts' ); // Register this fxn and allow Wordpress to call it automatcally in the header

// Подключаем отдельный аякс для рубрики;
add_action('wp_enqueue_scripts', 'enqueue_ajax_pagination_scripts');

function enqueue_ajax_pagination_scripts() {
    wp_enqueue_script('ajax-pagination', get_template_directory_uri() . '/js/ajax-pagination.js', array('jquery'), null, true);
    wp_localize_script('ajax-pagination', 'ajaxpagination', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}

// Подключаем скрипты и стили для фильтра недвижимости
add_action('wp_enqueue_scripts', 'enqueue_property_filter_scripts');

function enqueue_property_filter_scripts() {
    // Подключаем Google Maps API только на странице архива недвижимости и на странице одиночного объекта
    if (is_post_type_archive('property') || is_singular('property')) {
        // Получаем API-ключ из настроек
        $api_key = get_option('google_maps_api_key', '');
        if (!empty($api_key)) {
            wp_enqueue_script('google-maps-api', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key, array(), null, false);
        } else {
            // Если ключ не задан, используем без ключа (ограниченная функциональность)
            wp_enqueue_script('google-maps-api', 'https://maps.googleapis.com/maps/api/js', array(), null, false);
        }
        
        // Подключаем скрипт для работы с картами на странице архива и одиночной странице
        wp_enqueue_script('google-maps-property', get_template_directory_uri() . '/js/google-maps-property.js', array('jquery'), null, true);
    }
}

// Подключаем базовый класс темы
require get_template_directory() . '/inc/core/RealtyCore.php';

// Подключаем вспомогательные функции
require get_template_directory() . '/inc/helpers/helpers.php';

// Подключаем фичу Недвижимости
require get_template_directory() . '/inc/features/property/property.php';

// Инициализация фичи Недвижимости
$propertyFeature = PropertyFeature::getInstance();
$propertyFeature->init();

// Админка часть;
require get_template_directory() . '/inc/admin/admin-function.php';
// Подключаем файл с регистрацией CPT и таксономий
require get_template_directory() . '/inc/admin/pods-registration.php';
// Подключаем общие константы для админки
require get_template_directory() . '/inc/admin/shared/const_params.php';
// Подключаем страницу управления справочником характеристик
require get_template_directory() . '/inc/admin/settings/characteristics-settings.php';
// Метабокс характеристик
require get_template_directory() . '/inc/admin/settings/property-characteristics-metabox.php';
// Подключаем универсальную страницу настроек
require get_template_directory() . '/inc/admin/settings/index-settings.php';

// ============================================================
// Обратная совместимость - создаём глобальные функции-обёртки
// ============================================================

/**
 * Универсальная функция для получения списка недвижимости
 * Обёртка для PropertyFeature::getList()
 */
function properties_list($params = array()) {
    return PropertyFeature::getInstance()->getList($params);
}

/**
 * Рендер формы фильтрации (Vue компонент)
 */
function property_filter_render_form($context = 'default') {
    return PropertyFeature::getInstance()->shortcodeFilter(['context' => $context]);
}

/**
 * Рендер формы фильтрации для главной страницы
 */
function property_filter_render_home_form() {
    return PropertyFeature::getInstance()->renderHomeForm();
}

/**
 * Рендер списка недвижимости
 */
function property_render_list() {
    return PropertyFeature::getInstance()->property_filter_render_list();
}

/**
 * Получение данных активных фильтров
 */
function get_current_filters_data() {
    return PropertyFeature::getInstance()->getCurrentFilters();
}

// Добавляем проверку валидности параметров фильтрации при загрузке страницы
add_action('template_redirect', 'validate_property_search_parameters');

function validate_property_search_parameters() {
    // Проверяем, находимся ли мы на странице архива недвижимости и есть ли GET-параметры
    if (is_post_type_archive('property') && !empty($_GET)) {
        $errors = array();
        
        // Проверяем даты, если они переданы
        if (isset($_GET['checkin_date']) && !empty($_GET['checkin_date'])) {
            $checkin_errors = validate_property_dates(sanitize_text_field($_GET['checkin_date']),
                                                   isset($_GET['checkout_date']) ? sanitize_text_field($_GET['checkout_date']) : '');
            $errors = array_merge($errors, $checkin_errors);
        }
        
        // Проверяем количество гостей, если они переданы
        if ((isset($_GET['adults']) && !empty($_GET['adults'])) || (isset($_GET['children']) && !empty($_GET['children']))) {
            $guest_errors = validate_guest_numbers(
                isset($_GET['adults']) ? intval($_GET['adults']) : 0,
                isset($_GET['children']) ? intval($_GET['children']) : 0
            );
            $errors = array_merge($errors, $guest_errors);
        }
        
        // Если есть ошибки валидации, показываем сообщение
        if (!empty($errors)) {
            // Сохраняем ошибки во временное хранилище
            set_transient('property_search_errors', $errors, 30); // Храним 30 секунд
            
            // Перенаправляем на ту же страницу без невалидных параметров
            $redirect_url = remove_query_arg(array_keys($_GET), $_SERVER['REQUEST_URI']);
            wp_redirect($redirect_url);
            exit;
        }
    }
}

// Функция для получения ошибок валидации
function get_property_search_errors() {
    $errors = get_transient('property_search_errors');
    if ($errors) {
        delete_transient('property_search_errors'); // Удаляем после получения
        return $errors;
    }
    return array();
}

// Функция для вывода хлебных крошек
function custom_breadcrumbs($current_page = '') {
    echo '<nav class="breadcrumb__com" aria-label="breadcrumb">';
    echo '<a href="' . esc_url(home_url('/')) . '">' . esc_html__('Главная', 'naked') . '</a> <span>/</span> ';
    if (!empty($current_page)) {
        echo '<span class="breadcrumb_name">' . esc_html($current_page) . '</span>';
    }
    echo '</nav>';
}

// REST API эндпоинт для характеристик недвижимости
add_action('rest_api_init', function() {
    register_rest_route('property/v1', '/characteristics/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'realty_get_property_characteristics_api',
        'permission_callback' => '__return_true',
    ]);
});

function realty_get_property_characteristics_api($request) {
    $property_id = $request->get_param('id');

    $characteristics = get_post_meta($property_id, 'property_characteristics', true);
    if (!is_array($characteristics)) {
        return new WP_REST_Response(['groups' => []], 200);
    }

    usort($characteristics, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

    $groups_data = [];

    foreach ($characteristics as $item) {
        $char_id = absint($item['characteristic_id'] ?? 0);
        if (!$char_id) continue;

        $char = get_post($char_id);
        if (!$char || $char->post_status !== 'publish') continue;

        $terms = get_the_terms($char_id, 'char_group');
        $group_id = !empty($terms) ? $terms[0]->term_id : 0;

        if ($group_id) {
            $group = $terms[0];
            $display_style = get_term_meta($group_id, 'display_style', true) ?: 'standard';
            $type_ui = get_term_meta($group_id, 'type_ui', true) ?: 'checkbox';

            if (!isset($groups_data[$group_id])) {
                $groups_data[$group_id] = [
                    'id' => $group_id,
                    'name' => $group->name,
                    'slug' => $group->slug,
                    'description' => $group->description,
                    'display_style' => $display_style,
                    'type_ui' => $type_ui,
                    'active' => get_term_meta($group_id, 'active', true) ?: 1,
                    'sort_order' => get_term_meta($group_id, 'sort_order', true) ?: 0,
                    'use_in_filters' => get_term_meta($group_id, 'use_in_filters', true) ?: 1,
                    'show_in_archive' => get_term_meta($group_id, 'show_in_archive', true) ?: 0,
                    'characteristics' => [],
                ];
            }

            $groups_data[$group_id]['characteristics'][] = [
                'id' => $char_id,
                'title' => $char->post_title,
                'content' => $char->post_content,
                'icon' => get_post_meta($char_id, 'icon', true),
                'icon_type' => get_post_meta($char_id, 'icon_type', true) ?: 'material_symbol',
                'media_id' => get_post_meta($char_id, 'media_id', true),
                'label' => get_post_meta($char_id, 'label', true),
                'value' => get_post_meta($char_id, 'value', true),
                'hint' => get_post_meta($char_id, 'hint', true),
                'style' => get_term_meta($group_id, 'display_style', true) ?: 'standard',
                'sort_order' => $item['order'] ?? 0,
                'active' => $char->post_status === 'publish' ? 1 : 0,
            ];
        }
    }

    $groups = array_values($groups_data);

    return new WP_REST_Response(['groups' => $groups, 'total' => count($characteristics)], 200);
}

// AJAX обработчик для автозаполнения локаций
add_action('wp_ajax_get_locations_autocomplete', 'get_locations_autocomplete_callback');
add_action('wp_ajax_nopriv_get_locations_autocomplete', 'get_locations_autocomplete_callback');

function get_locations_autocomplete_callback() {
    // Проверяем nonce
    if (!wp_verify_nonce($_POST['nonce'], 'property_filter_nonce')) {
        wp_die('Security check failed');
    }
    
    $term = sanitize_text_field($_POST['term']);
    
    // Получаем термы из таксономии location
    $locations = get_terms(array(
        'taxonomy' => 'location',
        'hide_empty' => false,
        'search' => $term, // Ищем по любому совпадению в названии
        'number' => 15 // Увеличиваем количество результатов для лучшего выбора
    ));
    
    $results = array();
    if (!is_wp_error($locations)) {
        foreach ($locations as $location) {
            $results[] = array(
                'name' => $location->name,
                'slug' => $location->slug
            );
        }
        
        
        // Сортируем результаты: сначала те, которые начинаются с введенного термина
        usort($results, function($a, $b) use ($term) {
            $a_starts_with = stripos($a['name'], $term) === 0;
            $b_starts_with = stripos($b['name'], $term) === 0;
            
            // Если оба начинаются с термина или оба нет - сортируем по алфавиту
            if ($a_starts_with == $b_starts_with) {
                return strcasecmp($a['name'], $b['name']);
            }
            
            // Те, что начинаются с термина, идут первыми
            return $a_starts_with ? -1 : 1;
        });
        
        // Ограничиваем количество результатов до 10 после сортировки
        $results = array_slice($results, 0, 10);
    }
    
    wp_send_json_success($results);
}