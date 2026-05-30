<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
	/*-----------------------------------------------------------------------------------*/
	/* This file will be referenced every time a template/page loads on your Wordpress site
	/* This is the place to define custom fxns and specialty code
	/*-----------------------------------------------------------------------------------*/

// Define the version so we can easily replace it throughout the theme
define( 'NAKED_VERSION', 1.0 );
/*-----------------------------------------------------------------------------------*/
	/*  Set the maximum allowed width for any content in the theme
	/*-----------------------------------------------------------------------------------*/
if ( ! isset( $content_width ) ) $content_width = 900;

/*-----------------------------------------------------------------------------------*/
	/* Add Rss feed support to Head section
	/*-----------------------------------------------------------------------------------*/
add_theme_support( 'automatic-feed-links' );

/*-----------------------------------------------------------------------------------*/
	/* Add post thumbnail/featured image support
	/*-----------------------------------------------------------------------------------*/
add_theme_support( 'post-thumbnails' );

/*-----------------------------------------------------------------------------------*/
	/* Custom image sizes for property teasers
	/*-----------------------------------------------------------------------------------*/
// Property card teaser: 400x300 (aspect ratio 4:3, high quality)
add_image_size( 'property-teaser', 400, 300, true ); // true = hard crop

/*-----------------------------------------------------------------------------------*/
	/* Register main menu for Wordpress use
	/*-----------------------------------------------------------------------------------*/
register_nav_menus(
	array(
		'primary'	=>	__( 'Primary Menu', 'naked' ), // Register the Primary menu
		// Copy and paste the line above right here if you want to make another menu,
		// just change the 'primary' to another name
	)
);

/*-----------------------------------------------------------------------------------*/
	/* Activate sidebar for Wordpress use
	/*-----------------------------------------------------------------------------------*/
function naked_register_sidebars() {
	register_sidebar(array(				// Start a series of sidebars to register
		'id' => 'sidebar', 					// Make an ID
		'name' => 'Sidebar',				// Name it
		'description' => 'Take it on the side...', // Dumb description for the admin side
		'before_widget' => '<div>',	// What to display before each widget
		'after_widget' => '</div>',	// What to display following each widget
		'before_title' => '<h3 class="side-title">',	// What to display before each widget's title
		'after_title' => '</h3>',		// What to display following each widget's title
		'empty_title'=> '',					// What to display in the case of no title defined for a widget
		// Copy and paste the lines above right here if you want to make another menu,
		// just change the values of id and name to another word/name
	));
}
// adding sidebars to Wordpress (these are created in functions.php)
add_action( 'widgets_init', 'naked_register_sidebars' );

/*-----------------------------------------------------------------------------------*/
	/* Enqueue Styles and Scripts
	/*-----------------------------------------------------------------------------------*/
function naked_scripts()  {
    /*----Start CSS----*/
    // get the theme directory style.css and link to it in the header
	wp_enqueue_style('style.css', get_stylesheet_directory_uri() . '/style.css');
    // стили Element Plus
    wp_enqueue_style('element-plus', get_template_directory_uri() . '/assets/vue_module/cdn/element-plus/index.css', array(), NAKED_VERSION, 'all');
    wp_enqueue_style('main.min.css', get_stylesheet_directory_uri() . '/dist/css/main.min.css');
    
    // Временно подключаем основной CSS с стилями для формы поиска
    //wp_enqueue_style('main-styles.css', get_stylesheet_directory_uri() . '/assets/css/___main.css');

    // Подключаем стили Splide для галереи на странице недвижимости
    if (is_singular('property')) {
        wp_enqueue_style('splide-core', get_template_directory_uri() . '/js/slider/splide.min.css', array(), NAKED_VERSION, 'all');
    }
    //wp_enqueue_style('main.css', get_stylesheet_directory_uri() . '/assets/css/main.css');
    /*----End CSS----*/
    wp_enqueue_script( 'naked-jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js', array( 'jquery' ), NAKED_VERSION, false );
	// Подключаем Vue.js и Vuex после jQuery в header
    wp_enqueue_script( 'vue-js', get_template_directory_uri() . '/assets/vue_module/cdn/vue.global.min.js', array( 'naked-jquery' ), NAKED_VERSION, false );
    wp_enqueue_script( 'vuex-js', get_template_directory_uri() . '/assets/vue_module/cdn/vuex.global.min.js', array( 'vue-js' ), NAKED_VERSION, false );
        // add theme scripts vue
	wp_enqueue_script( 'vue-build', get_template_directory_uri() . '/assets/vue_module/dish/appVueBuild.js', array('vue-js', 'vuex-js'), NAKED_VERSION, true );
    // add theme scripts
    wp_enqueue_script( 'naked', get_template_directory_uri() . '/js/site.js', array(), NAKED_VERSION, true );

    // Передаем данные в JS для работы избранного и других функций
    wp_localize_script( 'naked', 'RealtyData', array(
        'isLoggedIn'    => is_user_logged_in(),
        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
        'favoriteNonce' => wp_create_nonce( 'my_cabinet_favorite_nonce' ),
    ) );

    
    // Подключаем скрипт Splide для галереи на странице недвижимости
    if (is_singular('property')) {
        wp_enqueue_script('splide-core', get_template_directory_uri() . '/js/slider/splide.min.js', array('jquery'), NAKED_VERSION, true);
    }

    /*----End JS----*/

}
add_action( 'wp_enqueue_scripts', 'naked_scripts' ); // Register this fxn and allow Wordpress to call it automatcally in the header

// Подключаем отдельный аякс для рубрики;
add_action('wp_enqueue_scripts', 'enqueue_ajax_pagination_scripts');

function enqueue_ajax_pagination_scripts() {
    wp_enqueue_script('ajax-pagination', get_template_directory_uri() . '/js/ajax-pagination.js', array('jquery'), null, true);
    wp_localize_script('ajax-pagination', 'ajaxpagination', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}

// Подключаем скрипты и стили для фильтра недвижимости
add_action('wp_enqueue_scripts', 'enqueue_property_filter_scripts');

function enqueue_property_filter_scripts() {
    // Подключаем Google Maps API на страницах архива, одиночного объекта и dashboard
    if (is_post_type_archive('property') || is_singular('property') || is_page('my-dashboard') || (isset($_GET['property_id']) && isset($_GET['thread_id']))) {
        // Получаем API-ключ из настроек
        $api_key = get_option('google_maps_api_key', '');
        if (!empty($api_key)) {
            wp_enqueue_script('google-maps-api', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key, array(), null, false);
        } else {
            // Если ключ не задан, используем без ключа (ограниченная функциональность)
            wp_enqueue_script('google-maps-api', 'https://maps.googleapis.com/maps/api/js', array(), null, false);
        }
        
        // Подключаем скрипт для работы с картами на странице архива и одиночной странице
       // wp_enqueue_script('google-maps-property', get_template_directory_uri() . '/js/google-maps-property.js', array('jquery'), null, true);
    }
}

// Подключаем API для фронтенда
require get_template_directory() . '/inc/api/api_front.php';

// Подключаем базовый класс темы
require get_template_directory() . '/inc/core/RealtyCore.php';

// Подключаем вспомогательные функции
require get_template_directory() . '/inc/helpers/helpers.php';

// Подключаем фичу Недвижимости
require get_template_directory() . '/inc/features/property/property.php';

// Инициализация фичи Недвижимости
$propertyFeature = PropertyFeature::getInstance();
$propertyFeature->init();

// Админка часть;
require get_template_directory() . '/inc/admin/admin-function.php';
// Подключаем файл с регистрацией CPT и таксономий
require get_template_directory() . '/inc/admin/pods-registration.php';
// Подключаем общие константы для админки
require get_template_directory() . '/inc/admin/shared/const_params.php';
// Подключаем страницу управления справочником характеристик
require get_template_directory() . '/inc/admin/settings/characteristics-settings.php';
// Метабокс характеристик
require get_template_directory() . '/inc/admin/settings/property-characteristics-metabox.php';
// Метабокс длительного проживания
require get_template_directory() . '/inc/admin/settings/property-long-stay-metabox.php';
// Подключаем универсальную страницу настроек
require get_template_directory() . '/inc/admin/settings/index-settings.php';
// Admin: Messages management
require get_template_directory() . '/inc/admin/messages-admin.php';

// Подключаем модуль "Мой кабинет"
require get_template_directory() . '/my/cpt/cpt-registration.php';
require get_template_directory() . '/inc/cpt/booking-request.php';
require get_template_directory() . '/inc/migrations/migrate-messages-to-booking.php';
require get_template_directory() . '/inc/features/booking/booking-functions.php';
require get_template_directory() . '/my/hook/hook-function.php';



// ============================================================
// Обратная совместимость - создаём глобальные функции-обёртки
// ============================================================

/**
 * Универсальная функция для получения списка недвижимости
 * Обёртка для PropertyFeature::getList()
 */
function properties_list($params = array()) {
    return PropertyFeature::getInstance()->getList($params);
}

/**
 * Рендер формы фильтрации (Vue компонент)
 */
function property_filter_render_form($context = 'default') {
    return PropertyFeature::getInstance()->shortcodeFilter(['context' => $context]);
}

/**
 * Рендер формы фильтрации для главной страницы
 */
function property_filter_render_home_form(array $args = array()) {
    return PropertyFeature::getInstance()->renderHomeForm($args);
}

/**
 * Рендер списка недвижимости
 */
function property_render_list() {
    return PropertyFeature::getInstance()->property_filter_render_list();
}

/**
 * Получение данных активных фильтров
 */
function get_current_filters_data() {
    return PropertyFeature::getInstance()->getCurrentFilters();
}

// Добавляем проверку валидности параметров фильтрации при загрузке страницы
add_action('template_redirect', 'validate_property_search_parameters');

function validate_property_search_parameters() {
    // Проверяем, находимся ли мы на странице архива недвижимости и есть ли GET-параметры
    if (is_post_type_archive('property') && !empty($_GET)) {
        $errors = array();
        
        // Проверяем даты, если они переданы
        if (isset($_GET['checkin_date']) && !empty($_GET['checkin_date'])) {
            $checkin_errors = validate_property_dates(sanitize_text_field($_GET['checkin_date']),
                                                   isset($_GET['checkout_date']) ? sanitize_text_field($_GET['checkout_date']) : '');
            $errors = array_merge($errors, $checkin_errors);
        }
        
        // Проверяем количество гостей, если они переданы
        if ((isset($_GET['adults']) && !empty($_GET['adults'])) || (isset($_GET['children']) && !empty($_GET['children']))) {
            $guest_errors = validate_guest_numbers(
                isset($_GET['adults']) ? intval($_GET['adults']) : 0,
                isset($_GET['children']) ? intval($_GET['children']) : 0
            );
            $errors = array_merge($errors, $guest_errors);
        }
        
        // Если есть ошибки валидации, показываем сообщение
        if (!empty($errors)) {
            // Сохраняем ошибки во временное хранилище
            set_transient('property_search_errors', $errors, 30); // Храним 30 секунд
            
            // Перенаправляем на ту же страницу без невалидных параметров
            $redirect_url = remove_query_arg(array_keys($_GET), $_SERVER['REQUEST_URI']);
            wp_redirect($redirect_url);
            exit;
        }
    }
}

function realty_register_locations_rewrite_rules() {
    add_rewrite_tag('%locations_archive%', '([0-9]+)');
    add_rewrite_rule('^locations/?$', 'index.php?locations_archive=1', 'top');
    add_rewrite_rule('^locations/page/([0-9]{1,})/?$', 'index.php?locations_archive=1&paged=$matches[1]', 'top');
}
add_action('init', 'realty_register_locations_rewrite_rules');

function realty_add_locations_query_var($vars) {
    $vars[] = 'locations_archive';
    return $vars;
}
add_filter('query_vars', 'realty_add_locations_query_var');

function realty_flush_rewrite_rules_on_theme_switch() {
    realty_register_locations_rewrite_rules();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'realty_flush_rewrite_rules_on_theme_switch');

function realty_maybe_flush_rewrite_rules_once() {
    if ( '1' !== get_option( 'realty_locations_rewrite_flushed', '0' ) ) {
        realty_register_locations_rewrite_rules();
        flush_rewrite_rules( false );
        update_option( 'realty_locations_rewrite_flushed', '1' );
    }
}
add_action( 'admin_init', 'realty_maybe_flush_rewrite_rules_once' );

// Поддержка шаблонов, перемещённых в папку templates/
function realty_theme_template_include($template) {
    $base_dir = get_template_directory();
    if (get_query_var('locations_archive')) {
        $custom = $base_dir . '/templates/pages/archive-location.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    if (is_post_type_archive('property')) {
        $custom = $base_dir . '/templates/pages/archive-property.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    if (is_tax('location')) {
        $custom = $base_dir . '/templates/single/single-location.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    if (is_singular('property')) {
        $custom = $base_dir . '/templates/single/single-property.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    if (is_singular('location')) {
        $custom = $base_dir . '/templates/single/single-location.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
}
add_filter('template_include', 'realty_theme_template_include', 99);

// Функция для получения ошибок валидации
function get_property_search_errors() {
    $errors = get_transient('property_search_errors');
    if ($errors) {
        delete_transient('property_search_errors'); // Удаляем после получения
        return $errors;
    }
return array();
}

// Функция для вывода хлебных крошек
function custom_breadcrumbs($current_page = '') {
    echo '<nav class="breadcrumb__com" aria-label="breadcrumb">';
    echo '<a href="' . esc_url(home_url('/')) . '">' . esc_html__('Главная', 'naked') . '</a> <span>/</span> ';
    if (!empty($current_page)) {
        echo '<span class="breadcrumb_name">' . esc_html($current_page) . '</span>';
    }
    echo '</nav>';
}