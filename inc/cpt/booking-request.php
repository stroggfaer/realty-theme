<?php
/**
 * Регистрация CPT booking_request для системы бронирований
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
 * Регистрация CPT booking_request
 * 
 * Сущность хранит заявку на бронирование с собственным чат-потоком.
 * Позволяет создавать множественные заявки на один объект недвижимости.
 */
function my_cabinet_register_booking_request_cpt() {
    $labels = array(
        'name'                  => 'Заявки на бронирование',
        'singular_name'         => 'Заявка на бронирование',
        'menu_name'             => 'Заявки',
        'name_admin_bar'        => 'Заявка',
        'archives'              => 'Архив заявок',
        'all_items'             => 'Все заявки',
        'add_new_item'          => 'Добавить новую заявку',
        'add_new'               => 'Добавить новую',
        'new_item'              => 'Новая заявка',
        'edit_item'             => 'Редактировать заявку',
        'update_item'           => 'Обновить заявку',
        'view_item'             => 'Просмотреть заявку',
        'search_items'          => 'Искать заявки',
        'not_found'             => 'Не найдено',
        'not_found_in_trash'    => 'Не найдено в корзине',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => false,
        'show_ui'               => false, // Убираем отдельный список — управление через booking-messages
        'show_in_menu'          => false,  // Не показываем в меню
        'show_in_admin_bar'     => false,
        'menu_position'         => 6,
        'menu_icon'             => 'dashicons-calendar-alt',
        'capability_type'       => 'post',
        'supports'              => array( 'title', 'author', 'custom-fields' ),
        'has_archive'           => false,
        'rewrite'               => false,
        'query_var'             => false,
        'can_export'            => true,
        'delete_with_user'      => false,
    );

    register_post_type( 'booking_request', $args );
}
add_action( 'init', 'my_cabinet_register_booking_request_cpt' );

/**
 * Регистрация мета-полей для CPT booking_request
 * 
 * Все поля типизированы и безопасны для REST API.
 */
function my_cabinet_register_booking_request_meta() {
    $meta_fields = array(
        // Связи
        '_property_id'      => array(
            'type'              => 'integer',
            'description'       => 'ID объекта недвижимости',
            'single'            => true,
            'sanitize_callback' => 'absint',
            'auth_callback'     => function() {
                return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
            },
        ),
        '_owner_id'         => array(
            'type'              => 'integer',
            'description'       => 'ID владельца недвижимости',
            'single'            => true,
            'sanitize_callback' => 'absint',
            'auth_callback'     => function() {
                return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
            },
        ),
        '_client_id'        => array(
            'type'              => 'integer',
            'description'       => 'ID пользователя (клиента)',
            'single'            => true,
            'sanitize_callback' => 'absint',
            'auth_callback'     => function() {
                return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
            },
        ),
        '_thread_id'        => array(
            'type'              => 'string',
            'description'       => 'ID чат-потока',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function() {
                return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
            },
        ),
        
        // Параметры бронирования
        '_checkin_date'     => array(
            'type'              => 'string',
            'description'       => 'Дата заезда (YYYY-MM-DD)',
            'single'            => true,
            'sanitize_callback' => function( $value ) {
                return sanitize_text_field( $value );
            },
            'auth_callback'     => function() {
                return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
            },
        ),
        '_guests_count'     => array(
            'type'              => 'string',
            'description'       => 'JSON с количеством гостей',
            'single'            => true,
            'sanitize_callback' => function( $value ) {
                // Валидация JSON
                $decoded = json_decode( $value, true );
                if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                    return wp_json_encode( $decoded );
                }
                return '';
            },
            'auth_callback'     => function() {
                return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
            },
        ),
        '_status'           => array(
            'type'              => 'string',
            'description'       => 'Статус заявки (pending/new/in_progress/confirmed/completed/cancelled)',
            'single'            => true,
            'sanitize_callback' => function( $value ) {
                $valid_statuses = array( 'pending', 'new', 'in_progress', 'confirmed', 'completed', 'cancelled' );
                return in_array( $value, $valid_statuses, true ) ? $value : 'new';
            },
            'auth_callback'     => function() {
                return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
            },
        ),
        '_created_date'     => array(
            'type'              => 'string',
            'description'       => 'Дата создания заявки',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function() {
                return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
            },
        ),
    );

    // Регистрируем каждое мета-поле
    foreach ( $meta_fields as $meta_key => $meta_args ) {
        register_post_meta( 'booking_request', $meta_key, array(
            'type'              => $meta_args['type'],
            'description'       => $meta_args['description'],
            'single'            => $meta_args['single'],
            'sanitize_callback' => $meta_args['sanitize_callback'],
            'auth_callback'     => $meta_args['auth_callback'],
            'show_in_rest'      => true, // Для REST API поддержки
        ) );
    }
}
add_action( 'init', 'my_cabinet_register_booking_request_meta' );

/**
 * Регистрация мета-поля _booking_request_id для CPT message
 * 
 * Это поле связывает сообщение с заявкой на бронирование.
 */
function my_cabinet_register_message_booking_request_meta() {
    register_post_meta( 'message', '_booking_request_id', array(
        'type'              => 'integer',
        'description'       => 'ID заявки на бронирование',
        'single'            => true,
        'sanitize_callback' => 'absint',
        'auth_callback'     => function() {
            return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
        },
        'show_in_rest'      => true,
    ) );
}
add_action( 'init', 'my_cabinet_register_message_booking_request_meta' );
