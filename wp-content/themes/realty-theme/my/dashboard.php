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
                <?php get_template_part('my/component/card-booking'); ?>
            </div>

            <!-- Правая колонка: Боковая панель действий -->
            <div class="my-content-sidebar">
                <?php get_template_part('my/component/info-sidebar'); ?>
            </div>
        </div>
    </main>
</div>

<?php
get_footer();
