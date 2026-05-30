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

// ============================================================
// АРХИТЕКТУРА: URL содержит только property_id
// ============================================================
// Все данные бронирования читаются из БД (message meta).
// GET параметры (checkin_date, checkout_date, guests) НЕ используются.
// ============================================================

// Читаем property_id и thread_id из URL
$property_id = isset( $_GET['property_id'] ) ? absint( $_GET['property_id'] ) : 0;
$thread_id   = isset( $_GET['thread_id'] ) ? sanitize_text_field( wp_unslash( $_GET['thread_id'] ) ) : '';
$owner_id    = isset( $_GET['owner_id'] ) ? absint( $_GET['owner_id'] ) : 0;

// Хост/админ не видит booking context в ЛК — только в админке
if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
    $has_booking_context = false;
} else {
    // Показываем режим бронирования если есть property_id
    $has_booking_context = ( $property_id > 0 );
}

// Находим booking_request для передачи в компоненты
$booking_id     = 0;
$booking_status = 'pending';
$found_thread_id = '';

if ( $has_booking_context && is_user_logged_in() ) {
    $current_user_id = get_current_user_id();
    
    // ПРИОРИТЕТ 1: Ищем по thread_id (если передан в URL)
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
            error_log( '[dashboard] Found booking by thread_id: ' . $booking_id . ', status=' . $booking_status );
        }
    }
    
    // ПРИОРИТЕТ 2: Ищем по property_id + client_id (fallback)
    if ( ! $booking_id ) {
        // Ищем сначала pending, потом new
        $statuses_to_find = array( 'pending', 'new' );
        
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
                error_log( '[dashboard] Found booking by property_id: ' . $booking_id . ', status=' . $booking_status );
                break;
            }
        }
    }
    
    // Если booking_request не найден — редирект на чистый dashboard
    if ( ! $booking_id ) {
        error_log( '[dashboard] No booking_request found, redirecting to clean dashboard' );
        wp_safe_redirect( '/my/dashboard/' );
        exit;
    }
    
    // Используем найденный thread_id
    $thread_id = $found_thread_id;
    
    // Получаем даты бронирования для info-sidebar
    $checkin_date = get_post_meta( $booking_id, '_checkin_date', true ) ?: '';
    $checkout_date = get_post_meta( $booking_id, '_checkout_date', true ) ?: '';
    
    // Получаем гостей из booking_request
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
    <!-- Боковое меню -->
    <?php get_template_part('my/component/my-sidebar'); ?>
    <!-- Основной контент -->
    <main class="my-main-content">
        <h1 class="my-page-title">
            <?php if($has_booking_context): ?>
               <a href="/my/dashboard/"><span class="material-symbols-outlined">arrow_back</span></a>
            <?php endif; ?>
            <?php esc_html_e( 'Личный кабинет', 'realty-theme' ); ?>
        </h1>
        
        <?php
        if ( $has_booking_context ) {
            // РЕЖИМ БРОНИРОВАНИЯ: показываем карточку и форму
            // card-booking.php сам получит все данные из БД (message meta)
            ?>
            <div class="my-content-grid">
                <!-- Левая колонка: Карточка бронирования -->
                <div class="my-content-main">
                    <!-- Карточка бронирования -->
                    <?php 
                    // card-booking.php получает данные из booking_request или SESSION
                    get_template_part('my/component/card-booking', null, array(
                        'property_id'   => $property_id,
                        'owner_id'      => $owner_id,
                        'booking_id'    => $booking_id,
                        'booking_status' => $booking_status,
                        'thread_id'     => $thread_id
                    )); 
                    ?>
                    <!-- Форма сообщения -->
                    <div class="my-message-section">
                        <h3 class="my-page-title"><?php esc_html_e( 'Написать хозяину', 'realty-theme' ); ?></h3>
                        <?php 
                        get_template_part('my/component/form-message', null, array(
                            'property_id'   => $property_id,
                            'owner_id'      => $owner_id,
                            'booking_id'    => $booking_id,
                            'booking_status' => $booking_status,
                            'thread_id'     => $thread_id,
                            'context'       => 'booking_inquiry',
                            'checkin_date'  => $checkin_date ?? '',
                            'checkout_date' => $checkout_date ?? '',
                            'guests_text'   => $guests_text ?? ''
                        )); 
                        ?>
                    </div>
                </div>

                    <!-- Правая колонка: Боковая панель действий -->
                    <div class="my-content-sidebar">
                        <?php 
                        get_template_part('my/component/info-sidebar', null, array(
                            'property_id'    => $property_id,
                            'owner_id'       => $owner_id,
                            'booking_status' => $booking_status,
                            'thread_id'      => $thread_id,
                            'checkin_date'   => $checkin_date ?? '',
                            'checkout_date'  => $checkout_date ?? ''
                        )); 
                        ?>
                    </div>
                </div>
            <?php
        } else {
            // РЕЖИМ БЕЗ PROPERTY_ID: показываем таблицу диалогов по объектам
            ?>
            <div class="my-content">
                <!-- Левая колонка: Таблица диалогов -->
                <div class="my-content-main">
                    <?php get_template_part('my/component/my-table-messages'); ?>
                </div>
            </div>
            <?php
        }
        ?>
    </main>
</div>

<?php
get_footer();
