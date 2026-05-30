<?php
/**
 * Компонент формы длительного проживания
 * Модуль "Мой кабинет" для темы Realty Theme
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Рендер формы длительного проживания
 *
 * @param array $args Аргументы формы
 */
function my_cabinet_render_form_longstay( $args = array() ) {
    $defaults = array(
        'id' => 'my-cabinet-longstay-form',
        'action' => '',
        'method' => 'post',
        'submit_text' => __( 'Забронировать', 'realty-theme' ),
        'min_stay' => 30,
        'max_stay' => 365,
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    ob_start();
    ?>

    <form id="<?php echo esc_attr( $args['id'] ); ?>" class="my-cabinet-form-longstay" action="<?php echo esc_url( $args['action'] ); ?>" method="<?php echo esc_attr( $args['method'] ); ?>">
        <div class="form-longstay-content">
            <div class="form-group">
                <label for="longstay-property" class="form-label">
                    <?php esc_html_e( 'Объект недвижимости', 'realty-theme' ); ?>
                </label>
                <select 
                    id="longstay-property" 
                    name="longstay_property" 
                    class="form-input"
                    required
                >
                    <option value=""><?php esc_html_e( 'Выберите объект', 'realty-theme' ); ?></option>
                    <?php
                    // Получаем список объектов недвижимости
                    $properties = get_posts( array(
                        'post_type' => 'property',
                        'post_status' => 'publish',
                        'numberposts' => -1,
                    ) );
                    
                    if ( $properties ) {
                        foreach ( $properties as $property ) {
                            echo '<option value="' . esc_attr( $property->ID ) . '">' . esc_html( get_the_title( $property->ID ) ) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="longstay-checkin" class="form-label">
                    <?php esc_html_e( 'Заезд', 'realty-theme' ); ?>
                </label>
                <input 
                    type="date" 
                    id="longstay-checkin" 
                    name="longstay_checkin" 
                    class="form-input" 
                    required
                    min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"
                >
            </div>

            <div class="form-group">
                <label for="longstay-checkout" class="form-label">
                    <?php esc_html_e( 'Выезд', 'realty-theme' ); ?>
                </label>
                <input 
                    type="date" 
                    id="longstay-checkout" 
                    name="longstay_checkout" 
                    class="form-input" 
                    required
                    min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"
                >
            </div>

            <div class="form-group">
                <label for="longstay-guests" class="form-label">
                    <?php esc_html_e( 'Количество гостей', 'realty-theme' ); ?>
                </label>
                <input 
                    type="number" 
                    id="longstay-guests" 
                    name="longstay_guests" 
                    class="form-input" 
                    min="1" 
                    max="10"
                    required
                    value="1"
                >
            </div>

            <div class="form-group">
                <label for="longstay-notes" class="form-label">
                    <?php esc_html_e( 'Дополнительные пожелания', 'realty-theme' ); ?>
                </label>
                <textarea 
                    id="longstay-notes" 
                    name="longstay_notes" 
                    class="form-input form-textarea" 
                    rows="3"
                    placeholder="<?php esc_attr_e( 'Укажите дополнительные пожелания', 'realty-theme' ); ?>"
                ></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo esc_html( $args['submit_text'] ); ?>
                </button>
            </div>
        </div>
    </form>

    <?php
    return ob_get_clean();
}
