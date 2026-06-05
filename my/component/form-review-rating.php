<?php
/**
 * Компонент формы отзыва (inline Vue, по паттерну single-property.php)
 * Поля динамически подгружаются из справочника характеристик (группа "Оценки")
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$property_id    = $args['property_id'] ?? 0;
$booking_id     = $args['booking_id'] ?? 0;
$booking_status = $args['booking_status'] ?? 'new';

// Динамические критерии из справочника
$criteria = realty_get_review_criteria();
$criteria_json = wp_json_encode( $criteria, JSON_UNESCAPED_UNICODE );
$criteria_keys = array_keys( $criteria );
$criteria_labels = array_values( $criteria );
?>

<div id="vue-review-form" data-criteria='<?php echo esc_attr( $criteria_json ); ?>'>
    <el-dialog 
        v-model="reviewParams.isVisible" 
        :destroy-on-close="true"
        :close-on-click-modal="false"
        width="560px"
    >
        <div style="text-align:center;margin-bottom:12px">
            <div style="font-size:17px;font-weight:600">Поделитесь впечатлениями</div>
            <div style="font-size:13px;color:#909399;margin-top:4px">Ваш отзыв поможет другим гостям</div>
        </div>

        <el-form
            ref="reviewFormRef"
            :model="reviewForm"
            :rules="reviewRules"
            label-position="top"
        >
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 20px;margin-bottom:12px">
                <el-form-item
                    v-for="(label, key) in criteria"
                    :key="key"
                    :label="label"
                    :prop="key"
                    style="margin-bottom:0"
                >
                    <el-rate v-model="reviewForm[key]" :max="10" size="large" show-score score-template="{value}" style="--el-rate-icon-size:22px" />
                </el-form-item>
            </div>

            <el-form-item label="Комментарий" prop="comment" style="margin-bottom:12px">
                <el-input type="textarea" v-model="reviewForm.comment" :rows="3" placeholder="Расскажите о ваших впечатлениях (минимум 10 символов)" show-word-limit :maxlength="500" />
            </el-form-item>
        </el-form>

        <template #footer>
            <el-button @click="reviewParams.isVisible = false">Позже</el-button>
            <el-button type="primary" @click="submitReview" :loading="reviewLoading">Отправить</el-button>
        </template>
    </el-dialog>
</div>

<script type="module">
    (function() {
        const { createAppModule, ElDialog, ElForm, ElFormItem, ElInput, ElRate, ElButton, ElMessage } = window.VueAppModule;
        const { ref, reactive, onMounted } = Vue;

        const reviewParams = reactive({ isVisible: false });
        const reviewLoading = ref(false);
        const reviewFormRef = ref(null);

        // Критерии из справочника
        const criteria = JSON.parse(document.getElementById('vue-review-form').dataset.criteria || '{}');
        const criteriaKeys = Object.keys(criteria);

        // Строим form динамически
        const formDefaults = {};
        const formRules = {};
        criteriaKeys.forEach(key => {
            formDefaults[key] = 0;
            formRules[key] = [{ required: true, message: 'Укажите оценку', trigger: 'blur' }];
        });
        formDefaults.comment = '';
        formRules.comment = [
            { required: true, message: 'Напишите комментарий', trigger: 'blur' },
            { min: 10, message: 'Минимум 10 символов', trigger: 'blur' },
        ];

        const reviewForm = reactive(formDefaults);
        const reviewRules = reactive(formRules);

        const reviewAjaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
        const reviewNonce = '<?php echo esc_js( wp_create_nonce( 'property_filter_nonce' ) ); ?>';
        const reviewBookingId = <?php echo absint( $booking_id ); ?>;

        const submitReview = async () => {
            if (reviewLoading.value || !reviewFormRef.value) return;
            await reviewFormRef.value.validate(async (valid) => {
                if (!valid) return;
                reviewLoading.value = true;
                const fd = new FormData();
                fd.append('action', 'submit_booking_review');
                fd.append('nonce', reviewNonce);
                fd.append('booking_id', reviewBookingId);
                // Динамические поля: rating_{key}
                criteriaKeys.forEach(key => {
                    fd.append('rating_' + key, reviewForm[key]);
                });
                fd.append('comment', reviewForm.comment);
                try {
                    const res = await fetch(reviewAjaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
                    const data = await res.json();
                    if (data.success) {
                        ElMessage({ message: 'Спасибо! Ваш отзыв отправлен.', type: 'success' });
                        reviewParams.isVisible = false;
                        const btn = document.querySelector('.js-rating-review');
                        if (btn) { btn.style.display = 'none'; }
                    } else {
                        throw new Error(data.data?.message || 'Ошибка отправки');
                    }
                } catch (e) {
                    ElMessage({ message: e.message, type: 'error' });
                } finally {
                    reviewLoading.value = false;
                }
            });
        };

        const AppReviewForm = createAppModule({
            setup() {
                return { reviewParams, reviewLoading, reviewFormRef, reviewForm, reviewRules, submitReview, criteria };
            },
        });

        AppReviewForm.component('el-dialog', ElDialog);
        AppReviewForm.component('el-form', ElForm);
        AppReviewForm.component('el-form-item', ElFormItem);
        AppReviewForm.component('el-input', ElInput);
        AppReviewForm.component('el-rate', ElRate);
        AppReviewForm.component('el-button', ElButton);
        AppReviewForm.mount('#vue-review-form');

        jQuery(document).on('click', '.js-rating-review', function(e) {
            e.preventDefault();
            reviewParams.isVisible = true;
        });
    })();
</script>