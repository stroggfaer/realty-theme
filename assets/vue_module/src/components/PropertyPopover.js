import { ref, onMounted, onBeforeUnmount, computed } from 'vue';

/**
 * Кастомный Popover для отображения информации об объекте недвижимости на карте
 * Без Element Plus - полностью своя реализация
 */
export default {
  name: 'PropertyPopover',
  
  template: `
    <div 
      class="custom-popover"
      :class="{ 'custom-popover--visible': visible }"
      :style="popoverStyle"
    
    >
      <!-- Стрелка popover -->
      <div class="custom-popover__arrow"></div>
      
      <!-- Слайдер фотографий -->
      <div class="custom-popover__slider" v-if="hasImages"   @click="navigateToProperty">
        <div class="slider-track" :style="sliderTrackStyle">
          <div class="slider-slide" v-for="(image, index) in images" :key="index">
            <img :src="image" class="slider-image" :alt="'Фото ' + (index + 1)">
          </div>
        </div>
        
        <!-- Навигация слайдера -->
        <button class="slider-nav slider-nav--prev" @click.stop="prevSlide" v-if="images.length > 1">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
          </svg>
        </button>
        <button class="slider-nav slider-nav--next" @click.stop="nextSlide" v-if="images.length > 1">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
            <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
          </svg>
        </button>
        
        <!-- Счетчик -->
        <div v-if="images.length > 1" class="slider-counter">{{ currentSlide + 1 }} / {{ images.length }}</div>
      </div>

      <!-- Информация об объекте -->
      <div class="custom-popover__info">
        <div class="popover-header">
          <span class="rating-badge">{{ propertyData.rating }}</span>
          <button
            class="favorite-btn js-favorite"
            :data-property-id="propertyData.id"
            :data-is-favorite="Number(propertyData.is_favorite)"
            :class="{'active': propertyData.is_favorite}"
          >
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
          </button>
        </div>

        <h3 class="property-title">{{ propertyData.title }}</h3>

        <div class="property-price-block">
          <span class="price">{{ propertyData.price }} ₽</span>
          <span class="period">/ {{ propertyData.price_period }}</span>
        </div>
      </div>
    </div>
  `,

  props: {
    propertyData: {
      type: Object,
      required: true
    },
    anchorElement: {
      type: HTMLElement,
      default: null
    },
    onClose: {
      type: Function,
      required: true
    }
  },

  setup(props) {
    const visible = ref(false);
    const currentSlide = ref(0);
    const popoverPosition = ref({ top: 0, left: 0 });
    let animationFrameId = null; // ID для requestAnimationFrame

    // Подготавливаем массив изображений
    // Используем новое поле images (массив URL строк)
    const images = [];
    if (props.propertyData.images && Array.isArray(props.propertyData.images)) {
      // Новое поле: images = ['url1', 'url2', ...]
      images.push(...props.propertyData.images.filter(url => url));
    } else if (props.propertyData.thumbnail) {
      // Fallback: только thumbnail
      images.push(props.propertyData.thumbnail);
    }

    const hasImages = images.length > 0;

    // Вычисляем позицию popover (FIXED positioning)
    const popoverStyle = computed(() => {
      return {
        position: 'fixed',
        top: `${popoverPosition.value.top}px`,
        left: `${popoverPosition.value.left}px`,
        transform: 'translate(-50%, -100%)'
      };
    });

    const sliderTrackStyle = computed(() => {
      return {
        transform: `translateX(-${currentSlide.value * 100}%)`
      };
    });

    function updatePosition() {
      if (!props.anchorElement) return;
      
      // Получаем позицию маркера относительно viewport (не документа!)
      const rect = props.anchorElement.getBoundingClientRect();
      
      popoverPosition.value = {
        top: rect.top - 10, // 10px отступ от маркера
        left: rect.left + rect.width / 2
      };
    }
    
    // Постоянное отслеживание позиции через requestAnimationFrame
    // Это решает проблему инерции карты (когда отпускаешь и карта еще двигается)
    function startTrackingPosition() {
      function track() {
        updatePosition();
        // Продолжаем отслеживать пока popover виден
        if (visible.value) {
          animationFrameId = requestAnimationFrame(track);
        }
      }
      // Запускаем цикл отслеживания
      animationFrameId = requestAnimationFrame(track);
    }
    
    function stopTrackingPosition() {
      if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
      }
    }

    function nextSlide() {
      if (currentSlide.value < images.length - 1) {
        currentSlide.value++;
      } else {
        currentSlide.value = 0;
      }
    }

    function prevSlide() {
      if (currentSlide.value > 0) {
        currentSlide.value--;
      } else {
        currentSlide.value = images.length - 1;
      }
    }

    function navigateToProperty() {
      if (props.propertyData.permalink) {
        window.location.href = props.propertyData.permalink;
      }
    }

    function toggleFavorite() {
      props.propertyData.is_favorite = !props.propertyData.is_favorite;
    }

    // Обработчик клика вне popover
    function handleClickOutside(event) {
      const popover = document.querySelector('.custom-popover--visible');
      const marker = props.anchorElement;
      
      // Игнорируем клики на саму карту и её элементы (drag не должен закрывать)
      if (event.target.closest('.property-map') || 
          event.target.closest('.gm-style') ||
          event.target.closest('.map-marker-price')) {
        return; // НЕ закрываем при взаимодействии с картой
      }
      
      // Проверяем, что клик был НЕ по popover и НЕ по маркеру
      if (popover && !popover.contains(event.target) && 
          marker && !marker.contains(event.target)) {
        // Закрываем popover
        props.onClose();
      }
    }
    
    // Обработчик скролла для обновления позиции ( popover следует за маркером)
    function handleScroll() {
      // При скролле просто обновляем позицию - FIXED positioning не скроллится
      updatePosition();
    }
    
    // Обработчик клавиш (Escape для закрытия)
    function handleKeyDown(event) {
      if (event.key === 'Escape') {
        props.onClose();
      }
    }

    onMounted(() => {
      // Обновляем позицию
      updatePosition();
          
      // Показываем popover с небольшой задержкой
      setTimeout(() => {
        visible.value = true;
        // Запускаем постоянное отслеживание позиции (решает проблему инерции)
        startTrackingPosition();
      }, 50);
          
      // Добавляем обработчики
      setTimeout(() => {
        document.addEventListener('click', handleClickOutside);
        document.addEventListener('keydown', handleKeyDown);
        // При скролле страницы обновляем позицию
        window.addEventListener('scroll', handleScroll, true);
        window.addEventListener('resize', updatePosition);
      }, 100);

      document.addEventListener('click', handleClickOutside);

    });
        
    onBeforeUnmount(() => {
      // Останавливаем трекинг позиции
      stopTrackingPosition();
          
      document.removeEventListener('click', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
      window.removeEventListener('scroll', handleScroll, true);
      window.removeEventListener('resize', updatePosition);
    });

    return {
      visible,
      currentSlide,
      images,
      hasImages,
      popoverStyle,
      sliderTrackStyle,
      nextSlide,
      prevSlide,
      navigateToProperty,
      toggleFavorite,
      // handleScroll больше не нужен
    };
  }
};
