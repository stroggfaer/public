<?php
/**
 * Функции для работы со списком недвижимости
 */

/**
 * Универсальная функция для получения списка недвижимости с фильтрацией
 *
 * @param array $params Параметры фильтрации
 * @return array Результаты запроса и дополнительные данные
 */
function properties_list($params = array()) {
    global $wpdb;

    // Базовые параметры запроса
    $defaults = array(
        'posts_per_page' => 12,
        'paged'          => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $params = wp_parse_args($params, $defaults);

    // Извлекаем фильтры
    $filters = array();
    if (isset($params['location']) || isset($params['price_min']) || isset($params['price_max']) ||
        isset($params['adults']) || isset($params['children']) || isset($params['location_slug'])) {
        $filters = $params;
        // Убираем ненужные параметры из фильтров
        unset($filters['posts_per_page'], $filters['paged'], $filters['orderby'], $filters['order']);
    }

    // Строим WHERE для запроса
    $where = array();
    $where[] = "t.post_status = 'publish'";
    $where[] = "t.post_type = 'property'";

    // Локация
    if (!empty($filters['location']) || !empty($filters['location_slug'])) {
        $location_where = array();
        if (!empty($filters['location'])) {
            $location_where[] = "tm.name = '" . esc_sql(sanitize_text_field($filters['location'])) . "'";
        }
        if (!empty($filters['location_slug'])) {
            $location_where[] = "tm.slug = '" . esc_sql(sanitize_title($filters['location_slug'])) . "'";
        }
        $where[] = "EXISTS (
            SELECT 1
            FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->terms} tm ON tm.term_id = tt.term_id
            WHERE tr.object_id = t.ID
            AND tt.taxonomy = 'location'
            AND (" . implode(' OR ', $location_where) . ")
        )";
    }

    // Цена
    if (!empty($filters['price_min']) && intval($filters['price_min']) > 0) {
        $where[] = "CAST((SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = t.ID AND meta_key = 'price' LIMIT 1) AS UNSIGNED) >= " . intval($filters['price_min']);
    }
    if (!empty($filters['price_max']) && intval($filters['price_max']) > 0) {
        $where[] = "CAST((SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = t.ID AND meta_key = 'price' LIMIT 1) AS UNSIGNED) <= " . intval($filters['price_max']);
    }

    // Взрослые (guests_count)
    if (!empty($filters['adults']) && intval($filters['adults']) > 0) {
        $where[] = "CAST((SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = t.ID AND meta_key = 'guests_count' LIMIT 1) AS UNSIGNED) >= " . intval($filters['adults']);
    }

    // Собираем SQL запрос
    $sql = "SELECT DISTINCT t.*, 
                   (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = t.ID AND meta_key = 'price' LIMIT 1) as price,
                   (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = t.ID AND meta_key = 'guests_count' LIMIT 1) as guests_count
            FROM {$wpdb->posts} t";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    // Добавляем ORDER BY
    $orderby_map = array(
        'date' => 't.post_date',
        'title' => 't.post_title',
        'price' => 'CAST((SELECT meta_value FROM ' . $wpdb->postmeta . ' WHERE post_id = t.ID AND meta_key = "price" LIMIT 1) AS UNSIGNED)',
    );

    $orderby_field = isset($orderby_map[$params['orderby']]) ? $orderby_map[$params['orderby']] : $orderby_map['date'];
    $order_direction = (strtoupper($params['order']) === 'ASC') ? 'ASC' : 'DESC';
    $sql .= " ORDER BY {$orderby_field} {$order_direction}";

    // Получаем общее количество записей для пагинации
    $count_sql = "SELECT COUNT(DISTINCT t.ID) as total FROM {$wpdb->posts} t " . (!empty($where) ? "WHERE " . implode(' AND ', $where) : "");
    $total_results = $wpdb->get_var($count_sql);

    // Добавляем LIMIT
    $offset = ($params['paged'] - 1) * $params['posts_per_page'];
    $sql .= " LIMIT {$offset}, {$params['posts_per_page']}";

    // Выполняем запрос
    $results = $wpdb->get_results($sql);

    // Подготавливаем координаты для карты
    $coordinates = array();
    foreach ($results as $post) {
        // Получаем координаты из метаполей
        $lat = get_post_meta($post->ID, 'latitude', true);
        $lng = get_post_meta($post->ID, 'longitude', true);
        if ($lat && $lng) {
            $coordinates[] = array(
                'id' => $post->ID,
                'lat' => $lat,
                'lng' => $lng,
                'title' => $post->post_title,
            );
        }
    }

    return array(
        'posts' => $results,
        'total' => $total_results,
        'coordinates' => $coordinates,
        'total_pages' => ceil($total_results / $params['posts_per_page']),
    );
}