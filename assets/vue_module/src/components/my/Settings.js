import { ref, reactive, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import { ApiFetchService } from '../../services/api-fetch';

export default {
  props: {
    appData: {
      type: Object,
      required: true
    }
  },
  template: `
    <div class="settings-container">
      <div class="settings-grid">
        <el-card class="settings-card">
          <template #header>
            <div class="settings-header">
              <div class="settings-header-icon">
                <span class="material-symbols-outlined">person</span>
              </div>
              <h2 class="settings-header-title">Профиль пользователя</h2>
            </div>
          </template>

          <el-form
            ref="profileFormRef"
            :model="profileForm"
            :rules="profileRules"
            label-position="top"
            class="settings-form"
            @submit.prevent="submitProfile"
          >
            <div class="settings-form-row">
              <el-form-item label="Фамилия" prop="last_name" class="settings-form-item">
                <el-input
                  v-model="profileForm.last_name"
                  placeholder="Введите фамилию"
                  size="large"
                  :disabled="profileLoading"
                >
                  <template #prefix>
                    <span class="material-symbols-outlined">badge</span>
                  </template>
                </el-input>
              </el-form-item>

              <el-form-item label="Имя" prop="first_name" class="settings-form-item">
                <el-input
                  v-model="profileForm.first_name"
                  placeholder="Введите имя"
                  size="large"
                  :disabled="profileLoading"
                >
                  <template #prefix>
                    <span class="material-symbols-outlined">person</span>
                  </template>
                </el-input>
              </el-form-item>
            </div>

            <el-form-item label="Email (только для чтения)" class="settings-form-item">
              <el-input
                v-model="profileForm.email"
                disabled
                size="large"
              >
                <template #prefix>
                  <span class="material-symbols-outlined">mail</span>
                </template>
              </el-input>
            </el-form-item>

            <div class="settings-form-actions">
              <el-button
                type="primary"
                size="large"
                class="settings-submit-btn"
                :loading="profileLoading"
                native-type="submit"
                @click.prevent="submitProfile"
              >
                Сохранить изменения
              </el-button>
            </div>
          </el-form>
        </el-card>

        <el-card class="settings-card settings-password-card">
          <template #header>
            <div class="settings-header">
              <div class="settings-header-icon">
                <span class="material-symbols-outlined">lock</span>
              </div>
              <h2 class="settings-header-title">Смена пароля</h2>
            </div>
          </template>

          <el-form
            ref="passwordFormRef"
            :model="passwordForm"
            :rules="passwordRules"
            label-position="top"
            class="settings-form"
            @submit.prevent="submitPassword"
          >
            <el-form-item label="Текущий пароль" prop="current_password" class="settings-form-item">
              <el-input
                v-model="passwordForm.current_password"
                type="password"
                placeholder="Введите текущий пароль"
                size="large"
                show-password
                :disabled="passwordLoading"
              >
                <template #prefix>
                  <span class="material-symbols-outlined">lock</span>
                </template>
              </el-input>
            </el-form-item>

            <el-form-item label="Новый пароль" prop="new_password" class="settings-form-item">
              <el-input
                v-model="passwordForm.new_password"
                type="password"
                placeholder="Минимум 6 символов"
                size="large"
                show-password
                :disabled="passwordLoading"
              >
                <template #prefix>
                  <span class="material-symbols-outlined">vpn_key</span>
                </template>
              </el-input>
            </el-form-item>

            <el-form-item label="Повторите новый пароль" prop="password_confirm" class="settings-form-item">
              <el-input
                v-model="passwordForm.password_confirm"
                type="password"
                placeholder="Повторите новый пароль"
                size="large"
                show-password
                :disabled="passwordLoading"
              >
                <template #prefix>
                  <span class="material-symbols-outlined">lock</span>
                </template>
              </el-input>
            </el-form-item>

            <div class="settings-form-actions">
              <el-button
                type="warning"
                size="large"
                class="settings-submit-btn"
                :loading="passwordLoading"
                native-type="submit"
                @click.prevent="submitPassword"
              >
                Изменить пароль
              </el-button>
            </div>
          </el-form>
        </el-card>
      </div>
    </div>
  `,

  setup(props) {
    // Profile form
    const profileForm = reactive({
      last_name: '',
      first_name: '',
      email: '',
    });
    const profileLoading = ref(false);
    const profileFormRef = ref(null);

    const profileRules = {
      last_name: [
        { required: true, message: 'Введите фамилию', trigger: 'blur' },
      ],
      first_name: [
        { required: true, message: 'Введите имя', trigger: 'blur' },
      ],
    };

    // Password form
    const passwordForm = reactive({
      current_password: '',
      new_password: '',
      password_confirm: '',
    });
    const passwordLoading = ref(false);
    const passwordFormRef = ref(null);

    const passwordRules = {
      current_password: [
        { required: true, message: 'Введите текущий пароль', trigger: 'blur' },
      ],
      new_password: [
        { required: true, message: 'Введите новый пароль', trigger: 'blur' },
        { min: 6, message: 'Минимум 6 символов', trigger: 'blur' },
      ],
      password_confirm: [
        { required: true, message: 'Повторите новый пароль', trigger: 'blur' },
        {
          validator(rule, value, callback) {
            if (value !== passwordForm.new_password) {
              callback(new Error('Пароли не совпадают'));
            } else {
              callback();
            }
          },
          trigger: 'blur',
        },
      ],
    };

    // Load user profile data
    const loadProfile = async () => {
      try {
        const body = new URLSearchParams();
        body.append('action', 'my_cabinet_get_profile');
        body.append('nonce', props.appData.profileNonce);

        const result = await ApiFetchService.post(props.appData.ajaxUrl, body);

        if (result.success) {
          profileForm.last_name = result.data.last_name || '';
          profileForm.first_name = result.data.first_name || '';
          profileForm.email = result.data.email || '';
        }
      } catch (error) {
        console.error('Error loading profile:', error);
      }
    };

    // Submit profile update
    const submitProfile = () => {
      if (!profileFormRef.value) {
        return;
      }

      profileFormRef.value.validate(async (valid) => {
        if (!valid) {
          return;
        }

        profileLoading.value = true;

        try {
          const body = new URLSearchParams();
          body.append('action', 'my_cabinet_update_profile');
          body.append('nonce', props.appData.updateProfileNonce);
          body.append('last_name', profileForm.last_name);
          body.append('first_name', profileForm.first_name);

          const result = await ApiFetchService.post(props.appData.ajaxUrl, body);

          if (result.success) {
            ElMessage({
              message: result.data.message || 'Профиль успешно обновлен',
              type: 'success',
            });
          } else {
            ElMessage({
              message: result.data.message || 'Ошибка обновления профиля',
              type: 'error',
            });
          }
        } catch (error) {
          ElMessage({
            message: 'Ошибка сети. Попробуйте ещё раз.',
            type: 'error',
          });
        } finally {
          profileLoading.value = false;
        }
      });
    };

    // Submit password change
    const submitPassword = () => {
      if (!passwordFormRef.value) {
        return;
      }

      passwordFormRef.value.validate(async (valid) => {
        if (!valid) {
          return;
        }

        passwordLoading.value = true;

        try {
          const body = new URLSearchParams();
          body.append('action', 'my_cabinet_change_password');
          body.append('nonce', props.appData.changePasswordNonce);
          body.append('current_password', passwordForm.current_password);
          body.append('new_password', passwordForm.new_password);

          const result = await ApiFetchService.post(props.appData.ajaxUrl, body);

          if (result.success) {
            ElMessage({
              message: result.data.message || 'Пароль успешно изменен',
              type: 'success',
            });
            // Clear password form
            passwordForm.current_password = '';
            passwordForm.new_password = '';
            passwordForm.password_confirm = '';
          } else {
            ElMessage({
              message: result.data.message || 'Ошибка изменения пароля',
              type: 'error',
            });
          }
        } catch (error) {
          ElMessage({
            message: 'Ошибка сети. Попробуйте ещё раз.',
            type: 'error',
          });
        } finally {
          passwordLoading.value = false;
        }
      });
    };

    // Load profile on mount
    onMounted(() => {
      loadProfile();
    });

    return {
      profileForm,
      profileLoading,
      profileFormRef,
      profileRules,
      passwordForm,
      passwordLoading,
      passwordFormRef,
      passwordRules,
      submitProfile,
      submitPassword,
    };
  }
};
