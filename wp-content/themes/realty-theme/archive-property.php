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
                            <!-- Vue 3 Hello World Test -->
                            <div id="vue-app">
                                <hello-word></hello-word>
                            </div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    console.log('Vue:', typeof Vue);
                                    console.log('Vuex:', typeof Vuex);
                                    console.log('VueAppModule:', typeof VueAppModule);
                                    
                                    if (typeof VueAppModule !== 'undefined' && typeof Vue !== 'undefined') {
                                        const app = VueAppModule.createAppModule({
                                            components: { HelloWorld: VueAppModule.HelloWorld },
                                            template: '<HelloWorld></HelloWorld>'
                                        });
                                        app.mount('#vue-app');
                                        console.log('Vue 3 initialized successfully!');
                                    } else {
                                        console.error('VueAppModule or Vue not found!');
                                    }
                                });
                            </script>
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