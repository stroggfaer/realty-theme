import { ref, computed, onMounted, watch, h } from 'vue';
import usePropertyFilters from '../hooks/usePropertyFilters';
import { declension } from '../utlis/helpers';

export default {
  name: 'PropertyBookingSidebar',

  props: {
    ajaxUrl: {
      type: String,
      required: true
    },
    nonce: {
      type: String,
      required: true
    },
    bookingSessionNonce: {
      type: String,
      required: true
    },
    bookingThreadNonce: {
      type: String,
      required: true
    },
    propertyId: {
      type: Number,
      required: true
    },
    ownerId: {
      type: Number,
      required: true
    },
    currentUserId: {
      type: Number,
      default: 0
    },
    config: {
      type: Object,
      default: () => ({})
    },
    nameUser: {
      type: String,
    },
    longStayInfoText: {
      type: String,
      default: 'Длительное проживание — это возможность арендовать жилье на срок от 1 месяца и более.'
    },

  },

  template: `
    <div class="property-booking-sidebar">
      <!-- Date Range Picker -->
      <div class="booking-dates">
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          start-placeholder="Заезд"
          end-placeholder="Выезд"
          format="D MMM"
          value-format="YYYY-MM-DD"
          :disabled-date="disabledCheckinDate"
          @change="handleDateChange"
          :prefix-icon="{ render: DateRangeIcon }"
          class="booking-date-picker"
          size="large"
        />
      </div>

      <!-- Guests Popover -->
      <div v-if="hasGuestFilters" class="booking-guests">
        <el-popover
          v-model:visible="guestsPopoverVisible"
          placement="bottom-start"
          :width="300"
          trigger="click"
        >
          <template #reference>
            <div class="input-with-icon guests-trigger from-input__com">
              <span class="material-symbols-outlined">group</span>
              <input type="text" readonly :value="guestsDisplayText" class="input__com guests-placeholder" :placeholder="guestsDisplayText">
            </div>
          </template>
          
          <!-- Guest controls - динамический рендер -->
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
        </el-popover>
      </div>

      
      <!--./Дата заезда и контакт-->
      <div class="host-info">
        <div class="avatar-placeholder">
          <span class="material-symbols-outlined">person</span>
        </div>
        <div class="host-details">
          <h3>Хозяин: {{nameUser}}</h3>
          <div class="hint">Отвечает в течение часа</div>
        </div>
      </div>
      
      <div class="contact-card-wrap">
        <button class="button__com contact-button booking-contact-btn" @click="handleContactHost">Написать хозяину</button>
        <button class="button__com outline info-button" @click="handleLongStayInfo">Узнать о длительном проживании</button>
      </div>

      <div class="card-footer">
        <span class="material-symbols-outlined security-icon">verified_user</span>
        <p>Личность подтверждена и безопасная переписка</p>
      </div>

      <!-- Модальное окно "Узнать о длительном проживании" -->
      <el-dialog
        v-model="longStayDialogVisible"
        title="Длительное проживание"
        width="500px"
        :close-on-click-modal="true"
      >
        <div class="long-stay-info" v-html="longStayInfoText"></div>
      </el-dialog>
    </div>
  `,

  setup(props) {
    // Иконка для date picker
    const DateRangeIcon = () => h('span', {
      class: 'icon material-symbols-outlined',
      style: 'font-size: 24px;'
    }, 'date_range');

    // Используем usePropertyFilters для валидации дат
    const {
      disabledCheckinDate,
      disabledCheckoutDate,
      getDefaultDates,
      filters
    } = usePropertyFilters({
      config: props.config,
      context: 'single-property',
      initFilters: [],
      ajaxUrl: props.ajaxUrl,
      nonce: props.nonce
    });
    
    // State
    const defaultDates = getDefaultDates();
    const dateRange = ref([defaultDates.checkin, defaultDates.checkout]);
    const checkinDate = ref(defaultDates.checkin);
    const checkoutDate = ref(defaultDates.checkout);
    const guestValues = ref({});
    const guestsPopoverVisible = ref(false);
    const guestsDisplayText = ref('Гости');
    const longStayDialogVisible = ref(false);

    // Получаем конфигурацию гостей из props
    const guestsConfig = computed(() => {
      return Array.isArray(props.config.guests) ? props.config.guests : [];
    });

    // Проверяем, есть ли фильтры гостей
    const hasGuestFilters = computed(() => {
      return guestsConfig.value.length > 0;
    });

    // Обработка изменения дат
    function handleDateChange(val) {
      if (val && val.length === 2) {
        checkinDate.value = val[0];
        checkoutDate.value = val[1];
      } else {
        checkinDate.value = '';
        checkoutDate.value = '';
      }
    }

    // Обновление текста для гостей
    function updateGuestsDisplay() {
      const guests = guestsConfig.value;
      if (guests.length === 0) {
        guestsDisplayText.value = 'Гости';
        return;
      }

      const parts = [];
      guests.forEach(guest => {
        const value = guestValues.value[guest.name] || 0;
        if (value > 0 && guest.name === 'adults') {
          const adultObj = declension(['взрослый', 'взрослых', 'взрослых'], value);
          parts.push(`${value} ${adultObj.text}`);
        } else if (value > 0 && guest.name === 'children') {
          const childrenObj = declension(['ребенок', 'ребенка', 'детей'], value);
          parts.push(`${value} ${childrenObj.text}`);
        } else if (value > 0) {
          parts.push(`${guest.label}: ${value}`);
        }
      });

      guestsDisplayText.value = parts.length > 0 ? parts.join(', ') : 'Гости';
    }

    // Инициализация значений гостей
    function initGuestValues() {
      const guests = guestsConfig.value;
      guests.forEach(guest => {
        guestValues.value[guest.name] = guest.min || 0;
      });
    }

    // Обработка кнопки "Написать хозяину"
    async function handleContactHost() {
      // Проверка: хост не может написать сам себе
      if (props.currentUserId && props.currentUserId === props.ownerId) {
        window.location.href = '/my/dashboard/';
        return;
      }

      // Валидация дат
      if (!checkinDate.value || !checkoutDate.value) {
        alert('Пожалуйста, выберите даты заезда и выезда');
        return;
      }

      // Валидация гостей (хотя бы 1 взрослый)
      const adultsCount = guestValues.value['adults'] || 0;
      if (adultsCount < 1) {
        alert('Должен быть хотя бы 1 взрослый гость');
        return;
      }

      // Формируем guests_count JSON
      const guestsCount = {};
      Object.entries(guestValues.value).forEach(([name, val]) => {
        if (val > 0) {
          guestsCount[name] = val;
        }
      });

      // Показываем загрузку
      const contactBtn = document.querySelector('.booking-contact-btn');
      if (contactBtn) {
        contactBtn.disabled = true;
        contactBtn.textContent = 'Сохранение...';
      }

      try {
        // НОВЫЙ endpoint: создаем booking_request вместо SESSION
        const body = new URLSearchParams();
        body.append('action', 'my_cabinet_create_or_get_booking_thread');
        body.append('nonce', props.bookingThreadNonce); // ← новый nonce
        body.append('property_id', props.propertyId);
        body.append('owner_id', props.ownerId);
        body.append('checkin_date', checkinDate.value);
        body.append('checkout_date', checkoutDate.value);
        body.append('guests_count', JSON.stringify(guestsCount));

        const response = await fetch(props.ajaxUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
        });

        const result = await response.json();

        if (result.success && result.data.redirect) {
          // Редирект на dashboard (теперь с thread_id!)
          window.location.href = result.data.redirect;
        } else {
          alert(result.data.message || 'Ошибка создания чат-потока');
          if (contactBtn) {
            contactBtn.disabled = false;
            contactBtn.textContent = 'Написать хозяину';
          }
        }
      } catch (error) {
        console.error('Ошибка создания чат-потока:', error);
        alert('Произошла ошибка. Попробуйте еще раз.');
        if (contactBtn) {
          contactBtn.disabled = false;
          contactBtn.textContent = 'Написать хозяину';
        }
      }
    }

    // Обработка кнопки "Узнать о длительном проживании"
    function handleLongStayInfo() {
      longStayDialogVisible.value = true;
    }

    // Watch для обновления текста гостей
    watch(guestValues, () => {
      updateGuestsDisplay();
    }, { deep: true });

    // onMounted
    onMounted(() => {
      initGuestValues();
      updateGuestsDisplay();
    });

    return {
      dateRange,
      checkinDate,
      checkoutDate,
      guestValues,
      guestsConfig,
      hasGuestFilters,
      guestsPopoverVisible,
      guestsDisplayText,
      disabledCheckinDate,
      disabledCheckoutDate,
      handleDateChange,
      updateGuestsDisplay,
      initGuestValues,
      handleContactHost,
      DateRangeIcon,
      longStayDialogVisible,
      handleLongStayInfo
    };
  }
};
