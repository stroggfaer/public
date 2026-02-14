<?php
/**
 * Шаблон: Галерея объекта недвижимости
 *
 * Логика:
 * - если галерея Pods пустая → показываем featured image (если есть)
 * - иначе → всегда карусель Splide (даже если 1 фото)
 */

if (!defined('ABSPATH')) {
    exit;
}

$pod = $args['pod'] ?? null;
if (!$pod) {
    return;
}

$post_id    = get_the_ID();
$post_title = get_the_title($post_id);

// Получаем данные галереи Pods
$gallery_data = pod_get_gallery_data($pod, 'large');

// Если галерея пустая выводим заглушка
if (empty($gallery_data)) { ?>
    <div class="property-gallery-container">
        <div class="property-gallery-single">
            <img src="<?= empty_img_placeholder() ?>" alt="<?= esc_attr($post_title) ?>" />
        </div>
    </div>
    <?php
    return;
}
// Если одна;
if (count($gallery_data) === 1) { ?>
    <div class="property-gallery-container">
        <div class="property-gallery-single">
            <img src="<?= $gallery_data[0]['src'] ?>" alt="<?= esc_attr($post_title) ?>" />
        </div>
    </div>
    <?php
    return;
}


?>
    <div class="property-gallery-container">
        <div class="property-gallery">
            <div id="main-slider" class="splide main-slider-wrapper" role="group" aria-label="Галерея объекта">
                <div class="splide__track">
                    <ul class="splide__list">
                        <?php foreach ($gallery_data as $item):?>
                            <li class="splide__slide">
                                <img src="<?= esc_url($item['src']) ?>"
                                     alt="<?= esc_attr($item['alt']) ?>"
                                     loading="lazy"
                                >
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <?php if (count($gallery_data) > 1): ?>
                <div id="thumbnail-slider" class="splide thumb-slider-wrapper" role="group" aria-label="Превью галереи">
                    <div class="splide__track">
                        <ul class="splide__list">
                            <?php foreach ($gallery_data as $item): ?>
                                <li class="splide__slide">
                                    <img src="<?= esc_url($item['src']) ?>"
                                         alt="<?= esc_attr($item['alt']) ?>"
                                         loading="lazy">
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
<?php