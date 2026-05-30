import { ref, onMounted, onUnmounted, watch } from 'vue';
import { createApp } from 'vue';
import PropertyPopover from '../components/PropertyPopover.js';

/**
 * Создает класс CustomPriceMarker только когда Google Maps загружен
 * Lazy initialization чтобы избежать ошибки "google is not defined"
 */
function createCustomPriceMarkerClass() {
  if (typeof google === 'undefined' || !google.maps) {
    console.warn('[CustomPriceMarker] Google Maps API not loaded yet');
    return null;
  }
  
  return class CustomPriceMarker extends google.maps.OverlayView {
  constructor(position, propertyData, onClick) {
    super();
    this.position = position;
    this.propertyData = propertyData;
    this.onClick = onClick;
    this.div = null;
    this.lastLeft = null;
    this.lastTop = null;
  }

  onAdd() {
    this.div = document.createElement('div');
    this.div.className = 'map-marker-price';
    this.div.setAttribute('role', 'button');
    this.div.setAttribute('aria-label', `Объект: ${this.propertyData.title}, цена: ${this.propertyData.price}`);
    this.div.innerHTML = `<span class="price-text">${this.propertyData.price} ₽</span>`;
    
    // Hover эффекты - поднимаем z-index и выделяем
    this.div.addEventListener('mouseenter', () => {
      this.div.classList.add('hover');
      // Поднимаем z-index при наведении
      this.div.style.zIndex = '100';
    });
    this.div.addEventListener('mouseleave', () => {
      this.div.classList.remove('hover');
      // Возвращаем обычный z-index
      this.div.style.zIndex = '10';
    });
    
    // Click handler
    this.div.addEventListener('click', (e) => {
      e.stopPropagation();
      this.onClick(this.propertyData, this.div);
    });
    
    const panes = this.getPanes();
    panes.overlayMouseTarget.appendChild(this.div);
  }

  draw() {
    const projection = this.getProjection();
    if (!projection || !this.div) return;
    
    const pos = projection.fromLatLngToDivPixel(this.position);
    
    // Округляем значения для избежания sub-pixel дрожания
    const left = Math.round(pos.x);
    const top = Math.round(pos.y);
    
    // Обновляем только если позиция изменилась (избегаем лишних reflow)
    if (this.lastLeft !== left || this.lastTop !== top) {
      this.div.style.left = left + 'px';
      this.div.style.top = top + 'px';
      this.div.style.transform = 'translate(-50%, -100%)';
      
      this.lastLeft = left;
      this.lastTop = top;
    }
  }

  onRemove() {
    if (this.div && this.div.parentNode) {
      this.div.parentNode.removeChild(this.div);
      this.div = null;
    }
  }
  
  // Метод для совместимости с google.maps.Marker API
  getPosition() {
    return this.position;
  }
  
  setMap(map) {
    if (map) {
      super.setMap(map);
    } else {
      this.onRemove();
      super.setMap(null);
    }
  }
};
}

/**
 * Хук для работы с Google Maps
 * Содержит бизнес-логику: инициализация, маркеры, события
 */
export function useGoogleMap(options = {}) {
    // Проверяем что Google Maps API загружен
    if (typeof google === 'undefined' || !google.maps) {
        console.warn('[useGoogleMap] Google Maps API not loaded - returning stub');
        // Возвращаем stub чтобы компонент мог импортироваться без ошибок
        return {
            map: ref(null),
            markers: ref([]),
            infoWindows: ref([]),
            isLoading: ref(false),
            isMapReady: ref(false),
            coordinates: ref([]),
            initMap: () => { console.warn('[useGoogleMap] initMap called but Google Maps not loaded'); },
            updateCoordinates: () => { console.warn('[useGoogleMap] updateCoordinates called but Google Maps not loaded'); },
            initEventListeners: () => {},
            cleanupEventListeners: () => {}
        };
    }
    
    // Создаем класс маркера только теперь когда google доступен
    const CustomPriceMarker = createCustomPriceMarkerClass();
    
    const {
        mapId = 'property-map',
        defaultZoom = 6,
        defaultCenter = { lat: 55.7558, lng: 37.6173 },
        onMarkerClick = null,
        onMapReady = null
    } = options;

    // Состояние
    const map = ref(null);
    const markers = ref([]);
    const infoWindows = ref([]);
    const markerMap = ref({});
    const isLoading = ref(true);
    const isMapReady = ref(false);
    const coordinates = ref([]);
    
    // Управление popover
    let activePopovers = [];
    let activeMarkerElement = null; // Ссылка на текущий активный маркер (для toggle)

    // Проверка загрузки Google Maps API
    function isGoogleMapsLoaded() {
        return typeof google !== 'undefined' && typeof google.maps !== 'undefined';
    }

    // Инициализация карты
    function initMap(mapCoordinates = [], singleProperty = false, lat = null, lng = null) {
        if (!isGoogleMapsLoaded()) {
            console.error('Google Maps API не загружен');
            isLoading.value = false;
            return;
        }

        const mapContainer = document.getElementById(mapId);
        if (!mapContainer) {
            console.error('Контейнер для карты не найден:', mapId);
            isLoading.value = false;
            return;
        }

        let mapCenter = { ...defaultCenter };
        let mapZoom = defaultZoom;

        // Если это одиночный объект
        if (singleProperty && lat && lng) {
            mapCenter = {
                lat: parseFloat(lat),
                lng: parseFloat(lng)
            };
            mapZoom = 15;
        } else if (mapCoordinates && mapCoordinates.length > 0) {
            mapCenter = {
                lat: parseFloat(mapCoordinates[0].lat),
                lng: parseFloat(mapCoordinates[0].lng)
            };
        }

        const mapOptions = {
            zoom: mapZoom,
            center: mapCenter,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        map.value = new google.maps.Map(mapContainer, mapOptions);

        // Убрали слушатели center_changed, zoom_changed, drag
        // Теперь popover использует requestAnimationFrame для постоянного трекинга позиции
        // Это решает проблему инерции карты и работает намного плавнее

        // Добавляем маркеры
        if (singleProperty && lat && lng) {
            addSingleMarker(lat, lng);
        } else if (mapCoordinates && mapCoordinates.length > 0) {
            addMarkers(mapCoordinates);
        }

        isMapReady.value = true;
        isLoading.value = false;

        if (onMapReady) {
            onMapReady(map.value);
        }
    }

    // Добавить маркер для одиночного объекта
    function addSingleMarker(lat, lng) {
        const marker = new google.maps.Marker({
            position: {
                lat: parseFloat(lat),
                lng: parseFloat(lng)
            },
            map: map.value,
            title: 'Объект недвижимости'
        });
        markers.value.push(marker);
    }

    // Добавить маркеры для списка объектов
    function addMarkers(newCoordinates) {
        clearMarkers();

        newCoordinates.forEach(coord => {
            const position = {
                lat: parseFloat(coord.lat),
                lng: parseFloat(coord.lng)
            };

            // Используем кастомный маркер с ценой
            const marker = new CustomPriceMarker(position, coord, handleMarkerClick);
            marker.setMap(map.value);
            markers.value.push(marker);
            markerMap.value[coord.id] = {
                marker: marker
            };
        });

        // Автоматическое масштабирование
        if (markers.value.length > 1) {
            const bounds = new google.maps.LatLngBounds();
            markers.value.forEach(marker => bounds.extend(marker.getPosition()));
            map.value.fitBounds(bounds);
        } else if (markers.value.length === 1) {
            map.value.setCenter(markers.value[0].getPosition());
        }
    }
    
    // Обработчик клика на маркер (работает как toggle)
    function handleMarkerClick(propertyData, markerElement) {
        // Проверяем, это тот же самый маркер? (toggle)
        if (activeMarkerElement === markerElement && activePopovers.length > 0) {
            // Повторный клик на тот же маркер - закрываем
            closeAllPopovers();
            activeMarkerElement = null;
            return;
        }
        
        // Клик на другой маркер или первый клик - открываем
        // Закрыть все открытые popover
        closeAllPopovers();
        
        // Убираем active класс у всех маркеров
        document.querySelectorAll('.map-marker-price').forEach(marker => {
            marker.classList.remove('active');
            marker.style.zIndex = '10';
        });
        
        // Добавляем active класс к текущему маркеру
        if (markerElement) {
            markerElement.classList.add('active');
            markerElement.style.zIndex = '100';
            activeMarkerElement = markerElement; // Запоминаем активный маркер
        }
        
        // Создать и показать новый popover
        showPropertyPopover(propertyData, markerElement);
        
        // Убрали подсветку карточки и скролл - только открываем popover
        // highlightPropertyCard(propertyData.id); // отключено
    }
    
    // Показать popover с информацией об объекте
    function showPropertyPopover(propertyData, anchorElement) {
        const container = document.createElement('div');
        container.className = 'map-marker-popover-container';
        document.body.appendChild(container);
        const app = createApp(PropertyPopover, {
            propertyData,
            anchorElement,
            onClose: () => {
                app.unmount();
                container.remove();
                activePopovers = activePopovers.filter(p => p !== app);
                activeMarkerElement = null; // Сбрасываем активный маркер
            }
        });
        
        app.mount(container);
        activePopovers.push(app);
    }
    
    // Закрыть все popover
    function closeAllPopovers() {
        activePopovers.forEach(app => {
            try {
                app.unmount();
            } catch (e) {
                console.warn('Ошибка при закрытии popover:', e);
            }
        });
        activePopovers = [];
        activeMarkerElement = null; // Сбрасываем активный маркер
        document.querySelectorAll('.map-marker-popover-container').forEach(el => el.remove());
        
        // Убираем active класс у всех маркеров
        document.querySelectorAll('.map-marker-price.active').forEach(marker => {
            marker.classList.remove('active');
            marker.style.zIndex = '10';
        });
    }
    
    // Обновить позиции всех активных popover
    // Очистка маркеров
    function clearMarkers() {
        markers.value.forEach(marker => {
            if (marker && typeof marker.setMap === 'function') {
                marker.setMap(null);
            } else if (marker && typeof marker.onRemove === 'function') {
                marker.onRemove();
            }
        });
        markers.value = [];
        
        // Закрываем InfoWindow с проверкой
        infoWindows.value.forEach(iw => {
            if (iw && typeof iw.close === 'function') {
                iw.close();
            }
        });
        infoWindows.value = [];
        markerMap.value = {};
        
        // Закрыть все popover
        closeAllPopovers();
    }

    // Выделение карточки в списке
    function highlightPropertyCard(propertyId) {
        document.querySelectorAll('.property-card').forEach(card => {
            card.classList.remove('highlighted');
        });

        const card = document.querySelector(`.property-card[data-id="${propertyId}"]`);
        if (card) {
            card.classList.add('highlighted');
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Центрирование карты на объекте
    function panToProperty(propertyId, lat, lng) {
        if (!map.value) return;

        const position = new google.maps.LatLng(parseFloat(lat), parseFloat(lng));
        map.value.panTo(position);
        map.value.setZoom(14);

        if (markerMap.value[propertyId]) {
            // Закрываем все InfoWindow (если они есть)
            infoWindows.value.forEach(iw => {
                if (iw && typeof iw.close === 'function') {
                    iw.close();
                }
            });
            
            // Открываем InfoWindow только если он существует
            // (для кастомных маркеров используем popover, а не InfoWindow)
            const markerData = markerMap.value[propertyId];
            if (markerData.infoWindow && typeof markerData.infoWindow.open === 'function') {
                markerData.infoWindow.open(
                    map.value,
                    markerData.marker
                );
            }
        }
    }

    // Обновление координат
    function updateCoordinates(newCoordinates) {
        if (!map.value) {
            if (isGoogleMapsLoaded()) {
                initMap(newCoordinates);
            }
            return;
        }

        // addMarkers() сам вызывает clearMarkers()
        // coordinates.value уже содержит все координаты (включая пагинацию)
        addMarkers(newCoordinates);
    }

    // Обработчик клика по кнопке карты
    function handleCardClick(event) {
        const mapButton = event.target.closest('.js-map-location');
        if (!mapButton || !map.value) return;

        event.preventDefault();
        event.stopPropagation();

        const id = mapButton.dataset.id;
        const lat = mapButton.dataset.lat;
        const lng = mapButton.dataset.lng;

        if (lat && lng) {
            // Убираем активный класс у всех кнопок карты
            document.querySelectorAll('.js-map-location').forEach(btn => {
                btn.classList.remove('active');
            });
            // Добавляем активный класс текущей кнопке
            mapButton.classList.add('active');

            panToProperty(id, lat, lng);
        }
    }

    // Обработчик AJAX завершения фильтрации
    function handleAjaxComplete(event, xhr, settings) {
        if (!settings.data || settings.data.indexOf('action=filter_properties_custom') === -1) {
            return;
        }

        try {
            const response = JSON.parse(xhr.responseText);
            if (response.success && response.data && response.data.coordinates) {
                updateCoordinates(response.data.coordinates);
            }
        } catch (e) {
            console.error('Ошибка при обработке ответа AJAX:', e);
        }
    }

    // Инициализация обработчиков событий (для вызова в onMounted компонента)
    function initEventListeners() {
        document.addEventListener('click', handleCardClick);
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxComplete(handleAjaxComplete);
        }
    }

    // Очистка обработчиков событий (для вызова в onUnmounted компонента)
    function cleanupEventListeners() {
        clearMarkers();
        document.removeEventListener('click', handleCardClick);
    }

    // Watch за координатами
    watch(coordinates, (newCoords) => {
        if (newCoords && newCoords.length > 0 && isMapReady.value) {
            updateCoordinates(newCoords);
        }
    }, { deep: true });

    return {
        // State
        map,
        markers,
        infoWindows,
        isLoading,
        isMapReady,
        coordinates,
        
        // Methods
        initMap,
        addMarkers,
        clearMarkers,
        updateCoordinates,
        panToProperty,
        highlightPropertyCard,
        initEventListeners,
        cleanupEventListeners
    };
}
