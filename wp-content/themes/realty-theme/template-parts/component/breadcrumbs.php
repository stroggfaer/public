<?php
/**
 * Шаблон Breadcrumbs
 *
 */
if (!defined('ABSPATH')) {
    exit;
}

// Breadcrumbs: выводим иерархию таксономии 'location' и название объекта
$location_terms = get_the_terms(get_the_ID(), 'location');
if (!empty($location_terms) && !is_wp_error($location_terms)) {
    // если терминов несколько, берем первый
    $term = $location_terms[0];
    $ancestors = get_ancestors($term->term_id, 'location');
    $ancestors = array_reverse($ancestors);
    echo '<nav class="breadcrumb__com" aria-label="breadcrumb">';
    echo '<a href="' . esc_url(home_url('/')) . '">Главная</a> <span>/</span> ';
    echo '<a href="' . esc_url(home_url('/locations/')) . '">Города</a> <span>/</span> ';
    foreach ($ancestors as $ancestor_id) {
        $ancestor = get_term($ancestor_id, 'location');
        if ($ancestor && !is_wp_error($ancestor)) {
            $ancestor_link = get_term_link($ancestor);
            if (is_wp_error($ancestor_link)) {
                $ancestor_link = home_url('/locations/' . $ancestor->slug . '/');
            }
            echo '<a href="' . esc_url($ancestor_link) . '">' . esc_html($ancestor->name) . '</a> <span>/</span> ';
        }
    }
    $term_link = get_term_link($term);
    if (is_wp_error($term_link)) {
        $term_link = home_url('/locations/' . $term->slug . '/');
    }
    echo '<a href="' . esc_url($term_link) . '">' . esc_html($term->name) . '</a> <span>/</span> ';
    echo '<span class="breadcrumb_name">' . esc_html(get_the_title()) . '</span>';
    echo '</nav>';
}
?>