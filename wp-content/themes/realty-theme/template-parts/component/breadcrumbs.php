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
    foreach ($ancestors as $ancestor_id) {
        $ancestor = get_term($ancestor_id, 'location');
        if ($ancestor && !is_wp_error($ancestor)) {
            echo '<a href="' . esc_url(get_term_link($ancestor)) . '">' . esc_html($ancestor->name) . '</a> <span>/</span> ';
        }
    }
    echo '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a> <span>/</span> ';
    echo '<span class="breadcrumb_name">' . esc_html(get_the_title()) . '</span>';
    echo '</nav>';
}
?>