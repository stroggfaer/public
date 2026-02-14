<?php
// Логотип;
function blog_theme_setup() {
    add_theme_support('custom-logo', array(
        'height'      => 600,
        'width'       => 600,
        'flex-height' => true,
        'flex-width'  => true,
    ));
}
add_action('after_setup_theme', 'blog_theme_setup');

function custom_widgets_init() {
    register_sidebar(array(
        'name'          => __('Контентная область для виджеты', 'text_domain'),
        'id'            => 'content-custom-widget',
        'description'   => __('Добавьте сюда свои виджеты.', 'text_domain'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    register_sidebar(array(
        'name'          => __('Виджет в шапке', 'text_domain'),
        'id'            => 'top-header-custom-widget',
        'description'   => __('Добавьте сюда свои виджеты.', 'text_domain'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    register_sidebar(array(
        'name'          => __('Виджет для поста', 'text_domain'),
        'id'            => 'post-widget',
        'description'   => __('Добавьте сюда свои виджеты.', 'text_domain'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    register_sidebar(array(
        'name'          => __('Футер виджет 1', 'text_domain'),
        'id'            => 'footer-widget-1',
        'description'   => __('Добавьте сюда свои виджеты.', 'text_domain'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    register_sidebar(array(
        'name'          => __('Футер виджет 2', 'text_domain'),
        'id'            => 'footer-widget-2',
        'description'   => __('Добавьте сюда свои виджеты.', 'text_domain'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    register_sidebar(array(
        'name'          => __('Футер виджет 3', 'text_domain'),
        'id'            => 'footer-widget-3',
        'description'   => __('Добавьте сюда свои виджеты.', 'text_domain'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    register_sidebar(array(
        'name'          => __('Футер виджет 4', 'text_domain'),
        'id'            => 'footer-widget-4',
        'description'   => __('Добавьте сюда свои виджеты.', 'text_domain'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}

add_action('widgets_init', 'custom_widgets_init');


function str_phone($str) {
    if(empty($str)) return '';
    return preg_replace('/[^0-9]/', '', $str);
}

function custom_search_form($form) {
    $form = '<form role="search" method="get" class="search-form" action="' . esc_url(home_url('/')) . '">
        <label>
            <span class="screen-reader-text">Поиск по сайту:</span>
            <input type="search" class="search-field" placeholder="Введите запрос..." value="' . get_search_query() . '" name="s" />
            <button type="submit">
                <svg width="20" height="20" fill="#ff3e17" class="dgwt-wcas-ico-magnifier" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 51.539 51.361" xml:space="preserve">
		             <path fill="#ff3e17" d="M51.539,49.356L37.247,35.065c3.273-3.74,5.272-8.623,5.272-13.983c0-11.742-9.518-21.26-21.26-21.26 S0,9.339,0,21.082s9.518,21.26,21.26,21.26c5.361,0,10.244-1.999,13.983-5.272l14.292,14.292L51.539,49.356z M2.835,21.082 c0-10.176,8.249-18.425,18.425-18.425s18.425,8.249,18.425,18.425S31.436,39.507,21.26,39.507S2.835,31.258,2.835,21.082z"></path>
				</svg>
            </button>
        </label>
        <!-- Удаляем кнопку отправки -->
    </form>';
    return $form;
}
add_filter('get_search_form', 'custom_search_form');

/*--Пагинация--*/
// пагинация для гл страниа рубрики без перезагрузки;
add_action('wp_ajax_load_posts', 'load_posts_ajax_handler');
add_action('wp_ajax_nopriv_load_posts', 'load_posts_ajax_handler');
// Рубрика статьи;
function load_posts_ajax_handler() {
    // Определяем текущую страницу
    $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : get_option('custom_per_page', 12);
    $query = query_post(['paged'=>$paged], $per_page);
    if (!empty($query) && $query->have_posts()):
        locate_template('template-parts/component/post-list.php', true, false, array('query' => $query, 'paged' => $paged, 'per_page' => $per_page));
    else:
        echo '<div class="empty_text">' . __('Записей не найдено.') . '</div>';
    endif;
    wp_reset_postdata();
    wp_die();
}
/*--./Пагинация--*/
// Задайте максимальное количество символов
function custom_excerpt_length($post_excerpt) {
    $max_length = 240; // Задайте максимальное количество символов
    $post_excerpt = wp_strip_all_tags($post_excerpt);
    if (strlen($post_excerpt) > $max_length) {
        $post_excerpt = substr($post_excerpt, 0, strpos($post_excerpt, ' ', $max_length)) . '[...]'; // Обрезаем до ближайшего пробела
    }
    return $post_excerpt;
}

add_filter('the_excerpt', 'custom_excerpt_length');

function custom_category_menu() {
    $categories = get_categories(array(
        'hide_empty' => false, // Показывать даже пустые категории
    ));

    // Получаем текущий объект (категорию)
    $current_category = get_queried_object();

    // Проверка на ошибки текущей категории
    if (is_wp_error($current_category)) {
        return '<p>Ошибка при получении текущей категории.</p>';
    }

    function get_top_parent_category($category) {
        if (empty($category) || is_wp_error($category)) return null;

        // Если у текущей категории нет родителя, возвращаем её
        if ($category->parent == 0) {
            return $category;
        }

        // Пока у категории есть родитель, продолжаем искать
        while ($category->parent != 0) {
            $parent_category = get_category($category->parent);
            if (is_wp_error($parent_category)) {
                return null; // Возвращаем null в случае ошибки
            }
            $category = $parent_category;
        }
        return $category; // Возвращаем самую верхнюю категорию
    }

    // Получаем самую верхнюю родительскую категорию
    $top_category = get_top_parent_category($current_category);

    // Рекурсивная функция для построения дерева категорий
    function build_category_tree($categories, $parent_id = 0, $current_category = null, $top_category = null) {
        $output = '<ul class="parent-menu">';

        foreach ($categories as $category) {
            if ($category->parent == $parent_id) {
                // Проверяем, есть ли у текущей категории подкатегории
                $has_children = false;

                foreach ($categories as $sub_category) {
                    if ($sub_category->parent == $category->term_id) {
                        $has_children = true;
                        break;
                    }
                }

                // Определяем активные классы для категорий
                $is_active_parent = (!empty($top_category) && $top_category->term_id === $category->term_id) ? 'parent-active' : '';
                $is_active = ($current_category && $current_category->term_id === $category->term_id) ? ' active' : '';

                if ($current_category && $current_category->parent == $category->term_id) {
                    $is_active .= ' parent-active';
                }

                // Создаем пункт меню с классом "active", если это текущая категория
                $output .= '<li class="menu-item category-item' . esc_attr($is_active) . ' ' . esc_attr($is_active_parent) . '" data-id="' . esc_attr($category->term_id) . '">';

                // Добавляем стрелку только если есть подкатегории
                if ($has_children) {
                    $output .= '<span class="toggle-arrow">
                                <svg width="18px" height="18px" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 16.5V8.5L16 12.5L10 16.5Z" fill="#121923"/>
                                </svg>
                                </span>';
                }

                // Добавляем ссылку на категорию
                $output .= '<a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a>';

                // Рекурсивно добавляем подкатегории
                if ($has_children) {
                    $output .= build_category_tree($categories, $category->term_id, $current_category, $top_category);
                }

                $output .= '</li>';
            }
        }

        $output .= '</ul>';
        return $output;
    }

    return build_category_tree($categories, 0, $current_category, $top_category);
}


// Регистрация шорткода для вывода меню категорий
function custom_category_menu_shortcode() {
    // Возвращаем результат работы функции custom_category_menu
    return custom_category_menu();
}
// Регистрируем шорткод [custom_category_menu]
add_shortcode('custom_category_menu', 'custom_category_menu_shortcode');

// доработка хлебня крошка;
function custom_breadcrumbs($name = '') {
    $separator = ' / '; // Разделитель между "крошками"
    $home = 'Главная'; // Название ссылки на главную страницу
    echo '<div class="breadcrumb__com">';

    // Сначала добавляем ссылку на главную страницу
    echo '<a href="' . get_home_url() . '">' . $home . '</a>' . $separator;

    // Проверяем, является ли текущая страница постом
    if (is_single()) {
        // Получаем категории текущего поста
        $categories = get_the_category();
        if ($categories) {
            // Берем первую категорию
            $category = $categories[0];

            // Проверяем родительскую иерархию категорий
            $parents = get_category_parents($category, true, $separator);
            if (!is_wp_error($parents)) {
                echo $parents;
            }
        }

        // Выводим название текущего поста
        echo '<span class="breadcrumb_name">';
        the_title();
        echo '</span>';

    } elseif (is_page()) {
        // Если текущая страница — это страница
        $post = get_post();
        if ($post->post_parent) {
            // Если страница имеет родительскую страницу, получаем все её родительские страницы
            $parent_id = $post->post_parent;
            $breadcrumbs = array();
            while ($parent_id) {
                $page = get_post($parent_id);
                $breadcrumbs[] = '<a href="' . esc_url(get_permalink($page->ID)) . '">' . get_the_title($page->ID) . '</a>';
                $parent_id = $page->post_parent;
            }
            $breadcrumbs = array_reverse($breadcrumbs);
            foreach ($breadcrumbs as $crumb) {
                echo $crumb . $separator;
            }
        }
        // Выводим название текущей страницы
        echo '<span class="breadcrumb_name">';
        the_title();
        echo '</span>';

    } elseif (is_category()) {
        // Если текущая страница — это категория
        $current_category = get_queried_object();
        if ($current_category->parent) {
            // Если категория имеет родителя
            $parents = get_category_parents($current_category->parent, true, $separator);
            if (!is_wp_error($parents)) {
                echo $parents;
            }
        }
        echo '<span class="breadcrumb_name">' . single_cat_title('', false) . '</span>';

    } else {
        if ($name) {
            echo '<span class="breadcrumb_name">';
            echo $name;
            echo '</span>';
        }
    }

    echo '</div>';
}

function getPostMenuActive() {
    $category = get_the_category();
    $elementId = '';

    if (!empty( $category ) ) {
        $current_category = $category[0];
        switch ($current_category->term_id) {
            case 6:
                $elementId = 'progress';
                break;
            default:
                break;
        }
    }
    return $elementId;
}

// Добавление опции per_page с дефолтным значением 12
add_action('after_setup_theme', function() {
    if (!get_option('custom_per_page')) {
        update_option('custom_per_page', 6);
    }
});
// Запрос рубрика;
function query_post($args = [], $per_page = null) {
    global $wpdb;

    // Устанавливаем количество постов на страницу
    $per_page = !empty($per_page) ? intval($per_page) : intval(get_option('custom_per_page', 12));
    // Базовые параметры для WP_Query
    $default_args = [
        'post_type'      => 'post',
        'posts_per_page' => $per_page,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'paged'          => !empty($args['paged']) ? intval($args['paged']) : max(1, get_query_var('paged', 1)),
    ];

    // Добавляем фильтрацию по текущей категории, если находимся на странице категории
    if (is_category()) {
        $current_category = get_queried_object();
        if ($current_category && isset($current_category->term_id)) {
            $default_args['cat'] = intval($current_category->term_id);
        }
    }

    // Обрабатываем поисковый запрос
    if (!empty($_GET['s'])) {
        $search_query = sanitize_text_field($_GET['s']);
        $default_args['s'] = $search_query;
        $default_args['post_status'] = 'publish';

        // Используем фильтр для поиска только по заголовкам
        add_filter('posts_where', function($where) use ($wpdb, $search_query) {
            $search_query = esc_sql($search_query);
            return $where . " AND {$wpdb->posts}.post_title LIKE '%$search_query%'";
        }, 10, 1);

        // Удаляем фильтр после запроса
        add_action('wp_query', function () {
            remove_filter('posts_where', '__return_false', 10, 1);
        });
    }

    // Объединяем переданные аргументы с базовыми
    $query_args = array_merge($default_args, $args);

    // Выполняем запрос и возвращаем объект WP_Query
    return new WP_Query($query_args);
}

/**
 * Получает данные галереи Pods
 * - cover_only = true  → возвращает один элемент (обложка или заглушка)
 * - cover_only = false → возвращает массив всех элементов (или пустой)
 *
 * @param int|object $post_or_pod  ID поста / Pods-объект / WP_Post
 * @param string     $size         Размер изображения ('large', 'medium', 'full'…)
 * @param bool       $cover_only   true = только первое фото / заглушка
 * @return array                   один элемент или массив элементов
 */
function pod_get_gallery_data($post_or_pod, string $size = 'large', bool $cover_only = false): array {
    // Нормализация входных данных → получаем Pods и post_id
    if (is_numeric($post_or_pod)) {
        $post_id = (int) $post_or_pod;
        $pod = function_exists('pods') ? pods('property', $post_id) : null;
    } elseif (is_object($post_or_pod)) {
        if (method_exists($post_or_pod, 'field')) {
            $pod = $post_or_pod;
            $post_id = $pod->id() ?: get_the_ID();
        } else {
            $post_id = $post_or_pod->ID ?? get_the_ID();
            $pod = function_exists('pods') ? pods('property', $post_id) : null;
        }
    } else {
        $post_id = get_the_ID();
        $pod = function_exists('pods') ? pods('property', $post_id) : null;
    }

    // Нет Pods или невалидный → заглушка или пусто
    if (!$pod || !$pod->valid()) {
        return $cover_only ? pod_placeholder($post_id) : [];
    }

    $raw = $pod->field('gallery');
    if (empty($raw)) {
        return $cover_only ? pod_placeholder($post_id) : [];
    }

    $items = (array) $raw;
    $title = get_the_title($post_id);

    foreach ($items as $item) {
        $id = 0;
        if (is_array($item))     $id = (int) ($item['ID']   ?? 0);
        elseif (is_object($item)) $id = (int) ($item->ID    ?? 0);
        elseif (is_numeric($item)) $id = (int) $item;

        if ($id < 1) continue;

        $img = wp_get_attachment_image_src($id, $size);
        if (!$img || empty($img[0])) continue;

        $data = [
            'id'     => $id,
            'src'    => esc_url($img[0]),
            'alt'    => esc_attr(get_post_meta($id, '_wp_attachment_image_alt', true) ?: $title),
            'width'  => (int) ($img[1] ?? 0),
            'height' => (int) ($img[2] ?? 0),
        ];

        if ($cover_only) {
            return $data;  // ← сразу возвращаем один элемент
        }

        $result[] = $data;
    }

    // Если ничего не нашли и cover_only → заглушка
    if ($cover_only) {
        return pod_placeholder($post_id);
    }

    return $result ?? [];
}

/** Заглушка в формате элемента галереи */
function pod_placeholder(int $post_id): array {
    $url = empty_img_placeholder();
    $title = get_the_title($post_id);
    return [
        'id'     => 0,
        'src'    => $url,
        'alt'    => esc_attr($title),
        'width'  => 0,
        'height' => 0,
    ];
}

function empty_img_placeholder() : string {
   return esc_url(get_template_directory_uri() . '/assets/images/no_image.jpg');
}