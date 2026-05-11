<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Получаем параметры из $args
$property_id   = $args['property_id'] ?? 0;
$checkin_date  = $args['checkin_date'] ?? '';
$checkout_date = $args['checkout_date'] ?? '';
$owner_id      = $args['owner_id'] ?? 0;

// Получить данные объекта недвижимости
$property_data = null;
$property_price = '';
$property_location = '';
$property_rating = '4.9';
$property_reviews = '128';

if ($property_id) {
    $property = get_post($property_id);
    if ($property) {
        // Цена
        $price_raw = get_post_meta($property_id, 'price', true);
        if ($price_raw) {
            $property_price = number_format((float) $price_raw, 0, '.', ' ') . ' ₽';
        }
        
        // Локация
        $location_terms = get_the_terms($property_id, 'location');
        if ($location_terms && !is_wp_error($location_terms)) {
            $property_location = $location_terms[0]->name;
        }
        
        // Хозяин
        $author_id = get_post_field('post_author', $property_id);
        $owner_name = '';
        if ($author_id) {
            $first_name = get_the_author_meta('first_name', (int) $author_id);
            $last_name = get_the_author_meta('last_name', (int) $author_id);
            
            if ($first_name || $last_name) {
                $owner_name = trim($first_name . ' ' . $last_name);
            } else {
                $owner_name = get_the_author_meta('display_name', (int) $author_id);
            }
        }
        
        $property_data = array(
            'title'    => $property->post_title,
            'image'    => get_the_post_thumbnail_url($property_id, 'medium'),
            'author_id' => $author_id,
            'owner_name' => $owner_name
        );
    }
}

// Форматирование дат на русском
$formatted_checkin = '';
$formatted_checkout = '';

if ($checkin_date) {
    $timestamp = strtotime($checkin_date);
    if ($timestamp) {
        setlocale(LC_TIME, 'ru_RU.UTF-8');
        $formatted_checkin = strftime('%d %B, %Y', $timestamp);
        // Убираем точку в конце месяца
        $formatted_checkin = rtrim($formatted_checkin, '.');
    }
}

if ($checkout_date) {
    $timestamp = strtotime($checkout_date);
    if ($timestamp) {
        setlocale(LC_TIME, 'ru_RU.UTF-8');
        $formatted_checkout = strftime('%d %B, %Y', $timestamp);
        $formatted_checkout = rtrim($formatted_checkout, '.');
    }
}

// Подсчет гостей из URL
$guests_text = '';
$adults = isset($_GET['adults']) ? intval($_GET['adults']) : 0;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;

if ($adults > 0 || $children > 0) {
    $guests_parts = [];
    if ($adults > 0) {
        // Склонение для взрослых
        $cases = [2, 0, 1, 1, 1, 2];
        $mod = $adults % 100;
        $case = ($mod > 4 && $mod < 20) ? 2 : $cases[min($mod % 10, 5)];
        $adults_forms = ['взрослый', 'взрослых', 'взрослых'];
        $guests_parts[] = $adults . ' ' . $adults_forms[$case];
    }
    if ($children > 0) {
        // Склонение для детей
        $cases = [2, 0, 1, 1, 1, 2];
        $mod = $children % 100;
        $case = ($mod > 4 && $mod < 20) ? 2 : $cases[min($mod % 10, 5)];
        $children_forms = ['ребенок', 'ребенка', 'детей'];
        $guests_parts[] = $children . ' ' . $children_forms[$case];
    }
    $guests_text = implode(', ', $guests_parts);
}
?>

<div class="my-booking-card">
    <!-- Заголовок карточки -->
    <div class="my-booking-header">
        <div class="my-booking-image">
            <?php if ($property_data && $property_data['image']) : ?>
                <img src="<?php echo esc_url($property_data['image']); ?>" alt="<?php echo esc_attr($property_data['title']); ?>" />
            <?php endif; ?>
        </div>
        <div class="my-booking-info">
            <div class="my-booking-rating">
                <span class="material-symbols-outlined my-rating-star">star</span>
                <span class="my-rating-value"><?php echo esc_html($property_rating); ?> (<?php echo esc_html($property_reviews); ?>)</span>
                <span class="my-rating-badge"><?php esc_html_e('Подтверждено', 'realty-theme'); ?></span>
            </div>
            <h2 class="my-booking-title">
                <?php echo $property_data ? esc_html($property_data['title']) : 'Объект недвижимости'; ?>
            </h2>
            <?php if ($property_location) : ?>
            <div class="my-booking-location">
                <span class="material-symbols-outlined">location_on</span>
                <span><?php echo esc_html($property_location); ?></span>
            </div>
            <?php endif; ?>
            <div class="my-booking-status my-booking-status--pending">
                <?php esc_html_e('ОЖИДАНИЕ ЗАЕЗДА', 'realty-theme'); ?>
            </div>
        </div>
        <?php if ($property_price) : ?>
        <div class="my-booking-price">
            <div class="my-price-amount"><?php echo esc_html($property_price); ?></div>
            <div class="my-price-period"><?php esc_html_e('ЗА СУТКИ', 'realty-theme'); ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Детали проживания -->
    <div class="my-booking-details">
        <div class="my-details-section">
            <h3 class="my-details-title"><?php esc_html_e('ДЕТАЛИ ПРОЖИВАНИЯ', 'realty-theme'); ?></h3>
            
            <?php if ($formatted_checkin) : ?>
            <div class="my-details-row">
                <span class="my-details-label"><?php esc_html_e('Дата заезда', 'realty-theme'); ?></span>
                <span class="my-details-value"><?php echo esc_html($formatted_checkin); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($formatted_checkout) : ?>
            <div class="my-details-row">
                <span class="my-details-label"><?php esc_html_e('Дата выезда', 'realty-theme'); ?></span>
                <span class="my-details-value"><?php echo esc_html($formatted_checkout); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($guests_text) : ?>
            <div class="my-details-row">
                <span class="my-details-label"><?php esc_html_e('Гости', 'realty-theme'); ?></span>
                <span class="my-details-value"><?php echo esc_html($guests_text); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($property_data && $property_data['owner_name']) : ?>
            <div class="my-details-row">
                <span class="my-details-label"><?php esc_html_e('Хозяин', 'realty-theme'); ?></span>
                <span class="my-details-value my-details-value--link"><?php echo esc_html($property_data['owner_name']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($property_location) : ?>
        <div class="my-details-section">
            <h3 class="my-details-title"><?php esc_html_e('РАСПОЛОЖЕНИЕ', 'realty-theme'); ?></h3>
            <div class="my-details-row">
                <span class="my-details-label"><?php esc_html_e('Адрес', 'realty-theme'); ?></span>
                <span class="my-details-value"><?php echo esc_html($property_location); ?></span>
            </div>
            <div class="my-details-row">
                <a href="#" class="my-details-link">
                    <span class="material-symbols-outlined">navigation</span>
                    <span><?php esc_html_e('Показать маршрут', 'realty-theme'); ?></span>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
