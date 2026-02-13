<?php
/**
 * Шаблон страницы детального просмотра объекта недвижимости
 *
 * @package WordPress
 * @subpackage Realty Theme
 */
get_header();

?>

<div class="col-full">
    <div class="property-detail">
        <?php
        // Loop through the post
        while (have_posts()) :
            the_post();
            $pod = pods('property', get_the_ID());
            $address = $pod->field('address') ?? false;
            $price = $pod->field('price'); // Цена

            // Получаем город из таксономии location
            $locations = get_the_terms(get_the_ID(), 'location');
            $city = !is_wp_error($locations) && !empty($locations) ? $locations[0]->name : '';

            // Формируем отображение: г. Город, Адрес
            $location_display = trim('г. ' . $city . ', ' . $address, ', ');
//            echo '<pre>';
//            print_r($pod);
//            echo '</pre>';
            ?>
            <?php  get_template_part( 'template-parts/component/breadcrumbs'); ?>
            <div class="property-columns">
                <div class="property-content">
                    <!--Блок галерия-->
                    <?php  get_template_part( 'template-parts/content/single/property-gallery',null, array('pod' => $pod)); ?>
                    <!--./Блок галерия-->
                    <div class="property-details">
                        <div class="entry-header">
                            <h1><?php the_title(); ?></h1>
                            <div class="action">
                                <div class="share js-share"><span class="material-symbols-outlined">share</span></div>
                                <div class="favorite js-favorite"><span class="material-symbols-outlined">favorite</span></div>
                            </div>
                        </div>
                        <div class="location">
                            <span class="material-symbols-outlined">location_on</span>
                            <?=esc_html($location_display)?>
                        </div>
                        <?php  get_template_part( 'template-parts/content/single/property-features',null, array('pod' => $pod)); ?>

                        <?php  get_template_part( 'template-parts/content/single/property-rules',null, array('pod' => $pod)); ?>

                        <div class="section-property property-description">
                            <h3 class="title">Описание</h3>
                            <?php the_content(); ?>
                        </div>

                        <?php  get_template_part( 'template-parts/content/single/property-characteristics',null, array('pod' => $pod)); ?>

                        <?php  get_template_part( 'template-parts/content/single/property-map',null, array('pod' => $pod)); ?>
                    </div>
                </div>

                <aside class="property-aside">
                    <?php  get_template_part( 'template-parts/content/aside/property-aside',null, array('pod' => $pod)); ?>
                </aside>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php get_footer(); ?>
