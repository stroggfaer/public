<?php
/**
 * Страница админки для управления сообщениями
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
 * Добавление раздела "Бронирование" в меню админки
 * Структура аналогична "Недвижимость":
 *   Бронирование
 *   └─ Сообщения
 */
function my_cabinet_add_messages_menu() {
    // Родительский пункт меню "Бронирование" (без callback — не кликабельный заголовок)
    add_menu_page(
        'Бронирование',
        'Бронирование',
        'manage_options',
        'booking',
        '__return_null',
        'dashicons-calendar-alt',
        6  // После Недвижимость (position 5)
    );

    // Подпункт "Сообщения"
    add_submenu_page(
        'booking',
        'Сообщения',
        'Сообщения',
        'manage_options',
        'booking-messages',
        'my_cabinet_messages_page'
    );

    // Убираем дублирующий первый подпункт "Бронирование", который WordPress добавляет автоматически
    remove_submenu_page( 'booking', 'booking' );
}
add_action( 'admin_menu', 'my_cabinet_add_messages_menu' );

/**
 * Очистка кэша меню после изменения позиции
 */
function my_cabinet_flush_menu_cache() {
    if ( ! get_option( 'booking_menu_position_updated', false ) ) {
        delete_option( 'menu_order' );
        update_option( 'booking_menu_position_updated', true );
    }
}
add_action( 'admin_init', 'my_cabinet_flush_menu_cache' );

/**
 * Подключение CSS и JS для страницы сообщений
 */
function my_cabinet_enqueue_messages_assets( $hook ) {
    // Подключаем только на странице Сообщений (подпункт раздела Бронирование)
    // Hook suffix для подпунктов: {parent_slug}_page_{child_slug} (дефисы → подчёркивания)
    $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
    if ( 'booking-messages' !== $page ) {
        return;
    }
    
    // Получаем текущий view и thread_id для условной загрузки скриптов
    $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
    $thread_id = isset( $_GET['thread_id'] ) ? sanitize_text_field( wp_unslash( $_GET['thread_id'] ) ) : '';
    
    // CSS
    wp_enqueue_style(
        'my-cabinet-messages-admin',
        get_template_directory_uri() . '/inc/admin/assets/css/messages.css',
        array(),
        '1.0.1'
    );
    
    // JS для списка сообщений
    wp_enqueue_script(
        'my-cabinet-messages-admin',
        get_template_directory_uri() . '/inc/admin/assets/js/messages-admin.js',
        array(),
        '1.0.0',
        true
    );
    
    // JS для страницы диалога
    wp_enqueue_script(
        'my-cabinet-dialog-view',
        get_template_directory_uri() . '/inc/admin/assets/js/dialog-view.js',
        array(),
        '1.0.0',
        true
    );

    // Передаем данные в JS для страницы списка
    wp_localize_script( 'my-cabinet-messages-admin', 'myCabinetMessagesData', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'getDialogsNonce' => wp_create_nonce( 'my_cabinet_get_dialogs_nonce' ),
        'getDialogNonce' => wp_create_nonce( 'my_cabinet_get_dialog_nonce' ),
        'sendReplyNonce' => wp_create_nonce( 'my_cabinet_send_reply_nonce' ),
        'deleteMessageNonce' => wp_create_nonce( 'my_cabinet_delete_message_nonce' ),
        'markReadNonce' => wp_create_nonce( 'my_cabinet_mark_read_nonce' ),
        'currentUserId' => get_current_user_id(),
    ) );
    
    // Передаем данные в JS для страницы диалога
    if ( $view === 'dialog' && ! empty( $thread_id ) ) {
        wp_localize_script( 'my-cabinet-dialog-view', 'dialogViewData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'threadId' => $thread_id,
            'getDialogNonce' => wp_create_nonce( 'my_cabinet_get_dialog_nonce' ),
            'sendReplyNonce' => wp_create_nonce( 'my_cabinet_send_reply_nonce' ),
        ) );
    }
}
add_action( 'admin_enqueue_scripts', 'my_cabinet_enqueue_messages_assets' );

/**
 * Добавление счетчика непрочитанных сообщений в меню
 */
function my_cabinet_add_unread_count_to_menu() {
    global $menu;
    
    $unread_count = my_cabinet_get_unread_messages_count();
    
    if ( $unread_count > 0 ) {
        foreach ( $menu as $key => $value ) {
            if ( 'booking' === $menu[ $key ][2] ) {
                $menu[ $key ][0] .= ' <span class="awaiting-mod">' . $unread_count . '</span>';
                break;
            }
        }
    }
}
add_action( 'admin_menu', 'my_cabinet_add_unread_count_to_menu', 999 );

/**
 * Отображение страницы сообщений
 */
function my_cabinet_messages_page() {
    // Проверяем, нужно ли показать страницу диалога
    $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
    $thread_id = isset( $_GET['thread_id'] ) ? sanitize_text_field( wp_unslash( $_GET['thread_id'] ) ) : '';
    
    if ( $view === 'dialog' && ! empty( $thread_id ) ) {
        my_cabinet_dialog_view_page( $thread_id );
        return;
    }
    
    // Обычная страница со списком сообщений
    my_cabinet_messages_list_page();
}

/**
 * Страница списка сообщений
 */
function my_cabinet_messages_list_page() {
    // Подсчет непрочитанных сообщений
    $unread_count = my_cabinet_get_unread_messages_count();
    
    ?>
    <div class="wrap" id="my-cabinet-messages-admin">
        <h1 class="wp-heading-inline">
            Сообщения
            <?php if ( $unread_count > 0 ): ?>
                <span class="awaiting-mod"><?php echo esc_html( $unread_count ); ?></span>
            <?php endif; ?>
        </h1>
        <hr class="wp-header-end">

        <div class="messages-container">
            <div class="messages-filters">
                <h3>Фильтры</h3>
                <div class="filter-row">
                    <input type="date" id="filter-date" class="regular-text" placeholder="Дата" />
                    <input type="text" id="filter-login" class="regular-text" placeholder="Логин" />
                    <input type="text" id="filter-subject" class="regular-text" placeholder="Тема" />
                    <button type="button" id="apply-filters" class="button button-primary">Применить</button>
                    <button type="button" id="clear-filters" class="button">Сбросить</button>
                </div>
            </div>
            <p class="unread-notice" style="display:none;">
                    У вас новые сообщения: <strong class="unread-count">0</strong>
                </p>
            </div>

            <table class="wp-list-table widefat fixed striped messages-table">
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th class="col-login">Логин</th>
                        <th class="col-name">Имя</th>
                        <th class="col-subject">Заголовок</th>
                        <th class="col-date">Дата</th>
                        <th class="col-actions">Действия</th>
                    </tr>
                </thead>
                <tbody id="messages-list">
                    <tr>
                        <td colspan="6" class="loading">Загрузка...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Подсчет непрочитанных сообщений для хоста
 */
function my_cabinet_get_unread_messages_count() {
    $host_id = get_current_user_id();
    
    $args = array(
        'post_type'      => 'message',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            // Только сообщения, где хозяин - получатель
            array(
                'key'     => '_receiver_id',
                'value'   => $host_id,
                'compare' => '=',
            ),
            // Только непрочитанные
            array(
                'key'     => '_is_read',
                'value'   => '0',
                'compare' => '=',
            ),
            // Исключаем сообщения, где хозяин - отправитель
            array(
                'key'     => '_sender_id',
                'value'   => $host_id,
                'compare' => '!=',
            ),
        ),
    );

    $query = new WP_Query( $args );
    return $query->found_posts;
}

/**
 * Страница просмотра диалога
 */
function my_cabinet_dialog_view_page( $thread_id ) {
    // Получаем информацию о диалоге
    $args = array(
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
        'order'          => 'DESC',
    );

    $query = new WP_Query( $args );
    
    if ( ! $query->have_posts() ) {
        echo '<div class="wrap"><h1>Диалог не найден</h1>';
        echo '<a href="' . admin_url( 'admin.php?page=booking-messages' ) . '" class="button">← Назад к списку</a></div>';
        return;
    }

    // Получаем информацию о пользователе
    $first_message = $query->posts[0];
    $sender_id = get_post_meta( $first_message->ID, '_sender_id', true );
    $receiver_id = get_post_meta( $first_message->ID, '_receiver_id', true );
    $sender = get_userdata( $sender_id );
    
    // URL для возврата
    $back_url = admin_url( 'admin.php?page=booking-messages' );
    
    ?>
    <div class="wrap dialog-view-page">
        <h1 class="wp-heading-inline">
            Диалог с <?php echo $sender ? esc_html( $sender->display_name ) : 'Пользователем'; ?>
        </h1>
        <a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action">← Назад к списку</a>
        <hr class="wp-header-end">

        <div class="dialog-info-box">
            <strong>Логин:</strong> <?php echo $sender ? esc_html( $sender->user_login ) : '—'; ?> | 
            <strong>Email:</strong> <?php echo $sender ? esc_html( $sender->user_email ) : '—'; ?> | 
            <strong>ID диалога:</strong> <?php echo esc_html( $thread_id ); ?>
        </div>

        <div id="dialog-messages-container" class="dialog-messages-view">
            <div class="loading-messages">Загрузка сообщений...</div>
        </div>

        <div class="dialog-reply-box">
            <h3>Ответить</h3>
            <textarea id="dialog-reply-textarea" rows="5" placeholder="Введите ваше сообщение..."></textarea>
            <button type="button" id="dialog-send-btn" class="button button-primary button-hero">
                Отправить сообщение
            </button>
            <span id="dialog-send-status" class="send-status"></span>
        </div>
    </div>
    <?php
}
