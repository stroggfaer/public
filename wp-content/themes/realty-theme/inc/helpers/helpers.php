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


