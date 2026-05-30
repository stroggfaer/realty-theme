import { ref, reactive } from 'vue';
import { ApiFetchService } from '../../services/api-fetch';

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
      ref="loginFormRef"
      :model="loginForm"
      :rules="loginRules"
      label-position="top"
      @submit.prevent="submitLogin"
    >
      <el-form-item label="Email адрес" prop="email">
        <el-input
          v-model="loginForm.email"
          type="email"
          placeholder="example@estatestay.com"
          size="large"
          :disabled="loginLoading"
        >
          <template #suffix>
            <span class="material-symbols-outlined">mail</span>
          </template>
        </el-input>
      </el-form-item>

      <el-form-item prop="password">
        <template #label>
          <span class="auth-form__label-row">
            <span>Пароль</span>
            <a href="#" class="auth-form__forgot" @click.prevent="showForgot = true">Забыли пароль?</a>
          </span>
        </template>
        <el-input
          v-model="loginForm.password"
          type="password"
          placeholder="••••••••"
          size="large"
          show-password
          :disabled="loginLoading"
        >
          <template #suffix>
            <span class="material-symbols-outlined">lock</span>
          </template>
        </el-input>
      </el-form-item>

      <el-button
        type="primary"
        size="large"
        class="auth-form__submit"
        :loading="loginLoading"
        native-type="submit"
        @click.prevent="submitLogin"
      >Войти в аккаунт <span class="material-symbols-outlined">output</span></el-button>

      <el-alert
        v-if="loginError"
        :title="loginError"
        type="error"
        show-icon
        :closable="false"
        class="auth-form__alert"></el-alert>
    </el-form>

    <div class="auth-form__divider"></div>

    <div class="auth-form__footer">
      <p class="auth-form__footer-hint">Нет аккаунта?</p>
      <p class="auth-form__footer-cta">Зарегистрируйтесь за 30 секунд</p>
      <a href="#" class="auth-form__footer-link" @click.prevent="$emit('switchToRegister')">Создать новый аккаунт</a>
    </div>
  `,

  emits: ['switchToRegister'],

  components: {
    MessageIcon: window.ElementPlusIconsVue?.Message,
    LockIcon: window.ElementPlusIconsVue?.Lock,
  },

  setup(props) {
    const loginForm = reactive({
      email: '',
      password: '',
    });
    const loginLoading = ref(false);
    const loginError = ref('');
    const loginFormRef = ref(null);
    const showForgot = ref(false);

    const loginRules = {
      email: [
        { required: true, message: 'Введите email', trigger: 'blur' },
        { type: 'email', message: 'Некорректный email', trigger: 'blur' },
      ],
      password: [
        { required: true, message: 'Введите пароль', trigger: 'blur' },
        { min: 6, message: 'Минимум 6 символов', trigger: 'blur' },
      ],
    };

    const doAjaxLogin = async (data) => {
      const body = new URLSearchParams();
      body.append('action', 'my_cabinet_login');
      body.append('nonce', props.appData.loginNonce);
      Object.keys(data).forEach((key) => body.append(key, data[key]));

      return ApiFetchService.post(props.appData.ajaxUrl, body);
    };

    const submitLogin = () => {
      if (!loginFormRef.value) {
        return;
      }

      loginFormRef.value.validate(async (valid) => {
        if (!valid) {
          return;
        }

        loginLoading.value = true;
        loginError.value = '';

        try {
          const result = await doAjaxLogin({
            email: loginForm.email,
            password: loginForm.password,
          });

          if (result.success) {
            window.location.href = result.data?.redirect || props.appData.redirectUrl;
            return;
          }

          loginError.value = result.data?.message || 'Ошибка входа';
        } catch (error) {
          loginError.value = 'Ошибка сети. Попробуйте ещё раз.';
        } finally {
          loginLoading.value = false;
        }
      });
    };

    return {
      loginForm,
      loginLoading,
      loginError,
      loginFormRef,
      loginRules,
      showForgot,
      submitLogin,
    };
  }
};
