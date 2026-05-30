import { ref, reactive } from 'vue';
import { ApiFetchService } from '../../services/api-fetch';

export default {
  props: {
    appData: {
      type: Object,
      required: true
    }
  },
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
            <span class="material-symbols-outlined">mail</span>
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
            <span class="material-symbols-outlined">lock</span>
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
            <span class="material-symbols-outlined">lock</span>
          </template>
        </el-input>
      </el-form-item>

      <el-form-item prop="consentAgreed">
        <el-checkbox v-model="registerForm.consentAgreed">
          Я даю согласие на <a href="#" class="auth-form__consent-link" @click.prevent>обработку персональных данных</a>
        </el-checkbox>
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
      >Регистрация   <span class="material-symbols-outlined">output</span></el-button>
    </el-form>

    <div class="auth-form__divider"></div>

    <div class="auth-form__footer">
      <p class="auth-form__footer-hint">Уже есть аккаунт?</p>
      <a href="#" class="auth-form__footer-link" @click.prevent="$emit('switchToLogin')">Войти в аккаунт</a>
    </div>
  `,

  emits: ['switchToLogin'],

  setup(props) {
    const registerForm = reactive({
      email: '',
      password: '',
      passwordConfirm: '',
      consentAgreed: false,
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
      consentAgreed: [
        {
          type: 'enum',
          enum: [true],
          message: 'Необходимо дать согласие на обработку персональных данных',
          trigger: 'change',
        },
      ]
    };

    const doAjaxRegister = async (data) => {
      const body = new URLSearchParams();
      body.append('action', 'my_cabinet_register');
      body.append('nonce', props.appData.registerNonce);
      Object.keys(data).forEach((key) => body.append(key, data[key]));

      return ApiFetchService.post(props.appData.ajaxUrl, body);
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
