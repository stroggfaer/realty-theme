import {ref, onMounted, watch, h, computed} from 'vue';
import moment from 'moment';
import usePropertyFilters from '../hooks/usePropertyFilters';
import {declension} from "@assets/vue_module/src/utlis/helpers";

export default {

  template: `
    <form id="property-home-filter-form" class="property-filter-form home-filter" @submit.prevent="handleSubmit">
      <div class="filter-row">
        <!-- Локация -->
        <el-autocomplete
              v-if="activeFilters.includes('location')"
              v-model="locationInput"
              :fetch-suggestions="querySearch"
              placeholder="Город или регион"
              class="location-autocomplete"
              @select="localHandleLocationSelect"
              clearable
              :disabled="disabledLocation"
          >
            <template #prefix>
              <span class="icon material-symbols-outlined">location_on</span>
            </template>
            <template #default="{ item }">
              <span>{{ item.name }}</span>
            </template>
            <template #empty>
              <span>Ничего не найдено</span>
            </template>
        </el-autocomplete>
        <div class="line"></div>
        <!-- Даты (Date Range) -->
        <el-date-picker
            v-if="activeFilters.includes('checkin_date') && activeFilters.includes('checkout_date')"
            v-model="dateRange"
            type="daterange"
            range-separator="-"
            start-placeholder="Заезд"
            end-placeholder="Выезд"
            format="D MMM"
            value-format="YYYY-MM-DD"
            :disabled-date="disabledCheckinDate"
            @change="handleDateChange"
            :prefix-icon="{ render: DateRangeIcon }"/>
        <div class="line"></div>
        <!-- Гости -->
        <div v-if="hasGuestFilters" class="input-group__com guests-group">
          <el-popover
            v-model:visible="guestsPopoverVisible"
            placement="bottom-start"
            :width="300"
            trigger="click"
          >
            <template #reference>
              <div class="input-with-icon">
                <span class="icon material-symbols-outlined">group</span>
                <input
                  type="text" 
                  readonly 
                  :value="guestsDisplayText" 
                  class="input__com guests-placeholder" 
                  :placeholder="guestsDisplayText"
                >
              </div>
            </template>
            <!-- Guest control - динамический рендер -->
            <div class="guest-controls">
              <div v-for="guest in guestsConfig" :key="guest.name" class="guest-control">
                <div class="flex-label">
                  <span class="guest-label">{{ guest.label }}</span>
                  <span class="guest-desc" v-if="guest.desc">{{ guest.desc }}</span>
                </div>
                <div class="guest-input">
                  <el-input-number
                    v-model="guestValues[guest.name]"
                    :min="guest.min"
                    :max="guest.max"
                    :controls="true"
                  />
                </div>
              </div>
            </div>
            <!-- ./Guest control-->
          </el-popover>
        </div>
      </div>
      
      <div class="filter-actions">
        <button type="submit" class="button__com lg">
          <span class="icon material-symbols-outlined">search</span>
          <span class="text-b">Искать</span>
        </button>
      </div>
    </form>
  `,

  props: {
    activeFilters: {
      type: Array,
      default: () => ['location', 'checkin_date', 'checkout_date', 'adults', 'children']
    },
    config: {
      type: Object,
      default: () => ({
        guests: [
          { name: 'adults', label: 'Взрослые', desc: 'от 13 лет', min: 1, max: 10 },
          { name: 'children', label: 'Дети', desc: 'от 2 до 12 лет', min: 0, max: 8 }
        ]
      })
    },
    ajaxUrl: {
      type: String,
      default: ''
    },
    nonce: {
      type: String,
      default: ''
    },
    location: {
      type: Object,
      default: null
    },
  },

  setup(props) {
    const DateRangeIcon = () => h('span', {
      class: 'icon material-symbols-outlined',
      style: 'font-size: 24px;'
    }, 'date_range')
    // Переиспользуем хук для autocomplete и валидации дат
    const {
      querySearch,
      handleLocationSelect,
      disabledCheckinDate,
      disabledCheckoutDate,
      filters
    } = usePropertyFilters({
      config: props.config,
      context: 'home',
      initFilters: [],
      ajaxUrl: props.ajaxUrl,
      nonce: props.nonce
    });

    const locationInput = ref('');
    const locationSlug = ref('');
    const dateRange = ref([]);
    const checkinDate = ref('');
    const checkoutDate = ref('');
    const guestValues = ref({}); // Динамические значения гостей: { adults: 1, children: 0, ... }
    const guestsPopoverVisible = ref(false);

    const disabledLocation = computed(() => {
      return Boolean(props.location);
    })

    // Получаем конфигурацию гостей из props
    const guestsConfig = computed(() => {
      return Array.isArray(props.config.guests) ? props.config.guests : [];
    });

    // Проверяем, есть ли фильтры гостей
    const hasGuestFilters = computed(() => {
      return guestsConfig.value.length > 0;
    });

    // Локальная обертка для handleLocationSelect
    function localHandleLocationSelect(item) {
      locationInput.value = item.name;
      locationSlug.value = item.slug;
      handleLocationSelect(item);
    }

    // Даты - используем функции из хука
    function handleDateChange(val) {
      if (val && val.length === 2) {
        checkinDate.value = val[0];
        checkoutDate.value = val[1];
      } else {
        checkinDate.value = '';
        checkoutDate.value = '';
      }
    }



    const guestsDisplayText = ref('Гости');

    function updateGuestsDisplay() {
      const guests = guestsConfig.value;
      if (guests.length === 0) {
        guestsDisplayText.value = 'Гости';
        return;
      }

      // Собираем все значения гостей
      const parts = [];
      guests.forEach(guest => {
        const value = guestValues.value[guest.name] || 0;
        if (value > 0 && guest.name === 'adults') {
          // Специальная обработка для взрослых (сохраняем склонения)
          const adultObj = declension(['взрослых', 'взрослый', 'взрослых'], value);
          parts.push(`${value} ${adultObj.text}`);
        } else if (value > 0 && guest.name === 'children') {
          // Специальная обработка для детей (сохраняем склонения)
          const childrenObj = declension(['детей', 'ребенок', 'ребенка'], value);
          parts.push(`${value} ${childrenObj.text}`);
        } else if (value > 0) {
          // Для других типов гостей просто показываем label + значение
          parts.push(`${guest.label}: ${value}`);
        }
      });

      guestsDisplayText.value = parts.length > 0 ? parts.join(', ') : 'Гости';
    }

    const guestsPopover = () => {}

    const initFormData = (newVal) => {
      if (!newVal) return;
      
      console.log('[HomePropertyFilter] initFormData called with:', newVal);
      console.log('[HomePropertyFilter] guestsConfig:', guestsConfig.value);
      
      if (props.location) {
        locationInput.value = props.location.name;
        locationSlug.value = props.location.slug;
      }else {
        locationInput.value = newVal.locationInput;
        locationSlug.value = newVal.locationSlug;
      }
      checkinDate.value = newVal.checkin_date;
      checkoutDate.value = newVal.checkout_date;

      // Инициализируем динамические значения гостей
      const guests = guestsConfig.value;
      guests.forEach(guest => {
        const valueFromUrl = newVal[guest.name];
        const defaultValue = guest.min;
        guestValues.value[guest.name] = valueFromUrl ?? defaultValue;
        console.log(`[HomePropertyFilter] Guest ${guest.name}: URL=${valueFromUrl}, default=${defaultValue}, final=${guestValues.value[guest.name]}`);
      });

      // Синхронизируем dateRange для ElDatePicker
      if (newVal.checkin_date && newVal.checkout_date) {
        dateRange.value = [newVal.checkin_date, newVal.checkout_date];
      } else {
        dateRange.value = [];
      }
      
      console.log('[HomePropertyFilter] Final guestValues:', guestValues.value);
    }

    // Автоматически обновляем текст при изменении гостей
    watch(guestValues, () => {
      updateGuestsDisplay();
    }, { deep: true });

    watch(filters, (newVal) => {
      initFormData(newVal);
    }, { immediate: true });

    // Отправка формы
    function handleSubmit() {
      const params = new URLSearchParams();

      if (locationInput.value) {
        params.append('location', locationInput.value);
      }

      if (checkinDate.value) {
        params.append('checkin_date', checkinDate.value);
      }

      if (checkoutDate.value) {
        params.append('checkout_date', checkoutDate.value);
      }

      // Динамическая отправка всех типов гостей
      Object.entries(guestValues.value).forEach(([name, val]) => {
        if (val > 0) {
          params.append(name, val);
        }
      });

      const archiveUrl = '/property/' + (params.toString() ? '?' + params.toString() : '');
      window.location.href = archiveUrl;
    }



    onMounted(() => {
      updateGuestsDisplay();
    });

    return {
      locationInput,
      locationSlug,
      dateRange,
      checkinDate,
      checkoutDate,
      guestValues,
      guestsConfig,
      hasGuestFilters,
      guestsPopoverVisible,
      guestsDisplayText,
      disabledLocation,
      querySearch,
      localHandleLocationSelect,
      disabledCheckinDate,
      handleDateChange,
      updateGuestsDisplay,
      handleSubmit,
      DateRangeIcon,
      guestsPopover
    };
  }
};
