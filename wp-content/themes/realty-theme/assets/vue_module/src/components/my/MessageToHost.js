import { ref, reactive, onMounted } from 'vue';
import {ElNotification} from "element-plus";

export default {
  props: {
    appData: {
      type: Object,
      required: true
    }
  },
  template: `
    <div class="my-message-form">
      <!-- Booking Context Info -->
      <div v-if="appData.context === 'booking_inquiry' && bookingContext" class="booking-context-info">
        <div class="booking-context-header">
          <span class="material-symbols-outlined">calendar_today</span>
          <h3>Детали бронирования</h3>
        </div>
        <div class="booking-context-details">
          <div class="booking-detail-row">
            <span class="detail-label">Заезд:</span>
            <span class="detail-value">{{ bookingContext.checkinDateFormatted }}</span>
          </div>
          <div class="booking-detail-row">
            <span class="detail-label">Выезд:</span>
            <span class="detail-value">{{ bookingContext.checkoutDateFormatted }}</span>
          </div>
          <div class="booking-detail-row" v-if="bookingContext.guestsText">
            <span class="detail-label">Гости:</span>
            <span class="detail-value">{{ bookingContext.guestsText }}</span>
          </div>
        </div>
      </div>

      <!-- Список отправленных сообщений -->
      <div v-if="messages.length > 0" class="messages-history">
        <h3 class="messages-history-title">История сообщений</h3>
        <div class="messages-list">
          <div 
            v-for="msg in messages" 
            :key="msg.id" 
            class="message-item"
            :class="{ 'message-read': msg.is_read }"
          >
            <div class="message-header">
              <span class="message-title">{{ msg.title }}</span>
              <span class="message-date">{{ msg.date }}</span>
            </div>
            <div class="message-content">{{ msg.content }}</div>
            <div class="message-status">
              <span class="status-badge" :class="'status-' + msg.status">
                {{ getStatusText(msg.status) }}
              </span>
              <span class="notification-badge" :class="'notification-' + msg.notification">
                {{ getNotificationText(msg.notification) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Форма нового сообщения -->
      <el-form
        ref="messageFormRef"
        :model="messageForm"
        :rules="messageRules"
        label-position="top"
        @submit.prevent="sendMessage"
        class="el-form__com"
      >
        <el-form-item label="Тема сообщения" prop="title">
          <el-input
            v-model="messageForm.title"
            type="text"
            placeholder="Введите тему сообщения"
            size="large"
            :disabled="messageLoading"
          >
            <template #suffix>
              <span class="material-symbols-outlined">subject</span>
            </template>
          </el-input>
        </el-form-item>

        <el-form-item label="Текст сообщения" prop="content">
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
              <span class="material-symbols-outlined">message</span>
            </template>
          </el-input>
        </el-form-item>

        <!-- Шаблонные фразы -->
        <div class="message-templates">
          <span class="templates-label">Быстрое заполнение:</span>
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
        >Отправить <span class="material-symbols-outlined">send</span></el-button>
      </el-form>
    </div>
  `,

  setup(props) {
    const messageForm = reactive({
      title: '',
      content: '',
    });
    const messageLoading = ref(false);
    const messageError = ref('');
    const messageSuccess = ref('');
    const messageFormRef = ref(null);
    const messages = ref([]);
    const bookingContext = ref(null);

    const templates = [
      { id: 1, label: 'Доступность', text: 'Интересует информация о доступности на выбранные даты.' },
      { id: 2, label: 'Животные', text: 'Можно ли с животными?' },
      { id: 3, label: 'Парковка', text: 'Есть ли парковка?' },
      { id: 4, label: 'Выезд', text: 'Как оформить выезд раньше времени?' },
    ];

    const messageRules = {
      title: [
        { required: true, message: 'Введите тему сообщения', trigger: 'blur' },
        { min: 3, message: 'Минимум 3 символа', trigger: 'blur' },
      ],
      content: [
        { required: true, message: 'Введите текст сообщения', trigger: 'blur' },
        { min: 10, message: 'Минимум 10 символов', trigger: 'blur' },
      ],
    };

    const doAjaxSendMessage = async (data) => {
      const body = new URLSearchParams();
      body.append('action', 'my_cabinet_send_message');
      body.append('nonce', props.appData.messageNonce);
      Object.keys(data).forEach((key) => body.append(key, data[key]));

      const response = await fetch(props.appData.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      });

      return response.json();
    };

    const applyTemplate = (templateText) => {
      if (messageForm.content) {
        messageForm.content += '\n\n' + templateText;
      } else {
        messageForm.content = templateText;
      }
    };

    const loadMessages = async () => {
      try {
        const body = new URLSearchParams();
        body.append('action', 'my_cabinet_get_messages');
        body.append('nonce', props.appData.getMessagesNonce);
        body.append('limit', '20');

        const response = await fetch(props.appData.ajaxUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
        });

        const result = await response.json();
        if (result.success) {
          messages.value = result.data.messages;
        }
      } catch (error) {
        console.error('Ошибка загрузки сообщений:', error);
      }
    };

    const getStatusText = (status) => {
      const statusMap = {
        'sent': 'Отправлено',
        'delivered': 'Доставлено',
        'read': 'Прочитано',
      };
      return statusMap[status] || status;
    };

    const getNotificationText = (notification) => {
      const notificationMap = {
        'sent': '✓ Email отправлен',
        'failed': '✗ Ошибка email',
        'pending': '⏳ Ожидание',
      };
      return notificationMap[notification] || notification;
    };

    const sendMessage = () => {
      if (!messageFormRef.value) {
        return;
      }

      messageFormRef.value.validate(async (valid) => {
        if (!valid) {
          return;
        }

        messageLoading.value = true;
        messageError.value = '';
        messageSuccess.value = '';

        try {
          const result = await doAjaxSendMessage({
            message_title: messageForm.title,
            message_content: messageForm.content,
            property_id: props.appData.propertyId || 0,
            owner_id: props.appData.ownerId || 0,
          });

          if (result.success) {
            messageSuccess.value = result.data?.message || 'Сообщение успешно отправлено!';
            messageForm.title = '';
            messageForm.content = '';
            messageFormRef.value.resetFields();
            ElNotification({
              title: 'Успех',
              message: messageSuccess.value,
              type: 'success',
            })
            // Обновляем список сообщений после отправки
            await loadMessages();
          } else {
            messageError.value = result.data?.message || 'Ошибка при отправке сообщения';
            ElNotification({
              title: 'Ошибка',
              message: messageError.value,
              type: 'error',
            })
          }
        } catch (error) {
          messageError.value = 'Ошибка сети. Попробуйте ещё раз.';
          ElNotification({
            title: 'Ошибка',
            message: messageError.value,
            type: 'error',
          })
        } finally {
          messageLoading.value = false;
        }
      });
    };

    // Форматирование даты на русском
    function formatDateRu(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr);
      const months = [
        'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'
      ];
      const day = date.getDate();
      const month = months[date.getMonth()];
      const year = date.getFullYear();
      return `${day} ${month}, ${year}`;
    }

    // Инициализация booking context
    function initBookingContext() {
      if (props.appData.context === 'booking_inquiry') {
        const checkinDate = props.appData.checkinDate;
        const checkoutDate = props.appData.checkoutDate;
        
        // Получаем параметры гостей из URL
        const params = new URLSearchParams(window.location.search);
        const adults = parseInt(params.get('adults')) || 0;
        const children = parseInt(params.get('children')) || 0;
        
        // Формируем текст гостей
        let guestsText = '';
        if (adults > 0 || children > 0) {
          const guestsParts = [];
          
          if (adults > 0) {
            // Склонение для взрослых
            const cases = [2, 0, 1, 1, 1, 2];
            const mod = adults % 100;
            const caseIdx = (mod > 4 && mod < 20) ? 2 : cases[Math.min(mod % 10, 5)];
            const adultsForms = ['взрослый', 'взрослых', 'взрослых'];
            guestsParts.push(`${adults} ${adultsForms[caseIdx]}`);
          }
          
          if (children > 0) {
            // Склонение для детей
            const cases = [2, 0, 1, 1, 1, 2];
            const mod = children % 100;
            const caseIdx = (mod > 4 && mod < 20) ? 2 : cases[Math.min(mod % 10, 5)];
            const childrenForms = ['ребенок', 'ребенка', 'детей'];
            guestsParts.push(`${children} ${childrenForms[caseIdx]}`);
          }
          
          guestsText = guestsParts.join(', ');
        }
        
        bookingContext.value = {
          checkinDate: checkinDate,
          checkoutDate: checkoutDate,
          checkinDateFormatted: formatDateRu(checkinDate),
          checkoutDateFormatted: formatDateRu(checkoutDate),
          adults: adults,
          children: children,
          guestsText: guestsText
        };
        
        // Предзаполняем тему и текст сообщения
        messageForm.title = 'Бронирование объекта';
        messageForm.content = `Здравствуйте!

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
      applyTemplate,
      loadMessages,
      getStatusText,
      getNotificationText,
      sendMessage,
      initBookingContext,
    };
  },

  mounted() {
    // Загружаем историю сообщений при монтировании компонента
    this.loadMessages();
    
    // Инициализируем booking context если есть
    this.initBookingContext();
  }
};
