import { ref, reactive, nextTick } from 'vue';
import { ElNotification } from 'element-plus';
import { ApiFetchService } from '../../services/api-fetch';

export default {
  props: {
    appData: {
      type: Object,
      required: true
    }
  },

  template: `
    <div class="my-message-form">

      <!-- История сообщений -->
      <div
          v-if="messages.length > 0"
          class="messages-history"
          ref="messagesContainer"
      >
        <div class="messages-list">

          <div
              v-for="msg in messages"
              :key="msg.id"
              class="message-item"
              :class="[
              { 'message-read': msg.is_read },
              msg.is_from_host
                ? 'message-from-host'
                : 'message-from-guest'
            ]"
          >
            <div class="message-header">
              <span class="message-title">
                {{ msg.title }}
              </span>

              <span class="message-date">
                {{ msg.date }}
              </span>
            </div>

            <div class="message-content">
              {{ msg.content }}
            </div>

            <div class="message-status">
              <span
                  class="status-badge"
                  :class="'status-' + msg.status"
              >
                {{ getStatusText(msg.status) }}
              </span>

              <span
                  class="notification-badge"
                  :class="'notification-' + msg.notification"
              >
                {{ getNotificationText(msg.notification) }}
              </span>
            </div>
          </div>

        </div>
      </div>

      <!-- Форма -->
      <el-form
          ref="messageFormRef"
          :model="messageForm"
          :rules="messageRules"
          label-position="top"
          @submit.prevent="sendMessage"
          class="el-form__com"
      >

        <el-form-item
            label="Тема сообщения"
            prop="title"
        >
          <el-input
              v-model="messageForm.title"
              type="text"
              placeholder="Введите тему сообщения"
              size="large"
              :disabled="messageLoading"
          >
            <template #suffix>
              <span class="material-symbols-outlined">
                subject
              </span>
            </template>
          </el-input>
        </el-form-item>

        <el-form-item
            label="Текст сообщения"
            prop="content"
        >
          <el-input
              v-model="messageForm.content"
              type="textarea"
              :rows="5"
              placeholder="Введите текст сообщения"
              size="large"
              :disabled="messageLoading"
              class="message-textarea"
          >
            <template #suffix>
              <span class="material-symbols-outlined">
                message
              </span>
            </template>
          </el-input>
        </el-form-item>

        <!-- Шаблоны -->
        <div class="message-templates">
          <span class="templates-label">
            Быстрое заполнение:
          </span>

          <div class="templates-buttons">
            <el-button
                v-for="template in templates"
                :key="template.id"
                type="info"
                size="small"
                class="template-btn"
                @click="applyTemplate(template.text)"
            >
              {{ template.label }}
            </el-button>
          </div>
        </div>

        <el-button
            type="primary"
            size="large"
            class="message-submit"
            :loading="messageLoading"
            native-type="submit"
            @click.prevent="sendMessage"
        >
          Отправить
          <span class="material-symbols-outlined">
            send
          </span>
        </el-button>

      </el-form>
    </div>
  `,

  setup(props) {

    const messageFormRef = ref(null);

    // контейнер сообщений
    const messagesContainer = ref(null);

    const messages = ref([]);

    const bookingContext = ref(null);

    const messageLoading = ref(false);
    const messageError = ref('');
    const messageSuccess = ref('');

    const messageForm = reactive({
      title: 'Бронирование объекта',
      content: '',
    });

    // =====================================================
    // ФОКУС НА TEXTAREA
    // =====================================================

    const focusTextarea = async () => {
      await nextTick();
      
      if (messageFormRef.value) {
        const textarea = messageFormRef.value.$el?.querySelector('.message-textarea textarea');
        if (textarea) {
          textarea.focus();
        }
      }
    };

    // Делаем метод доступным извне
    if (typeof window !== 'undefined') {
      window.focusMessageTextarea = focusTextarea;
    }

    const templates = [
      {
        id: 1,
        label: 'Доступность',
        text: 'Интересует информация о доступности на выбранные даты.'
      },
      {
        id: 2,
        label: 'Животные',
        text: 'Можно ли с животными?'
      },
      {
        id: 3,
        label: 'Парковка',
        text: 'Есть ли парковка?'
      },
      {
        id: 4,
        label: 'Выезд',
        text: 'Как оформить выезд раньше времени?'
      },
    ];

    const messageRules = {
      title: [
        {
          required: true,
          message: 'Введите тему сообщения',
          trigger: ['submit']
        },
        {
          min: 3,
          message: 'Минимум 3 символа',
          trigger: ['blur']
        },
      ],

      content: [
        {
          required: true,
          message: 'Введите текст сообщения',
          trigger: ['submit']
        },
        {
          min: 10,
          message: 'Минимум 10 символов',
          trigger: ['blur']
        },
      ],
    };

    // =====================================================
    // SCROLL ВНИЗ КАК В МЕССЕНДЖЕРЕ
    // =====================================================

    const scrollToBottom = async () => {
      await nextTick();

      if (messagesContainer.value) {
        messagesContainer.value.scrollTop =
            messagesContainer.value.scrollHeight;
      }
    };

    // =====================================================
    // API
    // =====================================================

    const doAjaxSendMessage = async (data) => {

      const body = new URLSearchParams();

      body.append('action', 'my_cabinet_send_message');
      body.append('nonce', props.appData.messageNonce);

      Object.keys(data).forEach((key) => {
        body.append(key, data[key]);
      });

      return ApiFetchService.post(
          props.appData.ajaxUrl,
          body
      );
    };

    // =====================================================
    // ШАБЛОНЫ
    // =====================================================

    const applyTemplate = (templateText) => {

      if (messageForm.content) {
        messageForm.content += '\n' + templateText;
      } else {
        messageForm.content = templateText;
      }
    };

    // =====================================================
    // ЗАГРУЗКА СООБЩЕНИЙ
    // =====================================================

    const loadMessages = async () => {

      if (!props.appData.threadId) {
        return Promise.resolve();
      }

      try {

        await markMessagesAsRead();

        const body = new URLSearchParams();

        body.append(
            'action',
            'my_cabinet_get_messages'
        );

        body.append(
            'nonce',
            props.appData.getMessagesNonce
        );

        body.append('limit', '20');

        body.append(
            'thread_id',
            props.appData.threadId
        );

        const result = await ApiFetchService.post(
            props.appData.ajaxUrl,
            body
        );

        if (result.success) {

          // старые сверху, новые снизу
          messages.value = result.data.messages;

          // автоскролл вниз
          await scrollToBottom();
        }

      } catch (error) {

        console.error(
            'Ошибка загрузки сообщений:',
            error
        );
      }

      return Promise.resolve();
    };

    // =====================================================
    // ПРОЧИТАНО
    // =====================================================

    const markMessagesAsRead = async () => {

      if (!props.appData.threadId) {
        return;
      }

      try {

        const body = new URLSearchParams();

        body.append(
            'action',
            'my_cabinet_mark_messages_read'
        );

        body.append(
            'thread_id',
            props.appData.threadId
        );

        const result = await ApiFetchService.post(
            props.appData.ajaxUrl,
            body
        );

        if (
            result.success &&
            result.data.marked > 0
        ) {

          window.dispatchEvent(
              new CustomEvent(
                  'messages-marked-read'
              )
          );
        }

      } catch (error) {

        console.error(
            'Ошибка отметки сообщений:',
            error
        );
      }
    };

    // =====================================================
    // ТЕКСТЫ СТАТУСОВ
    // =====================================================

    const getStatusText = (status) => {

      const statusMap = {
        sent: 'Отправлено',
        delivered: 'Доставлено',
        read: 'Прочитано',
      };

      return statusMap[status] || status;
    };

    const getNotificationText = (notification) => {

      const notificationMap = {
        sent: '✓ Email отправлен',
        failed: '✗ Ошибка email',
        pending: '⏳ Ожидание',
      };

      return (
          notificationMap[notification] ||
          notification
      );
    };

    // =====================================================
    // ОТПРАВКА
    // =====================================================

    const sendMessage = () => {

      if (!messageFormRef.value) {
        return;
      }

      messageFormRef.value.validate(
          async (valid) => {

            if (!valid) {
              return;
            }

            messageLoading.value = true;

            messageError.value = '';
            messageSuccess.value = '';

            try {

              const result =
                  await doAjaxSendMessage({

                    message_title:
                    messageForm.title,

                    message_content:
                    messageForm.content,

                    property_id:
                        props.appData.propertyId || 0,

                    owner_id:
                        props.appData.ownerId || 0,

                    thread_id:
                        props.appData.threadId || '',

                    checkin_date:
                        props.appData.checkinDate || '',

                    checkout_date:
                        props.appData.checkoutDate || ''
                  });

              if (result.success) {

                messageSuccess.value =
                    result.data?.message ||
                    'Сообщение успешно отправлено!';

                messageForm.content = '';

                messageFormRef.value.resetFields();

                ElNotification({
                  title: 'Успех',
                  message: messageSuccess.value,
                  type: 'success',
                });

                // перезагрузка сообщений
                await loadMessages();

                // скролл вниз
                await scrollToBottom();

              } else {

                messageError.value =
                    result.data?.message ||
                    'Ошибка при отправке сообщения';

                ElNotification({
                  title: 'Ошибка',
                  message: messageError.value,
                  type: 'error',
                });
              }

            } catch (error) {

              messageError.value =
                  'Ошибка сети. Попробуйте ещё раз.';

              ElNotification({
                title: 'Ошибка',
                message: messageError.value,
                type: 'error',
              });

            } finally {

              messageLoading.value = false;
            }
          }
      );
    };

    // =====================================================
    // ДАТА
    // =====================================================

    function formatDateRu(dateStr) {

      if (!dateStr) return '';

      const date = new Date(dateStr);

      const months = [
        'января',
        'февраля',
        'марта',
        'апреля',
        'мая',
        'июня',
        'июля',
        'августа',
        'сентября',
        'октября',
        'ноября',
        'декабря'
      ];

      const day = date.getDate();

      const month =
          months[date.getMonth()];

      const year =
          date.getFullYear();

      return `${day} ${month}, ${year}`;
    }

    // =====================================================
    // BOOKING CONTEXT
    // =====================================================

    function initBookingContext() {

      if (
          props.appData.context ===
          'booking_inquiry'
      ) {

        const checkinDate =
            props.appData.checkinDate;

        const checkoutDate =
            props.appData.checkoutDate;

        const guestsText =
            props.appData.guestsText || '';

        bookingContext.value = {
          checkinDate,
          checkoutDate,

          checkinDateFormatted:
              formatDateRu(checkinDate),

          checkoutDateFormatted:
              formatDateRu(checkoutDate),

          guestsText
        };

        if (messages.value.length === 0) {

          messageForm.content =
              `Здравствуйте!
Интересует ваш объект недвижимости.
Даты проживания:
- Заезд: ${bookingContext.value.checkinDateFormatted}
- Выезд: ${bookingContext.value.checkoutDateFormatted}
Количество гостей:
${bookingContext.value.guestsText ? '- ' + bookingContext.value.guestsText : ''}
Прошу подтвердить доступность и сообщить детали бронирования.
Спасибо!`;
        }
      }
    }

    return {
      messageForm,
      messageLoading,
      messageError,
      messageSuccess,
      messageFormRef,
      messageRules,
      templates,
      messages,
      bookingContext,
      messagesContainer,
      applyTemplate,
      loadMessages,
      getStatusText,
      getNotificationText,
      sendMessage,
      initBookingContext,
      focusTextarea,
    };
  },

  mounted() {

    this.loadMessages().then(() => {

      this.initBookingContext();

      // при открытии тоже вниз
      this.$nextTick(() => {

        if (this.messagesContainer) {

          this.messagesContainer.scrollTop =
              this.messagesContainer.scrollHeight;
        }
      });
    });
  }
};