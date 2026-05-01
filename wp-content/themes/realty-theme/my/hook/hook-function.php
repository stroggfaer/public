<?php
/**
 * Хуки для модуля "Мой кабинет"
 * Модуль "Мой кабинет" для темы Realty Theme
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Регистрация шаблона личного кабинета
 */
function my_cabinet_register_template() {
    $template_path = get_template_directory() . '/my/dashboard.php';
    
    if ( file_exists( $template_path ) ) {
        // Основной маршрут /my/ - редирект на /my/dashboard/
        add_rewrite_rule( '^my/?$', 'index.php?my_cabinet_redirect=true', 'top' );
        
        // Маршрут /my/dashboard/ - главная страница ЛК
        add_rewrite_tag( '%my_cabinet%', 'true' );
        add_rewrite_rule( '^my/dashboard/?$', 'index.php?my_cabinet=true', 'top' );
        
        // Маршруты /my/{page}/ - другие страницы ЛК
        add_rewrite_tag( '%my_page%', '([^/]+)' );
        add_rewrite_rule( '^my/([^/]+)/?$', 'index.php?my_page=$matches[1]', 'top' );
    }
}
add_action( 'init', 'my_cabinet_register_template' );

/**
 * Добавление переменных запроса для шаблона
 */
function my_cabinet_add_query_var( $vars ) {
    $vars[] = 'my_cabinet';
    $vars[] = 'my_page';
    $vars[] = 'my_cabinet_redirect';
    return $vars;
}
add_filter( 'query_vars', 'my_cabinet_add_query_var' );

/**
 * Обработка редиректа /my/ -> /my/dashboard/
 */
function my_cabinet_handle_redirect() {
    if ( get_query_var( 'my_cabinet_redirect' ) ) {
        wp_safe_redirect( home_url( '/my/dashboard/' ), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'my_cabinet_handle_redirect', 1 );

/**
 * Подключение шаблона личного кабинета
 */
function my_cabinet_template_include( $template ) {
    // Главная страница /my/dashboard/
    if ( get_query_var( 'my_cabinet' ) ) {
        // Проверка авторизации: только для вошедших пользователей
        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( home_url( '/my-auth/' ) );
            exit;
        }
        
        $custom_template = get_template_directory() . '/my/dashboard.php';
        if ( file_exists( $custom_template ) ) {
            return $custom_template;
        }
    }
    
    // Другие страницы /my/{page}/
    $my_page = get_query_var( 'my_page' );
    if ( $my_page ) {
        // Проверка авторизации
        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( home_url( '/my-auth/' ) );
            exit;
        }
        
        // Поиск шаблона для страницы
        $page_template = get_template_directory() . '/my/' . sanitize_file_name( $my_page ) . '.php';
        if ( file_exists( $page_template ) ) {
            return $page_template;
        }
        
        // Если шаблон не найден, редирект на главную
        wp_safe_redirect( home_url( '/my/dashboard/' ), 302 );
        exit;
    }
    
    return $template;
}
add_filter( 'template_include', 'my_cabinet_template_include', 99 );

/**
 * Подключение скриптов модуля "Мой кабинет"
 */
function my_cabinet_enqueue_scripts() {
    // Добавьте скрипты при необходимости
}
add_action( 'wp_enqueue_scripts', 'my_cabinet_enqueue_scripts' );

// ============================================================
// Маршрут страницы авторизации /my-auth/
// ============================================================

/**
 * Регистрация rewrite-правила для страницы авторизации
 */
function my_cabinet_auth_register_rewrite() {
    add_rewrite_tag( '%my_cabinet_auth%', 'true' );
    add_rewrite_rule( '^my-auth/?$', 'index.php?my_cabinet_auth=true', 'top' );
}
add_action( 'init', 'my_cabinet_auth_register_rewrite' );

/**
 * Однократная очистка rewrite rules после добавления нового маршрута
 */
function my_cabinet_auth_flush_rewrite_rules() {
    if ( ! get_option( 'my_cabinet_auth_rewrite_flushed', false ) ) {
        flush_rewrite_rules( false );
        update_option( 'my_cabinet_auth_rewrite_flushed', true );
    }
}
add_action( 'init', 'my_cabinet_auth_flush_rewrite_rules', 20 );

/**
 * Очистка rewrite rules для обновления маршрутов /my/
 */
function my_cabinet_flush_my_routes_rewrite_rules() {
    // Флаг для отслеживания обновления маршрутов /my/
    if ( ! get_option( 'my_routes_updated_v2', false ) ) {
        flush_rewrite_rules( false );
        update_option( 'my_routes_updated_v2', true );
    }
}
add_action( 'init', 'my_cabinet_flush_my_routes_rewrite_rules', 25 );

/**
 * Добавление query var
 */
function my_cabinet_auth_query_var( $vars ) {
    $vars[] = 'my_cabinet_auth';
    return $vars;
}
add_filter( 'query_vars', 'my_cabinet_auth_query_var' );

/**
 * Подключение шаблона страницы авторизации
 */
function my_cabinet_auth_template_include( $template ) {
    if ( get_query_var( 'my_cabinet_auth' ) ) {
        $custom = get_template_directory() . '/my/auth.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $template;
}
add_filter( 'template_include', 'my_cabinet_auth_template_include', 99 );

/**
 * Проверка, что текущая страница — страница авторизации /my-auth/.
 */
function my_cabinet_is_auth_page() {
    if ( get_query_var( 'my_cabinet_auth' ) ) {
        return true;
    }

    if ( isset( $_SERVER['REQUEST_URI'] ) ) {
        $request_uri = wp_unslash( $_SERVER['REQUEST_URI'] );
        $request_path = trim( parse_url( $request_uri, PHP_URL_PATH ), '/' );
        if ( 'my-auth' === $request_path ) {
            return true;
        }
    }

    return false;
}

/**
 * Проверка, что текущая страница — страница личного кабинета /my/ или /my/*.
 */
function my_cabinet_is_cabinet_page() {
    if ( get_query_var( 'my_cabinet' ) || get_query_var( 'my_page' ) || get_query_var( 'my_cabinet_redirect' ) ) {
        return true;
    }

    if ( isset( $_SERVER['REQUEST_URI'] ) ) {
        $request_uri = wp_unslash( $_SERVER['REQUEST_URI'] );
        $request_path = trim( parse_url( $request_uri, PHP_URL_PATH ), '/' );
        
        // Проверяем /my/ и /my/*
        if ( 'my' === $request_path || strpos( $request_path, 'my/' ) === 0 ) {
            return true;
        }
    }

    return false;
}

/**
 * Фолбэк на случай, если rewrite-правило не сработало.
 * Позволяет выводить страницу /my-auth/ даже без перезаписи правил.
 */
function my_cabinet_auth_template_redirect() {
    if ( my_cabinet_is_auth_page() ) {
        $custom = get_template_directory() . '/my/auth.php';
        if ( file_exists( $custom ) ) {
            status_header( 200 );
            include $custom;
            exit;
        }
    }
}
add_action( 'template_redirect', 'my_cabinet_auth_template_redirect' );

/**
 * Фолбэк для защиты личного кабинета от неавторизованных пользователей.
 * Срабатывает даже если rewrite-правило не применило шаблон.
 */
function my_cabinet_protect_cabinet_page() {
    if ( my_cabinet_is_cabinet_page() && ! is_user_logged_in() ) {
        wp_safe_redirect( home_url( '/my-auth/' ) );
        exit;
    }
}
add_action( 'template_redirect', 'my_cabinet_protect_cabinet_page', 5 );


// ============================================================
// Скрытие WordPress Admin Bar для подписчиков
// ============================================================

/**
 * Отключение WordPress Admin Bar для пользователей с ролью Subscriber
 * Admin Bar показывается только для администраторов, редакторов и авторов
 */
function my_cabinet_disable_admin_bar_for_subscribers() {
    // Если пользователь не авторизован - admin bar и так скрыт
    if ( ! is_user_logged_in() ) {
        return;
    }
    
    $user = wp_get_current_user();
    
    // Скрываем admin bar для подписчиков
    if ( in_array( 'subscriber', (array) $user->roles, true ) ) {
        show_admin_bar( false );
    }
}
add_action( 'after_setup_theme', 'my_cabinet_disable_admin_bar_for_subscribers' );

// ============================================================
// AJAX: Вход
// ============================================================

/**
 * Обработчик AJAX входа (для гостей)
 */
function my_cabinet_ajax_login() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_login_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности. Обновите страницу.' ) );
    }

    $email    = isset( $_POST['email'] )    ? sanitize_email( wp_unslash( $_POST['email'] ) )    : '';
    $password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';

    if ( empty( $email ) || empty( $password ) ) {
        wp_send_json_error( array( 'message' => 'Заполните все поля.' ) );
    }

    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Некорректный email адрес.' ) );
    }

    // Получаем пользователя по email
    $user = get_user_by( 'email', $email );
    if ( ! $user ) {
        wp_send_json_error( array( 'message' => 'Пользователь с таким email не найден.' ) );
    }

    // Аутентификация
    $credentials = array(
        'user_login'    => $user->user_login,
        'user_password' => $password,
        'remember'      => true,
    );

    $result = wp_signon( $credentials, false );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => 'Неверный email или пароль.' ) );
    }

    wp_send_json_success( array(
        'redirect' => home_url( '/my/dashboard/' ),
        'message'  => 'Вход выполнен успешно.',
    ) );
}
add_action( 'wp_ajax_nopriv_my_cabinet_login', 'my_cabinet_ajax_login' );
add_action( 'wp_ajax_my_cabinet_login',        'my_cabinet_ajax_login' );

// ============================================================
// AJAX: Регистрация
// ============================================================

/**
 * Обработчик AJAX регистрации (для гостей)
 */
function my_cabinet_ajax_register() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_register_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности. Обновите страницу.' ) );
    }

    $email    = isset( $_POST['email'] )    ? sanitize_email( wp_unslash( $_POST['email'] ) )    : '';
    $password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';

    if ( empty( $email ) || empty( $password ) ) {
        wp_send_json_error( array( 'message' => 'Заполните все поля.' ) );
    }

    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Некорректный email адрес.' ) );
    }

    if ( strlen( $password ) < 6 ) {
        wp_send_json_error( array( 'message' => 'Пароль должен содержать минимум 6 символов.' ) );
    }

    // Проверяем, не занят ли email
    if ( email_exists( $email ) ) {
        wp_send_json_error( array( 'message' => 'Пользователь с таким email уже зарегистрирован.' ) );
    }

    // Формируем username из email (часть до @)
    $username_base = sanitize_user( strstr( $email, '@', true ), true );
    $username      = $username_base;
    $suffix        = 1;

    // Гарантируем уникальность username
    while ( username_exists( $username ) ) {
        $username = $username_base . $suffix;
        $suffix++;
    }

    // Создаём пользователя
    $user_id = wp_create_user( $username, $password, $email );

    if ( is_wp_error( $user_id ) ) {
        $error_message = $user_id->get_error_message();
        wp_send_json_error( array( 'message' => $error_message ) );
    }

    // Обновляем display_name
    wp_update_user( array(
        'ID'           => $user_id,
        'display_name' => $username,
    ) );

    // Автоматически логиним пользователя
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );

    wp_send_json_success( array(
        'redirect' => home_url( '/my/dashboard/' ),
        'message'  => 'Регистрация прошла успешно.',
    ) );
}
add_action( 'wp_ajax_nopriv_my_cabinet_register', 'my_cabinet_ajax_register' );
add_action( 'wp_ajax_my_cabinet_register',        'my_cabinet_ajax_register' );
