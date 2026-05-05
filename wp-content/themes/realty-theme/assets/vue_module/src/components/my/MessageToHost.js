import { ref, reactive } from 'vue';
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



    return {
      messageForm,
      messageLoading,
      messageError,
      messageSuccess,
      messageFormRef,
      messageRules,
      templates,
      applyTemplate,
      sendMessage,
    };
  }
};
