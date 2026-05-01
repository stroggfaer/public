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

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        
        <div class="my-cabinet-container">
            <header class="my-cabinet-header">
                <h1 class="my-cabinet-title"><?php esc_html_e( 'Личный кабинет', 'realty-theme' ); ?></h1>
                <p class="my-cabinet-subtitle"><?php esc_html_e( 'Добро пожаловать в ваш личный кабинет', 'realty-theme' ); ?></p>
            </header>

            <div class="my-cabinet-content">
                <!-- Здесь будет контент личного кабинета -->
                <div class="my-cabinet-welcome">
                    <h2><?php esc_html_e( 'Добро пожаловать!', 'realty-theme' ); ?></h2>
                    <p><?php esc_html_e( 'Здесь вы можете управлять своими заявками, избранными объектами и другими настройками.', 'realty-theme' ); ?></p>
                </div>
            </div>
        </div>

    </main>
</div>

<?php
get_footer();
