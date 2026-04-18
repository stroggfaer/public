<?php
/**
 * Вспомогательные функции темы
 * Общие утилиты не привязанные к конкретной фиче
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Очистка телефона от лишних символов
 */
function str_phone($str) {
    if (empty($str)) return '';
    return preg_replace('/[^0-9]/', '', $str);
}

/**
 * Получение данных галереи Pods
 * 
 * @param int|object $post_or_pod  ID поста / Pods-объект / WP_Post
 * @param string $size         Размер изображения
 * @param bool $cover_only     true = только первое фото / заглушка
 * @return array
 */
function pod_get_gallery_data($post_or_pod, string $size = 'large', bool $cover_only = false): array {
    // Нормализация входных данных
    if (is_numeric($post_or_pod)) {
        $post_id = (int)$post_or_pod;
        $pod = function_exists('pods') ? pods('property', $post_id) : null;
    } elseif (is_object($post_or_pod)) {
        if (method_exists($post_or_pod, 'field')) {
            $pod = $post_or_pod;
            $post_id = $pod->id() ?: get_the_ID();
        } else {
            $post_id = $post_or_pod->ID ?? get_the_ID();
            $pod = function_exists('pods') ? pods('property', $post_id) : null;
        }
    } else {
        $post_id = get_the_ID();
        $pod = function_exists('pods') ? pods('property', $post_id) : null;
    }

    // Нет Pods
    if (!$pod || !$pod->valid()) {
        return $cover_only ? pod_placeholder($post_id) : [];
    }

    $raw = $pod->field('gallery');
    if (empty($raw)) {
        return $cover_only ? pod_placeholder($post_id) : [];
    }

    $items = (array)$raw;
    $title = get_the_title($post_id);

    foreach ($items as $item) {
        $id = 0;
        if (is_array($item)) $id = (int)($item['ID'] ?? 0);
        elseif (is_object($item)) $id = (int)($item->ID ?? 0);
        elseif (is_numeric($item)) $id = (int)$item;

        if ($id < 1) continue;

        $img = wp_get_attachment_image_src($id, $size);
        if (!$img || empty($img[0])) continue;

        $data = [
            'id' => $id,
            'src' => esc_url($img[0]),
            'alt' => esc_attr(get_post_meta($id, '_wp_attachment_image_alt', true) ?: $title),
            'width' => (int)($img[1] ?? 0),
            'height' => (int)($img[2] ?? 0),
        ];

        if ($cover_only) {
            return $data;
        }

        $result[] = $data;
    }

    if ($cover_only) {
        return pod_placeholder($post_id);
    }

    return $result ?? [];
}

/**
 * Заглушка изображения
 */
function pod_placeholder(int $post_id): array {
    $url = empty_img_placeholder();
    $title = get_the_title($post_id);
    return [
        'id' => 0,
        'src' => $url,
        'alt' => esc_attr($title),
        'width' => 0,
        'height' => 0,
    ];
}

/**
 * URL заглушки изображения
 */
function empty_img_placeholder(): string {
    return esc_url(get_template_directory_uri() . '/assets/images/no_image.jpg');
}

/**
 * Валидация дат заезда и выезда
 */
function validate_property_dates($checkin_date, $checkout_date = '') {
    $errors = [];

    $checkin_ts = strtotime($checkin_date);
    if ($checkin_ts === false) {
        $errors[] = 'Неверный формат даты заезда';
    } else {
        $today = strtotime(date('Y-m-d'));
        if ($checkin_ts < $today) {
            $errors[] = 'Дата заезда не может быть в прошлом';
        }
    }

    if (!empty($checkout_date)) {
        $checkout_ts = strtotime($checkout_date);
        if ($checkout_ts === false) {
            $errors[] = 'Неверный формат даты выезда';
        } else {
            if ($checkin_ts !== false && $checkout_ts < $checkin_ts) {
                $errors[] = 'Дата выезда не может быть раньше даты заезда';
            }
        }
    }

    return $errors;
}

/**
 * Валидация количества гостей
 */
function validate_guest_numbers($adults = 0, $children = 0) {
    $errors = [];

    if ($adults < 1) {
        $errors[] = 'Количество взрослых должно быть не менее 1';
    } elseif ($adults > 10) {
        $errors[] = 'Количество взрослых не может превышать 10';
    }

    if ($children < 0) {
        $errors[] = 'Количество детей не может быть отрицательным';
    } elseif ($children > 8) {
        $errors[] = 'Количество детей не может превышать 8';
    }

    return $errors;
}


/**
 * Склонение числительных
 */
function declension($words, $number) {
    $number = abs($number) % 100;
    $num = $number % 10;
    if ($number > 10 && $number < 20) {
        return [$number, $words[2]];
    } elseif ($num > 1 && $num < 5) {
        return [$number, $words[1]];
    } elseif ($num == 1) {
        return [$number, $words[0]];
    } else {
        return [$number, $words[2]];
    }
}
/**
 * Получить характеристики недвижимости для фронтенда
 *
 * Функция возвращает характеристики объекта недвижимости в разных форматах:
 * - плоский массив (по умолчанию)
 * - сгруппированный по группам
 * - конкретную группу по ключу
 *
 * Поддерживает лимит, фильтрацию по стилю группы и кэширование.
 *
 * @param int         $property_id   ID поста недвижимости
 * @param string|null $group_style   Фильтр по стилю группы (circle|standard|prohibited|text) или null
 * @param array       $options       Дополнительные параметры:
 *                                  - type (string)      Тип вывода:
 *                                      flat (по умолчанию) — плоский массив
 *                                      all               — все группы
 *                                      one               — одна группа по ключу
 *                                  - limit (int)        Лимит характеристик (по умолчанию 20)
 *                                  - group_key (string) Ключ группы (slug) для type=one
 *                                  - cache (bool)       Использовать кэш (по умолчанию: !is_admin())
 *                                  - cache_ttl (int)    Время жизни кэша в секундах (по умолчанию 300)
 *
 * @return array Возвращает:
 *               - flat: плоский массив характеристик
 *               - all: массив групп с вложенными характеристиками
 *               - one: массив одной группы или пустой массив
 */
function realty_get_property_characteristicsOld($property_id, $group_style = null, $options = []) {

    $options = wp_parse_args($options, [
        'type'        => 'flat', // flat | all | one
        'limit'       => 12,
        'group_key'   => null,
        'system_temp' => null,
    ]);

    $saved = get_post_meta($property_id, 'property_characteristics', true);

    if (!is_array($saved) || empty($saved)) {
        return [];
    }

    // сортировка
    usort($saved, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

    // ===== SYSTEM TEMP =====
    $allowed_group_ids = [];

    if (!empty($options['system_temp'])) {
        $terms = get_terms([
            'taxonomy'   => 'char_group',
            'meta_key'   => 'group_system_template',
            'meta_value' => $options['system_temp'],
            'hide_empty' => false,
        ]);

        if (!is_wp_error($terms)) {
            $allowed_group_ids = array_column($terms, 'term_id');
        }
    }

    $groups = [];
    $flat   = [];

    // кеши (чтобы не дергать WP 100 раз)
    $post_cache = [];
    $term_cache = [];
    $term_meta_cache = [];

    // ===== ОСНОВНОЙ ПРОХОД =====
    foreach ($saved as $item) {

        $char_id = (int)($item['characteristic_id'] ?? 0);
        if (!$char_id) continue;

        // POST
        if (!isset($post_cache[$char_id])) {
            $post_cache[$char_id] = get_post($char_id);
        }

        $char = $post_cache[$char_id];
        if (!$char || $char->post_status !== 'publish') continue;

        // TERMS
        if (!isset($term_cache[$char_id])) {
            $terms = get_the_terms($char_id, 'char_group');
            $term_cache[$char_id] = (!empty($terms) && !is_wp_error($terms)) ? $terms[0] : null;
        }

        $term = $term_cache[$char_id];
        $group_id = $term ? $term->term_id : 0;

        // system filter
        if (!empty($allowed_group_ids) && !in_array($group_id, $allowed_group_ids, true)) {
            continue;
        }

        // TERM META
        if ($group_id && !isset($term_meta_cache[$group_id])) {
            $term_meta_cache[$group_id] = get_term_meta($group_id);
        }

        $term_meta = $group_id ? $term_meta_cache[$group_id] : [];

        $group_key     = $term_meta['group_key'][0] ?? ($term->slug ?? 'default');
        $group_name    = $term->name ?? '';
        $display_style = $term_meta['display_style'][0] ?? 'standard';
        $active        = $term_meta['active'][0] ?? 1;

        // фильтры
        if ($group_style && $display_style !== $group_style) continue;
        if (!$active) continue;
        if ($options['group_key'] && $options['group_key'] !== $group_key) continue;

        // DATA
        $char_data = [
            'characteristic_id' => $char_id,
            'group_id'          => $group_id,
            'group_name'        => $group_name,
            'group_key'         => $group_key,
            'display_style'     => $display_style,
            'title'             => $char->post_title,
            'icon'              => get_post_meta($char_id, 'icon', true),
            'icon_type'         => get_post_meta($char_id, 'icon_type', true),
            'media_id'          => get_post_meta($char_id, 'media_id', true),
            'label'             => get_post_meta($char_id, 'label', true),
            'value'             => get_post_meta($char_id, 'value', true),
            'hint'              => get_post_meta($char_id, 'hint', true),
            'order'             => $item['order'] ?? 0,
        ];

        // ===== FLAT =====
        if ($options['type'] === 'flat') {
            $flat[] = $char_data;
            continue;
        }

        // ===== GROUP =====
        if (!isset($groups[$group_key])) {
            $groups[$group_key] = [
                'group_id' => $group_id,
                'group_name' => $group_name,
                'group_key' => $group_key,
                'display_style' => $display_style,
                'total' => 0, // считаем ниже
                'characteristics' => []
            ];
        }

        $groups[$group_key]['characteristics'][] = $char_data;
        $groups[$group_key]['total']++; // считаем ДО limit
    }

    // ===== LIMIT (ПОСЛЕ ФИЛЬТРАЦИИ!) =====
    if ($options['type'] === 'flat') {
        $flat = array_slice($flat, 0, (int)$options['limit']);
        return $flat;
    }

    // GROUP LIMIT
    foreach ($groups as &$group) {
        $group['characteristics'] = array_slice(
            $group['characteristics'],
            0,
            (int)$options['limit']
        );
    }
    unset($group);

    // ===== OUTPUT =====
    if ($options['type'] === 'one') {
        $key = $options['group_key'] ?? '';
        return $groups[$key] ?? [];
    }

    return array_values($groups);
}
function realty_get_property_characteristicsddd($property_id, $group_style = null, $options = []) {

    $options = wp_parse_args($options, [
        'type'        => 'flat', // flat | all | one
        'limit'       => 12,
        'group_key'   => null,
        'system_temp' => null,
    ]);

    $saved = get_post_meta($property_id, 'property_characteristics', true);

    if (!is_array($saved) || empty($saved)) {
        return [];
    }

    usort($saved, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

    // ===== SYSTEM TEMP =====
    $allowed_group_ids = [];

    if (!empty($options['system_temp'])) {
        $terms = get_terms([
            'taxonomy'   => 'char_group',
            'meta_key'   => 'group_system_template',
            'meta_value' => $options['system_temp'],
            'hide_empty' => false,
        ]);

        if (!is_wp_error($terms)) {
            $allowed_group_ids = array_column($terms, 'term_id');
        }
    }

    $groups = [];
    $flat   = [];

    $post_cache = [];
    $term_cache = [];
    $term_meta_cache = [];

    foreach ($saved as $item) {

        $char_id = (int)($item['characteristic_id'] ?? 0);
        if (!$char_id) continue;

        if (!isset($post_cache[$char_id])) {
            $post_cache[$char_id] = get_post($char_id);
        }

        $char = $post_cache[$char_id];
        if (!$char || $char->post_status !== 'publish') continue;

        if (!isset($term_cache[$char_id])) {
            $terms = get_the_terms($char_id, 'char_group');
            $term_cache[$char_id] = (!empty($terms) && !is_wp_error($terms)) ? $terms[0] : null;
        }

        $term = $term_cache[$char_id];
        $group_id = $term ? $term->term_id : 0;

        if (!empty($allowed_group_ids) && !in_array($group_id, $allowed_group_ids, true)) {
            continue;
        }

        if ($group_id && !isset($term_meta_cache[$group_id])) {
            $term_meta_cache[$group_id] = get_term_meta($group_id);
        }

        $term_meta = $group_id ? $term_meta_cache[$group_id] : [];

        $group_key     = $term_meta['group_key'][0] ?? ($term->slug ?? 'default');
        $group_name    = $term->name ?? '';
        $display_style = $term_meta['display_style'][0] ?? 'standard';
        $active        = $term_meta['active'][0] ?? 1;

        if ($group_style && $display_style !== $group_style) continue;
        if (!$active) continue;
        if ($options['group_key'] && $options['group_key'] !== $group_key) continue;

        $char_data = [
            'characteristic_id' => $char_id,
            'group_id'          => $group_id,
            'group_name'        => $group_name,
            'group_key'         => $group_key,
            'display_style'     => $display_style,
            'title'             => $char->post_title,
            'icon'              => get_post_meta($char_id, 'icon', true),
            'icon_type'         => get_post_meta($char_id, 'icon_type', true),
            'media_id'          => get_post_meta($char_id, 'media_id', true),
            'label'             => get_post_meta($char_id, 'label', true),
            'value'             => get_post_meta($char_id, 'value', true),
            'hint'              => get_post_meta($char_id, 'hint', true),
            'order'             => $item['order'] ?? 0,
        ];

        if ($options['type'] === 'flat') {
            $flat[] = $char_data;
            continue;
        }

        if (!isset($groups[$group_key])) {
            $groups[$group_key] = [
                'group_id' => $group_id,
                'group_name' => $group_name,
                'group_key' => $group_key,
                'display_style' => $display_style,
                'total' => 0,
                'characteristics' => []
            ];
        }

        $groups[$group_key]['characteristics'][] = $char_data;
        $groups[$group_key]['total']++;
    }

    // ===== LIMIT (ТОЛЬКО ДЛЯ ВЫВОДА) =====
    if ($options['type'] === 'flat') {
        return $options['limit']
            ? array_slice($flat, 0, (int)$options['limit'])
            : $flat;
    }

    foreach ($groups as &$group) {
        if ($options['limit']) {
            $group['characteristics'] = array_slice(
                $group['characteristics'],
                0,
                (int)$options['limit']
            );
        }
    }
    unset($group);

    if ($options['type'] === 'one') {
        return $groups[$options['group_key']] ?? [];
    }

    return array_values($groups);
}

/**
 * Получить характеристики недвижимости для фронтенда
 *
 * @param int         $property_id   ID поста недвижимости
 * @param string|null $group_style   Фильтр по стилю группы
 * @param array       $options       Параметры: type, limit, group_key, system_temp, page, per_page, last_count
 *
 * @return array Схема: [data => [], meta => [...], filters => []]
 */
/**
 * Получить характеристики недвижимости (логика)
 * * @param int         $property_id
 * @param string|null $group_style
 * @param array       $options
 * @return array      Схема: [data, meta, filters]
 */
/**
 * Получить характеристики недвижимости (логика)
 */
function realty_get_property_characteristicsщдв($property_id, $group_style = null, $options = []) {
    $options = wp_parse_args($options, [
        'type'        => 'flat', // flat | all | one
        'group_key'   => null,
        'group_id'    => null,
        'system_temp' => null,
        'last_count'  => 0,    // Если > 0, игнорируем page/per_page
        'page'        => 1,
        'per_page'    => 12,
    ]);

    $response = [
        'data'    => [],
        'meta'    => [
            'current_page' => (int)$options['page'],
            'page_count'   => 0,
            'per_page'     => (int)$options['per_page'],
            'total'        => 0,
        ],
        'filters' => [],
    ];

    $saved = get_post_meta($property_id, 'property_characteristics', true);
    if (!is_array($saved) || empty($saved)) return $response;

    // 1. Сортировка исходных данных
    usort($saved, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

    // 2. Получаем разрешенные группы по шаблону (если есть)
    $allowed_group_ids = [];
    if (!empty($options['system_temp'])) {
        $terms = get_terms([
            'taxonomy'   => 'char_group',
            'meta_key'   => 'group_system_template',
            'meta_value' => $options['system_temp'],
            'hide_empty' => false,
        ]);
        if (!is_wp_error($terms)) $allowed_group_ids = array_column($terms, 'term_id');
    }

    $all_filtered_chars = [];
    $available_filters  = [];

    // 3. Сбор всех данных БЕЗ лимитов
    foreach ($saved as $item) {
        $char_id = (int)($item['characteristic_id'] ?? 0);
        $char = get_post($char_id);
        if (!$char || $char->post_status !== 'publish') continue;

        $terms = get_the_terms($char_id, 'char_group');
        $term  = (!empty($terms) && !is_wp_error($terms)) ? $terms[0] : null;
        $group_id = $term ? $term->term_id : 0;

        // Фильтрация
        if (!empty($allowed_group_ids) && !in_array($group_id, $allowed_group_ids, true)) continue;
        if (!empty($options['group_id']) && (int)$options['group_id'] !== $group_id) continue;

        $term_meta = $group_id ? get_term_meta($group_id) : [];
        $g_key     = $term_meta['group_key'][0] ?? ($term->slug ?? 'default');
        $g_style   = $term_meta['display_style'][0] ?? 'standard';
        $g_active  = (int)($term_meta['active'][0] ?? 1);

        if (!$g_active) continue;
        if ($group_style && $g_style !== $group_style) continue;
        if ($options['type'] === 'one' && $options['group_key'] && $options['group_key'] !== $g_key) continue;

        $char_data = [
            'characteristic_id' => $char_id,
            'group_id'          => $group_id,
            'group_name'        => $term->name ?? '',
            'group_key'         => $g_key,
            'display_style'     => $g_style,
            'title'             => $char->post_title,
            'icon'              => get_post_meta($char_id, 'icon', true),
            'icon_type'         => get_post_meta($char_id, 'icon_type', true),
            'media_id'          => get_post_meta($char_id, 'media_id', true),
            'label'             => get_post_meta($char_id, 'label', true),
            'value'             => get_post_meta($char_id, 'value', true),
            'hint'              => get_post_meta($char_id, 'hint', true),
            'order'             => (int)($item['order'] ?? 0),
        ];

        $all_filtered_chars[] = $char_data;

        if (!empty($term_meta['use_in_filters'][0]) && !isset($available_filters[$g_key])) {
            $available_filters[$g_key] = [
                'group_id'   => $group_id,
                'group_name' => $term->name ?? '',
                'group_key'  => $g_key,
            ];
        }
    }

    $total = count($all_filtered_chars);
    $response['meta']['total']   = $total;
    $response['filters']         = array_values($available_filters);

    // 4. ГЛАВНОЕ: Логика среза (Slice)
    $data_to_display = [];
    $last_count = (int)$options['last_count'];

    if ($last_count > 0) {
        // РЕЖИМ MORE: игнорируем пагинацию, берем от N до конца
        $data_to_display = array_slice($all_filtered_chars, $last_count);

        $response['meta']['current_page'] = 1;
        $response['meta']['page_count']   = 1;
        $response['meta']['per_page']     = count($data_to_display);
    } else {
        // РЕЖИМ ПАГИНАЦИИ
        $per_page = (int)$options['per_page'];
        $page     = max(1, (int)$options['page']);
        $offset   = ($page - 1) * $per_page;

        $response['meta']['page_count'] = $per_page > 0 ? ceil($total / $per_page) : 1;
        $data_to_display = ($per_page > 0) ? array_slice($all_filtered_chars, $offset, $per_page) : $all_filtered_chars;
    }

    // 5. Формирование финальной структуры (data)
    if ($options['type'] === 'all') {
        $grouped = [];
        foreach ($data_to_display as $char) {
            $key = $char['group_key'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'group_id'        => $char['group_id'],
                    'group_name'      => $char['group_name'],
                    'group_key'       => $char['group_key'],
                    'display_style'   => $char['display_style'],
                    'characteristics' => []
                ];
            }
            $grouped[$key]['characteristics'][] = $char;
        }
        $response['data'] = array_values($grouped);
    } else {
        $response['data'] = $data_to_display;
    }

    return $response;
}

function realty_get_property_characteristicsЩДВ($property_id, $group_style = null, $options = []) {

    $options = wp_parse_args($options, [
        'type' => 'flat',     // flat | all | one
        'limit' => 20,
        'group_key' => null,
        'system_temp' => null,
        'cache' => !(is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)),
        'cache_ttl' => 300,
    ]);

    $system_temp = $options['system_temp'];
    $allowed_group_ids = [];

    // ===== SYSTEM TEMPLATE =====
    if ($system_temp) {
        $system_temp_groups = get_terms([
            'taxonomy' => 'char_group',
            'meta_key' => 'group_system_template',
            'meta_value' => $system_temp,
            'hide_empty' => false,
        ]);

        if (!is_wp_error($system_temp_groups) && !empty($system_temp_groups)) {
            $allowed_group_ids = array_column($system_temp_groups, 'term_id');

            if ($options['type'] === 'one') {
                $group_term = get_term($allowed_group_ids[0], 'char_group');
                if ($group_term && !is_wp_error($group_term)) {
                    $group_meta = get_term_meta($group_term->term_id);
                    $options['group_key'] = $group_meta['group_key'][0] ?? $group_term->slug;
                }
            }
        }

        if (empty($allowed_group_ids)) {
            return [];
        }
    }

    // ===== CACHE =====
    $cache_key = 'realty_chars_' . $property_id . '_' . md5(serialize([$group_style, $options]));

    if ($options['cache']) {
        $cached = wp_cache_get($cache_key);
        if ($cached !== false) {
            return $cached;
        }
    }

    $saved = get_post_meta($property_id, 'property_characteristics', true);

    if (!is_array($saved) || empty($saved)) {
        return [];
    }

    // сортировка
    usort($saved, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

    // ===== TOTAL ДО LIMIT =====
    $group_totals = [];
    $post_cache = [];
    $term_cache = [];
    $term_meta_cache = [];

    foreach ($saved as $item) {

        $char_id = absint($item['characteristic_id'] ?? 0);
        if (!$char_id) continue;

        if (!isset($post_cache[$char_id])) {
            $post_cache[$char_id] = get_post($char_id);
        }

        $char = $post_cache[$char_id];
        if (!$char || $char->post_status !== 'publish') continue;

        if (!isset($term_cache[$char_id])) {
            $terms = get_the_terms($char_id, 'char_group');
            $term_cache[$char_id] = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : null;
        }

        $term = $term_cache[$char_id];
        $group_id = $term ? $term->term_id : 0;

        // system_temp filter
        if (!empty($allowed_group_ids) && !in_array($group_id, $allowed_group_ids, true)) {
            continue;
        }

        if ($group_id && !isset($term_meta_cache[$group_id])) {
            $term_meta_cache[$group_id] = get_term_meta($group_id);
        }

        $term_meta = $group_id ? $term_meta_cache[$group_id] : [];

        $group_key = !empty($term_meta['group_key'][0])
            ? $term_meta['group_key'][0]
            : ($term ? $term->slug : 'default');

        if (!isset($group_totals[$group_key])) {
            $group_totals[$group_key] = 0;
        }

        $group_totals[$group_key]++;
    }

    // ===== LIMIT =====
    $saved = array_slice($saved, 0, (int)$options['limit']);

    $result = [];
    $groups = [];
    $meta_cache = [];

    foreach ($saved as $item) {

        $char_id = absint($item['characteristic_id'] ?? 0);
        if (!$char_id) continue;

        $char = $post_cache[$char_id] ?? get_post($char_id);
        if (!$char || $char->post_status !== 'publish') continue;

        if (!isset($meta_cache[$char_id])) {
            $meta_cache[$char_id] = get_post_meta($char_id);
        }

        $meta = $meta_cache[$char_id];

        $term = $term_cache[$char_id] ?? null;
        $group_id = $term ? $term->term_id : 0;
        $group_name = $term ? $term->name : '';

        if (!empty($allowed_group_ids) && !in_array($group_id, $allowed_group_ids, true)) {
            continue;
        }

        $term_meta = $group_id ? ($term_meta_cache[$group_id] ?? get_term_meta($group_id)) : [];

        $group_key = !empty($term_meta['group_key'][0])
            ? $term_meta['group_key'][0]
            : ($term ? $term->slug : 'default');

        $display_style = $term_meta['display_style'][0] ?? 'standard';
        $active = $term_meta['active'][0] ?? 1;

        if ($group_style && $display_style !== $group_style) continue;
        if (!$active) continue;

        $char_data = [
            'characteristic_id' => $char_id,
            'group_id' => $group_id,
            'group_name' => $group_name,
            'group_key' => $group_key,
            'display_style' => $display_style,
            'title' => $char->post_title,
            'icon' => $meta['icon'][0] ?? '',
            'icon_type' => $meta['icon_type'][0] ?? '',
            'media_id' => $meta['media_id'][0] ?? 0,
            'label' => $meta['label'][0] ?? '',
            'value' => $meta['value'][0] ?? '',
            'hint' => $meta['hint'][0] ?? '',
            'order' => $item['order'] ?? 0,
        ];

        // ===== FLAT =====
        if ($options['type'] === 'flat') {
            $char_data['total'] = array_sum($group_totals);
            $result[] = $char_data;
            continue;
        }

        // ===== GROUP =====
        if (!isset($groups[$group_key])) {
            $groups[$group_key] = [
                'group_id' => $group_id,
                'group_name' => $group_name,
                'display_style' => $display_style,
                'group_key' => $group_key,
                'system_temp' => $system_temp,
                'total' => $group_totals[$group_key] ?? 0,
                'characteristics' => []
            ];
        }

        $groups[$group_key]['characteristics'][] = [
            'characteristic_id' => $char_id,
            'title' => $char->post_title,
            'icon' => $meta['icon'][0] ?? '',
            'icon_type' => $meta['icon_type'][0] ?? '',
            'media_id' => $meta['media_id'][0] ?? 0,
            'label' => $meta['label'][0] ?? '',
            'value' => $meta['value'][0] ?? '',
            'hint' => $meta['hint'][0] ?? '',
            'order' => $item['order'] ?? 0,
        ];
    }

    // ===== RESULT =====
    $active_key = ($options['type'] === 'one' && $system_temp)
        ? $options['group_key']
        : ($options['system_temp'] ?? $options['group_key']);

    if ($options['type'] === 'all') {
        $result = array_values($groups);
    } elseif ($options['type'] === 'one' && $active_key) {
        $result = $groups[$active_key] ?? [];
    }

    // ===== CACHE SAVE =====
    if ($options['cache']) {
        wp_cache_set($cache_key, $result, '', (int)$options['cache_ttl']);
    }

    return $result;
}

function realty_get_property_characteristicsв($property_id, $group_style = null, $options = []) {
    $options = wp_parse_args($options, [
        'type'        => 'flat',     // flat | all | one
        'limit'       => 12,
        'last_count'  => 0,          // Offset для AJAX
        'page'        => 1,
        'group_key'   => null,
        'group_id'    => null,
        'system_temp' => null,
        'cache'       => !(is_admin() || wp_doing_ajax()),
    ]);

    // Инициализация консистентного ответа
    $response = [
        'data'    => [],
        'meta'    => [
            'current_page' => (int)$options['page'],
            'page_count'   => 0,
            'per_page'     => (int)$options['limit'],
            'total'        => 0,
            'last_count'   => (int)$options['last_count'],
        ],
        'filters' => [],
    ];

    $saved = get_post_meta($property_id, 'property_characteristics', true);
    if (!is_array($saved) || empty($saved)) return $response;

    // Сортировка по порядку
    usort($saved, fn($a, $b) => (int)($a['order'] ?? 0) - (int)($b['order'] ?? 0));

    // 1. Предварительная фильтрация (собираем только подходящие ID)
    $filtered_pool = [];
    $group_totals = [];
    $available_filters = [];

    foreach ($saved as $item) {
        $char_id = absint($item['characteristic_id'] ?? 0);
        if (!$char_id) continue;

        $terms = get_the_terms($char_id, 'char_group');
        $term = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : null;
        $g_id = $term ? $term->term_id : 0;

        // Фильтр по group_id (для AJAX)
        if (!empty($options['group_id']) && (int)$options['group_id'] !== $g_id) continue;

        $g_meta = $g_id ? get_term_meta($g_id) : [];
        $g_key = $g_meta['group_key'][0] ?? ($term ? $term->slug : 'default');

        // Фильтр по system_temp
        if (!empty($options['system_temp'])) {
            $is_allowed = get_term_meta($g_id, 'group_system_template', true) === $options['system_temp'];
            if (!$is_allowed) continue;
        }

        $filtered_pool[] = array_merge($item, ['g_id' => $g_id, 'g_key' => $g_key]);

        if (!isset($group_totals[$g_key])) $group_totals[$g_key] = 0;
        $group_totals[$g_key]++;

        if (!empty($g_meta['use_in_filters'][0])) {
            $available_filters[$g_key] = [
                'group_id' => $g_id,
                'group_name' => $term->name,
                'group_key' => $g_key
            ];
        }
    }

    $total_filtered = count($filtered_pool);
    $response['meta']['total'] = $total_filtered;
    $response['meta']['page_count'] = ceil($total_filtered / (int)$options['limit']);
    $response['filters'] = array_values($available_filters);

    $offset = (int)$options['last_count'] ?: (((int)$options['page'] - 1) * (int)$options['limit']);
    $sliced_items = array_slice($filtered_pool, $offset, (int)$options['limit']);

    $groups = [];
    $flat_list = [];

    foreach ($sliced_items as $item) {
        $char_id = $item['characteristic_id'];
        $char = get_post($char_id);
        if (!$char || $char->post_status !== 'publish') continue;

        $g_id = $item['g_id'];
        $g_key = $item['g_key'];
        $term = get_term($g_id, 'char_group');
        $g_meta = get_term_meta($g_id);

        $char_data = [
            'characteristic_id' => $char_id,
            'title'      => $char->post_title,
            'group_name' => $term->name ?? '',
            'group_key'  => $g_key,
            'icon'       => get_post_meta($char_id, 'icon', true),
            'value'      => get_post_meta($char_id, 'value', true),
            'label'      => get_post_meta($char_id, 'label', true),
            'order'      => $item['order']
        ];

        if ($options['type'] === 'flat') {
            $flat_list[] = $char_data;
        } else {
            if (!isset($groups[$g_key])) {
                $groups[$g_key] = [
                    'group_id' => $g_id,
                    'group_name' => $term->name ?? '',
                    'group_key' => $g_key,
                    'total' => $group_totals[$g_key],
                    'characteristics' => []
                ];
            }
            $groups[$g_key]['characteristics'][] = $char_data;
        }
    }

    if ($options['type'] === 'all') {
        $response['data'] = array_values($groups);
    } elseif ($options['type'] === 'one') {
        $active_key = $options['group_key'] ?: (isset($g_key) ? $g_key : null);
        $response['data'] = $groups[$active_key] ?? (reset($groups) ?: []);
    } else {
        $response['data'] = $flat_list;
    }

    // ===== КОНСИСТЕНТНОСТЬ =====
    // Если это AJAX, нам нужен весь объект с мета-данными
    if (wp_doing_ajax() || $options['type'] === 'all') {
        return $response;
    }

    if ($options['type'] === 'one') {
        return $response['data'];
    }

    return $response;
}
function realty_get_property_characteristics($property_id, $group_style = null, $options = []) {
    $options = wp_parse_args($options, [
        'type'        => 'flat',     // flat | all | one
        'limit'        => 12,        // Сколько записей вернуть
        'last_count'  => 0,          // Смещение (сколько уже загружено)
        'page'        => 1,
        'group_key'   => null,
        'group_id'    => null,
        'system_temp' => null,
        'cache'       => true,
    ]);

    $response = [
        'data'    => [],
        'meta'    => [
            'current_page' => (int)$options['page'],
            'page_count'   => 0,
            'per_page'     => (int)$options['limit'],
            'total'        => 0,
            'last_count'   => (int)$options['last_count'],
        ],
        'filters' => [],
    ];

    $saved = get_post_meta($property_id, 'property_characteristics', true);
    if (!is_array($saved) || empty($saved)) return $response;

    // 1. Сортировка исходного массива
    usort($saved, fn($a, $b) => (int)($a['order'] ?? 0) - (int)($b['order'] ?? 0));

    $filtered_pool = [];
    $group_totals = [];
    $available_filters = [];

    // Оптимизация: предзагрузка кэша метаданных для всех связанных постов
    $all_char_ids = array_column($saved, 'characteristic_id');
    update_meta_cache('post', $all_char_ids);

    // 2. Фильтрация и сбор данных
    foreach ($saved as $item) {
        $char_id = absint($item['characteristic_id'] ?? 0);
        if (!$char_id) continue;

        $terms = get_the_terms($char_id, 'char_group');
        $term = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : null;
        $g_id = $term ? $term->term_id : 0;

        // Фильтр по ID группы
        if (!empty($options['group_id']) && (int)$options['group_id'] !== $g_id) continue;

        $g_meta = $g_id ? get_term_meta($g_id) : [];
        $g_key = $g_meta['group_key'][0] ?? ($term ? $term->slug : 'default');

        // Фильтр по system_temp
        if (!empty($options['system_temp'])) {
            $g_temp = get_term_meta($g_id, 'group_system_template', true);
            if ($g_temp !== $options['system_temp']) continue;
        }

        $char_post = get_post($char_id);
        if (!$char_post || $char_post->post_status !== 'publish') continue;

        $char_data = [
            'characteristic_id' => $char_id,
            'title'      => $char_post->post_title,
            'group_name' => $term->name ?? '',
            'group_key'  => $g_key,
            'g_id'       => $g_id,
            'icon'       => get_post_meta($char_id, 'icon', true),
            'value'      => get_post_meta($char_id, 'value', true),
            'label'      => get_post_meta($char_id, 'label', true),
            'order'      => (int)$item['order']
        ];

        $filtered_pool[] = $char_data;

        // Накопление мета-данных
        $group_totals[$g_key] = ($group_totals[$g_key] ?? 0) + 1;
        if (!empty($g_meta['use_in_filters'][0]) && !isset($available_filters[$g_key])) {
            $available_filters[$g_key] = [
                'group_id'   => $g_id,
                'group_name' => $term->name,
                'group_key'  => $g_key
            ];
        }
    }

    $total_filtered = count($filtered_pool);
    $response['meta']['total'] = $total_filtered;
    $response['meta']['page_count'] = ceil($total_filtered / (int)$options['limit']);
    $response['filters'] = array_values($available_filters);

    // 3. Логика среза (Pagination vs AJAX Last Count)
    // Если есть last_count, берем строго следующую порцию размером в limit
    if ((int)$options['last_count'] > 0) {
        $offset = (int)$options['last_count'];
        $limit  = (int)$options['limit'];
    } else {
        $offset = ((int)$options['page'] - 1) * (int)$options['limit'];
        $limit  = (int)$options['limit'];
    }

    $sliced_items = array_slice($filtered_pool, $offset, $limit);

    // 4. Группировка результата
    if ($options['type'] === 'flat') {
        $response['data'] = $sliced_items;
    } else {
        $groups = [];
        foreach ($sliced_items as $item) {
            $key = $item['group_key'];
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'group_id'   => $item['g_id'],
                    'group_name' => $item['group_name'],
                    'group_key'  => $key,
                    'total'      => $group_totals[$key] ?? 0,
                    'characteristics' => []
                ];
            }
            unset($item['g_id']); // Чистим вспомогательные данные
            $groups[$key]['characteristics'][] = $item;
        }

        if ($options['type'] === 'one') {
            $active_key = $options['group_key'] ?: key($groups);
            $response['data'] = $groups[$active_key] ?? (reset($groups) ?: []);
        } else {
            $response['data'] = array_values($groups);
        }
    }

    // Возврат данных в зависимости от контекста
    $is_rest = defined('REST_REQUEST') && REST_REQUEST;
    if (wp_doing_ajax() || $is_rest || $options['type'] === 'all') {
        return $response;
    }

    return ($options['type'] === 'one') ? $response['data'] : $response;
}

/**
 * Рендер иконки характеристики
 *
 * @param array $char_data Массив данных характеристики
 * @return string HTML иконки
 */
function realty_render_characteristic_icon($char_data, $className = '') {
    $icon_type = $char_data['icon_type'] ?? 'material_symbol"';

    if ($icon_type === 'upload' && !empty($char_data['media_id'])) {
        $url = wp_get_attachment_url($char_data['media_id']);
        if ($url) {
            return '<img src="' . esc_url($url) . '" alt="">';
        }
    }
    $icon = $char_data['icon'] ?? 'help';
    return '<span class="material-symbols-outlined '.$className.'">' . esc_html($icon) . '</span>';
}