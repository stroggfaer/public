<div class="property-archive-filters">
    <?php
    // Проверяем, были ли переданы параметры фильтрации через GET
    $has_filters = isset($_GET['location']) || isset($_GET['location_slug']) || isset($_GET['checkin_date']) ||
        isset($_GET['checkout_date']) || isset($_GET['adults']) ||
        isset($_GET['children']);

    if ($has_filters) {
        echo '<div class="active-filters-notification">';
        echo '<p><strong>Результаты фильтрации:</strong> ';

        $filter_descriptions = array();

        if (isset($_GET['location_slug']) && !empty($_GET['location_slug'])) {
            $location_term = get_term_by('slug', sanitize_text_field($_GET['location_slug']), 'location');
            if ($location_term) {
                $filter_descriptions[] = 'Город: ' . esc_html($location_term->name);
            }
        } else if (isset($_GET['location']) && !empty($_GET['location'])) {
            $location_term = get_term_by('slug', sanitize_text_field($_GET['location']), 'location');
            if ($location_term) {
                $filter_descriptions[] = 'Город: ' . esc_html($location_term->name);
            }
        }

        if (isset($_GET['checkin_date']) && !empty($_GET['checkin_date'])) {
            $filter_descriptions[] = 'Заезд: ' . esc_html($_GET['checkin_date']);
        }

        if (isset($_GET['checkout_date']) && !empty($_GET['checkout_date'])) {
            $filter_descriptions[] = 'Выезд: ' . esc_html($_GET['checkout_date']);
        }

        if (isset($_GET['adults']) && !empty($_GET['adults'])) {
            $filter_descriptions[] = 'Взрослые: ' . intval($_GET['adults']);
        }

        if (isset($_GET['children']) && !empty($_GET['children'])) {
            $filter_descriptions[] = 'Дети: ' . intval($_GET['children']);
        }

        echo implode(', ', $filter_descriptions);
        echo '</p>';
        echo '</div>';
    }
    ?>
    <div class="property-filter-wrapper">
        <?php echo property_filter_render_form('archive'); ?>
    </div>
</div>
<?php

?>
