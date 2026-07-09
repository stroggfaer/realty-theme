<?php
/**
 * Шаблон: Карточка объекта недвижимости в админке
 * 
 * Переиспользуется в: reviews-admin, messages-admin
 *
 * @package RealtyTheme
 * @subpackage Admin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Выводит карточку объекта недвижимости
 *
 * @param int    $property_id ID объекта (обязательно)
 * @param string $context     Контекст использования: 'review' | 'message' | 'booking' (по умолчанию 'review')
 */
function realty_render_property_card( $property_id, $context = 'review' ) {
    if ( ! $property_id ) {
        return;
    }

    $property_post = get_post( $property_id );
    if ( ! $property_post ) {
        return;
    }

    $property_title = $property_post->post_title;
    $property_url = get_permalink( $property_id );
    $property_thumbnail = get_the_post_thumbnail( $property_id, 'medium', array( 'class' => 'property-card-image' ) );
    $property_price = get_post_meta( $property_id, 'price', true );

    $location_terms = get_the_terms( $property_id, 'location' );
    $location_name = '';
    if ( $location_terms && ! is_wp_error( $location_terms ) ) {
        $location_name = $location_terms[0]->name;
    }
    ?>
    <div class="property-card-sidebar">
        <h3 class="property-card-title">
            <span class="dashicons dashicons-admin-home"></span>
            Объект недвижимости
        </h3>

        <?php if ( $property_thumbnail ) : ?>
            <div class="property-card-image-wrapper">
                <?php echo $property_thumbnail; ?>
            </div>
        <?php endif; ?>

        <div class="property-card-content">
            <h4 class="property-name">
                <a href="<?php echo esc_url( $property_url ); ?>" target="_blank">
                    <?php echo esc_html( $property_title ); ?>
                    <span class="dashicons dashicons-external"></span>
                </a>
            </h4>

            <?php if ( $location_name ) : ?>
                <div class="property-location">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html( $location_name ); ?>
                </div>
            <?php endif; ?>

            <?php if ( $property_price ) : ?>
                <div class="property-price">
                    <span class="price-label">Цена:</span>
                    <span class="price-value"><?php echo number_format( (float) $property_price, 0, '.', ' ' ); ?> ₽</span>
                    <?php
                    $period = get_post_meta( $property_id, 'hours_limit', true );
                    if ( $period ) {
                        echo '<span class="price-period">/' . esc_html( $period ) . '</span>';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if ( $property_url ) : ?>
                <a href="<?php echo esc_url( $property_url ); ?>" target="_blank" class="button property-view-btn">
                    Открыть объект →
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php
}