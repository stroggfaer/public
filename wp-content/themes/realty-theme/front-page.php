<?php
/**
 * Шаблон главной страницы
 *
 * Отображает визуальный баннер с формой поиска недвижимости
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        
        <!-- Визуальный баннер с формой поиска -->
        <section class="hero-banner">
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">Найдите идеальное жилье для вашего отдыха</h1>
                    <p class="hero-subtitle">Более 10,000 вариантов аренды по всему миру</p>
                
                    <!-- Форма поиска недвижимости -->
                    <div class="search-form-container">
                        <?php echo property_filter_render_home_form(); ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Дополнительный контент -->
        <section class="featured-properties">
            <div class="container">
                <h2 class="section-title">Популярные предложения</h2>
                <?php
                // Выводим несколько популярных объектов недвижимости
                $featured_properties = pods('property', array(
                    'limit' => 6,
                    'orderby' => 'RAND()',
                    'where' => 't.post_status = "publish"'
                ));
                
                if ($featured_properties->total() > 0) {
                    echo '<div class="properties-grid">';
                    while ($featured_properties->fetch()) {
                        get_template_part('template-parts/component/property-card', null, array('pod' => $featured_properties));
                    }
                    echo '</div>';
                } else {
                    echo '<p>Популярные предложения временно отсутствуют.</p>';
                }
                ?>
            </div>
        </section>

    </main>
</div>

<?php get_footer(); ?>