<?php
/**
 * Метабокс "Длительное проживание" для объектов недвижимости
 * 
 * Позволяет задать индивидуальный текст о длительном проживании для каждого объекта
 * 
 * @package Realty_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Регистрация метабокса "Длительное проживание" для CPT property
 */
function realty_register_long_stay_metabox() {
    add_meta_box(
        'property_long_stay_metabox',
        __( 'Длительное проживание', 'realty-theme' ),
        'realty_render_long_stay_metabox',
        'property',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'realty_register_long_stay_metabox' );

/**
 * Рендер метабокса "Длительное проживание"
 * 
 * @param WP_Post $post Объект поста
 */
function realty_render_long_stay_metabox( $post ) {
    // Получаем сохраненный текст
    $long_stay_text = get_post_meta( $post->ID, 'property_long_stay_info_text', true );
    
    // Nonce для безопасности
    wp_nonce_field( 'realty_save_long_stay', 'realty_long_stay_nonce' );
    ?>
    <div class="property-long-stay-metabox">
        <p class="description" style="margin-bottom: 10px;">
            <?php esc_html_e( 'Индивидуальный текст о длительном проживании для этого объекта. Оставьте пустым для использования текста по умолчанию из настроек темы.', 'realty-theme' ); ?>
        </p>
        <textarea 
            name="property_long_stay_info_text" 
            id="property_long_stay_info_text" 
            rows="6" 
            class="large-text"
            placeholder="<?php esc_attr_e( 'Оставьте пустым для использования текста по умолчанию...', 'realty-theme' ); ?>"
        ><?php echo esc_textarea( $long_stay_text ); ?></textarea>
    </div>
    <?php
}

/**
 * Сохранение данных метабокса "Длительное проживание"
 * 
 * @param int $post_id ID поста
 */
function realty_save_long_stay_metabox( $post_id ) {
    // Проверка nonce
    if ( ! isset( $_POST['realty_long_stay_nonce'] ) || 
         ! wp_verify_nonce( $_POST['realty_long_stay_nonce'], 'realty_save_long_stay' ) ) {
        return;
    }
    
    // Проверка автосохранения
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    // Проверка прав доступа
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // Сохраняем текст о длительном проживании
    if ( isset( $_POST['property_long_stay_info_text'] ) ) {
        $long_stay_text = wp_kses_post( wp_unslash( $_POST['property_long_stay_info_text'] ) );
        update_post_meta( $post_id, 'property_long_stay_info_text', $long_stay_text );
    } else {
        delete_post_meta( $post_id, 'property_long_stay_info_text' );
    }
}
add_action( 'save_post_property', 'realty_save_long_stay_metabox' );
