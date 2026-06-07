<?php
/**
 * Админка: Раздел "Отзывы"
 *
 * @package RealtyTheme
 * @subpackage Admin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Подключаем переиспользуемые шаблоны
require_once __DIR__ . '/template-parts/property-card.php';
require_once __DIR__ . '/template-parts/booking-dates.php';

/**
 * Регистрация страницы "Отзывы" в меню "Недвижимость"
 */
function realty_reviews_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=property',
        'Отзывы',
        'Отзывы',
        'manage_options',
        'reviews',
        'realty_reviews_admin_page'
    );
}
add_action( 'admin_menu', 'realty_reviews_admin_menu' );

/**
 * Рендер страницы "Отзывы"
 */
function realty_reviews_admin_page() {
    $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'list';
    $review_id = isset( $_GET['review_id'] ) ? absint( $_GET['review_id'] ) : 0;

    if ( $view === 'detail' && $review_id > 0 ) {
        realty_render_review_detail( $review_id );
    } else {
        realty_render_reviews_list();
    }
}

/**
 * Рендер таблицы отзывов
 */
function realty_render_reviews_list() {
    $page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
    $per_page = 20;
    $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';

    $args = array(
        'post_type'      => 'review',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $query = new WP_Query( $args );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Отзывы</h1>
        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" style="width: 60px;">ID</th>
                    <th scope="col">Клиент</th>
                    <th scope="col">Недвижимость</th>
                    <th scope="col" style="width: 100px;">Рейтинг</th>
                    <th scope="col" style="width: 120px;">Статус</th>
                    <th scope="col" style="width: 120px;">Дата</th>
                    <th scope="col" style="width: 120px;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post();
                    $review_id = get_the_ID();
                    $property_id = absint( get_post_meta( $review_id, '_property_id', true ) );
                    $client_id = absint( get_post_meta( $review_id, '_booking_id', true ) ? get_post_meta( $review_id, '_booking_id', true ) : get_post_field( 'post_author', $review_id ) );
                    $overall = get_post_meta( $review_id, '_rating_overall', true ) ?: '—';
                    $client_info = get_userdata( get_post_field( 'post_author', $review_id ) );
                    $client_name = $client_info ? $client_info->display_name : '—';
                    $property_title = $property_id ? get_the_title( $property_id ) : '—';
                ?>
                <tr>
                    <td><?php echo esc_html( $review_id ); ?></td>
                    <td><?php echo esc_html( $client_name ); ?></td>
                    <td>
                        <?php if ( $property_id ) : ?>
                            <a href="<?php echo esc_url( get_edit_post_link( $property_id ) ); ?>" target="_blank">
                                <?php echo esc_html( $property_title ); ?>
                            </a>
                        <?php else : ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo esc_html( $overall ); ?></strong></td>
                    <td><span class="status-badge status-publish">Опубликован</span></td>
                    <td><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=property&page=reviews&view=detail&review_id=' . $review_id ) ); ?>" class="button button-small">
                            Просмотреть
                        </a>
                    </td>
                </tr>
                <?php endwhile; else : ?>
                <tr>
                    <td colspan="7"><?php esc_html_e( 'Отзывов пока нет.', 'realty-theme' ); ?></td>
                </tr>
                <?php endif; wp_reset_postdata(); ?>
            </tbody>
        </table>

        <?php if ( $query->max_num_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $query->max_num_pages,
                    'current'   => $page,
                ) );
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Подключение CSS для страницы отзывов
 */
function realty_enqueue_reviews_admin_assets( $hook ) {
    $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
    if ( 'reviews' !== $page ) {
        return;
    }

    wp_enqueue_style(
        'realty-reviews-admin',
        get_template_directory_uri() . '/inc/admin/assets/css/messages.css',
        array(),
        '1.0.0'
    );
}
add_action( 'admin_enqueue_scripts', 'realty_enqueue_reviews_admin_assets' );

/**
 * Рендер детального просмотра отзыва
 */
function realty_render_review_detail( $review_id ) {
    $review = get_post( $review_id );
    if ( ! $review || $review->post_type !== 'review' ) {
        echo '<div class="wrap"><h1>Отзыв не найден</h1><p><a href="' . admin_url( 'edit.php?post_type=property&page=reviews' ) . '" class="button">← Назад к списку</a></p></div>';
        return;
    }

    $property_id = absint( get_post_meta( $review_id, '_property_id', true ) );
    $booking_id = get_post_meta( $review_id, '_booking_id', true );
    $host_id = get_post_meta( $review_id, '_host_id', true );
    $client_id = get_post_field( 'post_author', $review_id );
    $comment = get_the_content( $review_id );

    $client_info = get_userdata( $client_id );
    $client_name = $client_info ? $client_info->display_name : '—';
    $client_email = $client_info ? $client_info->user_email : '';

    $host_info = get_userdata( $host_id );
    $host_name = $host_info ? $host_info->display_name : '—';

    $ratings = array(
        'price_quality' => get_post_meta( $review_id, '_rating_price_quality', true ),
        'cleanliness'   => get_post_meta( $review_id, '_rating_cleanliness', true ),
        'location'      => get_post_meta( $review_id, '_rating_location', true ),
        'comfort'       => get_post_meta( $review_id, '_rating_comfort', true ),
        'food'          => get_post_meta( $review_id, '_rating_food', true ),
        'service'       => get_post_meta( $review_id, '_rating_service', true ),
    );
    $overall = get_post_meta( $review_id, '_rating_overall', true ) ?: '—';

    // Получаем критерии оценки из справочника
    $labels = realty_get_review_criteria();

    // Получаем данные бронирования если есть
    $booking_data = false;
    if ( $booking_id ) {
        $booking_data = realty_get_booking_data( $booking_id );
    }

    ?>
    <div class="wrap review-detail-page">
        <h1 class="wp-heading-inline">Просмотр отзыва #<?php echo esc_html( $review_id ); ?></h1>
        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=property&page=reviews' ) ); ?>" class="page-title-action">← Назад к списку</a>
        <hr class="wp-header-end">

        <div class="review-detail-layout">
            <!-- Левая колонка (70%): контейнер с объектом и датами -->
            <div class="review-detail-main">
                <!-- Внутренняя сетка: объект (50%) и даты (50%) -->
                <div class="review-inner-grid">
                    <!-- Блок объекта недвижимости (50%) -->
                    <?php if ( $property_id ) : ?>
                        <?php realty_render_property_card( $property_id, 'review' ); ?>
                    <?php else : ?>
                        <div class="no-property-info">
                            <span class="dashicons dashicons-warning"></span>
                            <p>Отзыв не привязан к объекту недвижимости</p>
                        </div>
                    <?php endif; ?>

                    <!-- Блок дат бронирования (50%, рядом с объектом) -->
                    <?php if ( $booking_data && ( $booking_data['checkin_date'] || $booking_data['checkout_date'] || ! empty( $booking_data['guests_count'] ) ) ) : ?>
                        <?php realty_render_booking_dates_block( $booking_data, $booking_id ); ?>
                    <?php endif; ?>
                </div>

                <!-- Блок клиента (100% ширина, после объекта и дат) -->
                <div class="client-info-card">
                    <h3 class="client-card-title">
                        <span class="dashicons dashicons-admin-users"></span>
                        Клиент
                    </h3>
                    <table class="form-table">
                        <tr><th style="width: 100px;">Имя</th><td><?php echo esc_html( $client_name ); ?></td></tr>
                        <tr><th>Email</th><td><?php echo esc_html( $client_email ); ?></td></tr>
                        <tr><th>Хост</th><td><?php echo esc_html( $host_name ); ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Правая колонка (30%) - сайдбар: оценки и отзыв -->
            <div class="review-detail-sidebar">
                <div class="review-ratings-card">
                    <h3 class="review-card-title">
                        <span class="dashicons dashicons-star-filled"></span>
                        Оценки и отзыв
                    </h3>

                    <table class="form-table">
                        <?php foreach ( $labels as $key => $label ) : 
                            $value = isset( $ratings[ $key ] ) ? $ratings[ $key ] : '—'; ?>
                        <tr>
                            <th style="width: 180px;"><?php echo esc_html( $label ); ?></th>
                            <td><strong><?php echo esc_html( $value ); ?></strong> / 10</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <th style="width: 180px; border-top: 2px solid #2271b1; padding-top: 12px;">Общий рейтинг</th>
                            <td style="border-top: 2px solid #2271b1; padding-top: 12px;">
                                <strong style="font-size: 18px; color: #2271b1;"><?php echo esc_html( $overall ); ?></strong> / 10
                            </td>
                        </tr>
                    </table>

                    <?php if ( ! empty( $comment ) ) : ?>
                    <div class="review-comment">
                        <h3>Комментарий гостя</h3>
                        <div class="review-comment-text">
                            <?php echo esc_html( $comment ); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ( $booking_id ) : ?>
        <div class="review-booking-link">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=booking-messages&view=dialog&thread_id=' . get_post_meta( $booking_id, '_thread_id', true ) ) ); ?>" class="button">
                Перейти к диалогу бронирования
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php
}