<?php
/**
 * The template for displaying property archive pages.
 *
 * @package Realty Theme
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main-map" role="main">
        <?php  get_template_part('template-parts/component/error-valid'); ?>
        <!-- Контейнер для фильтров, списка и карты -->
        <div class="property-archive-container">
            <!-- Сайдбар с фильтрами -->
            <aside class="property-filters-sidebar">
                <?php get_template_part('template-parts/content/list/property-filters'); ?>
            </aside>
            <!-- Основной контент: список и карта -->
            <div class="property-content-area">
                <!-- Контейнер для списка и карты -->
                <div class="property-layout">
                    <div class="content-card-list">
                        <div class="page-header">
                            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
                        </div>
                        <div class="property-list">
                           <?php get_template_part('template-parts/content/list/property-card-list'); ?>
                        </div>
                    </div>
                    <!-- Контейнер для карты -->
                    <div class="property-map-container">
                        <div id="property-map" class="property-map"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>


<?php
get_footer();
?>