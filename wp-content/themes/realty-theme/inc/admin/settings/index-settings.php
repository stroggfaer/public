<?php
/**
 * Универсальная страница настроек темы
 * Доступно в админке: Недвижимость → Настройки
 *
 * @package RealtyTheme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Регистрация страницы настроек в админке
 *
 * @since 1.0.0
 */
function realty_theme_add_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=property',
        __( 'Настройки темы', 'realty-theme' ),
        __( 'Настройки', 'realty-theme' ),
        'manage_options',
        'realty-theme-settings',
        'realty_theme_settings_page'
    );
}
add_action( 'admin_menu', 'realty_theme_add_admin_menu' );

/**
 * Регистрация CSS стилей для страницы настроек
 *
 * @param string $hook Текущий хук страницы.
 * @since 1.0.0
 */
function realty_theme_admin_styles( $hook ) {
    // Подключаем Material Symbols только на странице настроек темы
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'realty-theme-settings' ) {
        wp_enqueue_style(
            'material-symbols-outlined',
            'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200',
            [],
            null
        );
    }
}
add_action( 'admin_enqueue_scripts', 'realty_theme_admin_styles' );

/**
 * Регистрация всех настроек
 *
 * @since 1.0.0
 */
function realty_theme_register_settings() {
    // Настройки фильтрации
    register_setting( 'realty_theme_filter_settings', 'property_active_filters' );
    register_setting( 'realty_theme_filter_settings', 'property_price_range' );

    // Настройки карты
    register_setting( 'realty_theme_maps_settings', 'google_maps_api_key' );
    register_setting( 'realty_theme_maps_settings', 'realty_map_zoom' );
    register_setting( 'realty_theme_maps_settings', 'realty_map_height' );
    register_setting( 'realty_theme_maps_settings', 'realty_default_lat' );
    register_setting( 'realty_theme_maps_settings', 'realty_default_lng' );

    // Настройки главной страницы
    register_setting( 'realty_theme_home_settings', 'realty_home_title' );
    register_setting( 'realty_theme_home_settings', 'realty_home_subtitle' );
    register_setting( 'realty_theme_home_settings', 'realty_home_cta_text' );
    register_setting( 'realty_theme_home_settings', 'realty_home_popular_tags' );

    // Настройки темы
    register_setting( 'realty_theme_theme_settings', 'realty_logo_type' );
    register_setting( 'realty_theme_theme_settings', 'realty_company_name' );
    register_setting( 'realty_theme_theme_settings', 'realty_copyright_text' );
}
add_action( 'admin_init', 'realty_theme_register_settings' );

/**
 * Проверка nonce для фильтров
 *
 * @return bool
 * @since 1.0.0
 */
function realty_verify_filter_nonce() {
    return isset( $_POST['realty_filter_nonce'] ) 
        && wp_verify_nonce( $_POST['realty_filter_nonce'], 'realty_save_filter_settings' );
}

/**
 * Проверка nonce для карты
 *
 * @return bool
 * @since 1.0.0
 */
function realty_verify_maps_nonce() {
    return isset( $_POST['realty_maps_nonce'] ) 
        && wp_verify_nonce( $_POST['realty_maps_nonce'], 'realty_save_maps_settings' );
}

/**
 * Отображение страницы настроек
 *
 * @since 1.0.0
 */
function realty_theme_settings_page() {
    // Проверка прав доступа
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'У вас недостаточно прав для доступа к этой странице.', 'realty-theme' ) );
    }

    // Определяем активный таб с санитизацией
    $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'filter';

    // Обработка сохранения настроек
    $message      = '';
    $notice_class = 'notice-success';

    if ( isset( $_POST['realty_settings_nonce'] ) && wp_verify_nonce( $_POST['realty_settings_nonce'], 'realty_save_settings' ) ) {
        // Сохраняем настройки в зависимости от таба
        if ( $active_tab === 'filter' && realty_verify_filter_nonce() ) {
            realty_theme_save_filter_settings();
            $message = __( 'Настройки фильтрации успешно сохранены!', 'realty-theme' );
        } elseif ( $active_tab === 'maps' && realty_verify_maps_nonce() ) {
            realty_theme_save_maps_settings();
            $message = __( 'Настройки карты успешно сохранены!', 'realty-theme' );
        } elseif ( $active_tab === 'home' ) {
            realty_theme_save_home_settings();
            $message = __( 'Настройки главной страницы успешно сохранены!', 'realty-theme' );
        } elseif ( $active_tab === 'theme' ) {
            realty_theme_save_theme_settings();
            $message = __( 'Настройки темы успешно сохранены!', 'realty-theme' );
        }
    }

    ?>
    <div class="wrap realty-settings-wrap">
        <h1><?php echo esc_html__( 'Настройки темы Недвижимость', 'realty-theme' ); ?></h1>

        <?php if ( $message ) : ?>
            <div class="notice <?php echo esc_attr( $notice_class ); ?> is-dismissible">
                <p><?php echo esc_html( $message ); ?></p>
            </div>
        <?php endif; ?>

        <!-- Навигация по табам -->
        <h2 class="nav-tab-wrapper">
            <a href="?post_type=property&page=realty-theme-settings&tab=home" class="nav-tab <?php echo 'home' === $active_tab ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Главная', 'realty-theme' ); ?>
            </a>
            <a href="?post_type=property&page=realty-theme-settings&tab=theme" class="nav-tab <?php echo 'theme' === $active_tab ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Темы', 'realty-theme' ); ?>
            </a>
            <a href="?post_type=property&page=realty-theme-settings&tab=filter" class="nav-tab <?php echo 'filter' === $active_tab ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Фильтрация', 'realty-theme' ); ?>
            </a>
            <a href="?post_type=property&page=realty-theme-settings&tab=maps" class="nav-tab <?php echo 'maps' === $active_tab ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'API Карта', 'realty-theme' ); ?>
            </a>
        </h2>

        <form method="post" action="">
            <?php wp_nonce_field( 'realty_save_settings', 'realty_settings_nonce' ); ?>

            <div class="realty-settings-content">
                <?php
                // Подключаем контент соответствующего таба
                $tab_file = realty_theme_get_settings_tab_path( $active_tab );
                if ( file_exists( $tab_file ) ) {
                    include $tab_file;
                } else {
                    echo '<p>' . esc_html__( 'Настройки не найдены.', 'realty-theme' ) . '</p>';
                }
                ?>
            </div>

            <?php submit_button( __( 'Сохранить настройки', 'realty-theme' ) ); ?>
        </form>
    </div>

    <style>
        .realty-settings-wrap {
            background: #fff;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .realty-settings-content {
            margin-top: 20px;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }
        .realty-settings-content h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #c3c4c7;
        }
        .realty-tab-description {
            background: #e7f5ff;
            padding: 10px 15px;
            border-left: 4px solid #0073aa;
            margin-bottom: 20px;
        }
        .realty-price-range-table {
            width: 100%;
            max-width: 400px;
        }
        .realty-price-range-table th {
            text-align: left;
            padding-right: 10px;
            width: 140px;
        }
    </style>
    <?php
}

/**
 * Получение пути к файлу таба настроек
 *
 * @param string $tab Имя таба.
 * @return string Путь к файлу таба.
 * @since 1.0.0
 */
function realty_theme_get_settings_tab_path( $tab ) {
    $dir = get_template_directory() . '/inc/admin/settings/';
    $files = array(
        'home'   => $dir . 'home-settings.php',
        'theme'  => $dir . 'theme-settings.php',
        'filter' => $dir . 'filter-settings.php',
        'maps'   => $dir . 'maps-settings.php',
    );

    return isset( $files[ $tab ] ) ? $files[ $tab ] : '';
}

/**
 * Сохранение настроек фильтрации
 *
 * @since 1.0.0
 */
function realty_theme_save_filter_settings() {
    // Проверка nonce
    if ( ! realty_verify_filter_nonce() ) {
        return;
    }

    // Сохраняем активные фильтры
    if ( isset( $_POST['property_active_filters'] ) && is_array( $_POST['property_active_filters'] ) ) {
        $active_filters = array_map( 'sanitize_text_field', (array) $_POST['property_active_filters'] );
        update_option( 'property_active_filters', $active_filters );
    } else {
        update_option( 'property_active_filters', array() );
    }

    // Сохраняем настройки диапазонов цен
    if ( isset( $_POST['property_price_range'] ) && is_array( $_POST['property_price_range'] ) ) {
        $price_range = array(
            'min'  => isset( $_POST['property_price_range']['min'] ) ? intval( $_POST['property_price_range']['min'] ) : 0,
            'max'  => isset( $_POST['property_price_range']['max'] ) ? intval( $_POST['property_price_range']['max'] ) : 1000000,
            'step' => isset( $_POST['property_price_range']['step'] ) ? intval( $_POST['property_price_range']['step'] ) : 1000
        );
        update_option( 'property_price_range', $price_range );
    }
}

/**
 * Сохранение настроек карты
 *
 * @since 1.0.0
 */
function realty_theme_save_maps_settings() {
    // Проверка nonce
    if ( ! realty_verify_maps_nonce() ) {
        return;
    }

    if ( isset( $_POST['google_maps_api_key'] ) ) {
        update_option( 'google_maps_api_key', sanitize_text_field( wp_unslash( $_POST['google_maps_api_key'] ) ) );
    }
    if ( isset( $_POST['realty_map_zoom'] ) ) {
        update_option( 'realty_map_zoom', intval( $_POST['realty_map_zoom'] ) );
    }
    if ( isset( $_POST['realty_map_height'] ) ) {
        update_option( 'realty_map_height', intval( $_POST['realty_map_height'] ) );
    }
    if ( isset( $_POST['realty_default_lat'] ) ) {
        update_option( 'realty_default_lat', sanitize_text_field( wp_unslash( $_POST['realty_default_lat'] ) ) );
    }
    if ( isset( $_POST['realty_default_lng'] ) ) {
        update_option( 'realty_default_lng', sanitize_text_field( wp_unslash( $_POST['realty_default_lng'] ) ) );
    }
}

/**
 * Сохранение настроек главной страницы
 *
 * @since 1.0.0
 */
function realty_theme_save_home_settings() {
    if ( isset( $_POST['realty_home_title'] ) ) {
        update_option( 'realty_home_title', sanitize_text_field( wp_unslash( $_POST['realty_home_title'] ) ) );
    }
    if ( isset( $_POST['realty_home_subtitle'] ) ) {
        update_option( 'realty_home_subtitle', sanitize_textarea_field( wp_unslash( $_POST['realty_home_subtitle'] ) ) );
    }
    if ( isset( $_POST['realty_home_cta_text'] ) ) {
        update_option( 'realty_home_cta_text', sanitize_text_field( wp_unslash( $_POST['realty_home_cta_text'] ) ) );
    }
    if ( isset( $_POST['realty_home_popular_tags'] ) && is_array( $_POST['realty_home_popular_tags'] ) ) {
        $tags = array_slice( array_map( 'absint', $_POST['realty_home_popular_tags'] ), 0, 10 );
        $tags = array_values( array_filter( $tags ) );
        update_option( 'realty_home_popular_tags', $tags );
    } else {
        update_option( 'realty_home_popular_tags', [] );
    }
}

/**
 * Сохранение настроек темы
 *
 * @since 1.0.0
 */
function realty_theme_save_theme_settings() {
    if ( isset( $_POST['realty_logo_type'] ) ) {
        update_option( 'realty_logo_type', sanitize_text_field( wp_unslash( $_POST['realty_logo_type'] ) ) );
    }
    if ( isset( $_POST['realty_company_name'] ) ) {
        update_option( 'realty_company_name', sanitize_text_field( wp_unslash( $_POST['realty_company_name'] ) ) );
    }
    if ( isset( $_POST['realty_copyright_text'] ) ) {
        update_option( 'realty_copyright_text', sanitize_text_field( wp_unslash( $_POST['realty_copyright_text'] ) ) );
    }
}

/**
 * Функции для получения настроек карты
 *
 * @return string API ключ Google Maps.
 * @since 1.0.0
 */
function realty_get_google_maps_api_key() {
    return get_option( 'google_maps_api_key', '' );
}

/**
 * Получение масштаба карты
 *
 * @return int Масштаб карты.
 * @since 1.0.0
 */
function realty_get_map_zoom() {
    return intval( get_option( 'realty_map_zoom', 12 ) );
}

/**
 * Получение высоты карты
 *
 * @return int Высота карты в пикселях.
 * @since 1.0.0
 */
function realty_get_map_height() {
    return intval( get_option( 'realty_map_height', 400 ) );
}

/**
 * Получение широты центра карты
 *
 * @return string Широта центра карты.
 * @since 1.0.0
 */
function realty_get_default_lat() {
    return get_option( 'realty_default_lat', '55.7558' );
}

/**
 * Получение долготы центра карты
 *
 * @return string Долгота центра карты.
 * @since 1.0.0
 */
function realty_get_default_lng() {
    return get_option( 'realty_default_lng', '37.6173' );
}

/**
 * Получение настроек главной страницы
 *
 * @return string Заголовок главной страницы.
 * @since 1.0.0
 */
function realty_get_home_title() {
    return get_option( 'realty_home_title', __( 'Найдите свой идеальный дом', 'realty-theme' ) );
}

/**
 * Получение подзаголовка главной страницы
 *
 * @return string Подзаголовок главной страницы.
 * @since 1.0.0
 */
function realty_get_home_subtitle() {
    return get_option( 'realty_home_subtitle', '' );
}

/**
 * Получение текста CTA кнопки
 *
 * @return string Текст кнопки.
 * @since 1.0.0
 */
function realty_get_home_cta_text() {
    return get_option( 'realty_home_cta_text', __( 'Начать поиск', 'realty-theme' ) );
}

/**
 * Получение массива ID популярных тегов (характеристик) для главной страницы
 *
 * @return int[] Массив ID характеристик.
 * @since 1.0.0
 */
function realty_get_home_popular_tags() {
    $ids = get_option( 'realty_home_popular_tags', [] );
    return is_array( $ids ) ? $ids : [];
}

/**
 * Получение характеристик с иконками для мультиселекта
 *
 * @return array[] Массив характеристик с полями id, title, label, icon, icon_type, media_id.
 * @since 1.0.0
 */
function realty_get_characteristics_with_icons() {
    $query = new WP_Query( [
        'post_type'      => 'characteristic',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'sort_order',
        'order'          => 'ASC',
    ] );

    $result = [];
    foreach ( $query->posts as $char ) {
        $icon      = get_post_meta( $char->ID, 'icon', true );
        $icon_type = get_post_meta( $char->ID, 'icon_type', true );
        $media_id  = (int) get_post_meta( $char->ID, 'media_id', true );

        // Фильтр: только с иконкой
        if ( ( $icon_type === 'upload' && $media_id ) || ( $icon_type !== 'upload' && $icon ) ) {
            $result[] = [
                'id'        => $char->ID,
                'title'     => $char->post_title,
                'label'     => get_post_meta( $char->ID, 'label', true ) ?: $char->post_title,
                'icon'      => $icon,
                'icon_type' => $icon_type,
                'media_id'  => $media_id,
            ];
        }
    }

    return $result;
}
