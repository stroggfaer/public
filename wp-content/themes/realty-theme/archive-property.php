<?php
/**
 * The template for displaying property archive pages.
 *
 * @package Realty Theme
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <header class="page-header">
            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
        </header>

        <!-- Фильтры -->
        <div class="property-filters">
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="price_min">Цена от</label>
                    <input type="number" id="price_min" name="price_min" min="0" placeholder="0">
                </div>
                
                <div class="filter-group">
                    <label for="price_max">до</label>
                    <input type="number" id="price_max" name="price_max" min="0" placeholder="1000000">
                </div>
                
                <div class="filter-group">
                    <label for="property_type">Тип</label>
                    <select id="property_type" name="property_type">
                        <option value="">Все</option>
                        <?php
                        $property_types = get_terms(array(
                            'taxonomy' => 'property_type',
                            'hide_empty' => false,
                        ));
                        foreach ($property_types as $type) {
                            echo '<option value="' . esc_attr($type->slug) . '">' . esc_html($type->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="location">Город</label>
                    <select id="location" name="location">
                        <option value="">Все</option>
                        <?php
                        $locations = get_terms(array(
                            'taxonomy' => 'location',
                            'hide_empty' => false,
                        ));
                        foreach ($locations as $loc) {
                            echo '<option value="' . esc_attr($loc->slug) . '">' . esc_html($loc->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort_order">Сортировка</label>
                    <select id="sort_order" name="sort_order">
                        <option value="date_desc">Новизне</option>
                        <option value="date_asc">Старизне</option>
                        <option value="price_asc">Цене (по возрастанию)</option>
                        <option value="price_desc">Цене (по убыванию)</option>
                        <option value="area_asc">Площади (по возрастанию)</option>
                        <option value="area_desc">Площади (по убыванию)</option>
                    </select>
                </div>
                
                <button id="apply_filters" class="filter-btn">Применить</button>
                <button id="reset_filters" class="filter-btn reset-btn">Сбросить</button>
            </div>
        </div>

        <div class="properties-container">
            <!-- Карта -->
            <div id="property-map" class="property-map"></div>
            
            <div class="properties-content">
                <!-- Список недвижимости -->
                <div id="properties-list" class="properties-grid">
                    <?php
                    // Используем Pods для получения объектов недвижимости
                    $current_page = get_query_var('paged') ? get_query_var('paged') : 1;
                    
                    // Подготовим параметры для фильтрации
                    $params = array(
                        'where' => 't.post_status = "publish"',
                        'limit' => 9,
                        'page' => $current_page,
                        'orderby' => 't.post_date DESC'
                    );
                    
                    // Создаем экземпляр Pods
                    $property_pod = pods('property', $params);
                    
                    if ($property_pod->total() > 0) {
                        while ($property_pod->fetch()) {
                            $property_id = $property_pod->id();
                            $property_title = $property_pod->display('post_title');
                            $property_content = $property_pod->display('post_content');
                            $property_price = $property_pod->field('price');
                            $property_address = $property_pod->field('address');
                            $property_guest_count = $property_pod->field('guest_count');
                            $property_lat = $property_pod->field('latitude');
                            $property_lng = $property_pod->field('longitude');
                            
                            // Получаем термины для таксономий
                            $property_locations = $property_pod->field('location');
                            $property_types = $property_pod->field('property_type');
                            
                            // Получаем изображение
                            $thumbnail_url = get_the_post_thumbnail_url($property_id, 'medium');
                            if (!$thumbnail_url) {
                                $thumbnail_url = get_template_directory_uri() . '/assets/images/no_image.png';
                            }
                            ?>
                            <article id="post-<?php echo $property_id; ?>" class="property-card" data-id="<?php echo $property_id; ?>"
                                data-lat="<?php echo $property_lat; ?>" data-lng="<?php echo $property_lng; ?>">
                                <div class="property-image">
                                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($property_title); ?>">
                                </div>
                                
                                <div class="property-info">
                                    <h3 class="property-title">
                                        <a href="<?php echo esc_url(get_permalink($property_id)); ?>"><?php echo esc_html($property_title); ?></a>
                                    </h3>
                                    
                                    <div class="property-details">
                                        <?php if ($property_price) : ?>
                                            <div class="property-price">₽<?php echo number_format($property_price, 0, '', ' '); ?></div>
                                        <?php endif; ?>
                                        
                                        <?php if ($property_address) : ?>
                                            <div class="property-address"><?php echo esc_html($property_address); ?></div>
                                        <?php endif; ?>
                                        
                                        <?php if ($property_locations) : ?>
                                            <div class="property-location">
                                                <?php
                                                $location_names = array();
                                                if (is_array($property_locations)) {
                                                    foreach ($property_locations as $location) {
                                                        $location_names[] = $location['name'];
                                                    }
                                                } else {
                                                    $location_names[] = $property_locations['name'];
                                                }
                                                echo esc_html(implode(', ', $location_names));
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($property_types) : ?>
                                            <div class="property-type">
                                                <?php
                                                $type_names = array();
                                                if (is_array($property_types)) {
                                                    foreach ($property_types as $type) {
                                                        $type_names[] = $type['name'];
                                                    }
                                                } else {
                                                    $type_names[] = $property_types['name'];
                                                }
                                                echo esc_html(implode(', ', $type_names));
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($property_guest_count) : ?>
                                            <div class="property-guests">Гостей: <?php echo intval($property_guest_count); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="property-excerpt">
                                        <?php echo wp_kses_post(wp_trim_words($property_content, 15)); ?>
                                    </div>
                                    
                                    <a href="<?php echo esc_url(get_permalink($property_id)); ?>" class="property-more">Подробнее</a>
                                </div>
                            </article>
                            <?php
                        }
                        
                        // Пагинация
                        $big = 99999; // уникальное значение
                        echo paginate_links(array(
                            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format' => '?paged=%#%',
                            'current' => max(1, $current_page),
                            'total' => $property_pod->total_pages(),
                            'type' => 'plain',
                            'class' => 'property-pagination'
                        ));
                    } else {
                        echo '<p class="no-properties">Недвижимость не найдена.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Подключаем Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
.property-filters {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.filter-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 120px;
}

.filter-group label {
    font-size: 14px;
    margin-bottom: 5px;
    color: #555;
}

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.filter-btn {
    padding: 8px 16px;
    background: #007cba;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.filter-btn:hover {
    background: #005a87;
}

.reset-btn {
    background: #6c757d;
    margin-left: 8px;
}

.reset-btn:hover {
    background: #545b62;
}

.properties-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.property-map {
    height: 300px;
    border-radius: 8px;
    overflow: hidden;
}

.properties-content {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
}

.property-card {
    border: 1px solid #eaeaea;
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow 0.3s ease;
    background: white;
}

.property-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.property-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
}

.property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.property-info {
    padding: 15px;
}

.property-title {
    margin: 0 0 10px 0;
    font-size: 18px;
}

.property-title a {
    text-decoration: none;
    color: #333;
}

.property-title a:hover {
    color: #007cba;
}

.property-details {
    margin-bottom: 10px;
}

.property-price {
    font-weight: bold;
    font-size: 18px;
    color: #28a745;
    margin-bottom: 5px;
}

.property-address,
.property-location,
.property-type,
.property-guests {
    font-size: 14px;
    color: #666;
    margin-bottom: 3px;
}

.property-excerpt {
    margin: 10px 0;
    color: #555;
    line-height: 1.5;
}

.property-more {
    display: inline-block;
    padding: 6px 12px;
    background: #f8f9fa;
    color: #007cba;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
}

.property-more:hover {
    background: #e9ecef;
}

.property-pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
}

.property-pagination a,
.property-pagination span {
    padding: 8px 12px;
    text-decoration: none;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.property-pagination a:hover {
    background: #f8f9fa;
}

.no-properties {
    text-align: center;
    padding: 40px;
    color: #666;
    font-size: 18px;
}

@media (max-width: 768px) {
    .filter-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .properties-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
get_sidebar();
get_footer();
?>