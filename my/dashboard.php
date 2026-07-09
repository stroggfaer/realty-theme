<?php
/**
 * Шаблон личного кабинета
 * Модуль "Мой кабинет" для темы Realty Theme
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
get_header();

$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
$property_id = isset( $_GET['property_id'] ) ? absint( $_GET['property_id'] ) : 0;
$thread_id   = isset( $_GET['thread_id'] ) ? sanitize_text_field( wp_unslash( $_GET['thread_id'] ) ) : '';
$owner_id    = isset( $_GET['owner_id'] ) ? absint( $_GET['owner_id'] ) : 0;

if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
    $has_booking_context = false;
} else {
    $has_booking_context = ( $property_id > 0 );
}

$booking_id     = 0;
$booking_status = 'pending';
$found_thread_id = '';

if ( $has_booking_context && is_user_logged_in() ) {
    $current_user_id = get_current_user_id();
    
    if ( ! empty( $thread_id ) ) {
        $booking_query = new WP_Query( array(
            'post_type'      => 'booking_request',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array( 'key' => '_thread_id', 'value' => $thread_id ),
                array( 'key' => '_client_id', 'value' => $current_user_id ),
            ),
        ) );

        if ( $booking_query->have_posts() ) {
            $booking_id = $booking_query->posts[0]->ID;
            $booking_status = get_post_meta( $booking_id, '_status', true ) ?: 'pending';
            $found_thread_id = get_post_meta( $booking_id, '_thread_id', true );
        }
    }
    
    if ( ! $booking_id ) {
        if ( $section === 'archive-review' ) {
            $statuses_to_find = array( 'completed', 'cancelled' );
        } else {
            $statuses_to_find = array( 'pending', 'new' );
        }
        
        foreach ( $statuses_to_find as $status ) {
            $booking_query = new WP_Query( array(
                'post_type'      => 'booking_request',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    array( 'key' => '_property_id', 'value' => $property_id ),
                    array( 'key' => '_client_id', 'value' => $current_user_id ),
                    array( 'key' => '_status', 'value' => $status ),
                ),
                'orderby'        => 'date',
                'order'          => 'DESC',
            ) );

            if ( $booking_query->have_posts() ) {
                $booking_id = $booking_query->posts[0]->ID;
                $booking_status = get_post_meta( $booking_id, '_status', true );
                $found_thread_id = get_post_meta( $booking_id, '_thread_id', true );
                break;
            }
        }
    }
    
    if ( ! $booking_id ) {
        wp_safe_redirect( '/my/dashboard/' );
        exit;
    }
    
    $thread_id = $found_thread_id;
    $checkin_date = get_post_meta( $booking_id, '_checkin_date', true ) ?: '';
    $checkout_date = get_post_meta( $booking_id, '_checkout_date', true ) ?: '';
    
    $guests_text = '';
    $guests_count_raw = get_post_meta( $booking_id, '_guests_count', true );
    if ( ! empty( $guests_count_raw ) ) {
        $guests_count = json_decode( $guests_count_raw, true );
        if ( is_array( $guests_count ) ) {
            $guest_types_config = function_exists( 'realty_get_guest_types_config' ) ? realty_get_guest_types_config() : array();
            $guests_parts = array();
            foreach ( $guest_types_config as $guest_type ) {
                if ( empty( $guest_type['enabled'] ) ) continue;
                $guest_name = $guest_type['name'];
                $guest_value = $guests_count[ $guest_name ] ?? 0;
                if ( $guest_value > 0 ) {
                    $guests_parts[] = $guest_value . ' ' . $guest_type['label'];
                }
            }
            $guests_text = implode( ', ', $guests_parts );
        }
    }
}
?>

<div class="my-dashboard-layout col-full">
    <?php get_template_part('my/component/my-sidebar'); ?>
    <main class="my-main-content">
        <h1 class="my-page-title">
            <?php if($has_booking_context): ?>
               <a href="/my/dashboard/"><span class="material-symbols-outlined">arrow_back</span></a>
            <?php endif; ?>
            <?php esc_html_e( 'Личный кабинет', 'realty-theme' ); ?>
        </h1>
        
        <?php if ( $has_booking_context ) : ?>
            <div class="my-content-grid">
                <div class="my-content-main">
                    <?php get_template_part('my/component/card-booking', null, array(
                        'property_id'    => $property_id,
                        'owner_id'       => $owner_id,
                        'booking_id'     => $booking_id,
                        'booking_status' => $booking_status,
                        'thread_id'      => $thread_id
                    )); ?>
                    
                    <div class="my-message-section">
                        <h3 class="my-page-title"><?php esc_html_e( 'Написать хозяину', 'realty-theme' ); ?></h3>
                        <?php get_template_part('my/component/form-message', null, array(
                            'property_id'    => $property_id,
                            'owner_id'       => $owner_id,
                            'booking_id'     => $booking_id,
                            'booking_status' => $booking_status,
                            'thread_id'      => $thread_id,
                            'context'        => 'booking_inquiry',
                            'checkin_date'   => $checkin_date ?? '',
                            'checkout_date'  => $checkout_date ?? '',
                            'guests_text'    => $guests_text ?? ''
                        )); ?>
                    </div>
                </div>

                <div class="my-content-sidebar">
                    <?php get_template_part('my/component/info-sidebar', null, array(
                        'property_id'    => $property_id,
                        'owner_id'       => $owner_id,
                        'booking_id'     => $booking_id,
                        'booking_status' => $booking_status,
                        'thread_id'      => $thread_id,
                        'checkin_date'   => $checkin_date ?? '',
                        'checkout_date'  => $checkout_date ?? ''
                    )); ?>
                </div>
            </div>
        <?php elseif ( $section === 'archive-bookings' ) : ?>
            <div class="my-content">
                <div class="my-content-main">
                    <?php get_template_part('my/component/booking-archive'); ?>
                </div>
            </div>
        <?php else : ?>
            <div class="my-content">
                <div class="my-content-main">
                    <?php get_template_part('my/component/my-table-messages'); ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php get_footer(); ?>