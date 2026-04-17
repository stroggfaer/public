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
 * @param int       $property_id   ID поста недвижимости
 * @param string    $group_style Фильтр по стилю группы (circle|standard|prohibited|text)
 * @param bool      $grouped     Вернуть сгруппированным по группам
 * @return array Массив сгруппированных характеристик
 */
function realty_get_property_characteristicsOld($property_id, $group_style = null, $grouped = false) {
    $saved = get_post_meta($property_id, 'property_characteristics', true);
    if (!is_array($saved) || empty($saved)) {
        return [];
    }

    usort($saved, function($a, $b) {
        return ($a['order'] ?? 0) - ($b['order'] ?? 0);
    });

    $result = [];

    foreach ($saved as $item) {
        $char_id = isset($item['characteristic_id']) ? absint($item['characteristic_id']) : 0;
        if (!$char_id) continue;

        $char = get_post($char_id);
        if (!$char || $char->post_status !== 'publish') continue;

        $terms = get_the_terms($char_id, 'char_group');
        $group_id = !empty($terms) ? $terms[0]->term_id : 0;

        $display_style = $group_id ? get_term_meta($group_id, 'display_style', true) : 'standard';

        if ($group_style && $display_style !== $group_style) continue;

        $active = $group_id ? get_term_meta($group_id, 'active', true) : 1;
        if (!$active) continue;

        $char_data = [
            'characteristic_id' => $char_id,
            'group_id' => $group_id,
            'group_name' => !empty($terms) ? $terms[0]->name : '',
            'display_style' => $display_style,
            'title' => $char->post_title,
            'icon' => get_post_meta($char_id, 'icon', true),
            'icon_type' => get_post_meta($char_id, 'icon_type', true),
            'media_id' => get_post_meta($char_id, 'media_id', true),
            'label' => get_post_meta($char_id, 'label', true),
            'value' => get_post_meta($char_id, 'value', true),
            'hint' => get_post_meta($char_id, 'hint', true),
            'order' => $item['order'] ?? 0,
        ];

        if ($grouped) {
            if (!isset($result[$group_id])) {
                $result[$group_id] = [
                    'group_id' => $group_id,
                    'group_name' => !empty($terms) ? $terms[0]->name : '',
                    'display_style' => $display_style,
                    'characteristics' => []
                ];
            }
            $result[$group_id]['characteristics'][] = $char_data;
        } else {
            $result[] = $char_data;
        }
    }

    return $grouped ? array_values($result) : $result;
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
function realty_get_property_characteristics($property_id, $group_style = null, $options = []) {

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

    if ($system_temp) {
        $system_temp_groups = get_terms([
            'taxonomy' => 'char_group',
            'meta_key' => 'group_system_template',
            'meta_value' => $system_temp,
            'hide_empty' => false,
        ]);
        if (!is_wp_error($system_temp_groups) && !empty($system_temp_groups)) {
            $allowed_group_ids = array_column($system_temp_groups, 'term_id');
            
            // Для type='one' устанавливаем group_key из группы
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

    usort($saved, function($a, $b) {
        return ($a['order'] ?? 0) - ($b['order'] ?? 0);
    });

    $saved = array_slice($saved, 0, (int)$options['limit']);

    $result = [];
    $groups = [];

    $post_cache = [];
    $meta_cache = [];
    $term_cache = [];
    $term_meta_cache = [];

    foreach ($saved as $item) {

        $char_id = isset($item['characteristic_id']) ? absint($item['characteristic_id']) : 0;
        if (!$char_id) continue;

        // POST
        if (!isset($post_cache[$char_id])) {
            $post_cache[$char_id] = get_post($char_id);
        }
        $char = $post_cache[$char_id];
        if (!$char || $char->post_status !== 'publish') continue;

        // META
        if (!isset($meta_cache[$char_id])) {
            $meta_cache[$char_id] = get_post_meta($char_id);
        }
        $meta = $meta_cache[$char_id];

        // TERMS
        if (!isset($term_cache[$char_id])) {
            $terms = get_the_terms($char_id, 'char_group');
            $term_cache[$char_id] = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : null;
        }

        $term = $term_cache[$char_id];
        $group_id = $term ? $term->term_id : 0;
        $group_name = $term ? $term->name : '';

        // Проверка system_temp: фильтрация по group_system_template
        if (!empty($allowed_group_ids) && !in_array($group_id, $allowed_group_ids, true)) {
            continue;
        }

        // TERM META
        if ($group_id && !isset($term_meta_cache[$group_id])) {
            $term_meta_cache[$group_id] = get_term_meta($group_id);
        }

        $term_meta = $group_id ? $term_meta_cache[$group_id] : [];

        $group_key = !empty($term_meta['group_key'][0]) ? $term_meta['group_key'][0] : ($term ? $term->slug : '');
        $display_style = $term_meta['display_style'][0] ?? 'standard';
        $active = $term_meta['active'][0] ?? 1;

        if ($group_style && $display_style !== $group_style) continue;
        if (!$active) continue;

        // CHARACTERISTIC
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
            $char_data['total'] = (int)$options['limit'];
            $result[] = $char_data;
            continue;
        }

        // ===== GROUPED =====
        if (!isset($groups[$group_key])) {
            $groups[$group_key] = [
                'group_id' => $group_id,
                'group_name' => $group_name,
                'display_style' => $display_style,
                'group_key' => $group_key,
                'system_temp' => $system_temp,
                'total' => (int)$options['limit'],
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
    $active_key = ($options['type'] === 'one' && $system_temp) ? $options['group_key'] : ($options['system_temp'] ?? $options['group_key']);
    
    if ($options['type'] === 'all') {
        $result = array_values($groups);
    } elseif ($options['type'] === 'one' && $active_key) {
        $result = $groups[$active_key] ?? [];
    }

    // ===== SAVE CACHE =====
    if ($options['cache']) {
        wp_cache_set($cache_key, $result, '', (int)$options['cache_ttl']);
    }

    return $result;
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