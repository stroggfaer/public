<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
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
            <span class="my-host-status"></span>
            <div class="avatar-placeholder">
                <span class="material-symbols-outlined">person</span>
            </div>
            <!--                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=80&h=80&fit=crop" alt="Хозяин" />-->
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