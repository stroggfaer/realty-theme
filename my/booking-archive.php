<?php
/**
 * Страница архива бронирований
 * URL: /my/booking-archive/
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$property_id = isset( $_GET['property_id'] ) ? absint( $_GET['property_id'] ) : 0;
$booking_id  = 0;
$booking_status = 'pending';
$booking_title = '';

// Если передан property_id — загружаем данные бронирования
if ( $property_id > 0 && is_user_logged_in() ) {
    $current_user_id = get_current_user_id();
    $booking_query = new WP_Query( array(
        'post_type'      => 'booking_request',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array( 'key' => '_property_id', 'value' => $property_id ),
            array( 'key' => '_client_id', 'value' => $current_user_id ),
            array( 'key' => '_status', 'value' => array( 'completed', 'cancelled' ), 'compare' => 'IN' ),
        ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    if ( $booking_query->have_posts() ) {
        $booking = $booking_query->posts[0];
        $booking_id = $booking->ID;
        $booking_status = get_post_meta( $booking_id, '_status', true ) ?: 'pending';
        $property = get_post( $property_id );
        $booking_title = $property ? $property->post_title : '';
    }
}
?>

<?php get_header(); ?>

<div class="my-dashboard-layout col-full">
    <?php get_template_part('my/component/my-sidebar'); ?>
    <main class="my-main-content">
        <h1 class="my-page-title">
            <?php if ( $property_id && $booking_id ) : ?>
                <a href="<?php echo esc_url( home_url( '/my/booking-archive/' ) ); ?>">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
            <?php endif; ?>
            <?php esc_html_e( 'Архив бронирований', 'realty-theme' ); ?>
        </h1>

        <?php if ( $property_id && $booking_id ) : ?>
            <!-- Детальный просмотр бронирования -->
            <div class="my-content-grid">
                <div class="my-content-main">
                    <?php get_template_part('my/component/card-booking', null, array(
                        'property_id'    => $property_id,
                        'booking_id'     => $booking_id,
                        'booking_status' => $booking_status,
                    )); ?>
                </div>
                <div class="my-content-sidebar">
                    <?php get_template_part('my/component/info-sidebar', null, array(
                        'property_id'    => $property_id,
                        'booking_id'     => $booking_id,
                        'booking_status' => $booking_status,
                    )); ?>
                </div>
            </div>
        <?php else : ?>
            <!-- Таблица архива -->
            <div class="my-content">
                <div class="my-content-main">
                    <?php get_template_part('my/component/booking-archive'); ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php get_footer(); ?>