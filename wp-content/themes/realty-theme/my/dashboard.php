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
?>

<div class="my-dashboard-layout col-full">
    <!-- Боковое меню -->
    <?php get_template_part('my/component/my-sidebar'); ?>
    <!-- Основной контент -->
    <main class="my-main-content">
        <h1 class="my-page-title"><?php esc_html_e( 'Личный кабинет', 'realty-theme' ); ?></h1>
        
        <?php
        // Читаем GET параметры для режима бронирования
        $property_id   = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
        $checkin_date  = isset($_GET['checkin_date']) ? sanitize_text_field($_GET['checkin_date']) : '';
        $checkout_date = isset($_GET['checkout_date']) ? sanitize_text_field($_GET['checkout_date']) : '';
        $owner_id      = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;
        
        // Валидация дат на сервере
        $has_booking_context = ($property_id > 0 && $checkin_date && $checkout_date);
        
        if ($has_booking_context) {
            // Проверяем корректность дат
            $checkin_timestamp = strtotime($checkin_date);
            $checkout_timestamp = strtotime($checkout_date);
            
            if ($checkin_timestamp && $checkout_timestamp && $checkin_timestamp < $checkout_timestamp) {
                // РЕЖИМ С GET ПАРАМЕТРАМИ: показываем карточку бронирования и форму
                ?>
                <div class="my-content-grid">
                    <!-- Левая колонка: Карточка бронирования -->
                    <div class="my-content-main">
                        <!-- Карточка бронирования -->
                        <?php 
                        get_template_part('my/component/card-booking', null, array(
                            'property_id'   => $property_id,
                            'checkin_date'  => $checkin_date,
                            'checkout_date' => $checkout_date,
                            'owner_id'      => $owner_id
                        )); 
                        ?>
                        <!-- Форма сообщения -->
                        <div class="my-message-section">
                            <h3 class="my-page-title"><?php esc_html_e( 'Написать хозяину', 'realty-theme' ); ?></h3>
                            <?php 
                            get_template_part('my/component/form-message', null, array(
                                'property_id'   => $property_id,
                                'owner_id'      => $owner_id,
                                'checkin_date'  => $checkin_date,
                                'checkout_date' => $checkout_date,
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
                // Ошибка валидации дат
                ?>
                <div class="my-error-message">
                    <p><?php esc_html_e('Ошибка: Неверный формат дат. Пожалуйста, выберите корректные даты заезда и выезда.', 'realty-theme'); ?></p>
                    <a href="/property/" class="button__com"><?php esc_html_e('Вернуться к поиску', 'realty-theme'); ?></a>
                </div>
                <?php
            }
        } else {
            // РЕЖИМ БЕЗ GET ПАРАМЕТРОВ: обычный dashboard (будет реализован в следующей итерации)
            ?>
            <div class="my-content-grid">
                <!-- Левая колонка: Список диалогов (заглушка) -->
                <div class="my-content-main">
                    <div class="my-threads-placeholder">
                        <h2><?php esc_html_e('Ваши сообщения', 'realty-theme'); ?></h2>
                        <p><?php esc_html_e('У вас пока нет сообщений. Выберите недвижимость и напишите хозяину.', 'realty-theme'); ?></p>
                        <a href="/property/" class="button__com"><?php esc_html_e('Перейти к поиску', 'realty-theme'); ?></a>
                    </div>
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
