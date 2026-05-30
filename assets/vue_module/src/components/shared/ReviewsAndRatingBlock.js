import { ElDialog, ElRate } from 'element-plus';
import { computed } from 'vue';

/**
 * Компонент отзыва и рейтинга (переиспользуется в админке и ЛК)
 *
 * Props:
 * - review: Object — данные отзыва {ratings, rating_overall, comment}
 * - booking: Object — данные бронирования (опционально)
 * - property: Object — данные объекта недвижимости (опционально)
 */
export default {
    name: 'ReviewsAndRatingBlock',

    props: {
        review: {
            type: Object,
            default: () => ({})
        },
        booking: {
            type: Object,
            default: () => ({})
        },
        property: {
            type: Object,
            default: () => ({})
        }
    },

    components: {
        ElDialog,
        ElRate
    },

    setup(props) {
        const ratingCriteria = [
            { key: 'price_quality', label: 'Цена/Качество' },
            { key: 'cleanliness', label: 'Чистота' },
            { key: 'location', label: 'Расположение' },
            { key: 'comfort', label: 'Комфорт' },
            { key: 'food', label: 'Питание' },
            { key: 'service', label: 'Обслуживание' }
        ];

        const overallRating = computed(() => props.review?.rating_overall ?? 0);
        const comment = computed(() => props.review?.comment ?? '');
        const ratings = computed(() => props.review?.ratings ?? {});

        return {
            ratingCriteria,
            overallRating,
            comment,
            ratings
        };
    },

    template: `
        <div class="reviews-rating-block">
            <!-- Общий рейтинг -->
            <div class="rating-summary" v-if="overallRating > 0">
                <span class="rating-value">{{ overallRating }}/10</span>
                <el-rate 
                    :model-value="overallRating" 
                    :max="10" 
                    disabled 
                    show-score 
                    score-template="{value} баллов"
                />
            </div>

            <!-- Таблица оценок -->
            <table class="ratings-table" v-if="ratings && Object.keys(ratings).length > 0">
                <tbody>
                    <tr v-for="criterion in ratingCriteria" :key="criterion.key">
                        <td class="rating-label">{{ criterion.label }}</td>
                        <td class="rating-stars">
                            <el-rate 
                                :model-value="ratings[criterion.key]" 
                                :max="10" 
                                disabled
                            />
                        </td>
                        <td class="rating-value">{{ ratings[criterion.key] || 0 }}/10</td>
                    </tr>
                </tbody>
            </table>

            <!-- Комментарий -->
            <div class="review-comment" v-if="comment">
                <p>{{ comment }}</p>
            </div>
        </div>
    `
};