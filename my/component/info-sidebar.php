<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Получаем данные из args
$property_id    = $args['property_id'] ?? 0;
$owner_id       = $args['owner_id'] ?? 0;
$booking_status = $args['booking_status'] ?? 'new';
$thread_id      = $args['thread_id'] ?? '';
$checkin_date   = $args['checkin_date'] ?? '';
$checkout_date  = $args['checkout_date'] ?? '';

// Данные объекта
$property_title = '';
$property_url = '';
$owner_name = '';
$owner_avatar = '';
$owner_rating = '4.9';
$owner_reviews = '128';
$response_time = '15 минут';

if ( $property_id > 0 ) {
    $property = get_post( $property_id );
    if ( $property ) {
        $property_title = $property->post_title;
        $property_url = get_permalink( $property_id );
        
        // Данные хоста
        $author_id = get_post_field( 'post_author', $property_id );
        if ( $author_id ) {
            $first_name = get_the_author_meta( 'first_name', (int) $author_id );
            $last_name = get_the_author_meta( 'last_name', (int) $author_id );
            $owner_name = trim( $first_name . ' ' . $last_name );
            
            if ( empty( $owner_name ) ) {
                $owner_name = get_the_author_meta( 'display_name', (int) $author_id );
            }
            
            // Аватар хоста
            $owner_avatar = get_avatar_url( $author_id, array( 'size' => 80 ) );
        }
    }
}

// Если нет property_id — показываем fallback режим
if ( $property_id === 0 ) :
    ?>
    <!-- РЕЖИМ 2: Fallback без $property_id -->
    <div class="my-actions-panel">
        <!-- Fallback режим: нет привязки к объекту -->
        <div class="my-info-notice my-info-notice--fallback">
            <span class="material-symbols-outlined my-notice-icon">info</span>
            <p class="my-notice-text">
                <?php esc_html_e( 'Для просмотра деталей перейдите к конкретному объекту из списка диалогов', 'realty-theme' ); ?>
            </p>
        </div>

        <a href="/property/" class="my-action-btn my-action-btn--primary">
            <span class="material-symbols-outlined">search</span>
            <span><?php esc_html_e( 'Перейти к поиску объектов', 'realty-theme' ); ?></span>
        </a>
    </div>
    <?php
    return;
endif;
?>

<div class="my-actions-panel">
    <!-- Информация об объекте -->
<!--    <div class="my-widget-property">-->
<!--        <h3 class="my-widget-property-title"><?php //echo esc_html( $property_title ); ?></h3>-->
<!--    </div>-->

    <!-- Информация о хосте -->
    <div class="my-host-info">
        <div class="my-host-avatar">
            <?php if ($owner_avatar ) : ?>

                <div class="avatar-placeholder">
                    <img src="<?php echo esc_url( $owner_avatar ); ?>" alt="<?php echo esc_attr( $owner_name ); ?>" />
                </div>
            <?php else : ?>
                <div class="avatar-placeholder">
                    <span class="material-symbols-outlined">person</span>
                </div>
            <?php endif; ?>
            <span class="my-host-status"></span>
        </div>
        <div class="my-host-details">
            <div class="my-host-name"><?php echo esc_html( $owner_name ); ?></div>
            <div class="my-host-response">
                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; margin-right: 2px;">schedule</span>
                Отвечает обычно за <?php echo esc_html( $response_time ); ?>
            </div>
        </div>
    </div>

    <!-- Кнопки действий -->
    <a href="#" class="my-action-btn my-action-btn--primary my-btn-write-host">
        <span class="material-symbols-outlined">chat</span>
        <span><?php esc_html_e( 'Написать хосту', 'realty-theme' ); ?></span>
    </a>

    <?php if ( $property_url && in_array( $booking_status, array( 'new', 'pending' ) ) ) : ?>
    <a href="<?php echo esc_url( $property_url ); ?>" class="my-action-btn my-action-btn--secondary">
        <span class="material-symbols-outlined">edit</span>
        <span><?php esc_html_e( 'Изменить бронирование', 'realty-theme' ); ?></span>
    </a>
    <?php endif; ?>

    <!-- Информационное уведомление -->
    <?php if ( in_array( $booking_status, array( 'new', 'pending', 'confirmed' ) ) ) : ?>
    <div class="my-info-notice">
        <span class="material-symbols-outlined my-notice-icon">info</span>
        <p class="my-notice-text">
            <?php
            if ( $booking_status === 'new' || $booking_status === 'pending' ) :
                echo esc_html( realty_get_booking_notice_new_pending() );
            else :
                echo esc_html( realty_get_booking_notice_confirmed() );
            endif;
            ?>
        </p>
    </div>
    <?php endif; ?>
</div>
