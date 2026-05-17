<?php
/**
 * Шаблон личного кабинета
 * Модуль "Мой кабинет" для темы Realty Theme
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
get_header();

// ============================================================
// АРХИТЕКТУРА: URL содержит только property_id
// ============================================================
// Все данные бронирования читаются из БД (message meta).
// GET параметры (checkin_date, checkout_date, guests) НЕ используются.
// ============================================================

// Читаем только property_id из URL
$property_id = isset( $_GET['property_id'] ) ? intval( $_GET['property_id'] ) : 0;
$owner_id    = isset( $_GET['owner_id'] ) ? intval( $_GET['owner_id'] ) : 0;

// Показываем режим бронирования если есть property_id
$has_booking_context = ( $property_id > 0 );

// Находим booking_request для передачи в компоненты
$booking_id     = 0;
$booking_status = 'new';
$thread_id      = '';

if ( $has_booking_context && is_user_logged_in() ) {
    $current_user_id = get_current_user_id();
    
    // Сначала проверяем SESSION
    $session_data = realty_get_booking_session();
    
    if ( $session_data ) {
        // Есть SESSION — ищем активную заявку
        error_log( '[dashboard] SESSION found, looking for active booking: property_id=' . $property_id . ', client_id=' . $current_user_id );
        $active_booking = realty_find_active_booking( $property_id, $current_user_id );
        
        if ( $active_booking ) {
            $booking_id = $active_booking->ID;
            $booking_status = get_post_meta( $booking_id, '_status', true ) ?: 'new';
            $thread_id = get_post_meta( $booking_id, '_thread_id', true );
            error_log( '[dashboard] Active booking found: booking_id=' . $booking_id . ', status=' . $booking_status );
            
            // ВАЖНО: Если booking_request пустой (guests_count) — обновляем из SESSION
            $booking_guests = get_post_meta( $booking_id, '_guests_count', true );
            if ( empty( $booking_guests ) && $booking_status === 'new' ) {
                error_log( '[dashboard] booking_request пустой, обновляем из SESSION' );
                realty_update_booking_from_session( $booking_id, $session_data );
            }
        } else {
            error_log( '[dashboard] No active booking found for SESSION' );
        }
        // Если активной заявки нет — SESSION будет использован при создании
    } else {
        // SESSION нет — ищем активную или последнюю заявку
        error_log( '[dashboard] No SESSION, looking for booking_request: property_id=' . $property_id . ', client_id=' . $current_user_id );
        $booking_query = new WP_Query( array(
            'post_type'      => 'booking_request',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'   => '_property_id',
                    'value' => $property_id,
                ),
                array(
                    'key'   => '_client_id',
                    'value' => $current_user_id,
                ),
                array(
                    'key'     => '_status',
                    'value'   => array( 'new', 'in_progress', 'confirmed' ),
                    'compare' => 'IN',
                ),
            ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        if ( $booking_query->have_posts() ) {
            $booking_query->the_post();
            $booking_id = get_the_ID();
            $booking_status = get_post_meta( $booking_id, '_status', true ) ?: 'new';
            $thread_id = get_post_meta( $booking_id, '_thread_id', true );
            error_log( '[dashboard] Booking found: booking_id=' . $booking_id . ', status=' . $booking_status );
            wp_reset_postdata();
        } else {
            // booking_request НЕ НАЙДЕН (удален админом)
            // Редирект на /my/dashboard/ без параметров
            error_log( '[dashboard] No booking_request found, redirecting to clean dashboard' );
            wp_safe_redirect( '/my/dashboard/' );
            exit;
        }
    }
}


?>

<div class="my-dashboard-layout col-full">
    <!-- Боковое меню -->
    <?php get_template_part('my/component/my-sidebar'); ?>
    <!-- Основной контент -->
    <main class="my-main-content">
        <h1 class="my-page-title">
            <?php if($has_booking_context): ?>
               <a href="/my/dashboard/"><span class="material-symbols-outlined">arrow_back</span></a>
            <?php endif; ?>
            <?php esc_html_e( 'Личный кабинет', 'realty-theme' ); ?>
        </h1>
        
        <?php
        if ( $has_booking_context ) {
            // РЕЖИМ БРОНИРОВАНИЯ: показываем карточку и форму
            // card-booking.php сам получит все данные из БД (message meta)
            ?>
            <div class="my-content-grid">
                <!-- Левая колонка: Карточка бронирования -->
                <div class="my-content-main">
                    <!-- Карточка бронирования -->
                    <?php 
                    // card-booking.php получает данные из booking_request или SESSION
                    get_template_part('my/component/card-booking', null, array(
                        'property_id'   => $property_id,
                        'owner_id'      => $owner_id,
                        'booking_id'    => $booking_id,
                        'booking_status' => $booking_status,
                        'thread_id'     => $thread_id
                    )); 
                    ?>
                    <!-- Форма сообщения -->
                    <div class="my-message-section">
                        <h3 class="my-page-title"><?php esc_html_e( 'Написать хозяину', 'realty-theme' ); ?></h3>
                        <?php 
                        get_template_part('my/component/form-message', null, array(
                            'property_id'   => $property_id,
                            'owner_id'      => $owner_id,
                            'booking_id'    => $booking_id,
                            'booking_status' => $booking_status,
                            'context'       => 'booking_inquiry'
                        )); 
                        ?>
                    </div>
                </div>

                    <!-- Правая колонка: Боковая панель действий -->
                    <div class="my-content-sidebar">
                        <?php get_template_part('my/component/info-sidebar'); ?>
                    </div>
                </div>
            <?php
        } else {
            // РЕЖИМ БЕЗ PROPERTY_ID: показываем таблицу диалогов по объектам
            ?>
            <div class="my-content-grid">
                <!-- Левая колонка: Таблица диалогов -->
                <div class="my-content-main">
                    <?php get_template_part('my/component/my-table-messages'); ?>
                </div>
                <!-- Правая колонка: Боковая панель действий -->
                <div class="my-content-sidebar">
                    <?php get_template_part('my/component/info-sidebar'); ?>
                </div>
            </div>
            <?php
        }
        ?>
    </main>
</div>

<?php
get_footer();
