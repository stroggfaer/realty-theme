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

/**
 * Получаем координаты для карты напрямую из backend
 * 
 * Используем PropertyFeature::getListFromGet() чтобы получить ВСЕ 11 полей:
 * - id, lat, lng, title
 * - price, price_period
 * - thumbnail, gallery (до 5 фото)
 * - rating, is_favorite, permalink
 * 
 * Это современное решение:
 * ✅ Не читает из DOM (никаких data-атрибутов)
 * ✅ Все данные приходят с сервера сразу
 * ✅ Работает при initial load И после AJAX фильтрации
 * ✅ Масштабируется - просто добавляем поля в property.php
 */
$propertyFeature = PropertyFeature::getInstance();
$initial_data = $propertyFeature->getListFromGet();
$map_coordinates_json = wp_json_encode(
    $initial_data['coordinates'] ?? [],
    JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

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
    const { ref, onMounted, onUnmounted, watch, computed } = Vue;

    // Создаем Vue приложение
    const AppPropertyMap = createAppModule({
        components: {
            GoogleMap
        },
        setup() {
            const store = Vuex.useStore();
            
            // Получаем координаты из Vuex store (usePropertyFilters уже управляет ими!)
            const mapCoordinates = computed(() => store.getters['propertyFilters/getCoordinates']);

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
                console.log('[Vue GoogleMap] Initial coordinates from server:', <?=$map_coordinates_json?>);

                // Инициализируем карту с координатами с сервера
                const initialCoords = <?=$map_coordinates_json?>;
                if (initialCoords.length > 0) {
                    initMap(initialCoords);
                    // Сохраняем в store для синхронизации
                    store.commit('propertyFilters/SET_COORDINATES', initialCoords);
                } else {
                    initMap([]);
                }

                // Инициализируем обработчики событий
                initEventListeners();
            });

            onUnmounted(() => {
                console.log('[Vue GoogleMap] Component unmounted');
                cleanupEventListeners();
            });

            // Watch за изменениями координат в Vuex store (фильтрация + пагинация)
            watch(mapCoordinates, (newCoords) => {
                console.log('[Vue GoogleMap] Coordinates from store changed:', newCoords?.length || 0, 'coords');
                if (isMapReady.value && newCoords && newCoords.length > 0) {
                    console.log('[Vue GoogleMap] Updating map with', newCoords.length, 'coordinates');
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
