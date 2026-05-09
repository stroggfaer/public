<?php
/**
 * Шаблон страницы "Избранное"
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
        <div class="my-content-wrapper">
            <h1 class="my-page-title"><?php esc_html_e( 'Избранное', 'realty-theme' ); ?></h1>
            
            <div class="my-content-main">
                <div class="my-empty-state">
                    <span class="material-symbols-outlined my-empty-icon">favorite_border</span>
                    <p class="my-empty-text"><?php esc_html_e( 'У вас пока нет избранных объектов', 'realty-theme' ); ?></p>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
get_footer();
