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

get_header(); ?>

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
    const { createAppModule, GoogleMap } = window.VueAppModule;
    const { ref, onMounted } = Vue;

    // Функция для извлечения координат из DOM (как в легаси)
    function getPropertyCoordinates() {
        const coordinates = [];
        jQuery('.property-card[data-lat][data-lng]').each(function() {
            const $card = jQuery(this);
            coordinates.push({
                id: $card.data('id'),
                lat: $card.data('lat'),
                lng: $card.data('lng'),
                title: $card.find('.entry-title a').text().trim() || 'Объект недвижимости'
            });
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

            onMounted(() => {
                // Получаем координаты из DOM
                mapCoordinates.value = getPropertyCoordinates();
                console.log('[Vue GoogleMap] Initial coordinates:', mapCoordinates.value);
            });

            return {
                mapCoordinates
            };
        }
    });

    // Монтируем приложение
    const container = document.getElementById('vue-property-map');
    if (container) {
        AppPropertyMap.mount('#vue-property-map');
        console.log('[Vue GoogleMap] Mounted successfully');
    } else {
        console.error('[Vue GoogleMap] Container not found');
    }
})();
</script>

<?php
get_footer();
?>
