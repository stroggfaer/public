<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
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
