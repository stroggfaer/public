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
        <div class="my-content-grid">
            <!-- Левая колонка: Карточка бронирования -->
            <div class="my-content-main">
                <!-- Карточка бронирования -->
                <div class="my-booking-card">
                    <!-- Заголовок карточки -->
                    <div class="my-booking-header">
                        <div class="my-booking-image">
                            <!--                                <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=400&h=300&fit=crop" alt="Объект недвижимости" />-->
                        </div>
                        <div class="my-booking-info">
                            <div class="my-booking-rating">
                                <span class="material-symbols-outlined my-rating-star">star</span>
                                <span class="my-rating-value">4.9 (128)</span>
                                <span class="my-rating-badge"><?php esc_html_e( 'Подтверждено', 'realty-theme' ); ?></span>
                            </div>
                            <h2 class="my-booking-title">Современный пентхаус с видом на набережную</h2>
                            <div class="my-booking-location">
                                <span class="material-symbols-outlined">location_on</span>
                                <span>Центральный район, д. 42</span>
                            </div>
                            <div class="my-booking-status my-booking-status--pending">
                                <?php esc_html_e( 'ОЖИДАНИЕ ЗАЕЗДА', 'realty-theme' ); ?>
                            </div>
                        </div>
                        <div class="my-booking-price">
                            <div class="my-price-amount">12 500 ₽</div>
                            <div class="my-price-period"><?php esc_html_e( 'ЗА СУТКИ', 'realty-theme' ); ?></div>
                        </div>
                    </div>

                    <!-- Детали проживания -->
                    <div class="my-booking-details">
                        <div class="my-details-section">
                            <h3 class="my-details-title"><?php esc_html_e( 'ДЕТАЛИ ПРОЖИВАНИЯ', 'realty-theme' ); ?></h3>
                            <div class="my-details-row">
                                <span class="my-details-label"><?php esc_html_e( 'Дата заезда', 'realty-theme' ); ?></span>
                                <span class="my-details-value">15 Октября, 2024</span>
                            </div>
                            <div class="my-details-row">
                                <span class="my-details-label"><?php esc_html_e( 'Дата выезда', 'realty-theme' ); ?></span>
                                <span class="my-details-value">22 Октября, 2024</span>
                            </div>
                            <div class="my-details-row">
                                <span class="my-details-label"><?php esc_html_e( 'Хозяин', 'realty-theme' ); ?></span>
                                <span class="my-details-value my-details-value--link"><?php esc_html_e( 'Суперхозяин', 'realty-theme' ); ?></span>
                            </div>
                        </div>

                        <div class="my-details-section">
                            <h3 class="my-details-title"><?php esc_html_e( 'РАСПОЛОЖЕНИЕ', 'realty-theme' ); ?></h3>
                            <div class="my-details-row">
                                <span class="my-details-label"><?php esc_html_e( 'Адрес', 'realty-theme' ); ?></span>
                                <span class="my-details-value">Набережная Пресненская, 12, Москва</span>
                            </div>
                            <div class="my-details-row">
                                <a href="#" class="my-details-link">
                                    <span class="material-symbols-outlined">navigation</span>
                                    <span><?php esc_html_e( 'Показать маршрут', 'realty-theme' ); ?></span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Правая колонка: Боковая панель действий -->
            <aside class="my-content-sidebar">
                <div class="my-actions-panel">
                    <h3 class="my-actions-title"><?php esc_html_e( 'Действия', 'realty-theme' ); ?></h3>

                    <a href="#" class="my-action-btn my-action-btn--primary">
                        <span class="material-symbols-outlined">chat</span>
                        <span><?php esc_html_e( 'Написать хозяину', 'realty-theme' ); ?></span>
                    </a>

                    <a href="#" class="my-action-btn my-action-btn--secondary">
                        <span class="material-symbols-outlined">calendar_today</span>
                        <span><?php esc_html_e( 'Узнать о длительном проживании', 'realty-theme' ); ?></span>
                    </a>

                    <div class="my-host-info">
                        <div class="my-host-avatar">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=80&h=80&fit=crop" alt="Хозяин" />
                            <span class="my-host-status"></span>
                        </div>
                        <div class="my-host-details">
                            <div class="my-host-name"><?php esc_html_e( 'Хозяин отвечает', 'realty-theme' ); ?></div>
                            <div class="my-host-response"><?php esc_html_e( 'Обычно в течение 15 минут', 'realty-theme' ); ?></div>
                        </div>
                    </div>

                    <div class="my-info-notice">
                        <span class="material-symbols-outlined my-notice-icon">info</span>
                        <p class="my-notice-text">
                            <?php esc_html_e( 'Вы можете изменить даты бронирования или отменить его до 12 октября без комиссии.', 'realty-theme' ); ?>
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</div>

<?php
get_footer();
