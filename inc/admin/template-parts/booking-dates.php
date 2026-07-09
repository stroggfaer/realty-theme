<?php
/**
 * Шаблон: Блок дат бронирования в админке
 * 
 * Переиспользуется в: reviews-admin, messages-admin (режим просмотра)
 *
 * @package RealtyTheme
 * @subpackage Admin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Выводит блок дат бронирования
 *
 * @param array  $booking_data   Данные бронирования (checkin_date, checkout_date, guests_count, status)
 * @param int    $booking_id     ID заявки (для ссылки на диалог)
 */
function realty_render_booking_dates_block( $booking_data, $booking_id = 0 ) {
    if ( ! $booking_data ) {
        return;
    }

    $checkin_date = $booking_data['checkin_date'] ?? '';
    $checkout_date = $booking_data['checkout_date'] ?? '';
    $guests_count = $booking_data['guests_count'] ?? array();
    $booking_status = $booking_data['status'] ?? 'new';

    // Формируем значения гостей для вывода
    $guests_values = array();
    $guest_types_config = realty_get_guest_types_config();
    if ( ! empty( $guests_count ) && is_array( $guests_count ) ) {
        foreach ( $guest_types_config as $guest_type ) {
            if ( empty( $guest_type['enabled'] ) ) {
                continue;
            }
            $guest_name = $guest_type['name'];
            $guest_value = $guests_count[ $guest_name ] ?? 0;
            if ( $guest_value > 0 ) {
                $guests_values[ $guest_name ] = array(
                    'value' => $guest_value,
                    'label' => $guest_type['label'],
                );
            }
        }
    }

    // Получаем данные статуса
    $booking_statuses = realty_get_booking_statuses();
    $current_status = realty_get_booking_status_data( $booking_status );
    ?>
    <div class="booking-dates-card">
        <h3 class="booking-card-title">
            <span class="dashicons dashicons-calendar-alt"></span>
            Даты бронирования
        </h3>

        <div class="booking-view-mode">
            <?php if ( $checkin_date ) : ?>
                <div class="booking-date-row">
                    <span class="date-label">Заезд:</span>
                    <span class="date-value"><?php echo esc_html( date_i18n( 'd F Y', strtotime( $checkin_date ) ) ); ?></span>
                </div>
            <?php endif; ?>

            <?php if ( $checkout_date ) : ?>
                <div class="booking-date-row">
                    <span class="date-label">Выезд:</span>
                    <span class="date-value"><?php echo esc_html( date_i18n( 'd F Y', strtotime( $checkout_date ) ) ); ?></span>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $guests_values ) ) : ?>
                <div class="booking-guests-row">
                    <span class="guests-label">Гости:</span>
                    <span class="guests-value">
                        <?php
                        $guests_parts = array();
                        foreach ( $guests_values as $guest_data ) {
                            if ( $guest_data['value'] > 0 ) {
                                $guests_parts[] = $guest_data['value'] . ' ' . $guest_data['label'];
                            }
                        }
                        echo esc_html( implode( ', ', $guests_parts ) );
                        ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Статус бронирования -->
            <div class="booking-status-row">
                <span class="status-label">Статус:</span>
                <span class="booking-status-badge booking-status--<?php echo esc_attr( $current_status['class'] ); ?>">
                    <?php echo esc_html( $current_status['label'] ); ?>
                </span>
            </div>
        </div>
    </div>
<?php
}