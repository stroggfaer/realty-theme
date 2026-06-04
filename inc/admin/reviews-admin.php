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

    $property_title = $property_id ? get_the_title( $property_id ) : '—';
    $property_url = $property_id ? get_permalink( $property_id ) : '';

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

    $labels = array(
        'price_quality' => 'Цена / Качество',
        'cleanliness'   => 'Чистота',
        'location'      => 'Расположение',
        'comfort'       => 'Комфорт',
        'food'          => 'Питание',
        'service'       => 'Обслуживание',
    );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Просмотр отзыва #<?php echo esc_html( $review_id ); ?></h1>
        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=property&page=reviews' ) ); ?>" class="page-title-action">← Назад к списку</a>
        <hr class="wp-header-end">

        <div class="review-detail-layout" style="display: flex; gap: 30px; margin-top: 20px;">
            <!-- Левая колонка: информация об объекте и бронировании -->
            <div class="review-detail-main" style="flex: 1;">
                <!-- Карточка объекта -->
                <div class="postbox" style="padding: 20px; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">Объект недвижимости</h2>
                    <table class="form-table">
                        <tr>
                            <th style="width: 150px;">Название</th>
                            <td>
                                <strong><?php echo esc_html( $property_title ); ?></strong>
                                <?php if ( $property_url ) : ?>
                                    <a href="<?php echo esc_url( $property_url ); ?>" target="_blank" style="margin-left: 10px;">→ Просмотр на сайте</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ( $property_id ) : ?>
                        <tr>
                            <th>В админке</th>
                            <td><a href="<?php echo esc_url( get_edit_post_link( $property_id ) ); ?>" target="_blank">Редактировать объект</a></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Информация о клиенте -->
                <div class="postbox" style="padding: 20px; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">Клиент</h2>
                    <table class="form-table">
                        <tr><th style="width: 150px;">Имя</th><td><?php echo esc_html( $client_name ); ?></td></tr>
                        <tr><th>Email</th><td><?php echo esc_html( $client_email ); ?></td></tr>
                        <tr><th>Хост</th><td><?php echo esc_html( $host_name ); ?></td></tr>
                    </table>
                </div>

                <!-- Даты бронирования -->
                <?php if ( $booking_id ) : 
                    $checkin = get_post_meta( $booking_id, '_checkin_date', true );
                    $checkout = get_post_meta( $booking_id, '_checkout_date', true );
                ?>
                <div class="postbox" style="padding: 20px; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">Даты бронирования</h2>
                    <table class="form-table">
                        <?php if ( $checkin ) : ?><tr><th style="width: 150px;">Заезд</th><td><?php echo esc_html( date_i18n( 'd F Y', strtotime( $checkin ) ) ); ?></td></tr><?php endif; ?>
                        <?php if ( $checkout ) : ?><tr><th>Выезд</th><td><?php echo esc_html( date_i18n( 'd F Y', strtotime( $checkout ) ) ); ?></td></tr><?php endif; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Правая колонка: оценки и отзыв -->
            <div class="review-detail-sidebar" style="width: 450px; min-width: 350px;">
                <div class="postbox" style="padding: 20px; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">Оценки и отзыв</h2>
                    
                    <table class="form-table">
                        <?php foreach ( $labels as $key => $label ) : 
                            $value = isset( $ratings[ $key ] ) ? $ratings[ $key ] : '—';
                        ?>
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
                    <div class="review-comment" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ccd0d4;">
                        <h3>Комментарий гостя</h3>
                        <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; line-height: 1.6;">
                            <?php echo esc_html( $comment ); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ( $booking_id ) : ?>
        <div class="review-booking-link" style="margin-top: 10px;">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=booking-messages&view=dialog&thread_id=' . get_post_meta( $booking_id, '_thread_id', true ) ) ); ?>" class="button">
                Перейти к диалогу бронирования
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php
}