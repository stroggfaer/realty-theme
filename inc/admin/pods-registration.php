<?php
/**
 * Регистрация Custom Post Type и таксономий для объектов недвижимости
 * Используется система Pods для создания пользовательских полей
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Регистрация CPT property и связанных таксономий
 */
function register_property_cpt_and_taxonomies() {
    // Резервный вариант - регистрируем стандартными средствами WordPress
    // Это гарантирует, что CPT будет зарегистрирован даже если Pods не активен
    add_action( 'init', 'register_property_post_type', 0 );
    add_action( 'init', 'register_property_location_taxonomy', 0 );
    add_action( 'init', 'register_property_type_taxonomy', 0 );
    
    // Регистрация CPT характеристик и таксономии групп
    add_action( 'init', 'register_characteristic_cpt', 0 );
    add_action( 'init', 'setup_characteristic_groups_taxonomy', 0 );
    add_action( 'init', 'register_characteristic_meta_fields', 10 );
    
    // Если Pods активен, дополнительно настраиваем через Pods
    if ( class_exists( 'Pods_Init' ) ) {
        add_action( 'init', 'setup_property_pods', 12 );
    }
}

/**
 * Настройка структуры через Pods
 */
function setup_property_pods() {
    // Создаем или обновляем POD для свойств недвижимости
    $pod_exists = pods_api()->load_pod( array( 'name' => 'property' ), false );
    
    if ( empty( $pod_exists ) ) {
        // Создаем POD для объектов недвижимости
    $pod_data = array(
            'name' => 'property',
            'label' => 'Объекты недвижимости',
            'type' => 'post_type',
            'storage' => 'meta',
            'object' => '',
            'fields' => array(
                'title' => array(
                    'name' => 'post_title',
                    'label' => 'Название',
                    'type' => 'text',
                    'required' => true,
                ),
                'description' => array(
                    'name' => 'post_content',
                    'label' => 'Описание',
                    'type' => 'wysiwyg',
                    'required' => false,
                ),
                'price' => array(
                    'name' => 'price',
                    'label' => 'Цена',
                    'type' => 'number',
                    'required' => false,
                ),
                'address' => array(
                    'name' => 'address',
                    'label' => 'Адрес',
                    'type' => 'text',
                    'required' => false,
                ),
                'area' => array(
                    'name' => 'area',
                    'label' => 'Площадь',
                    'type' => 'number',
                    'required' => false,
                ),
                'rooms' => array(
                    'name' => 'rooms',
                    'label' => 'Количество комнат',
                    'type' => 'number',
                    'required' => false,
                ),
                'floor' => array(
                    'name' => 'floor',
                    'label' => 'Этаж',
                    'type' => 'number',
                    'required' => false,
                ),
                'latitude' => array(
                    'name' => 'latitude',
                    'label' => 'Широта (геолокация)',
                    'type' => 'number',
                    'required' => false,
                ),
                'longitude' => array(
                    'name' => 'longitude',
                    'label' => 'Долгота (геолокация)',
                    'type' => 'number',
                    'required' => false,
                ),
                'guest_count' => array(
                    'name' => 'guest_count',
                    'label' => 'Количество гостей',
                    'type' => 'number',
                    'required' => false,
                ),
                'location' => array(
                    'name' => 'location',
                    'label' => 'Расположение',
                    'type' => 'text',
                    'required' => false,
                ),
                'availability_dates' => array(
                    'name' => 'availability_dates',
                    'label' => 'Даты доступности',
                    'type' => 'date',
                    'required' => false,
                    'options' => array(
                        'date_format' => 'Y-m-d',
                        'pick_date' => true,
                        'pick_time' => false,
                    )
                ),
                'gallery' => array(
                    'name' => 'gallery',
                    'label' => 'Галерея изображений',
                    'type' => 'file',
                    'required' => false,
                    'options' => array(
                        'file_format_type' => 'multi',
                        'file_uploader' => 'attachment',
                        'file_field_template' => 'rows',
                    )
                ),
                ),
            'options' => array(
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
                'has_archive' => true,
                'rewrite' => array( 'slug' => 'property' ),
                'capability_type' => 'post',
                'hierarchical' => false,
                'menu_position' => 5,
                'menu_icon' => 'dashicons-admin-home',
                'rest_enabled' => true,
            ),
        );
        
        pods_api()->save_pod( $pod_data );
    }
    
    // Создаем или обновляем таксономию location
    $location_taxonomy_exists = pods_api()->load_pod( array( 'name' => 'location' ), false );
    
    if ( empty( $location_taxonomy_exists ) ) {
        $taxonomy_data = array(
            'name' => 'location',
            'label' => 'Локации',
            'type' => 'taxonomy',
            'storage' => 'meta',
            'object' => '',
            'fields' => array(),
            'options' => array(
                'public' => true,
                'show_ui' => true,
                'hierarchical' => true,
                'show_in_nav_menus' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array( 'slug' => 'locations' ),
                'rest_enabled' => true,
            ),
        );
        
        pods_api()->save_pod( $taxonomy_data );
    }
    
    // Создаем или обновляем таксономию property_type
    $property_type_taxonomy_exists = pods_api()->load_pod( array( 'name' => 'property_type' ), false );
    
    if ( empty( $property_type_taxonomy_exists ) ) {
        $taxonomy_data = array(
            'name' => 'property_type',
            'label' => 'Типы жилья',
            'type' => 'taxonomy',
            'storage' => 'meta',
            'object' => '',
            'fields' => array(),
            'options' => array(
                'public' => true,
                'show_ui' => true,
                'hierarchical' => false,
                'show_in_nav_menus' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array( 'slug' => 'property-type' ),
                'rest_enabled' => true,
            ),
        );
        
        pods_api()->save_pod( $taxonomy_data );
    }
}

/**
 * Резервная регистрация CPT если Pods не активен
 */
function register_property_post_type() {
    $labels = array(
        'name'                  => 'Объекты недвижимости',
        'singular_name'         => 'Объект недвижимости',
        'menu_name'             => 'Недвижимость',
        'name_admin_bar'        => 'Объект недвижимости',
        'archives'              => 'Архив объектов недвижимости',
        'attributes'            => 'Атрибуты объекта недвижимости',
        'parent_item_colon'     => 'Родительский объект:',
        'all_items'             => 'Все объекты',
        'add_new_item'          => 'Добавить новый объект',
        'add_new'               => 'Добавить новый',
        'new_item'              => 'Новый объект',
        'edit_item'             => 'Редактировать объект',
        'update_item'           => 'Обновить объект',
        'view_item'             => 'Просмотреть объект',
        'view_items'            => 'Просмотреть объекты',
        'search_items'          => 'Искать объект',
        'not_found'             => 'Не найдено',
        'not_found_in_trash'    => 'Не найдено в корзине',
        'featured_image'        => 'Изображение объекта',
        'set_featured_image'    => 'Установить изображение объекта',
        'remove_featured_image' => 'Удалить изображение объекта',
        'use_featured_image'    => 'Использовать как изображение объекта',
        'insert_into_item'      => 'Вставить в объект',
        'uploaded_to_this_item' => 'Загружено для этого объекта',
        'items_list'            => 'Список объектов',
        'items_list_navigation' => 'Навигация по списку объектов',
        'filter_items_list'     => 'Фильтровать список объектов',
    );
    
    $args = array(
        'label'                 => 'Объект недвижимости',
        'description'           => 'Объекты недвижимости',
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail' ),
        'taxonomies'            => array( 'location', 'property_type' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-admin-home',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'rewrite'               => array( 'slug' => 'property' ),
        'capability_type'       => 'post',
        'show_in_rest'          => true,
    );
    
    register_post_type( 'property', $args );
}

/**
 * Резервная регистрация таксономии location если Pods не активен
 */
function register_property_location_taxonomy() {
    $labels = array(
        'name'                       => 'Локации',
        'singular_name'              => 'Локация',
        'menu_name'                  => 'Локации',
        'all_items'                  => 'Все локации',
        'parent_item'                => 'Родительская локация',
        'parent_item_colon'          => 'Родительская локация:',
        'new_item_name'              => 'Новое название локации',
        'add_new_item'               => 'Добавить новую локацию',
        'edit_item'                  => 'Редактировать локацию',
        'update_item'                => 'Обновить локацию',
        'view_item'                  => 'Просмотреть локацию',
        'separate_items_with_commas' => 'Разделить локации запятыми',
        'add_or_remove_items'        => 'Добавить или удалить локации',
        'choose_from_most_used'      => 'Выбрать из часто используемых локаций',
        'popular_items'              => 'Популярные локации',
        'search_items'               => 'Поиск локаций',
        'not_found'                  => 'Не найдено',
        'no_terms'                   => 'Нет локаций',
        'items_list'                 => 'Список локаций',
        'items_list_navigation'      => 'Навигация по списку локаций',
    );

    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => false,
        'rewrite'                    => array( 'slug' => 'locations' ),
        'show_in_rest'               => true,
    );

    register_taxonomy( 'location', array( 'property' ), $args );
}

/**
 * Резервная регистрация таксономии property_type если Pods не активен
 */
function register_property_type_taxonomy() {
    $labels = array(
        'name'                       => 'Типы жилья',
        'singular_name'              => 'Тип жилья',
        'menu_name'                  => 'Типы жилья',
        'all_items'                  => 'Все типы жилья',
        'parent_item'                => 'Родительский тип',
        'parent_item_colon'          => 'Родительский тип:',
        'new_item_name'              => 'Новое название типа жилья',
        'add_new_item'               => 'Добавить новый тип жилья',
        'edit_item'                  => 'Редактировать тип жилья',
        'update_item'                => 'Обновить тип жилья',
        'view_item'                  => 'Просмотреть тип жилья',
        'separate_items_with_commas' => 'Разделить типы жилья запятыми',
        'add_or_remove_items'        => 'Добавить или удалить типы жилья',
        'choose_from_most_used'      => 'Выбрать из часто используемых типов жилья',
        'popular_items'              => 'Популярные типы жилья',
        'search_items'               => 'Поиск типов жилья',
        'not_found'                  => 'Не найдено',
        'no_terms'                   => 'Нет типов жилья',
        'items_list'                 => 'Список типов жилья',
        'items_list_navigation'      => 'Навигация по списку типов жилья',
    );

    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => false,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => false,
        'rewrite'                    => array( 'slug' => 'property-type' ),
        'show_in_rest'               => true,
    );

    register_taxonomy( 'property_type', array( 'property' ), $args );
    
    // Добавляем стандартные термины для property_type
    add_default_property_types();
}

/**
 * Добавление стандартных терминов для таксономии property_type
 */
function add_default_property_types() {
    $terms = array(
        'Дом',
        'Квартира',
        'Комната',
        'Глэмпинг',
        'Гостевой дом',
        'Отель',
        'Хостел'
    );
    
    foreach ( $terms as $term ) {
        if ( ! term_exists( $term, 'property_type' ) ) {
            wp_insert_term( $term, 'property_type' );
        }
    }
}

/**
 * Регистрация CPT characteristic для справочника характеристик недвижимости
 * 
 * Иерархический CPT с поддержкой post_parent для вложенности
 */
function register_characteristic_cpt() {
    $labels = array(
        'name'                  => 'Характеристики',
        'singular_name'         => 'Характеристика',
        'menu_name'             => 'Характеристики',
        'name_admin_bar'        => 'Характеристика',
        'archives'              => 'Архив характеристик',
        'attributes'            => 'Атрибуты характеристики',
        'parent_item_colon'     => 'Родительская характеристика:',
        'all_items'             => 'Все характеристики',
        'add_new_item'          => 'Добавить новую характеристику',
        'add_new'               => 'Добавить новую',
        'new_item'              => 'Новая характеристика',
        'edit_item'             => 'Редактировать характеристику',
        'update_item'           => 'Обновить характеристику',
        'view_item'             => 'Просмотреть характеристику',
        'view_items'            => 'Просмотреть характеристики',
        'search_items'          => 'Искать характеристики',
        'not_found'             => 'Не найдено',
        'not_found_in_trash'    => 'Не найдено в корзине',
        'items_list'            => 'Список характеристик',
        'items_list_navigation' => 'Навигация по списку характеристик',
        'filter_items_list'     => 'Фильтровать список характеристик',
    );
    
    $args = array(
        'label'                 => 'Характеристика',
        'description'           => 'Справочник характеристик недвижимости',
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor' ),
        'taxonomies'            => array( 'char_group', 'char_subtype' ),
        'hierarchical'          => true,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => false,
        'menu_position'         => null,
        'menu_icon'             => null,
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'rewrite'               => false,
        'capability_type'       => 'post',
        'show_in_rest'          => false,
    );
    
    register_post_type( 'characteristic', $args );
}

/**
 * Регистрация таксономии char_group для группировки характеристик
 * 
 * Иерархическая таксономия для организации характеристик по группам
 */
function setup_characteristic_groups_taxonomy() {
    $labels = array(
        'name'                       => 'Группы характеристик',
        'singular_name'              => 'Группа характеристик',
        'menu_name'                  => 'Группы характеристик',
        'all_items'                  => 'Все группы характеристик',
        'parent_item'                => 'Родительская группа',
        'parent_item_colon'          => 'Родительская группа:',
        'new_item_name'              => 'Новое название группы',
        'add_new_item'               => 'Добавить новую группу',
        'edit_item'                  => 'Редактировать группу',
        'update_item'                => 'Обновить группу',
        'view_item'                  => 'Просмотреть группу',
        'separate_items_with_commas' => 'Разделить группы запятыми',
        'add_or_remove_items'        => 'Добавить или удалить группы',
        'choose_from_most_used'      => 'Выбрать из часто используемых групп',
        'popular_items'              => 'Популярные группы',
        'search_items'               => 'Поиск групп',
        'not_found'                  => 'Не найдено',
        'no_terms'                   => 'Нет групп',
        'items_list'                 => 'Список групп',
        'items_list_navigation'      => 'Навигация по списку групп',
    );

    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => false,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => false,
        'show_tagcloud'              => false,
        'rewrite'                    => false,
        'show_in_rest'               => false,
    );

    register_taxonomy( 'char_group', array( 'characteristic' ), $args );
}

/**
 * Регистрация meta полей для CPT characteristic
 * 
 * Все поля с санитизацией и валидацией согласно WordPress standards
 */
function register_characteristic_meta_fields() {
    // icon - название Material Symbol или ID media
    register_meta( 'post', 'icon', array(
        'object_subtype'    => 'characteristic',
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => false,
    ) );
    
    // style - стиль отображения (circle|standard|prohibited|text)
    register_meta( 'post', 'style', array(
        'object_subtype'    => 'characteristic',
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => false,
    ) );
    
    // label - лейбл характеристики
    register_meta( 'post', 'label', array(
        'object_subtype'    => 'characteristic',
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => false,
    ) );
    
    // hint - хинт/подпись
    register_meta( 'post', 'hint', array(
        'object_subtype'    => 'characteristic',
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => false,
    ) );
    
    // value - дополнительное значение
    register_meta( 'post', 'value', array(
        'object_subtype'    => 'characteristic',
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => false,
    ) );
    
    // sort_order - порядок сортировки
    register_meta( 'post', 'sort_order', array(
        'object_subtype'    => 'characteristic',
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
        'show_in_rest'      => false,
    ) );
    
    // icon_type - тип иконки (material_symbol|upload)
    register_meta( 'post', 'icon_type', array(
        'object_subtype'    => 'characteristic',
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => false,
    ) );
    
    // media_id - ID вложения для загруженных иконок
    register_meta( 'post', 'media_id', array(
        'object_subtype'    => 'characteristic',
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
        'show_in_rest'      => false,
    ) );
    
    // show_in_filter - показывать ли в фильтре на фронте
    register_meta( 'post', 'show_in_filter', array(
        'object_subtype'    => 'characteristic',
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
        'show_in_rest'      => false,
    ) );
    
    // Term meta для таксономии char_group
    register_term_meta( 'char_group', 'type_ui', array(
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    
    register_term_meta( 'char_group', 'display_style', array(
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    
    register_term_meta( 'char_group', 'description_full', array(
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    
    register_term_meta( 'char_group', 'active', array(
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
    ) );
    
    register_term_meta( 'char_group', 'sort_order', array(
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
    ) );
    
    register_term_meta( 'char_group', 'use_in_filters', array(
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
    ) );
    
    register_term_meta( 'char_group', 'show_in_archive', array(
        'type'              => 'integer',
        'single'            => true,
        'sanitize_callback' => 'absint',
    ) );
}

// Вызываем функцию регистрации
register_property_cpt_and_taxonomies();

