<?php
/**
 * Шаблон карты местоположения объекта недвижимости
 * Использует Vue3 и хук useGoogleMap для отображения карты
 *
 * @param object $pod Pods объект недвижимости
 */
if (!defined('ABSPATH')) {
    exit;
}

$pod = $args['pod'] ?? null;
if (!$pod) {
    return;
}

// Получаем координаты для отображения карты
$latitude = $pod->field('latitude');
$longitude = $pod->field('longitude');
?>
<div class="section-property property-map">
    <h3 class="title">Местоположение</h3>
    <?php if (!empty($latitude) && !empty($longitude)) : ?>
        <div id="vue-property-map-container">
            <div id="property-map" style="width: 100%; height: 400px;"></div>
        </div>
        
        <!-- Vue Google Map Script -->
        <script type="module">
            (function() {
                const { onMounted, onUnmounted, nextTick } = Vue;
                const { createAppModule, useGoogleMap } = window.VueAppModule;

                
                const AppPropertyMap = createAppModule({
                    setup() {
                        const {
                            map,
                            isLoading,
                            isMapReady,
                            initMap,
                            initEventListeners,
                            cleanupEventListeners
                        } = useGoogleMap({
                            mapId: 'property-map',
                            defaultZoom: 15
                        });

                        onMounted(() => {
                            initEventListeners();
                            // Инициализация карты для одиночного объекта
                            const latitude = <?php echo esc_js($latitude); ?>;
                            const longitude = <?php echo esc_js($longitude); ?>;

                            // Ждем загрузки Google Maps API
                            const checkGoogleMaps = () => {
                                if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                                    nextTick(() => {
                                        initMap([], true, latitude, longitude);
                                    });
                                } else {
                                    requestAnimationFrame(checkGoogleMaps);
                                }
                            };

                            checkGoogleMaps();
                        });

                        onUnmounted(() => {
                            cleanupEventListeners();
                        });

                        return {
                            map,
                            isLoading,
                            isMapReady
                        };
                    }
                });
                
                AppPropertyMap.mount('#vue-property-map-container');
            })();
        </script>
    <?php else : ?>
        <p>Местоположение не указано</p>
    <?php endif; ?>
</div>