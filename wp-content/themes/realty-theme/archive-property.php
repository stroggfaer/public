<?php
/**
 * The template for displaying property archive pages.
 *
 * @package Realty Theme
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main-map" role="main">
        <?php
        // Показываем ошибки валидации, если они есть
        $validation_errors = get_property_search_errors();
        if (!empty($validation_errors)):
        ?>
            <div class="validation-errors">
                <ul>
                    <?php foreach ($validation_errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Контейнер для фильтров, списка и карты -->
        <div class="property-archive-container">
            <!-- Сайдбар с фильтрами -->
            <aside class="property-filters-sidebar">
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
            </aside>
        
            <!-- Основной контент: список и карта -->
            <div class="property-content-area">
                <!-- Контейнер для списка и карты -->
                <div class="property-layout">
                    <div class="content-card-list">
                        <div class="page-header">
                            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
                        </div>
                        <div class="property-list">
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
                        </div>
                    </div>
                    <!-- Контейнер для карты -->
                    <div class="property-map-container">
                        <div id="property-map" class="property-map"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>


<?php
get_footer();
?>