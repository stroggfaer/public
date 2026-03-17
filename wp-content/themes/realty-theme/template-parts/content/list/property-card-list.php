<?php
// Вывод начального списка недвижимости
// Получаем параметры из GET-запроса для фильтрации
$filters = array();
if (isset($_GET['location_slug']) && !empty($_GET['location_slug'])) {
    // Используем slug если он доступен
    $filters['location'] = sanitize_text_field($_GET['location_slug']);
} else if (isset($_GET['location']) && !empty($_GET['location'])) {
    // Обратная совместимость
    $filters['location'] = sanitize_text_field($_GET['location']);
}
if (isset($_GET['checkin_date']) && !empty($_GET['checkin_date'])) {
    $filters['checkin_date'] = sanitize_text_field($_GET['checkin_date']);
}
if (isset($_GET['checkout_date']) && !empty($_GET['checkout_date'])) {
    $filters['checkout_date'] = sanitize_text_field($_GET['checkout_date']);
}
if (isset($_GET['adults']) && !empty($_GET['adults'])) {
    $filters['adults'] = intval($_GET['adults']);
}
if (isset($_GET['children']) && !empty($_GET['children'])) {
    $filters['children'] = intval($_GET['children']);
}

echo property_filter_render_list($filters);
?>
