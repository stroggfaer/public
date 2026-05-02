<?php
/**
 * Шаблон страницы "Сообщения"
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

<div class="my-dashboard-layout">
    <!-- Боковое меню -->
    <aside class="my-sidebar">
        <nav class="my-sidebar-nav">
            <a href="<?php echo esc_url( home_url( '/my/dashboard/' ) ); ?>" class="my-sidebar-item">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="my-sidebar-label">Dashboard</span>
            </a>
            <a href="<?php echo esc_url( home_url( '/my/favorites/' ) ); ?>" class="my-sidebar-item">
                <span class="material-symbols-outlined">favorite</span>
                <span class="my-sidebar-label">Favorites</span>
            </a>
            <a href="<?php echo esc_url( home_url( '/my/messages/' ) ); ?>" class="my-sidebar-item my-sidebar-item--active">
                <span class="material-symbols-outlined">chat</span>
                <span class="my-sidebar-label">Messages</span>
            </a>
            <a href="<?php echo esc_url( home_url( '/my/settings/' ) ); ?>" class="my-sidebar-item">
                <span class="material-symbols-outlined">settings</span>
                <span class="my-sidebar-label">Settings</span>
            </a>
        </nav>
        
        <div class="my-sidebar-footer">
            <a href="<?php echo esc_url( home_url( '/help/' ) ); ?>" class="my-sidebar-item">
                <span class="material-symbols-outlined">help</span>
                <span class="my-sidebar-label">Help Center</span>
            </a>
            <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="my-sidebar-item my-sidebar-item--logout">
                <span class="material-symbols-outlined">logout</span>
                <span class="my-sidebar-label">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Основной контент -->
    <main class="my-main-content">
        <div class="my-content-wrapper">
            <h1 class="my-page-title"><?php esc_html_e( 'Сообщения', 'realty-theme' ); ?></h1>
            
            <div class="my-content-main">
                <div class="my-empty-state">
                    <span class="material-symbols-outlined my-empty-icon">chat_bubble_outline</span>
                    <p class="my-empty-text"><?php esc_html_e( 'У вас пока нет сообщений', 'realty-theme' ); ?></p>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
get_footer();
