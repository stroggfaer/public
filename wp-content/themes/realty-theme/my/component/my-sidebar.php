<?php
/**
 * Aside сайдбар
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<aside class="my-sidebar">
    <nav class="my-sidebar-nav">
        <a href="<?php echo esc_url( home_url( '/my/dashboard/' ) ); ?>" class="my-sidebar-item my-sidebar-item--active">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="my-sidebar-label">Главная</span>
        </a>
        <a href="<?php echo esc_url( home_url( '/my/favorites/' ) ); ?>" class="my-sidebar-item">
            <span class="material-symbols-outlined">favorite</span>
            <span class="my-sidebar-label">Избраный</span>
        </a>
        <a href="<?php echo esc_url( home_url( '/my/settings/' ) ); ?>" class="my-sidebar-item">
            <span class="material-symbols-outlined">settings</span>
            <span class="my-sidebar-label">Настройки</span>
        </a>
    </nav>

    <div class="my-sidebar-footer">
        <a href="<?php echo esc_url( home_url( '/help/' ) ); ?>" class="my-sidebar-item">
            <span class="material-symbols-outlined">help</span>
            <span class="my-sidebar-label">Помощь</span>
        </a>
        <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="my-sidebar-item my-sidebar-item--logout">
            <span class="material-symbols-outlined">logout</span>
            <span class="my-sidebar-label">Выйти</span>
        </a>
    </div>
</aside>
