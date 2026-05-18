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
// AJAX: Создание SESSION для бронирования
// ============================================================

/**
 * AJAX: Создание временной сессии при нажатии "Написать хозяину"
 * 
 * Вызывается с страницы недвижимости перед редиректом в ЛК.
 * Сохраняет параметры бронирования в SESSION (24 часа TTL).
 */
function my_cabinet_create_booking_session() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_booking_session_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности. Обновите страницу.' ) );
    }

    // Проверка авторизации
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Необходимо авторизоваться.' ) );
    }

    $property_id  = isset( $_POST['property_id'] ) ? absint( $_POST['property_id'] ) : 0;
    $checkin_date = isset( $_POST['checkin_date'] ) ? sanitize_text_field( wp_unslash( $_POST['checkin_date'] ) ) : '';
    $checkout_date = isset( $_POST['checkout_date'] ) ? sanitize_text_field( wp_unslash( $_POST['checkout_date'] ) ) : '';
    $guests_count = isset( $_POST['guests_count'] ) ? wp_unslash( $_POST['guests_count'] ) : '';

    if ( ! $property_id ) {
        wp_send_json_error( array( 'message' => 'Не указан объект недвижимости.' ) );
    }

    // DEBUG
    error_log( '[create_booking_session] Creating: property_id=' . $property_id . ', checkin=' . $checkin_date . ', guests=' . $guests_count );

    // Валидация дат (если указаны)
    if ( ! empty( $checkin_date ) && ! empty( $checkout_date ) ) {
        if ( strtotime( $checkin_date ) >= strtotime( $checkout_date ) ) {
            wp_send_json_error( array( 'message' => 'Дата заезда должна быть раньше даты выезда.' ) );
        }
    }

    // Валидация guests_count (JSON)
    if ( ! empty( $guests_count ) ) {
        $decoded = json_decode( $guests_count, true );
        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
            wp_send_json_error( array( 'message' => 'Некорректный формат данных о гостях.' ) );
        }
    }

    // Создаем SESSION
    $session_data = array(
        'checkin_date'  => $checkin_date,
        'checkout_date' => $checkout_date,
        'guests_count'  => $guests_count,
    );

    $success = realty_start_booking_session( $property_id, $session_data );

    if ( ! $success ) {
        wp_send_json_error( array( 'message' => 'Не удалось сохранить параметры бронирования.' ) );
    }

    // Возвращаем URL для редиректа (только property_id, без дат и гостей)
    $dashboard_url = home_url( '/my/dashboard/?property_id=' . $property_id );

    wp_send_json_success( array(
        'message'   => 'Параметры сохранены.',
        'redirect'  => $dashboard_url,
    ) );
}
add_action( 'wp_ajax_my_cabinet_create_booking_session', 'my_cabinet_create_booking_session' );

// ============================================================
// AJAX: Создание или получение чат-потока бронирования
// ============================================================

/**
 * AJAX: Создать или получить booking_request при клике "Написать хозяину"
 * 
 * Новая логика вместо SESSION:
 * - Создает booking_request со статусом pending
 * - Возвращает thread_id для URL
 */
function my_cabinet_ajax_create_or_get_booking_thread() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_create_thread_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности. Обновите страницу.' ) );
    }

    // Проверка авторизации
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Необходимо авторизоваться.' ) );
    }

    // Получаем параметры
    $property_id = isset( $_POST['property_id'] ) ? absint( $_POST['property_id'] ) : 0;
    $owner_id    = isset( $_POST['owner_id'] ) ? absint( $_POST['owner_id'] ) : 0;
    $checkin_date = isset( $_POST['checkin_date'] ) ? sanitize_text_field( wp_unslash( $_POST['checkin_date'] ) ) : '';
    $checkout_date = isset( $_POST['checkout_date'] ) ? sanitize_text_field( wp_unslash( $_POST['checkout_date'] ) ) : '';
    $guests_count  = isset( $_POST['guests_count'] ) ? sanitize_text_field( wp_unslash( $_POST['guests_count'] ) ) : '';

    if ( ! $property_id || ! $owner_id ) {
        wp_send_json_error( array( 'message' => 'Не указаны параметры.' ) );
    }

    // Валидация дат
    if ( ! empty( $checkin_date ) && ! empty( $checkout_date ) ) {
        if ( strtotime( $checkin_date ) >= strtotime( $checkout_date ) ) {
            wp_send_json_error( array( 'message' => 'Дата заезда должна быть раньше даты выезда.' ) );
        }
    }

    $current_user_id = get_current_user_id();

    // ШАГ 1: Ищем booking_request со статусом pending
    $pending_booking = realty_find_pending_booking( $property_id, $current_user_id );

    if ( $pending_booking ) {
        // ШАГ 2A: Существует — используем его + UPDATE параметров
        $booking_id = $pending_booking->ID;
        $thread_id = get_post_meta( $booking_id, '_thread_id', true );
        
        realty_update_booking_parameters( $booking_id, array(
            'checkin_date'  => $checkin_date,
            'checkout_date' => $checkout_date,
            'guests_count'  => $guests_count
        ) );

        error_log( '[create_thread] Using existing pending booking: ' . $booking_id );

    } else {
        // ШАГ 2B: Проверяем архивные статусы
        $all_bookings_query = new WP_Query( array(
            'post_type'      => 'booking_request',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array( 'key' => '_property_id', 'value' => $property_id ),
                array( 'key' => '_client_id', 'value' => $current_user_id ),
            ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $create_new = true;

        if ( $all_bookings_query->have_posts() ) {
            $last_booking = $all_bookings_query->posts[0];
            $last_status = get_post_meta( $last_booking->ID, '_status', true );
            
            $archived_statuses = array( 'confirmed', 'checkin', 'completed', 'cancelled' );
            
            if ( ! in_array( $last_status, $archived_statuses, true ) ) {
                // Есть активная заявка (new или in_progress) — используем её
                $booking_id = $last_booking->ID;
                $thread_id = get_post_meta( $booking_id, '_thread_id', true );
                
                realty_update_booking_parameters( $booking_id, array(
                    'checkin_date'  => $checkin_date,
                    'checkout_date' => $checkout_date,
                    'guests_count'  => $guests_count
                ) );
                
                $create_new = false;
                error_log( '[create_thread] Using existing active booking: ' . $booking_id . ', status=' . $last_status );
            }
        }

        if ( $create_new ) {
            // Создаем НОВЫЙ booking_request
            $result = realty_create_booking_thread( $property_id, $current_user_id, $owner_id, array(
                'checkin_date'  => $checkin_date,
                'checkout_date' => $checkout_date,
                'guests_count'  => $guests_count
            ) );

            if ( ! $result ) {
                wp_send_json_error( array( 'message' => 'Не удалось создать чат-поток.' ) );
            }

            $booking_id = $result['booking_id'];
            $thread_id = $result['thread_id'];
            
            error_log( '[create_thread] Created new booking: ' . $booking_id );
        }
    }

    // ШАГ 3: Возвращаем URL для редиректа (с thread_id!)
    $dashboard_url = home_url( '/my/dashboard/?property_id=' . $property_id . '&thread_id=' . $thread_id );

    wp_send_json_success( array(
        'message'    => 'Чат-поток готов.',
        'redirect'   => $dashboard_url,
        'thread_id'  => $thread_id,
        'booking_id' => $booking_id,
    ) );
}
add_action( 'wp_ajax_my_cabinet_create_or_get_booking_thread', 'my_cabinet_ajax_create_or_get_booking_thread' );

// ============================================================
// AJAX: Отправка сообщения хозяину
// ============================================================

/**
 * AJAX: Отправка сообщения хозяину
 */
function my_cabinet_ajax_send_message() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_send_message_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности. Обновите страницу.' ) );
    }

    // Проверка авторизации
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Для отправки сообщения необходимо авторизоваться.' ) );
    }

    $title   = isset( $_POST['message_title'] ) ? sanitize_text_field( wp_unslash( $_POST['message_title'] ) ) : '';
    $content = isset( $_POST['message_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message_content'] ) ) : '';

    // Валидация
    if ( empty( $title ) || empty( $content ) ) {
        wp_send_json_error( array( 'message' => 'Заполните все поля формы.' ) );
    }

    if ( strlen( $title ) < 3 ) {
        wp_send_json_error( array( 'message' => 'Тема сообщения должна содержать минимум 3 символа.' ) );
    }

    if ( strlen( $content ) < 10 ) {
        wp_send_json_error( array( 'message' => 'Текст сообщения должен содержать минимум 10 символов.' ) );
    }

    // Получаем ID хозяина из запроса или fallback на настройки
    $property_id = isset( $_POST['property_id'] ) ? absint( $_POST['property_id'] ) : 0;
    $owner_id = isset( $_POST['owner_id'] ) ? absint( $_POST['owner_id'] ) : 0;
    
    // Если owner_id не передан, берем из настроек (fallback)
    if ( ! $owner_id ) {
        $owner_id = get_option( 'my_cabinet_host_user_id', 1 );
    }
    
    // Валидация owner_id
    if ( ! $owner_id || ! get_user_by( 'id', $owner_id ) ) {
        wp_send_json_error( array( 'message' => 'Получатель не найден.' ) );
    }

    $current_user_id = get_current_user_id();

    // ============================================================
    // НОВАЯ ЛОГИКА: Работа с booking_request по thread_id
    // ============================================================
    
    $booking_request_id = 0;
    $thread_id = '';
    $booking_status = 'new';

    // Получаем thread_id из POST (передается с формы)
    $thread_id_from_post = isset( $_POST['thread_id'] ) ? sanitize_text_field( wp_unslash( $_POST['thread_id'] ) ) : '';
    
    if ( ! empty( $thread_id_from_post ) ) {
        // Ищем booking_request по thread_id
        $booking_query = new WP_Query( array(
            'post_type'      => 'booking_request',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array( 'key' => '_thread_id', 'value' => $thread_id_from_post ),
            ),
        ) );

        if ( $booking_query->have_posts() ) {
            $booking_request_id = $booking_query->posts[0]->ID;
            $current_status = get_post_meta( $booking_request_id, '_status', true );
            
            // Если статус pending — меняем на new
            if ( $current_status === 'pending' ) {
                update_post_meta( $booking_request_id, '_status', 'new' );
                error_log( '[send_message] Status changed: pending → new, booking_id=' . $booking_request_id );
            }
            
            $thread_id = get_post_meta( $booking_request_id, '_thread_id', true );
            $booking_status = get_post_meta( $booking_request_id, '_status', true );
            
            error_log( '[send_message] Found booking by thread_id: ' . $booking_request_id . ', status=' . $booking_status );
        }
    }
    
    // Fallback: если booking_request не найден по thread_id, используем старую логику
    if ( empty( $thread_id ) && $property_id > 0 ) {
        error_log( '[send_message] Fallback: using old logic with SESSION' );
        
        // Проверяем SESSION (если есть — берем параметры оттуда)
        $session_data = realty_get_booking_session();
        error_log( '[send_message] SESSION: ' . ( $session_data ? 'FOUND' : 'NOT FOUND' ) );
        
        // Получаем или создаем booking_request
        error_log( '[send_message] Calling realty_get_or_create_booking_request: property_id=' . $property_id . ', client_id=' . $current_user_id . ', owner_id=' . $owner_id );
        $booking_result = realty_get_or_create_booking_request(
            $property_id,
            $current_user_id,
            $owner_id,
            $session_data ? $session_data : array()
        );

        if ( $booking_result ) {
            $booking_request_id = $booking_result['booking_id'];
            $thread_id = $booking_result['thread_id'];
            $booking_status = $booking_result['status'];
            error_log( '[send_message] booking_result: booking_id=' . $booking_request_id . ', action=' . $booking_result['action'] . ', status=' . $booking_status );

            // Если заявка создана впервые ИЛИ есть SESSION — обновляем параметры
            if ( $booking_result['action'] === 'created' && $session_data ) {
                error_log( '[send_message] booking created, data already saved' );
            } elseif ( $booking_result['action'] === 'existing' && $session_data ) {
                error_log( '[send_message] booking exists, updating from SESSION' );
                // Уже существует — обновляем из SESSION (если status=new)
                $update_result = realty_update_booking_from_session( $booking_request_id, $session_data );
                error_log( '[send_message] update result: ' . ( $update_result ? 'SUCCESS' : 'FAILED' ) );
            }

            // Очищаем SESSION (больше не нужен)
            if ( $session_data ) {
                realty_clear_booking_session();
                error_log( '[send_message] SESSION cleared' );
            }
        } else {
            error_log( '[send_message] realty_get_or_create_booking_request returned FALSE' );
        }
    }

    // Fallback: если booking_request не создан, используем старую логику thread_id
    if ( empty( $thread_id ) ) {
        if ( $property_id > 0 ) {
            $thread_id = 'thread_' . $property_id . '_' . $current_user_id . '_' . $owner_id;
        } else {
            $thread_id = 'thread_0_' . min( $current_user_id, $owner_id ) . '_' . max( $current_user_id, $owner_id );
        }
    }

    // Получаем booking meta (если переданы напрямую или из booking_request)
    $checkin_date = isset( $_POST['checkin_date'] ) ? sanitize_text_field( wp_unslash( $_POST['checkin_date'] ) ) : '';
    $checkout_date = isset( $_POST['checkout_date'] ) ? sanitize_text_field( wp_unslash( $_POST['checkout_date'] ) ) : '';
    $adults = isset( $_POST['adults'] ) ? absint( $_POST['adults'] ) : 0;
    $children = isset( $_POST['children'] ) ? absint( $_POST['children'] ) : 0;

    // Если есть booking_request, читаем данные из него (приоритет)
    if ( $booking_request_id > 0 ) {
        $booking_data = realty_get_booking_data( $booking_request_id );
        if ( $booking_data ) {
            $checkin_date = $booking_data['checkin_date'] ?: $checkin_date;
            $checkout_date = $booking_data['checkout_date'] ?: $checkout_date;
            
            // Декодируем guests_count
            if ( ! empty( $booking_data['guests_count'] ) && is_array( $booking_data['guests_count'] ) ) {
                $adults = $booking_data['guests_count']['adults'] ?? $adults;
                $children = $booking_data['guests_count']['children'] ?? $children;
            }
        }
    }
    
    // Валидация дат (если указаны)
    if ( ! empty( $checkin_date ) && ! empty( $checkout_date ) ) {
        if ( strtotime( $checkin_date ) >= strtotime( $checkout_date ) ) {
            wp_send_json_error( array( 'message' => 'Дата заезда должна быть раньше выезда.' ) );
        }
    }
    
    $message_id = wp_insert_post( array(
        'post_title'    => $title,
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_type'     => 'message',
        'post_author'   => $current_user_id,
    ) );

    if ( is_wp_error( $message_id ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка при сохранении сообщения.' ) );
    }

    // Сохраняем мета-данные
    update_post_meta( $message_id, '_receiver_id', $owner_id );
    update_post_meta( $message_id, '_sender_id', $current_user_id );
    update_post_meta( $message_id, '_property_id', $property_id );
    update_post_meta( $message_id, '_is_read', false );
    update_post_meta( $message_id, '_status', 'sent' );
    update_post_meta( $message_id, '_thread_id', $thread_id );
    // Сохраняем роль отправителя (клиент отправляет с публичной страницы)
    update_post_meta( $message_id, '_sender_role', 'client' );
    
    // Привязка к booking_request (если создана)
    if ( $booking_request_id > 0 ) {
        update_post_meta( $message_id, '_booking_request_id', $booking_request_id );
    }
    
    // Сохраняем booking meta (если есть)
    if ( ! empty( $checkin_date ) ) {
        update_post_meta( $message_id, '_checkin_date', $checkin_date );
    }
    if ( ! empty( $checkout_date ) ) {
        update_post_meta( $message_id, '_checkout_date', $checkout_date );
    }
    if ( $adults > 0 ) {
        update_post_meta( $message_id, '_adults', $adults );
    }
    if ( $children > 0 ) {
        update_post_meta( $message_id, '_children', $children );
    }

    // Отправка уведомления на email
    $to      = get_the_author_meta( 'user_email', $owner_id );
    $subject = '[' . get_bloginfo( 'name' ) . '] ' . $title;
    $message = "Пользователь " . wp_get_current_user()->display_name . " отправил сообщение:\n\n" . $content;
    
    // Добавляем info об объекте и датах в email
    if ( $property_id > 0 ) {
        $property_title = get_the_title( $property_id );
        $message .= "\n\n---\nОбъект: " . $property_title . " (ID: " . $property_id . ")";
    }
    if ( ! empty( $checkin_date ) && ! empty( $checkout_date ) ) {
        $message .= "\nДаты: " . date( 'd.m.Y', strtotime( $checkin_date ) ) . " - " . date( 'd.m.Y', strtotime( $checkout_date ) );
    }
    if ( $adults > 0 || $children > 0 ) {
        $guests = array();
        if ( $adults > 0 ) $guests[] = $adults . ' взросл.';
        if ( $children > 0 ) $guests[] = $children . ' дет.';
        $message .= "\nГости: " . implode( ', ', $guests );
    }
    
    $headers = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . wp_get_current_user()->user_email );

    $email_sent = wp_mail( $to, $subject, $message, $headers );

    // Обновляем статус отправки email
    update_post_meta( $message_id, '_notification_status', $email_sent ? 'sent' : 'failed' );

    if ( $message_id ) {
        wp_send_json_success( array( 
            'message' => 'Сообщение успешно отправлено!',
            'message_id' => $message_id,
        ) );
    } else {
        wp_send_json_error( array( 'message' => 'Не удалось отправить сообщение. Попробуйте позже.' ) );
    }
}
add_action( 'wp_ajax_nopriv_my_cabinet_send_message', 'my_cabinet_ajax_send_message' );
add_action( 'wp_ajax_my_cabinet_send_message',        'my_cabinet_ajax_send_message' );

// ============================================================
// AJAX: Получение истории сообщений пользователя
// ============================================================

/**
 * AJAX: Получение списка сообщений текущего пользователя
 */
function my_cabinet_ajax_get_messages() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_get_messages_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности. Обновите страницу.' ) );
    }

    // Проверка авторизации
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Необходимо авторизоваться.' ) );
    }

    $current_user_id = get_current_user_id();
    $limit = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 20;
    $offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
    $thread_id = isset( $_POST['thread_id'] ) ? sanitize_text_field( wp_unslash( $_POST['thread_id'] ) ) : '';

    // Запрос сообщений, где пользователь - отправитель
    $args = array(
        'post_type'      => 'message',
        'post_status'    => 'publish',
        'author'         => $current_user_id,
        'posts_per_page' => $limit,
        'offset'         => $offset,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    // Фильтрация по thread_id (строго один поток = один чат)
    if ( ! empty( $thread_id ) ) {
        $args['meta_query'] = array(
            array(
                'key'     => '_thread_id',
                'value'   => $thread_id,
                'compare' => '=',
            ),
        );
    }

    $query = new WP_Query( $args );
    $messages = array();

    // DEBUG: Log the query
    if ( ! empty( $thread_id ) ) {
        error_log( 'my_cabinet_ajax_get_messages: Filtering by thread_id=' . $thread_id . ', found=' . $query->found_posts );
    }

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            
            $message_id = get_the_ID();
            $property_id = (int) get_post_meta( $message_id, '_property_id', true );
            $property_title = '';
            $property_url = '';
            
            // Получаем информацию об объекте (если есть)
            if ( $property_id > 0 ) {
                $property_title = get_the_title( $property_id );
                $property_url = get_permalink( $property_id );
            }
            
            $messages[] = array(
                'id'                => $message_id,
                'title'             => get_the_title(),
                'content'           => get_the_content(),
                'date'              => get_the_date( 'd.m.Y H:i' ),
                'status'            => get_post_meta( $message_id, '_status', true ),
                'is_read'           => (bool) get_post_meta( $message_id, '_is_read', true ),
                'notification'      => get_post_meta( $message_id, '_notification_status', true ),
                'property_id'       => $property_id,
                'property_title'    => $property_title,
                'property_url'      => $property_url,
                'thread_id'         => get_post_meta( $message_id, '_thread_id', true ),
                'checkin_date'      => get_post_meta( $message_id, '_checkin_date', true ),
                'checkout_date'     => get_post_meta( $message_id, '_checkout_date', true ),
                'adults'            => (int) get_post_meta( $message_id, '_adults', true ),
                'children'          => (int) get_post_meta( $message_id, '_children', true ),
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success( array(
        'messages' => $messages,
        'total'    => $query->found_posts,
    ) );
}
add_action( 'wp_ajax_my_cabinet_get_messages', 'my_cabinet_ajax_get_messages' );

// ============================================================
// AJAX: Получение списка диалогов пользователя с объектами
// ============================================================

/**
 * AJAX: Получение списка диалогов текущего пользователя с информацией об объектах
 * Используется на dashboard при заходе без GET параметров
 */
function my_cabinet_ajax_get_user_threads() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_get_threads_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности. Обновите страницу.' ) );
    }

    // Проверка авторизации
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Необходимо авторизоваться.' ) );
    }

    $current_user_id = get_current_user_id();

    // Получаем все сообщения пользователя
    $args = array(
        'post_type'      => 'message',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_sender_id',
                'value'   => $current_user_id,
                'compare' => '=',
            ),
            array(
                'key'     => '_property_id',
                'value'   => 0,
                'compare' => '>',
            ),
        ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $query = new WP_Query( $args );
    $threads = array();
    $seen_threads = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            
            $message_id = get_the_ID();
            $property_id = (int) get_post_meta( $message_id, '_property_id', true );
            $thread_id = get_post_meta( $message_id, '_thread_id', true );
            
            // Группируем по thread_id (один поток = одна запись в списке)
            if ( ! empty( $thread_id ) && ! in_array( $thread_id, $seen_threads ) ) {
                $seen_threads[] = $thread_id;
                
                // Получаем данные объекта
                $property_post = get_post( $property_id );
                if ( ! $property_post ) {
                    continue;
                }
                
                $property_title = $property_post->post_title;
                $property_thumbnail = get_the_post_thumbnail_url( $property_id, 'thumbnail' );
                $property_url = get_permalink( $property_id );
                
                // Получаем локацию
                $location_terms = get_the_terms( $property_id, 'location' );
                $location_name = '';
                if ( $location_terms && ! is_wp_error( $location_terms ) ) {
                    $location_name = $location_terms[0]->name;
                }
                
                // Получаем адрес
                $address = get_post_meta( $property_id, 'address', true );
                
                // Получаем booking meta из первого сообщения
                $checkin_date = get_post_meta( $message_id, '_checkin_date', true );
                $checkout_date = get_post_meta( $message_id, '_checkout_date', true );
                
                // Форматируем даты
                $dates_display = '';
                if ( $checkin_date && $checkout_date ) {
                    $dates_display = date_i18n( 'd.m.Y', strtotime( $checkin_date ) ) . ' - ' . date_i18n( 'd.m.Y', strtotime( $checkout_date ) );
                }
                
                // Получаем booking_request по thread_id
                $status = 'new'; // статус по умолчанию
                if ( ! empty( $thread_id ) ) {
                    $booking_query = new WP_Query( array(
                        'post_type'      => 'booking_request',
                        'post_status'    => 'publish',
                        'posts_per_page' => 1,
                        'meta_query'     => array(
                            array(
                                'key'     => '_thread_id',
                                'value'   => $thread_id,
                                'compare' => '=',
                            ),
                        ),
                    ) );
                    
                    if ( $booking_query->have_posts() ) {
                        $booking_id = $booking_query->posts[0]->ID;
                        $booking_status = get_post_meta( $booking_id, '_status', true );
                        if ( ! empty( $booking_status ) ) {
                            $status = $booking_status;
                        }
                        wp_reset_postdata();
                    }
                }
                
                // Получаем метку статуса
                $booking_statuses = realty_get_booking_statuses();
                $status_label = isset( $booking_statuses[ $status ] ) ? $booking_statuses[ $status ]['label'] : 'Новая заявка';
                
                $threads[] = array(
                    'property_id'       => $property_id,
                    'property_title'    => $property_title,
                    'property_image'    => $property_thumbnail ?: '',
                    'property_url'      => $property_url,
                    'location'          => $location_name,
                    'address'           => $address ?: '',
                    'checkin_date'      => $checkin_date,
                    'checkout_date'     => $checkout_date,
                    'dates_display'     => $dates_display,
                    'status'            => $status_label,
                    'status_key'        => $status,
                    'thread_id'         => $thread_id,
                );
            }
        }
        wp_reset_postdata();
    }

    wp_send_json_success( array(
        'threads' => $threads,
        'total'   => count( $threads ),
    ) );
}
add_action( 'wp_ajax_my_cabinet_get_user_threads', 'my_cabinet_ajax_get_user_threads' );

// ============================================================
// AJAX: Получение списка диалогов (для админки)
// ============================================================

/**
 * AJAX: Получение списка диалогов с группировкой по пользователям
 */
function my_cabinet_ajax_get_dialogs() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_get_dialogs_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности.' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Недостаточно прав.' ) );
    }

    $filters = array(
        'date' => isset( $_POST['filter_date'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_date'] ) ) : '',
        'login' => isset( $_POST['filter_login'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_login'] ) ) : '',
        'status' => isset( $_POST['filter_status'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_status'] ) ) : '',
    );

    // Получаем все booking_requests
    $args = array(
        'post_type'      => 'booking_request',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    // Фильтр по статусу
    if ( ! empty( $filters['status'] ) ) {
        $args['meta_query'] = array(
            array(
                'key'     => '_status',
                'value'   => $filters['status'],
                'compare' => '=',
            ),
        );
    }

    $query = new WP_Query( $args );
    $dialogs = array();

    // DEBUG логирование
    error_log( 'my_cabinet_ajax_get_dialogs: Found ' . $query->found_posts . ' booking_requests' );

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            
            $booking_id = get_the_ID();
            $property_id = (int) get_post_meta( $booking_id, '_property_id', true );
            $client_id = (int) get_post_meta( $booking_id, '_client_id', true );
            $thread_id = get_post_meta( $booking_id, '_thread_id', true );
            $status = get_post_meta( $booking_id, '_status', true ) ?: 'new';
            $checkin_date = get_post_meta( $booking_id, '_checkin_date', true );
            $checkout_date = get_post_meta( $booking_id, '_checkout_date', true );
            
            // Декодируем гостей
            $guests_count_raw = get_post_meta( $booking_id, '_guests_count', true );
            $guests_count = ! empty( $guests_count_raw ) ? json_decode( $guests_count_raw, true ) : array();
            if ( ! is_array( $guests_count ) ) {
                $guests_count = array();
            }
            
            // Форматируем текст гостей
            $guests_text = '';
            if ( ! empty( $guests_count ) ) {
                $guest_types_config = realty_get_guest_types_config();
                $guests_parts = array();
                foreach ( $guest_types_config as $guest_type ) {
                    if ( empty( $guest_type['enabled'] ) ) continue;
                    $guest_name = $guest_type['name'];
                    $guest_value = $guests_count[ $guest_name ] ?? 0;
                    if ( $guest_value > 0 ) {
                        $guests_parts[] = $guest_value . ' ' . $guest_type['label'];
                    }
                }
                $guests_text = implode( ', ', $guests_parts );
            }
            
            // Получаем данные клиента
            $client = get_userdata( $client_id );
            
            // Получаем название недвижимости
            $property_title = $property_id > 0 ? get_the_title( $property_id ) : '—';
            
            $dialogs[ $thread_id ] = array(
                'booking_id'    => $booking_id,
                'thread_id'     => $thread_id,
                'client_id'     => $client_id,
                'client_login'  => $client ? $client->user_login : 'Unknown',
                'client_name'   => $client ? $client->display_name : 'Unknown',
                'property_id'   => $property_id,
                'property_title' => $property_title,
                'checkin_date'  => $checkin_date,
                'checkout_date' => $checkout_date,
                'guests_text'   => $guests_text,
                'status'        => $status,
                'created_date'  => get_the_date( 'd.m.Y H:i' ),
                'timestamp'     => get_the_date( 'U' ),
            );
        }
        wp_reset_postdata();
    }

    // Применяем фильтры
    if ( $filters['date'] ) {
        $date_timestamp = strtotime( $filters['date'] );
        if ( $date_timestamp === false ) {
            error_log( 'my_cabinet_ajax_get_dialogs: Invalid date filter: ' . $filters['date'] );
        } else {
            $filter_date = date( 'd.m.Y', $date_timestamp );
            $dialogs = array_filter( $dialogs, function( $dialog ) use ( $filter_date ) {
                return strpos( $dialog['created_date'], $filter_date ) === 0;
            } );
        }
    }

    if ( $filters['login'] ) {
        $login_lower = strtolower( $filters['login'] );
        $dialogs = array_filter( $dialogs, function( $dialog ) use ( $login_lower ) {
            return strpos( strtolower( $dialog['client_login'] ), $login_lower ) !== false;
        } );
    }

    // Фильтр по subject удален - теперь фильтр по status

    // Сортируем по дате (новые первые)
    usort( $dialogs, function( $a, $b ) {
        return $b['timestamp'] - $a['timestamp'];
    } );

    // DEBUG логирование
    error_log( 'my_cabinet_ajax_get_dialogs: Returning ' . count( $dialogs ) . ' dialogs' );

    wp_send_json_success( array(
        'dialogs' => array_values( $dialogs ),
    ) );
}
add_action( 'wp_ajax_my_cabinet_get_dialogs', 'my_cabinet_ajax_get_dialogs' );

// ============================================================
// AJAX: Получение диалога (переписка с пользователем)
// ============================================================

/**
 * AJAX: Получение всех сообщений диалога
 */
function my_cabinet_ajax_get_dialog() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_get_dialog_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности.' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Недостаточно прав.' ) );
    }

    $thread_id = isset( $_POST['thread_id'] ) ? sanitize_text_field( wp_unslash( $_POST['thread_id'] ) ) : '';

    if ( empty( $thread_id ) ) {
        wp_send_json_error( array( 'message' => 'Не указан thread_id.' ) );
    }

    $args = array(
        'post_type'      => 'message',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_thread_id',
                'value'   => $thread_id,
                'compare' => '=',
            ),
        ),
        'orderby'        => 'date',
        'order'          => 'ASC',
    );

    $query = new WP_Query( $args );
    $messages = array();
    $message_ids_to_mark = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            
            $message_id = get_the_ID();
            $sender_id = get_post_meta( $message_id, '_sender_id', true );
            $receiver_id = get_post_meta( $message_id, '_receiver_id', true );
            $property_id = (int) get_post_meta( $message_id, '_property_id', true );
            $is_read = (bool) get_post_meta( $message_id, '_is_read', true );
            $sender = get_userdata( $sender_id );
            
            // Получаем сохраненную роль или определяем fallback
            $sender_role = get_post_meta( $message_id, '_sender_role', true );
            if ( empty( $sender_role ) ) {
                // Fallback для старых сообщений: если sender == receiver, то это host
                $sender_role = ( $sender_id == $receiver_id ) ? 'host' : 'client';
            }
            
            // Получаем info об объекте (если есть)
            $property_title = '';
            $property_url = '';
            if ( $property_id > 0 ) {
                $property_title = get_the_title( $property_id );
                $property_url = get_permalink( $property_id );
            }
            
            $messages[] = array(
                'id' => $message_id,
                'sender_id' => $sender_id,
                'sender_name' => $sender ? $sender->display_name : 'Unknown',
                'sender_role' => $sender_role,
                'content' => get_the_content(),
                'date' => get_the_date( 'd.m.Y H:i:s' ),
                'is_read' => $is_read,
                'property_id' => $property_id,
                'property_title' => $property_title,
                'property_url' => $property_url,
                'checkin_date' => get_post_meta( $message_id, '_checkin_date', true ),
                'checkout_date' => get_post_meta( $message_id, '_checkout_date', true ),
                'adults' => (int) get_post_meta( $message_id, '_adults', true ),
                'children' => (int) get_post_meta( $message_id, '_children', true ),
            );

            // Отмечаем непрочитанные сообщения как прочитанные
            // Логика: помечаем только входящие (где текущий пользователь = receiver)
            if ( ! $is_read && $receiver_id == get_current_user_id() ) {
                $message_ids_to_mark[] = $message_id;
            }
        }
        wp_reset_postdata();
    }

    // Отмечаем как прочитанные
    foreach ( $message_ids_to_mark as $msg_id ) {
        update_post_meta( $msg_id, '_is_read', true );
    }

    wp_send_json_success( array(
        'messages' => $messages,
    ) );
}
add_action( 'wp_ajax_my_cabinet_get_dialog', 'my_cabinet_ajax_get_dialog' );

// ============================================================
// AJAX: Отправка ответа из админки
// ============================================================

/**
 * AJAX: Отправка ответа хоста пользователю
 */
function my_cabinet_ajax_send_reply() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_send_reply_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности.' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Недостаточно прав.' ) );
    }

    $thread_id = isset( $_POST['thread_id'] ) ? sanitize_text_field( wp_unslash( $_POST['thread_id'] ) ) : '';
    $content = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';

    if ( empty( $thread_id ) || empty( $content ) ) {
        wp_send_json_error( array( 'message' => 'Заполните все поля.' ) );
    }

    if ( strlen( $content ) < 3 ) {
        wp_send_json_error( array( 'message' => 'Сообщение слишком короткое.' ) );
    }

    // Извлекаем ID пользователя из thread_id
    // Формат: thread_{property_id}_{client_id}_{owner_id} или thread_0_{min}_{max}
    $parts = explode( '_', $thread_id );
    
    // Определяем format thread_id
    $property_id = 0;
    $receiver_id = 0;
    
    if ( count( $parts ) >= 4 && $parts[1] !== '0' ) {
        // Новый format: thread_{property_id}_{client_id}_{owner_id}
        $property_id = absint( $parts[1] );
        $client_id = absint( $parts[2] );
        $owner_id = absint( $parts[3] );
        
        // Хост отвечает, значит receiver = client
        $receiver_id = $client_id;
    } else {
        // Старый format: thread_0_{min}_{max} или thread_{min}_{max}
        $id1 = absint( $parts[1] );
        $id2 = absint( $parts[2] );
        
        if ( $id1 == get_current_user_id() ) {
            $receiver_id = $id2;
        } else {
            $receiver_id = $id1;
        }
    }

    if ( ! $receiver_id ) {
        wp_send_json_error( array( 'message' => 'Не удалось определить получателя.' ) );
    }
    
    // Получаем property_id и booking meta из первого сообщения диалога
    $first_message_property_id = 0;
    $first_message_args = array(
        'post_type'      => 'message',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'     => '_thread_id',
                'value'   => $thread_id,
                'compare' => '=',
            ),
        ),
        'orderby'        => 'date',
        'order'          => 'ASC',
    );
    
    $first_query = new WP_Query( $first_message_args );
    if ( $first_query->have_posts() ) {
        $first_query->the_post();
        $first_message_property_id = (int) get_post_meta( get_the_ID(), '_property_id', true );
        wp_reset_postdata();
    }

    // Создаем сообщение
    $host_id = get_current_user_id();
    $message_id = wp_insert_post( array(
        'post_title'    => 'Ответ',
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_type'     => 'message',
        'post_author'   => $host_id,
    ) );

    if ( is_wp_error( $message_id ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка при отправке.' ) );
    }

    update_post_meta( $message_id, '_receiver_id', $receiver_id );
    update_post_meta( $message_id, '_sender_id', $host_id );
    update_post_meta( $message_id, '_property_id', $first_message_property_id );
    update_post_meta( $message_id, '_is_read', true ); // Хост сам отправил, значит прочитано
    update_post_meta( $message_id, '_status', 'sent' );
    update_post_meta( $message_id, '_thread_id', $thread_id );
    // Сохраняем роль отправителя (хост отвечает из админки)
    update_post_meta( $message_id, '_sender_role', 'host' );
    
    // Копируем booking meta из первого сообщения (если есть)
    if ( $first_message_property_id > 0 ) {
        $first_message_meta = array( '_checkin_date', '_checkout_date', '_adults', '_children' );
        foreach ( $first_message_meta as $meta_key ) {
            $meta_value = get_post_meta( $first_query->post->ID, $meta_key, true );
            if ( ! empty( $meta_value ) ) {
                update_post_meta( $message_id, $meta_key, $meta_value );
            }
        }
    }

    // Отправляем email пользователю
    $receiver = get_userdata( $receiver_id );
    if ( $receiver ) {
        $to = $receiver->user_email;
        $subject = '[' . get_bloginfo( 'name' ) . '] Новый ответ на ваше сообщение';
        $message = "Здравствуйте, " . $receiver->display_name . "!\n\n";
        $message .= "Вам поступил ответ:\n\n" . $content . "\n\n";
        $message .= "С уважением,\n" . get_bloginfo( 'name' );
        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        
        wp_mail( $to, $subject, $message, $headers );
    }

    wp_send_json_success( array(
        'message' => 'Ответ отправлен!',
        'message_id' => $message_id,
    ) );
}
add_action( 'wp_ajax_my_cabinet_send_reply', 'my_cabinet_ajax_send_reply' );

// ============================================================
// AJAX: Обновление деталей бронирования
// ============================================================

/**
 * AJAX: Обновление дат, гостей и статуса бронирования
 * Только хост (владелец объекта) или админ может редактировать
 */
function my_cabinet_ajax_update_booking_details() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_update_booking_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности. Обновите страницу.' ) );
    }

    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Недостаточно прав.' ) );
    }

    // Принимаем booking_id (ID записи booking_request)
    $booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
    // Для обратной совместимости принимаем и message_id
    if ( ! $booking_id ) {
        $booking_id = isset( $_POST['message_id'] ) ? absint( $_POST['message_id'] ) : 0;
    }
    
    $thread_id = isset( $_POST['thread_id'] ) ? sanitize_text_field( wp_unslash( $_POST['thread_id'] ) ) : '';
    $checkin_date = isset( $_POST['checkin_date'] ) ? sanitize_text_field( wp_unslash( $_POST['checkin_date'] ) ) : '';
    $checkout_date = isset( $_POST['checkout_date'] ) ? sanitize_text_field( wp_unslash( $_POST['checkout_date'] ) ) : '';
    $booking_status = isset( $_POST['booking_status'] ) ? sanitize_text_field( wp_unslash( $_POST['booking_status'] ) ) : 'new';
    
    // Получаем типы гостей из настроек через хелпер
    $guest_types_config = realty_get_guest_types_config();
    
    // Собираем значения гостей из POST с валидацией
    $guests_values = array();
    $guests_labels = array();
    if ( ! empty( $guest_types_config ) && is_array( $guest_types_config ) ) {
        foreach ( $guest_types_config as $guest_type ) {
            if ( empty( $guest_type['enabled'] ) ) {
                continue;
            }
            $guest_name = $guest_type['name'];
            $guest_value = isset( $_POST[ $guest_name ] ) ? absint( $_POST[ $guest_name ] ) : 0;
            $guest_min = $guest_type['min'] ?? 0;
            $guest_max = $guest_type['max'] ?? 10;
            
            // Валидация
            $guest_value = max( $guest_min, min( $guest_max, $guest_value ) );
            
            $guests_values[ $guest_name ] = $guest_value;
            $guests_labels[ $guest_name ] = $guest_type['label'];
        }
    }

    if ( ! $booking_id ) {
        wp_send_json_error( array( 'message' => 'Не указан ID бронирования.' ) );
    }

    // Валидация статуса
    $valid_statuses = array( 'new', 'in_progress', 'confirmed', 'completed', 'cancelled' );
    if ( ! in_array( $booking_status, $valid_statuses, true ) ) {
        wp_send_json_error( array( 'message' => 'Некорректный статус бронирования.' ) );
    }

    // Валидация дат
    if ( ! empty( $checkin_date ) && ! empty( $checkout_date ) ) {
        if ( strtotime( $checkin_date ) >= strtotime( $checkout_date ) ) {
            wp_send_json_error( array( 'message' => 'Дата заезда должна быть раньше даты выезда.' ) );
        }
    }

    // Проверяем, что booking_request существует
    $booking = get_post( $booking_id );
    if ( ! $booking || $booking->post_type !== 'booking_request' ) {
        wp_send_json_error( array( 'message' => 'Бронирование не найдено.' ) );
    }

    // Обновляем мета-поля booking_request
    update_post_meta( $booking_id, '_checkin_date', $checkin_date );
    update_post_meta( $booking_id, '_checkout_date', $checkout_date );
    update_post_meta( $booking_id, '_status', $booking_status );
    
    // Сохраняем guests_count как JSON
    $guests_count_json = wp_json_encode( $guests_values );
    update_post_meta( $booking_id, '_guests_count', $guests_count_json );
    
    // Обновляем также сообщения в треде для обратной совместимости
    if ( ! empty( $thread_id ) ) {
        $args = array(
            'post_type'      => 'message',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_thread_id',
                    'value'   => $thread_id,
                    'compare' => '=',
                ),
            ),
            'orderby'        => 'date',
            'order'          => 'ASC',
        );

        $query = new WP_Query( $args );
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $msg_id = get_the_ID();
                update_post_meta( $msg_id, '_booking_status', $booking_status );
                update_post_meta( $msg_id, '_checkin_date', $checkin_date );
                update_post_meta( $msg_id, '_checkout_date', $checkout_date );
                foreach ( $guests_values as $guest_name => $guest_value ) {
                    update_post_meta( $msg_id, '_' . $guest_name, $guest_value );
                }
            }
            wp_reset_postdata();
        }
    }

    // Массив статусов для ответа (используем хелпер)
    $status_data = realty_get_booking_status_data( $booking_status );

    // Форматируем ответ
    $checkin_date_formatted = ! empty( $checkin_date ) ? date_i18n( 'd F Y', strtotime( $checkin_date ) ) : '';
    $checkout_date_formatted = ! empty( $checkout_date ) ? date_i18n( 'd F Y', strtotime( $checkout_date ) ) : '';
    
    // Формируем текст гостей динамически
    $guests_parts = array();
    foreach ( $guests_values as $guest_name => $guest_value ) {
        if ( $guest_value > 0 ) {
            $guests_parts[] = $guest_value . ' ' . $guests_labels[ $guest_name ];
        }
    }
    $guests_text = implode( ', ', $guests_parts );

    wp_send_json_success( array(
        'message'              => 'Изменения сохранены',
        'checkin_date'         => $checkin_date,
        'checkout_date'        => $checkout_date,
        'checkin_date_formatted'  => $checkin_date_formatted,
        'checkout_date_formatted' => $checkout_date_formatted,
        'guests_values'        => $guests_values,
        'guests_labels'        => $guests_labels,
        'guests_text'          => $guests_text,
        'booking_status'       => $booking_status,
        'status_label'         => $status_data['label'],
        'status_class'         => $status_data['class'],
    ) );
}
add_action( 'wp_ajax_my_cabinet_update_booking_details', 'my_cabinet_ajax_update_booking_details' );

// ============================================================
// AJAX: Удаление сообщения
// ============================================================

/**
 * AJAX: Удаление диалога
 */
function my_cabinet_ajax_delete_dialog() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_delete_message_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности.' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Недостаточно прав.' ) );
    }

    $thread_id = isset( $_POST['thread_id'] ) ? sanitize_text_field( wp_unslash( $_POST['thread_id'] ) ) : '';

    if ( empty( $thread_id ) ) {
        wp_send_json_error( array( 'message' => 'Не указан thread_id.' ) );
    }

    // Получаем все сообщения диалога
    $args = array(
        'post_type'      => 'message',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_thread_id',
                'value'   => $thread_id,
                'compare' => '=',
            ),
        ),
    );

    $query = new WP_Query( $args );
    $deleted = 0;

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            wp_delete_post( get_the_ID(), true );
            $deleted++;
        }
        wp_reset_postdata();
    }

    wp_send_json_success( array(
        'message' => "Диалог удален ($deleted сообщений).",
        'deleted' => $deleted,
    ) );
}
add_action( 'wp_ajax_my_cabinet_delete_dialog', 'my_cabinet_ajax_delete_dialog' );

/**
 * AJAX: Удаление заявки на бронирование
 */
function my_cabinet_ajax_delete_booking() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_cabinet_delete_message_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Ошибка безопасности.' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Недостаточно прав.' ) );
    }

    $booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;

    if ( empty( $booking_id ) ) {
        wp_send_json_error( array( 'message' => 'Не указан booking_id.' ) );
    }

    // Проверяем что это booking_request
    $post_type = get_post_type( $booking_id );
    if ( $post_type !== 'booking_request' ) {
        wp_send_json_error( array( 'message' => 'Неверный тип записи.' ) );
    }

    // Получаем thread_id для удаления связанных сообщений
    $thread_id = get_post_meta( $booking_id, '_thread_id', true );

    // Удаляем booking_request
    $result = wp_delete_post( $booking_id, true );

    if ( $result ) {
        $messages_deleted = 0;
        
        // Если есть thread_id, удаляем все связанные сообщения
        if ( ! empty( $thread_id ) ) {
            $message_query = new WP_Query( array(
                'post_type'      => 'message',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'     => '_thread_id',
                        'value'   => $thread_id,
                        'compare' => '=',
                    ),
                ),
            ) );

            if ( $message_query->have_posts() ) {
                while ( $message_query->have_posts() ) {
                    $message_query->the_post();
                    wp_delete_post( get_the_ID(), true );
                    $messages_deleted++;
                }
                wp_reset_postdata();
            }
        }

        wp_send_json_success( array(
            'message' => 'Заявка на бронирование удалена.' . ( $messages_deleted > 0 ? " (Удалено сообщений: {$messages_deleted})" : '' ),
            'messages_deleted' => $messages_deleted,
        ) );
    } else {
        wp_send_json_error( array( 'message' => 'Ошибка при удалении.' ) );
    }
}
add_action( 'wp_ajax_my_cabinet_delete_booking', 'my_cabinet_ajax_delete_booking' );

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
