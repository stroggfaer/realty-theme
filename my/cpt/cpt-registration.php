<?php
/**
 * Регистрация Custom Post Type для сообщений и заявок
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
 * Регистрация CPT message для системы сообщений
 */
function my_cabinet_register_message_cpt() {
    $labels = array(
        'name'                  => 'Сообщения',
        'singular_name'         => 'Сообщение',
        'menu_name'             => 'Сообщения',
        'name_admin_bar'        => 'Сообщение',
        'archives'              => 'Архив сообщений',
        'all_items'             => 'Все сообщения',
        'add_new_item'          => 'Добавить новое сообщение',
        'add_new'               => 'Добавить новое',
        'new_item'              => 'Новое сообщение',
        'edit_item'             => 'Редактировать сообщение',
        'update_item'           => 'Обновить сообщение',
        'view_item'             => 'Просмотреть сообщение',
        'search_items'          => 'Искать сообщения',
        'not_found'             => 'Не найдено',
        'not_found_in_trash'    => 'Не найдено в корзине',
    );

    $args = array(
        'label'                 => 'Сообщение',
        'description'           => 'Система сообщений между пользователями',
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor' ),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => false,
        'show_in_menu'          => true,
        'menu_position'         => 25,
        'menu_icon'             => 'dashicons-email',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'rewrite'               => false,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
    );

    register_post_type( 'message', $args );

    // Регистрация мета-полей
    register_meta( 'post', '_receiver_id', array(
        'object_subtype'    => 'message',
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
        'show_in_rest'      => true,
    ) );

    register_meta( 'post', '_sender_id', array(
        'object_subtype'    => 'message',
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
        'show_in_rest'      => true,
    ) );

    register_meta( 'post', '_property_id', array(
        'object_subtype'    => 'message',
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
        'show_in_rest'      => true,
    ) );

    register_meta( 'post', '_is_read', array(
        'object_subtype'    => 'message',
        'type'              => 'boolean',
        'single'            => true,
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'show_in_rest'      => true,
    ) );

    register_meta( 'post', '_status', array(
        'object_subtype'    => 'message',
        'type'              => 'string',
        'single'            => true,
        'default'           => 'sent',
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ) );

    register_meta( 'post', '_thread_id', array(
        'object_subtype'    => 'message',
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ) );

    register_meta( 'post', '_sender_role', array(
        'object_subtype'    => 'message',
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ) );

    register_meta( 'post', '_checkin_date', array(
        'object_subtype'    => 'message',
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ) );

    register_meta( 'post', '_checkout_date', array(
        'object_subtype'    => 'message',
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ) );

    register_meta( 'post', '_adults', array(
        'object_subtype'    => 'message',
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
        'show_in_rest'      => true,
    ) );

    register_meta( 'post', '_children', array(
        'object_subtype'    => 'message',
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
        'show_in_rest'      => true,
    ) );
}
add_action( 'init', 'my_cabinet_register_message_cpt' );

