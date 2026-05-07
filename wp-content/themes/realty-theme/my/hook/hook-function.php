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

    // Получаем ID хозяина (например, из настроек темы или пост-типа)
    $host_id = get_option( 'my_cabinet_host_user_id', 1 );
    
    if ( ! $host_id || ! get_user_by( 'id', $host_id ) ) {
        wp_send_json_error( array( 'message' => 'Не удалось определить получателя сообщения.' ) );
    }

    // Сохраняем сообщение в БД (CPT message)
    $current_user_id = get_current_user_id();
    
    // Создаем thread_id для группировки диалога с этим пользователем
    $thread_id = 'thread_' . min( $current_user_id, $host_id ) . '_' . max( $current_user_id, $host_id );
    
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
    update_post_meta( $message_id, '_receiver_id', $host_id );
    update_post_meta( $message_id, '_sender_id', $current_user_id );
    update_post_meta( $message_id, '_is_read', false );
    update_post_meta( $message_id, '_status', 'sent' );
    update_post_meta( $message_id, '_thread_id', $thread_id );
    // Сохраняем роль отправителя (клиент отправляет с публичной страницы)
    update_post_meta( $message_id, '_sender_role', 'client' );

    // Отправка уведомления на email
    $to      = get_the_author_meta( 'user_email', $host_id );
    $subject = '[' . get_bloginfo( 'name' ) . '] ' . $title;
    $message = "Пользователь " . wp_get_current_user()->display_name . " отправил сообщение:\n\n" . $content;
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

    $query = new WP_Query( $args );
    $messages = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            
            $message_id = get_the_ID();
            $messages[] = array(
                'id'           => $message_id,
                'title'        => get_the_title(),
                'content'      => get_the_content(),
                'date'         => get_the_date( 'd.m.Y H:i' ),
                'status'       => get_post_meta( $message_id, '_status', true ),
                'is_read'      => (bool) get_post_meta( $message_id, '_is_read', true ),
                'notification' => get_post_meta( $message_id, '_notification_status', true ),
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
        'subject' => isset( $_POST['filter_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_subject'] ) ) : '',
    );

    // Получаем все сообщения
    $args = array(
        'post_type'      => 'message',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $query = new WP_Query( $args );
    $dialogs = array();

    // DEBUG логирование
    error_log( 'my_cabinet_ajax_get_dialogs: Found ' . $query->found_posts . ' messages' );

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            
            $message_id = get_the_ID();
            $sender_id = get_post_meta( $message_id, '_sender_id', true );
            $receiver_id = get_post_meta( $message_id, '_receiver_id', true );
            $thread_id = get_post_meta( $message_id, '_thread_id', true );
            
            // Fallback: если thread_id пустой, создаем его
            if ( empty( $thread_id ) && $sender_id && $receiver_id ) {
                $thread_id = 'thread_' . min( $sender_id, $receiver_id ) . '_' . max( $sender_id, $receiver_id );
                // Сохраняем для будущих запросов
                update_post_meta( $message_id, '_thread_id', $thread_id );
            }
            
            $is_read = (bool) get_post_meta( $message_id, '_is_read', true );
            $host_id = get_current_user_id();
            
            // Группируем по thread_id
            if ( ! isset( $dialogs[ $thread_id ] ) ) {
                $sender = get_userdata( $sender_id );
                
                // Считаем непрочитанные ТОЛЬКО если:
                // 1. Хозяин - получатель (receiver_id = host_id)
                // 2. Отправитель НЕ хозяин (sender_id != host_id)
                // 3. Сообщение не прочитано
                $is_unread = ( $receiver_id == $host_id && $sender_id != $host_id && ! $is_read ) ? 1 : 0;
                
                $dialogs[ $thread_id ] = array(
                    'thread_id' => $thread_id,
                    'sender_id' => $sender_id,
                    'sender_login' => $sender ? $sender->user_login : 'Unknown',
                    'sender_name' => $sender ? $sender->display_name : 'Unknown',
                    'sender_email' => $sender ? $sender->user_email : '',
                    'last_message_id' => $message_id,
                    'last_subject' => get_the_title(),
                    'last_date' => get_the_date( 'd.m.Y H:i:s' ),
                    'last_timestamp' => get_the_date( 'U' ),
                    'unread_count' => $is_unread,
                );
            } else {
                // Обновляем счетчик непрочитанных (только от клиентов)
                if ( $receiver_id == $host_id && $sender_id != $host_id && ! $is_read ) {
                    $dialogs[ $thread_id ]['unread_count']++;
                }
            }
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
                return strpos( $dialog['last_date'], $filter_date ) === 0;
            } );
        }
    }

    if ( $filters['login'] ) {
        $login_lower = strtolower( $filters['login'] );
        $dialogs = array_filter( $dialogs, function( $dialog ) use ( $login_lower ) {
            return strpos( strtolower( $dialog['sender_login'] ), $login_lower ) !== false ||
                   strpos( strtolower( $dialog['sender_email'] ), $login_lower ) !== false;
        } );
    }

    if ( $filters['subject'] ) {
        $subject_lower = strtolower( $filters['subject'] );
        $dialogs = array_filter( $dialogs, function( $dialog ) use ( $subject_lower ) {
            return strpos( strtolower( $dialog['last_subject'] ), $subject_lower ) !== false;
        } );
    }

    // Сортируем по дате (новые первые)
    usort( $dialogs, function( $a, $b ) {
        return $b['last_timestamp'] - $a['last_timestamp'];
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
            $is_read = (bool) get_post_meta( $message_id, '_is_read', true );
            $sender = get_userdata( $sender_id );
            
            // Получаем сохраненную роль или определяем fallback
            $sender_role = get_post_meta( $message_id, '_sender_role', true );
            if ( empty( $sender_role ) ) {
                // Fallback для старых сообщений: если sender == receiver, то это host
                $sender_role = ( $sender_id == $receiver_id ) ? 'host' : 'client';
            }
            
            $messages[] = array(
                'id' => $message_id,
                'sender_id' => $sender_id,
                'sender_name' => $sender ? $sender->display_name : 'Unknown',
                'sender_role' => $sender_role,
                'content' => get_the_content(),
                'date' => get_the_date( 'd.m.Y H:i:s' ),
                'is_read' => $is_read,
            );

            // Отмечаем непрочитанные сообщения как прочитанные
            if ( ! $is_read && $sender_id != get_current_user_id() ) {
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
    // Формат: thread_{user_id}_{host_id}
    $parts = explode( '_', $thread_id );
    $receiver_id = isset( $parts[1] ) ? absint( $parts[1] ) : 0;
    
    if ( $receiver_id == get_current_user_id() ) {
        $receiver_id = isset( $parts[2] ) ? absint( $parts[2] ) : 0;
    }

    if ( ! $receiver_id ) {
        wp_send_json_error( array( 'message' => 'Не удалось определить получателя.' ) );
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
    update_post_meta( $message_id, '_is_read', true ); // Хост сам отправил, значит прочитано
    update_post_meta( $message_id, '_status', 'sent' );
    update_post_meta( $message_id, '_thread_id', $thread_id );
    // Сохраняем роль отправителя (хост отвечает из админки)
    update_post_meta( $message_id, '_sender_role', 'host' );

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
