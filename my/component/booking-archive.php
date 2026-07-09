<?php
/**
 * Шаблон архива бронирований
 * 
 * Показывает завершённые и отменённые бронирования
 * Использует ту же структуру таблицы что и my-table-messages.php
 * но загружает данные через AJAX my_cabinet_get_archive_threads
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<!-- Контейнер для таблицы архива (Vue 3) -->
<div id="my-archive-preloader" class="my-threads-loading">
    <span class="material-symbols-outlined spinning">progress_activity</span>
    <p><?php esc_html_e('Загрузка архива...', 'realty-theme'); ?></p>
</div>
<div id="vue-my-archive" style="display: none;">
    <!-- Loading state -->
    <div v-if="loading" class="my-threads-loading">
        <span class="material-symbols-outlined spinning">progress_activity</span>
        <p>{{ loadingText }}</p>
    </div>

    <!-- Archive table -->
    <div v-else-if="threads.length > 0" id="my-archive-container" class="my-threads-container"
         :data-nonce="nonce"
         :data-ajax-url="ajaxUrl">
        <table class="my-threads-table">
            <thead>
            <tr>
                <th class="col-preview">Превью</th>
                <th class="col-name">Название</th>
                <th class="col-city">Город</th>
                <th class="col-address">Адрес</th>
                <th class="col-dates">Даты</th>
                <th class="col-status">Статус</th>
                <th class="col-actions">Действия</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="thread in threads" :key="thread.property_id + '_' + thread.checkin_date">
                <td class="col-preview">
                    <img v-if="thread.property_image"
                         :src="thread.property_image"
                         :alt="thread.property_title"
                         class="thread-property-img">
                    <span v-else class="material-symbols-outlined no-image">image_not_supported</span>
                </td>
                <td class="col-name">
                    <a :href="thread.property_url" target="_blank" class="thread-property-link">
                        {{ thread.property_title }}
                    </a>
                </td>
                <td class="col-city">{{ thread.location || '—' }}</td>
                <td class="col-address">{{ thread.address || '—' }}</td>
                <td class="col-dates">{{ thread.dates_display || '—' }}</td>
                <td class="col-status">
                    <span :class="['status-badge', 'status-' + thread.status_key]">
                        {{ thread.status }}
                    </span>
                </td>
                <td class="col-actions">
                    <a :href="`/my/dashboard/?property_id=${thread.property_id}&thread_id=${thread.thread_id}&section=archive-review`"
                       class="button__com thread-view-btn">
                        Просмотр
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- Empty state -->
    <div v-else id="my-archive-empty" class="my-empty-placeholder">
        <?php
        get_template_part( 'template-parts/component/empty-block', null, array(
            'icon'        => 'archive',
            'title'       => __( 'Архив пуст', 'realty-theme' ),
            'description' => __( 'У вас пока нет завершённых или отменённых бронирований.', 'realty-theme' ),
            'button_text' => __( 'Перейти к поиску', 'realty-theme' ),
            'button_url'  => '/property/',
        ) );
        ?>
    </div>

    <!-- Error state -->
    <div v-if="error" class="my-error-message">
        <p>{{ error }}</p>
    </div>
</div>

<!-- Vue 3 Script -->
<script type="module">
    (function() {
        const { createAppModule } = window.VueAppModule;
        const { ref, onMounted } = Vue;

        const AppMyArchive = createAppModule({
            setup() {
                const threads = ref([]);
                const loading = ref(true);
                const error = ref(null);
                const loadingText = '<?php esc_html_e('Загрузка архива...', 'realty-theme'); ?>';
                const nonce = '<?php echo wp_create_nonce( 'my_cabinet_get_threads_nonce' ); ?>';
                const ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';

                // Загружаем архив бронирований
                const loadThreads = async () => {
                    try {
                        const body = new URLSearchParams();
                        body.append('action', 'my_cabinet_get_archive_threads');
                        body.append('nonce', nonce);

                        const response = await fetch(ajaxUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: body.toString()
                        });

                        const result = await response.json();

                        if (result.success) {
                            threads.value = result.data.threads || [];
                        } else {
                            error.value = result.data?.message || 'Ошибка загрузки данных';
                        }
                    } catch (err) {
                        console.error('Ошибка загрузки архива:', err);
                        error.value = 'Ошибка загрузки данных';
                    } finally {
                        loading.value = false;
                    }
                };

                onMounted(() => {
                    // Hide static preloader and show Vue component
                    const preloader = document.getElementById('my-archive-preloader');
                    const vueContainer = document.getElementById('vue-my-archive');
                    if (preloader) preloader.style.display = 'none';
                    if (vueContainer) vueContainer.style.display = 'block';

                    loadThreads();
                });

                return {
                    threads,
                    loading,
                    error,
                    loadingText,
                    nonce,
                    ajaxUrl
                };
            }
        });

        AppMyArchive.mount('#vue-my-archive');
    })();
</script>