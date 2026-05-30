/**
 * Host Profile Modal Component
 * Модальное окно с информацией о хозяине недвижимости
 * 
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

const { ref, onMounted } = Vue;
import { ApiFetchService } from '../../services/api-fetch';

export default {
  name: 'HostProfileModal',
  template: `
    <el-dialog 
      v-model="dialogVisible" 
      :show-close="true"
      width="540"
      :close-on-click-modal="true"
      class="host-profile-dialog"
    >
      <div class="host-bento-card" v-if="hostData">
        <!-- Header Section: Avatar + Name -->
        <div class="bento-header">
          <div class="bento-avatar-wrapper">
            <div class="bento-avatar">
              <img v-if="hostData.avatar" :src="hostData.avatar" :alt="hostName" />
              <span v-else class="material-symbols-outlined avatar-placeholder">person</span>
            </div>
            <div class="avatar-ring"></div>
          </div>
          
          <div class="bento-identity">
            <h3 class="bento-name">{{ fullName || hostName }}</h3>
            <p class="bento-username" v-if="hostData.user_login">@{{ hostData.user_login }}</p>
          </div>
        </div>

        <!-- Bento Grid: Info Cards -->
        <div class="bento-grid">
          <!-- Email Card -->
          <div class="bento-card bento-card--email" v-if="hostData.email">
            <div class="bento-card-icon">
              <span class="material-symbols-outlined">mail</span>
            </div>
            <div class="bento-card-content">
              <span class="bento-card-label">Email</span>
              <span class="bento-card-value">{{ hostData.email }}</span>
            </div>
          </div>

          <!-- Name Card -->
          <div class="bento-card bento-card--name" v-if="hostData.first_name || hostData.last_name">
            <div class="bento-card-icon">
              <span class="material-symbols-outlined">badge</span>
            </div>
            <div class="bento-card-content">
              <span class="bento-card-label">Имя</span>
              <span class="bento-card-value">{{ fullName }}</span>
            </div>
          </div>
        </div>

        <!-- Bio Section - Alternative Card Layout -->
        <div class="bento-bio-card" v-if="hostData.description">
          <div class="bio-card-wrapper">
            <div class="bio-card-sidebar">
              <span class="material-symbols-outlined bio-card-icon">person_outline</span>
            </div>
            <div class="bio-card-content">
              <h4 class="bio-card-title">О себе</h4>
              <p class="bio-card-text">{{ hostData.description }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="host-profile-loading">
        <div class="loading-spinner">
          <span class="material-symbols-outlined loading-icon">hourglass_empty</span>
        </div>
        <p>Загрузка профиля...</p>
      </div>
    </el-dialog>
  `,

  setup() {
    const dialogVisible = ref(false);
    const hostData = ref(null);
    const hostName = ref('');

    // Полное имя (Имя + Фамилия)
    const fullName = ref('');

    // Открыть модалку
    const openModal = (ownerId, name) => {
      if (!ownerId) {
        console.error('[HostProfileModal] owner_id is required');
        return;
      }

      dialogVisible.value = true;
      hostName.value = name || 'Хозяин';
      hostData.value = null;

      // Загружаем данные хоста
      loadHostData(ownerId);
    };

    // Загрузка данных хоста с сервера
    const loadHostData = async (ownerId) => {
      try {
        const formData = new URLSearchParams();
        formData.append('action', 'my_cabinet_get_host_profile');
        formData.append('owner_id', ownerId);

        const ajaxUrl = window.MyCabinetData?.ajaxUrl || '/wp-admin/admin-ajax.php';
        const result = await ApiFetchService.post(ajaxUrl, formData);

        if (result.success && result.data) {
          hostData.value = result.data;
          
          // Формируем полное имя
          const firstName = result.data.first_name || '';
          const lastName = result.data.last_name || '';
          fullName.value = (firstName + ' ' + lastName).trim() || result.data.display_name || '';
        } else {
          console.error('[HostProfileModal] Failed to load host data:', result.data?.message);
        }
      } catch (error) {
        console.error('[HostProfileModal] Error loading host data:', error);
      }
    };

    return {
      dialogVisible,
      hostData,
      hostName,
      fullName,
      openModal,
    };
  },
};
