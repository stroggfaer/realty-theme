import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useGoogleMap } from '../hooks/useGoogleMap.js';

/**
 * AppContentMap - Переиспользуемый компонент для отображения карты одного объекта недвижимости
 * Используется на:
 * - Single property page
 * - Dashboard modal (Показать маршрут)
 * - Любых других местах где нужна карта с одной точкой
 * 
 * @param {Number} latitude - Широта объекта (required)
 * @param {Number} longitude - Долгота объекта (required)
 * @param {String} mapId - ID контейнера карты (default: 'property-map')
 * @param {Number} zoom - Zoom уровень (default: 15)
 * @param {String} height - Высота карты (default: '400px')
 */
export default {
  name: 'AppContentMap',

  template: `
    <div :id="containerId" ref="mapContainer" :style="{ width: '100%', height: height }"></div>
  `,

  props: {
    latitude: {
      type: Number,
      required: true
    },
    longitude: {
      type: Number,
      required: true
    },
    mapId: {
      type: String,
      default: 'property-map'
    },
    zoom: {
      type: Number,
      default: 15
    },
    height: {
      type: String,
      default: '400px'
    }
  },

  setup(props) {
    const containerId = ref(props.mapId);
    const mapContainer = ref(null);
    
    console.log('[AppContentMap] Initializing with:', {
      latitude: props.latitude,
      longitude: props.longitude,
      mapId: props.mapId,
      containerId: containerId.value
    });
    
    // Создаем кастомную инициализацию карты без useGoogleMap hook
    // чтобы избежать конфликта с Vue ref
    const map = ref(null);
    const isLoading = ref(true);
    const isMapReady = ref(false);

    onMounted(() => {
      console.log('[AppContentMap] Mounted, container:', mapContainer.value);
      
      if (!mapContainer.value) {
        console.error('[AppContentMap] Container ref is null');
        return;
      }

      // Проверяем загрузку Google Maps API
      const checkGoogleMaps = () => {
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
          console.log('[AppContentMap] Google Maps API loaded, initializing');
          
          try {
            const mapOptions = {
              zoom: props.zoom,
              center: {
                lat: parseFloat(props.latitude),
                lng: parseFloat(props.longitude)
              },
              mapTypeId: google.maps.MapTypeId.ROADMAP
            };

            map.value = new google.maps.Map(mapContainer.value, mapOptions);

            // Добавляем маркер
            new google.maps.Marker({
              position: {
                lat: parseFloat(props.latitude),
                lng: parseFloat(props.longitude)
              },
              map: map.value,
              title: 'Объект недвижимости'
            });

            isMapReady.value = true;
            isLoading.value = false;
            console.log('[AppContentMap] Map initialized successfully');
          } catch (error) {
            console.error('[AppContentMap] Error initializing map:', error);
            isLoading.value = false;
          }
        } else {
          // Ждем загрузки API
          requestAnimationFrame(checkGoogleMaps);
        }
      };

      checkGoogleMaps();
    });

    onUnmounted(() => {
      console.log('[AppContentMap] Unmounted');
      // Очищаем карту
      if (map.value) {
        map.value = null;
      }
    });

    return {
      containerId,
      mapContainer,
      map,
      isLoading,
      isMapReady
    };
  }
};
