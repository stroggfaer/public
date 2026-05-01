import { ref, reactive } from 'vue';

export default {
  props: {
    appData: {
      type: Object,
      required: true
    }
  },

  // language=Vue
  template: `
    <el-form
      ref="registerFormRef"
      :model="registerForm"
      :rules="registerRules"
      label-position="top"
      @submit.prevent="submitRegister"
    >
      <el-form-item label="Email адрес" prop="email">
        <el-input
          v-model="registerForm.email"
          type="email"
          placeholder="example@estatestay.com"
          size="large"
          :disabled="registerLoading"
        >
          <template #suffix>
            <el-icon><MessageIcon /></el-icon>
          </template>
        </el-input>
      </el-form-item>

      <el-form-item label="Пароль" prop="password">
        <el-input
          v-model="registerForm.password"
          type="password"
          placeholder="Минимум 6 символов"
          size="large"
          show-password
          :disabled="registerLoading"
        >
          <template #suffix>
            <el-icon><LockIcon /></el-icon>
          </template>
        </el-input>
      </el-form-item>

      <el-form-item label="Повторите пароль" prop="passwordConfirm">
        <el-input
          v-model="registerForm.passwordConfirm"
          type="password"
          placeholder="Повторите пароль"
          size="large"
          show-password
          :disabled="registerLoading"
        >
          <template #suffix>
            <el-icon><LockIcon /></el-icon>
          </template>
        </el-input>
      </el-form-item>

      <el-alert
        v-if="registerError"
        :title="registerError"
        type="error"
        show-icon
        :closable="false"
        class="auth-form__alert"></el-alert>

      <el-button
        type="primary"
        size="large"
        class="auth-form__submit"
        :loading="registerLoading"
        native-type="submit"
        @click.prevent="submitRegister"
      >Регистрация &nbsp;→</el-button>
    </el-form>

    <div class="auth-form__divider"></div>

    <div class="auth-form__footer">
      <p class="auth-form__footer-hint">Уже есть аккаунт?</p>
      <a href="#" class="auth-form__footer-link" @click.prevent="$emit('switchToLogin')">Войти в аккаунт</a>
    </div>
  `,

  emits: ['switchToLogin'],

  components: {
    MessageIcon: window.ElementPlusIconsVue?.Message,
    LockIcon: window.ElementPlusIconsVue?.Lock,
  },

  setup(props) {
    const registerForm = reactive({
      email: '',
      password: '',
      passwordConfirm: '',
    });
    const registerLoading = ref(false);
    const registerError = ref('');
    const registerFormRef = ref(null);

    const registerRules = {
      email: [
        { required: true, message: 'Введите email', trigger: 'blur' },
        { type: 'email', message: 'Некорректный email', trigger: 'blur' },
      ],
      password: [
        { required: true, message: 'Введите пароль', trigger: 'blur' },
        { min: 6, message: 'Минимум 6 символов', trigger: 'blur' },
      ],
      passwordConfirm: [
        { required: true, message: 'Повторите пароль', trigger: 'blur' },
        {
          validator(rule, value, callback) {
            if (value !== registerForm.password) {
              callback(new Error('Пароли не совпадают'));
            } else {
              callback();
            }
          },
          trigger: 'blur',
        },
      ],
    };

    const doAjaxRegister = async (data) => {
      const body = new URLSearchParams();
      body.append('action', 'my_cabinet_register');
      body.append('nonce', props.appData.registerNonce);
      Object.keys(data).forEach((key) => body.append(key, data[key]));

      const response = await fetch(props.appData.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      });

      return response.json();
    };

    const submitRegister = () => {
      if (!registerFormRef.value) {
        return;
      }

      registerFormRef.value.validate(async (valid) => {
        if (!valid) {
          return;
        }

        registerLoading.value = true;
        registerError.value = '';

        try {
          const result = await doAjaxRegister({
            email: registerForm.email,
            password: registerForm.password,
          });

          if (result.success) {
            window.location.href = result.data?.redirect || props.appData.redirectUrl;
            return;
          }

          registerError.value = result.data?.message || 'Ошибка регистрации';
        } catch (error) {
          registerError.value = 'Ошибка сети. Попробуйте ещё раз.';
        } finally {
          registerLoading.value = false;
        }
      });
    };

    return {
      registerForm,
      registerLoading,
      registerError,
      registerFormRef,
      registerRules,
      submitRegister,
    };
  }
};
