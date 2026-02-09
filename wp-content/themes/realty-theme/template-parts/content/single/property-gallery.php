<?php
/**
 * Шаблон Галлерия недвижимости
 *
 * @param object $pod Pods объект недвижимости
 */
if (!defined('ABSPATH')) {
    exit;
}

$pod = $args['pod'] ?? null;
if (!$pod) {
    return;
}
?>

<div class="property-gallery-container">
    <?php

    $gallery_images = $pod->field('gallery');

    // Очищаем массив от пустых элементов и проверяем, что это массив
    if (!empty($gallery_images)) {
        $gallery_images = is_array($gallery_images) ? array_filter($gallery_images) : array($gallery_images);
    }

    if (!empty($gallery_images)) :
        $count = count($gallery_images);
        ?>
        <div class="property-gallery <?php echo ($count === 1) ? 'single-image-mode' : ''; ?>">

            <div id="main-slider" class="splide main-slider-wrapper">
                <div class="splide__track">
                    <ul class="splide__list">
                        <?php foreach ($gallery_images as $image_id) :
                            $img = wp_get_attachment_image_src($image_id, 'large');
                            if ($img) : ?>
                                <li class="splide__slide">
                                    <img src="<?php echo esc_url($img[0]); ?>" alt="Property Image">
                                </li>
                            <?php endif;
                        endforeach; ?>
                    </ul>
                </div>
            </div>

            <?php if ($count > 1) : ?>
                <div id="thumbnail-slider" class="splide thumb-slider-wrapper">
                    <div class="splide__track">
                        <ul class="splide__list">
                            <?php foreach ($gallery_images as $image_id) :
                                $thumb = wp_get_attachment_image_src($image_id, 'thumbnail');
                                if ($thumb) : ?>
                                    <li class="splide__slide">
                                        <img src="<?php echo esc_url($thumb[0]); ?>" alt="Thumbnail">
                                    </li>
                                <?php endif;
                            endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    <?php else : ?>
        <div class="property-gallery-empty">
            <?php the_post_thumbnail('large'); ?>
        </div>
    <?php endif; ?>
</div>

