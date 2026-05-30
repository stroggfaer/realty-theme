<?php
/**
 * Настройки Google Maps API
 *
 * @package RealtyTheme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Кастомный вывод настроек Google Maps
 *
 * @since 1.0.0
 */
function realty_render_maps_settings() {
    $api_key      = get_option( 'google_maps_api_key', '' );
    $map_zoom     = get_option( 'realty_map_zoom', 12 );
    $map_height   = get_option( 'realty_map_height', 400 );
    $default_lat  = get_option( 'realty_default_lat', '55.7558' );
    $default_lng  = get_option( 'realty_default_lng', '37.6173' );
    ?>
    <h2><?php esc_html_e( 'Настройки Google Maps API', 'realty-theme' ); ?></h2>

    <div class="realty-tab-description">
        <p><?php esc_html_e( 'Настройте параметры интеграции с Google Maps для отображения карты объектов недвижимости.', 'realty-theme' ); ?></p>
        <p>
            <a href="https://console.cloud.google.com/google/maps-apis" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e( 'Получить API ключ в Google Cloud Console', 'realty-theme' ); ?>
            </a>
        </p>
    </div>

    <?php wp_nonce_field( 'realty_save_maps_settings', 'realty_maps_nonce' ); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><label for="google_maps_api_key"><?php esc_html_e( 'API ключ Google Maps:', 'realty-theme' ); ?></label></th>
            <td>
                <input type="text" name="google_maps_api_key" id="google_maps_api_key"
                    value="<?php echo esc_attr( $api_key ); ?>"
                    class="regular-text" />
                <p class="description"><?php esc_html_e( 'Введите ваш API ключ Google Maps для полноценной работы карты.', 'realty-theme' ); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="realty_map_zoom"><?php esc_html_e( 'Масштаб карты:', 'realty-theme' ); ?></label></th>
            <td>
                <input type="number" name="realty_map_zoom" id="realty_map_zoom"
                    value="<?php echo esc_attr( $map_zoom ); ?>"
                    min="1" max="20" class="small-text" />
                <p class="description"><?php esc_html_e( 'Масштаб карты от 1 до 20 (по умолчанию: 12).', 'realty-theme' ); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="realty_map_height"><?php esc_html_e( 'Высота карты:', 'realty-theme' ); ?></label></th>
            <td>
                <input type="number" name="realty_map_height" id="realty_map_height"
                    value="<?php echo esc_attr( $map_height ); ?>"
                    min="100" max="1000" class="small-text" /> px
                <p class="description"><?php esc_html_e( 'Высота карты в пикселях (по умолчанию: 400).', 'realty-theme' ); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="realty_default_lat"><?php esc_html_e( 'Широта центра карты:', 'realty-theme' ); ?></label></th>
            <td>
                <input type="text" name="realty_default_lat" id="realty_default_lat"
                    value="<?php echo esc_attr( $default_lat ); ?>"
                    class="regular-text" />
                <p class="description"><?php esc_html_e( 'Широта центра карты по умолчанию (Москва: 55.7558).', 'realty-theme' ); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="realty_default_lng"><?php esc_html_e( 'Долгота центра карты:', 'realty-theme' ); ?></label></th>
            <td>
                <input type="text" name="realty_default_lng" id="realty_default_lng"
                    value="<?php echo esc_attr( $default_lng ); ?>"
                    class="regular-text" />
                <p class="description"><?php esc_html_e( 'Долгота центра карты по умолчанию (Москва: 37.6173).', 'realty-theme' ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

// Вывод настроек при включении файла
realty_render_maps_settings();
