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

                        <div class="property-features">
                            <div class="feature-item">
                                <div class="icon-circle">
                                    <span class="material-symbols-outlined">group</span>
                                </div>
                                <div class="feature-text">
                                    <span class="label">GUESTS</span>
                                    <span class="value">Up to 4</span>
                                </div>
                            </div>

                            <div class="feature-item">
                                <div class="icon-circle">
                                    <span class="material-symbols-outlined">bed</span>
                                </div>
                                <div class="feature-text">
                                    <span class="label">BEDROOMS</span>
                                    <span class="value">2 Queen</span>
                                </div>
                            </div>

                            <div class="feature-item">
                                <div class="icon-circle">
                                    <span class="material-symbols-outlined">bathtub</span>
                                </div>
                                <div class="feature-text">
                                    <span class="label">BATHS</span>
                                    <span class="value">1.5 Baths</span>
                                </div>
                            </div>

                            <div class="feature-item">
                                <div class="icon-circle">
                                    <span class="material-symbols-outlined">wifi</span>
                                </div>
                                <div class="feature-text">
                                    <span class="label">FIBER</span>
                                    <span class="value">500 Mbps</span>
                                </div>
                            </div>
                        </div>

                        <div class="property-description">
                            <h3 class="title">Описание</h3>
                            <?php the_content(); ?>
                        </div>

                        <div class="property-key-characteristics">
                            <h3 class="title">Основные характеристики</h3>
                            <div class="characteristics-grid">
                                <div class="characteristic-item">
                                    <span class="material-symbols-outlined">pets</span>
                                    <span class="label">Pet Friendly</span>
                                    <span class="value">Allowed</span>
                                </div>
                                <div class="characteristic-item">
                                    <span class="material-symbols-outlined">work</span>
                                    <span class="label">Workstation</span>
                                    <span class="value">Ergonomic</span>
                                </div>
                                <div class="characteristic-item">
                                    <span class="material-symbols-outlined">thermostat</span>
                                    <span class="label">Heating</span>
                                    <span class="value">Geothermal</span>
                                </div>
                                <div class="characteristic-item">
                                    <span class="material-symbols-outlined">ac_unit</span>
                                    <span class="label">Cooling</span>
                                    <span class="value">Air Purifier</span>
                                </div>
                                <div class="characteristic-item">
                                    <span class="material-symbols-outlined">kitchen</span>
                                    <span class="label">Kitchen</span>
                                    <span class="value">Fully Equipped</span>
                                </div>
                                <div class="characteristic-item">
                                    <span class="material-symbols-outlined">ev_charger</span>
                                    <span class="label">EV Charging</span>
                                    <span class="value">Level 2</span>
                                </div>
                            </div>
                            <div class="action"><a href="#" class="bold border all">Показыть все</a></div>
                        </div>

                        <div class="property-map">
                            <h3 class="title">Местоположение</h3>
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
