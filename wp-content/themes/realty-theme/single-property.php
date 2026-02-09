<?php
/**
 * Шаблон страницы детального просмотра объекта недвижимости
 *
 * @package WordPress
 * @subpackage Realty Theme
 */

get_header(); ?>

<div class="col-full">
    <div class="property-detail">
        <?php
        // Loop through the post
        while (have_posts()) :
            the_post();
            $pod = pods('property', get_the_ID());
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
                            Reine, Lofoten Islands, Norway
                        </div>

                        <div class="property-description">
                            <?php the_content(); ?>
                        </div>

                        <div class="property-features">
                            <h3>Основные характеристики</h3>
                            <ul>
                                <?php
                                // Получаем данные из пользовательских полей (Pods)
                                $price = $pod->field('price');
                                if (!empty($price)) {
                                    echo '<li><strong>Цена:</strong> ' . esc_html($price) . '</li>';
                                }

                                $area = $pod->field('area');
                                if (!empty($area)) {
                                    echo '<li><strong>Площадь:</strong> ' . esc_html($area) . ' м²</li>';
                                }

                                $rooms = $pod->field('rooms');
                                if (!empty($rooms)) {
                                    echo '<li><strong>Количество комнат:</strong> ' . esc_html($rooms) . '</li>';
                                }

                                $floor = $pod->field('floor');
                                if (!empty($floor)) {
                                    echo '<li><strong>Этаж:</strong> ' . esc_html($floor) . '</li>';
                                }

                                $address = $pod->field('address');
                                if (!empty($address)) {
                                    echo '<li><strong>Адрес:</strong> ' . esc_html($address) . '</li>';
                                }

                                // Получаем таксономию location
                                $location_terms = get_the_terms(get_the_ID(), 'location');
                                if (!empty($location_terms) && !is_wp_error($location_terms)) {
                                    $location_names = wp_list_pluck($location_terms, 'name');
                                    echo '<li><strong>Расположение:</strong> ' . esc_html(implode(', ', $location_names)) . '</li>';
                                }
                                ?>
                            </ul>
                        </div>

                        <div class="property-map">
                            <h3>Местоположение</h3>
                            <?php
                            // Получаем координаты для отображения карты
                            // Используем Pods для получения значений полей
                            $pod = pods('property', get_the_ID());
                            $latitude = $pod->field('latitude');
                            $longitude = $pod->field('longitude');

                            if (!empty($latitude) && !empty($longitude)) {
                                echo '<div id="property-map" style="width: 100%; height: 400px;" data-latitude="' . esc_attr($latitude) . '" data-longitude="' . esc_attr($longitude) . '"></div>';
                            } else {
                                echo '<p>Местоположение не указано</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <aside class="property-aside">
                    <div class="property-contact-card">
                        <div class="card-header">
                            <div class="price">
                                <span class="amount">27 075 ₽</span>
                                <span class="period">/ ночь</span>
                            </div>
                            <div class="rating">
                                <span class="material-symbols-outlined star-icon">star</span>
                                <span class="rating-value">4.98</span>
                                <span class="reviews-count">(124)</span>
                            </div>
                        </div>

                        <div class="host-info">
                            <div class="avatar-placeholder">
                                <span class="material-symbols-outlined">person</span>
                            </div>
                            <div class="host-details">
                                <h3>Хозяин: Сара</h3>
                                <div class="hint">Отвечает в течение часа</div>
                            </div>
                        </div>

                        <div class="contact-card-wrap">
                            <button class="button__com contact-button">Написать хозяину</button>
                            <button class="button__com outline info-button">Узнать о длительном проживании</button>
                        </div>

                        <div class="card-footer">
                            <span class="material-symbols-outlined security-icon">verified_user</span>
                            <p>Личность подтверждена и безопасная переписка</p>
                        </div>
                    </div>
                </aside>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php get_footer(); ?>