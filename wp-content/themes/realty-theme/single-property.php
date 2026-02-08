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
            ?>
            
            <div class="property-gallery">
                <?php
                // Получаем галерею изображений
                $pod = pods('property', get_the_ID());
                $gallery_images = $pod->field('gallery');
                if (!empty($gallery_images) && is_array($gallery_images)) {
                    echo '<div class="property-gallery-slider">';
                    foreach ($gallery_images as $image_id) {
                        $image_src = wp_get_attachment_image_src($image_id, 'large');
                        if ($image_src) {
                            echo '<div class="gallery-item">';
                            echo '<img src="' . esc_url($image_src[0]) . '" alt="' . esc_attr(get_the_title($image_id)) . '">';
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                } else {
                    // Если нет галереи, показываем стандартное изображение
                    if (has_post_thumbnail()) {
                        the_post_thumbnail('large');
                    } else {
                        echo '<img src="' . esc_url(get_template_directory_uri()) . '/assets/images/no_image.png" alt="Нет изображения">';
                    }
                }
                ?>
            </div>

            <div class="property-details">
                <h1><?php the_title(); ?></h1>
                
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

                <div class="property-contact">
                    <button class="btn btn-primary contact-button">Связаться</button>
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

        <?php endwhile; ?>
    </div>
</div>

<?php get_footer(); ?>