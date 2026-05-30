<?php
/**
 * Настройки фильтрации недвижимости
 *
 * @package RealtyTheme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Получает активные фильтры с проверкой типа данных
 *
 * @return array Массив активных фильтров
 */
function realty_get_active_filters() {
    $filters = get_option( 'property_active_filters', array() );
    
    if ( ! is_array( $filters ) ) {
        return array();
    }
    
    return $filters;
}

/**
 * Получает настройки диапазона цен с проверкой
 *
 * @return array Массив с настройками диапазона цен
 */
function realty_get_price_range() {
    $default = array(
        'min'  => 0,
        'max'  => 1000000,
        'step' => 1000
    );
    
    $range = get_option( 'property_price_range', $default );
    
    if ( ! is_array( $range ) ) {
        return $default;
    }
    
    return wp_parse_args( $range, $default );
}

/**
 * Получает текст о длительном проживании
 *
 * @return string Текст о длительном проживании
 */
function realty_get_long_stay_info_text() {
    $default_text = 'Длительное проживание — это возможность арендовать жилье на срок от 1 месяца и более. При долгосрочной аренде вы можете рассчитывать на специальные условия и скидки от хозяина недвижимости. Свяжитесь с хозяином для уточнения деталей.';
    
    $text = get_option( 'property_long_stay_info_text', $default_text );
    
    return $text;
}
?>
<h2><?php esc_html_e( 'Настройки фильтрации недвижимости', 'realty-theme' ); ?></h2>

<div class="realty-tab-description">
    <p><?php esc_html_e( 'Настройте параметры фильтрации объектов недвижимости на сайте.', 'realty-theme' ); ?></p>
</div>

<?php wp_nonce_field( 'realty_save_filter_settings', 'realty_filter_nonce' ); ?>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( 'Активные фильтры:', 'realty-theme' ); ?></th>
        <td>
            <?php
            $active_filters = realty_get_active_filters();
            ?>
            <fieldset>
                <label>
                    <input type="checkbox" name="property_active_filters[]" value="location"
                        <?php checked( in_array( 'location', $active_filters, true ), true ); ?> />
                    <?php esc_html_e( 'Город (таксономия location)', 'realty-theme' ); ?>
                </label><br/>

                <label>
                    <input type="checkbox" name="property_active_filters[]" value="checkin_date"
                        <?php checked( in_array( 'checkin_date', $active_filters, true ), true ); ?> />
                    <?php esc_html_e( 'Дата заезда', 'realty-theme' ); ?>
                </label><br/>

                <label>
                    <input type="checkbox" name="property_active_filters[]" value="checkout_date"
                        <?php checked( in_array( 'checkout_date', $active_filters, true ), true ); ?> />
                    <?php esc_html_e( 'Дата выезда', 'realty-theme' ); ?>
                </label><br/>

                <label>
                    <input type="checkbox" name="property_active_filters[]" value="adults"
                        <?php checked( in_array( 'adults', $active_filters, true ), true ); ?> />
                    <?php esc_html_e( 'Количество взрослых', 'realty-theme' ); ?>
                </label><br/>

                <label>
                    <input type="checkbox" name="property_active_filters[]" value="children"
                        <?php checked( in_array( 'children', $active_filters, true ), true ); ?> />
                    <?php esc_html_e( 'Количество детей', 'realty-theme' ); ?>
                </label><br/>
            </fieldset>
        </td>
    </tr>

    <tr>
        <th scope="row"><?php esc_html_e( 'Диапазон цен:', 'realty-theme' ); ?></th>
        <td>
            <?php
            $price_range = realty_get_price_range();
            ?>
            <table class="realty-price-range-table">
                <tr>
                    <th><label for="price_min"><?php esc_html_e( 'Минимальная:', 'realty-theme' ); ?></label></th>
                    <td>
                        <input name="property_price_range[min]" type="number" id="price_min"
                            value="<?php echo esc_attr( $price_range['min'] ); ?>"
                            class="small-text" /> <?php esc_html_e( '₽', 'realty-theme' ); ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="price_max"><?php esc_html_e( 'Максимальная:', 'realty-theme' ); ?></label></th>
                    <td>
                        <input name="property_price_range[max]" type="number" id="price_max"
                            value="<?php echo esc_attr( $price_range['max'] ); ?>"
                            class="small-text" /> <?php esc_html_e( '₽', 'realty-theme' ); ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="price_step"><?php esc_html_e( 'Шаг:', 'realty-theme' ); ?></label></th>
                    <td>
                        <input name="property_price_range[step]" type="number" id="price_step"
                            value="<?php echo esc_attr( $price_range['step'] ); ?>"
                            class="small-text" /> <?php esc_html_e( '₽', 'realty-theme' ); ?>
                    </td>
                </tr>
            </table>
            <p class="description"><?php esc_html_e( 'Настройте минимальную, максимальную цену и шаг изменения цены в фильтре.', 'realty-theme' ); ?></p>
        </td>
    </tr>
</table>

<h3><?php esc_html_e( 'Информационные модальные окна', 'realty-theme' ); ?></h3>
<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( 'Текст о длительном проживании:', 'realty-theme' ); ?></th>
        <td>
            <?php
            $long_stay_text = realty_get_long_stay_info_text();
            ?>
            <textarea 
                name="property_long_stay_info_text" 
                id="property_long_stay_info_text" 
                rows="6" 
                class="large-text"
                placeholder="<?php esc_attr_e( 'Введите информацию о длительном проживании...', 'realty-theme' ); ?>"
            ><?php echo esc_textarea( $long_stay_text ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Этот текст будет отображаться в модальном окне при нажатии кнопки "Узнать о длительном проживании" на странице объекта.', 'realty-theme' ); ?></p>
        </td>
    </tr>
</table>
