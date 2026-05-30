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
        <!-- Vue App Container -->
        <div id="vue-property-map-app">
            <app-content-map 
                :latitude="<?php echo esc_js($latitude); ?>" 
                :longitude="<?php echo esc_js($longitude); ?>"
                map-id="property-map"
                :zoom="15"
                height="400px"></app-content-map>
        </div>
        <!-- Vue Script -->
        <script type="module">
            (function() {
                const { createAppModule, AppContentMap } = window.VueAppModule;
                const AppPropertyMap = createAppModule({
                    components: {
                        AppContentMap
                    }
                });
                
                AppPropertyMap.mount('#vue-property-map-app');
            })();
        </script>
    <?php else : ?>
        <p>Местоположение не указано</p>
    <?php endif; ?>
</div>