<?php
/**
 * The template for displaying property archive pages.
 *
 * @package Realty Theme
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <header class="page-header">
            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
        </header>
        
        <!-- Подключение шорткода фильтрации -->
        <div class="property-archive-filters">
            <?php echo do_shortcode('[property_filter]'); ?>
        </div>

        <!-- Контейнер для списка и карты -->
        <div class="property-layout">
            <div class="property-list">
                <?php
                // Вывод начального списка недвижимости
                echo property_filter_render_list();
                ?>
            </div>

            <!-- Контейнер для карты -->
            <div id="property-map-container" class="property-map-container">
                <h2>Расположение объектов на карте</h2>
                <div id="property-map" class="property-map"></div>
            </div>
        </div>
    </main>
</div>


<?php
get_footer();
?>