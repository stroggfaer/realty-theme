<?php
/**
 * Регистрация CPT review для отзывов и рейтингов
 *
 * @package RealtyTheme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Регистрация CPT для отзывов
 */
function realty_register_review_cpt() {
    $labels = array(
        'name'                  => 'Отзывы',
        'singular_name'         => 'Отзыв',
        'menu_name'             => 'Отзывы',
        'name_admin_bar'        => 'Отзыв',
        'archives'              => 'Архив отзывов',
        'all_items'             => 'Все отзывы',
        'add_new_item'          => 'Добавить отзыв',
        'add_new'               => 'Добавить отзыв',
        'new_item'              => 'Новый отзыв',
        'edit_item'             => 'Редактировать отзыв',
        'update_item'           => 'Обновить отзыв',
        'view_item'             => 'Просмотреть отзыв',
        'search_items'          => 'Искать отзывы',
        'not_found'             => 'Не найдено',
        'not_found_in_trash'    => 'Не найдено в корзине',
    );

    $args = array(
        'labels'               => $labels,
        'public'               => false,
        'show_ui'              => false,
        'show_in_menu'         => false,
        'show_in_admin_bar'    => false,
        'supports'             => array( 'title', 'editor', 'author', 'custom-fields' ),
        'has_archive'          => false,
        'rewrite'              => false,
        'query_var'            => false,
        'can_export'           => true,
        'delete_with_user'     => false,
        'show_in_rest'         => true,
        'rest_base'            => 'reviews',
    );

    register_post_type( 'review', $args );
}
add_action( 'init', 'realty_register_review_cpt' );

/**
 * Регистрация мета-полей для CPT review
 */
function realty_register_review_meta() {
    $meta_fields = array(
        '_booking_id' => array(
            'type'              => 'integer',
            'description'       => 'ID бронирования',
            'single'            => true,
            'sanitize_callback' => 'absint',
        ),
        '_property_id' => array(
            'type'              => 'integer',
            'description'       => 'ID объекта недвижимости',
            'single'            => true,
            'sanitize_callback' => 'absint',
        ),
        '_host_id' => array(
            'type'              => 'integer',
            'description'       => 'ID хоста',
            'single'            => true,
            'sanitize_callback' => 'absint',
        ),
        '_rating_price_quality' => array(
            'type'              => 'number',
            'description'       => 'Оценка цена / качество',
            'single'            => true,
            'sanitize_callback' => 'absint',
        ),
        '_rating_cleanliness' => array(
            'type'              => 'number',
            'description'       => 'Оценка чистота',
            'single'            => true,
            'sanitize_callback' => 'absint',
        ),
        '_rating_location' => array(
            'type'              => 'number',
            'description'       => 'Оценка расположение',
            'single'            => true,
            'sanitize_callback' => 'absint',
        ),
        '_rating_comfort' => array(
            'type'              => 'number',
            'description'       => 'Оценка комфорт',
            'single'            => true,
            'sanitize_callback' => 'absint',
        ),
        '_rating_food' => array(
            'type'              => 'number',
            'description'       => 'Оценка питание',
            'single'            => true,
            'sanitize_callback' => 'absint',
        ),
        '_rating_service' => array(
            'type'              => 'number',
            'description'       => 'Оценка обслуживание',
            'single'            => true,
            'sanitize_callback' => 'absint',
        ),
        '_rating_overall' => array(
            'type'              => 'number',
            'description'       => 'Средний рейтинг отзыва',
            'single'            => true,
            'sanitize_callback' => function( $value ) {
                return round( floatval( $value ), 1 );
            },
        ),
        '_review_status' => array(
            'type'              => 'string',
            'description'       => 'Статус отзыва: draft, pending, published, rejected',
            'single'            => true,
            'sanitize_callback' => function( $value ) {
                $allowed = array( 'draft', 'pending', 'published', 'rejected' );
                return in_array( $value, $allowed, true ) ? $value : 'published';
            },
        ),
    );

    foreach ( $meta_fields as $meta_key => $meta_args ) {
        register_post_meta( 'review', $meta_key, array(
            'type'              => $meta_args['type'],
            'description'       => $meta_args['description'],
            'single'            => $meta_args['single'],
            'sanitize_callback' => $meta_args['sanitize_callback'],
            'auth_callback'     => function() {
                return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
            },
            'show_in_rest'      => true,
        ) );
    }
}
add_action( 'init', 'realty_register_review_meta' );
