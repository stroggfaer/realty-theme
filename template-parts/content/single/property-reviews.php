<?php
/**
 * Шаблон отзывы - УПРОЩЕННЫЙ ВАРИАНТ
 *
 * @param object $pod Pods объект недвижимости
 */
if (!defined('ABSPATH')) {
    exit;
}

$pod = $args['pod'] ?? null;
if (!$pod) {
    return;
}

$property_id = $pod->id();
if (!$property_id) {
    return;
}

// Проверка включена ли система отзывов
if (!realty_is_reviews_enabled()) {
    return;
}

// Статистика
$stats = realty_get_property_review_stats($property_id);
$average = round(floatval($stats['average'] ?? 0), 1);
$count = intval($stats['count'] ?? 0);

// Если нет отзывов - просто показываем "Отзывов пока нет"
if ($count === 0) {
    echo '<div class="section-property property-reviews">
        <h3 class="title">Отзывы</h3>
        <p class="reviews-empty">Отзывов пока нет</p>
    </div>';
    return;
}

// Критерии оценки
$criteria = realty_get_review_criteria();

// Получаем все опубликованные отзывы для объекта
// ПРОСТОЙ ЗАПРОС - находит отзывы любым способом
$reviews = get_posts(array(
    'post_type'      => 'review',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => array(
        'relation' => 'AND',
        array(
            'key'   => '_property_id',
            'value' => $property_id,
            'compare' => '='
        ),
        array(
            'key'   => '_review_status',
            'value' => 'published',
            'compare' => '='
        ),
    ),
));

// Если не нашли - пробуем более широкий запрос
if (empty($reviews) && $count > 0) {
    // Запрос 2: только по property_id
    $reviews = get_posts(array(
        'post_type'      => 'review',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'   => '_property_id',
                'value' => $property_id,
                'compare' => '='
            ),
        ),
    ));
}

// Если все еще не нашли - пробуем самый широкий запрос
if (empty($reviews) && $count > 0) {
    // Запрос 3: все отзывы, а потом фильтруем
    $all_reviews = get_posts(array(
        'post_type'      => 'review',
        'post_status'    => 'any', // Любой статус
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    
    // Фильтруем вручную по property_id
    foreach ($all_reviews as $review) {
        $review_property_id = get_post_meta($review->ID, '_property_id', true);
        $review_status = get_post_meta($review->ID, '_review_status', true);
        
        // Принимаем отзыв если property_id совпадает И статус published ИЛИ статус не указан
        if ($review_property_id == $property_id) {
            if ($review_status == 'published' || empty($review_status)) {
                $reviews[] = $review;
            }
        }
    }
}

// Вычисляем средние оценки по каждому критерию
$criteria_averages = array();
foreach ($criteria as $key => $label) {
    $sum = 0;
    $rating_count = 0;
    foreach ($reviews as $review) {
        $val = floatval(get_post_meta($review->ID, '_rating_' . $key, true));
        if ($val > 0) {
            $sum += $val;
            $rating_count++;
        }
    }
    $criteria_averages[$key] = $rating_count > 0 ? round($sum / $rating_count, 1) : 0;
}
?>

<div class="section-property property-reviews">
    <h3 class="title">Отзывы</h3>

    <?php if ($count > 0) : ?>
        <!-- Сводка рейтинга ВСЕГДА показываем если есть статистика -->
        <div class="reviews-summary">
            <div class="reviews-summary__overall">
                <span class="material-symbols-outlined star-icon">star</span>
                <span class="reviews-summary__value"><?php echo esc_html(floatval($average) > 0 ? number_format_i18n($average, 1) : '—'); ?></span>
                <span class="reviews-summary__count"><?php echo esc_html($count); ?> <?php echo esc_html(_n('отзыв', 'отзывов', $count, 'realty-theme')); ?></span>
            </div>
            
            <?php if (!empty($reviews)) : ?>
            <div class="reviews-summary__criteria">
                <?php foreach ($criteria as $key => $label) :
                    $avg = $criteria_averages[$key] ?? 0;
                    $percent = $avg > 0 ? ($avg / 10) * 100 : 0;
                ?>
                    <div class="reviews-criterion">
                        <span class="reviews-criterion__label"><?php echo esc_html($label); ?></span>
                        <div class="reviews-criterion__bar">
                            <div class="reviews-criterion__fill" style="width: <?php echo esc_attr($percent); ?>%"></div>
                        </div>
                        <span class="reviews-criterion__value"><?php echo esc_html(floatval($avg) > 0 ? number_format_i18n($avg, 1) : '—'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Список отзывов показываем только если найденные отзывы -->
        <?php if (!empty($reviews)) : ?>
        <div class="reviews-list">
            <?php foreach ($reviews as $review) :
                $author_id = $review->post_author;
                $author_info = get_userdata($author_id);
                $author_name = $author_info ? $author_info->display_name : '—';
                $review_date = get_the_date('d.m.Y', $review->ID);
                $comment = $review->post_content;
                $overall = get_post_meta($review->ID, '_rating_overall', true);
                $overall_display = (floatval($overall) > 0) ? number_format_i18n(floatval($overall), 1) : '—';
            ?>
                <div class="review-item">
                    <div class="review-item__header">
                        <div class="review-item__author">
                            <span class="review-item__avatar"><?php echo esc_html(mb_substr($author_name, 0, 1)); ?></span>
                            <div class="review-item__meta">
                                <span class="review-item__name"><?php echo esc_html($author_name); ?></span>
                                <span class="review-item__date"><?php echo esc_html($review_date); ?></span>
                            </div>
                        </div>
                        <div class="review-item__rating">
                            <span class="material-symbols-outlined star-icon">star</span>
                            <span class="review-item__rating-value"><?php echo esc_html($overall_display); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($comment)) : ?>
                        <div class="review-item__comment">
                            <?php echo esc_html($comment); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
    <?php else : ?>
        <p class="reviews-empty">Отзывов пока нет</p>
    <?php endif; ?>
</div>
