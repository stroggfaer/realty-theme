<?php
/**
 * Настройки бронирования
 *
 * @package RealtyTheme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Кастомный вывод настроек бронирования
 *
 * @since 1.0.0
 */
function realty_render_booking_settings() {
    ?>
    <h2><?php esc_html_e( 'Настройки бронирования', 'realty-theme' ); ?></h2>

    <div class="realty-tab-description">
        <p><?php esc_html_e( 'Настройте тексты уведомлений для личного кабинета клиента.', 'realty-theme' ); ?></p>
    </div>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="booking_notice_new_pending">
                    <?php esc_html_e( 'Уведомление для новых заявок:', 'realty-theme' ); ?>
                </label>
            </th>
            <td>
                <textarea name="booking_notice_new_pending" id="booking_notice_new_pending" rows="3" class="large-text"><?php 
                    echo esc_textarea( get_option( 'booking_notice_new_pending', __( 'Вы можете изменить даты бронирования или отменить его до подтверждения.', 'realty-theme' ) ) ); 
                ?></textarea>
                <p class="description">
                    <?php esc_html_e( 'Текст отображается для бронирований со статусом "Новая заявка" и "Ожидание".', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="booking_notice_confirmed">
                    <?php esc_html_e( 'Уведомление для подтверждённых бронирований:', 'realty-theme' ); ?>
                </label>
            </th>
            <td>
                <textarea name="booking_notice_confirmed" id="booking_notice_confirmed" rows="3" class="large-text"><?php 
                    echo esc_textarea( get_option( 'booking_notice_confirmed', __( 'Бесплатная отмена возможна до 48 часов до заезда.', 'realty-theme' ) ) ); 
                ?></textarea>
                <p class="description">
                    <?php esc_html_e( 'Текст отображается для бронирований со статусом "Подтверждено".', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

// Рендерим настройки
realty_render_booking_settings();
