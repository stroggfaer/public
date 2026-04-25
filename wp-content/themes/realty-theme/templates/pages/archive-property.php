<?php
/**
 * The template for displaying property archive pages.
 *
 * @package Realty Theme
 */

// Получаем настройки карты из админки
$map_zoom = get_option('realty_map_zoom', 6);
$map_center_lat = get_option('realty_default_lat', 55.7558);
$map_center_lng = get_option('realty_default_lng', 37.6173);
// Меняем слот ставка
add_filter('realty_header_slot', function ($content) {
    return '<div class="main-search-form-container">'.property_filter_render_home_form().'</div>';
});
get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main-map" role="main">
        <?php  get_template_part('template-parts/component/error-valid'); ?>
        <!-- Контейнер для фильтров, списка и карты -->
        <div class="property-archive-container">
            <!-- Сайдбар с фильтрами -->
            <aside class="property-filters-sidebar">
                <?php get_template_part('template-parts/content/list/property-filters'); ?>
            </aside>
            <!-- Основной контент: список и карта -->
            <div class="property-content-area">
                <!-- Контейнер для списка и карты -->
                <div class="property-layout">

                    <div class="content-card-list">
                        <div class="page-header">
                            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
                        </div>
                         <?php get_template_part('template-parts/content/list/property-card-list', null, [
                             'ajaxUrl' => admin_url('admin-ajax.php'),
                             'nonce' => wp_create_nonce('property_filter_nonce'),
                         ]); ?>
                    </div>
                    <!-- Vue Google Map Container -->
                    <div class="property-map-container">
                        <div id="vue-property-map">
                            <!-- Vue компонент GoogleMap -->
                            <google-map
                                map-id="property-map"
                                :coordinates="mapCoordinates"
                                :zoom="<?php echo esc_attr($map_zoom); ?>"
                                :center="{ lat: <?php echo esc_attr($map_center_lat); ?>, lng: <?php echo esc_attr($map_center_lng); ?> }"
                            ></google-map>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Vue Google Map Script -->
<script type="module">
(function() {
    const { createAppModule, GoogleMap, useGoogleMap } = window.VueAppModule;
    const { ref, onMounted, onUnmounted, watch } = Vue;

    // Функция для извлечения координат из DOM
    function getPropertyCoordinates() {
        const coordinates = [];
        jQuery('.property-card[data-lat][data-lng]').each(function() {
            const $card = jQuery(this);
            const lat = parseFloat($card.data('lat'));
            const lng = parseFloat($card.data('lng'));
            
            if (!isNaN(lat) && !isNaN(lng)) {
                coordinates.push({
                    id: $card.data('id'),
                    lat: lat,
                    lng: lng,
                    title: $card.find('.entry-title a').text().trim() || 'Объект недвижимости',
                    price: $card.find('.property-price').text().trim() || '',
                    address: $card.find('.property-address').text().trim() || ''
                });
            }
        });
        return coordinates;
    }

    // Создаем Vue приложение
    const AppPropertyMap = createAppModule({
        components: {
            GoogleMap
        },
        setup() {
            const mapCoordinates = ref([]);

            // Используем хук useGoogleMap для управления картой
            const {
                initMap,
                updateCoordinates,
                initEventListeners,
                cleanupEventListeners,
                isMapReady
            } = useGoogleMap({
                mapId: 'property-map',
                defaultZoom: <?php echo esc_attr($map_zoom); ?>,
                defaultCenter: { 
                    lat: <?php echo esc_attr($map_center_lat); ?>, 
                    lng: <?php echo esc_attr($map_center_lng); ?> 
                }
            });

            onMounted(() => {
                console.log('[Vue GoogleMap] Component mounted');
                
                // Получаем координаты из DOM
                mapCoordinates.value = getPropertyCoordinates();
                console.log('[Vue GoogleMap] Initial coordinates:', mapCoordinates.value);

                // Инициализируем карту с координатами
                if (mapCoordinates.value.length > 0) {
                    initMap(mapCoordinates.value);
                } else {
                    initMap([]);
                }

                // Инициализируем обработчики событий
                initEventListeners();

                // Слушаем AJAX события для обновления координат после фильтрации
                jQuery(document).on('ajaxComplete', function(event, xhr, settings) {
                    if (settings.data && settings.data.indexOf('action=filter_properties_custom') !== -1) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success && response.data) {
                                // Обновляем координаты из ответа или из DOM
                                const newCoordinates = response.data.coordinates || getPropertyCoordinates();
                                mapCoordinates.value = newCoordinates;
                                console.log('[Vue GoogleMap] Updated coordinates after filter:', newCoordinates);
                                
                                if (isMapReady.value) {
                                    updateCoordinates(newCoordinates);
                                }
                            }
                        } catch (e) {
                            console.error('[Vue GoogleMap] Error parsing AJAX response:', e);
                        }
                    }
                });
            });

            onUnmounted(() => {
                console.log('[Vue GoogleMap] Component unmounted');
                cleanupEventListeners();
            });

            // Watch за изменениями координат
            watch(mapCoordinates, (newCoords) => {
                console.log('[Vue GoogleMap] Coordinates changed:', newCoords);
                if (isMapReady.value && newCoords.length > 0) {
                    updateCoordinates(newCoords);
                }
            }, { deep: true });

            return {
                mapCoordinates
            };
        }
    });

    // Монтируем приложение
    const container = document.getElementById('vue-property-map');
    if (container) {
        AppPropertyMap.mount('#vue-property-map');
        console.log('[Vue GoogleMap] App mounted successfully');
    } else {
        console.error('[Vue GoogleMap] Container #vue-property-map not found');
    }
})();
</script>

<?php
get_footer();
?>
