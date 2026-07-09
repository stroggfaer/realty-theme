<?php
/**
 * Логика отзывов и рейтингов
 *
 * @package RealtyTheme
 * @subpackage Reviews
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX callback для получения архивных бронирований
 */
function realty_get_archive_bookings_ajax() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Авторизация обязательна' ), 401 );
    }

    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'property_filter_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Проверка безопасности не пройдена' ), 403 );
    }

    $user_id = get_current_user_id();
    $status_filter = sanitize_text_field( $_POST['status_filter'] ?? '' );
    $page = intval( $_POST['page'] ?? 1 );

    $result = ReviewService::getInstance()->getArchiveBookings( $user_id, array(
        'status_filter' => $status_filter,
        'page'          => $page,
        'per_page'      => 10,
    ) );

    wp_send_json_success( $result );
}
add_action( 'wp_ajax_get_archive_bookings', 'realty_get_archive_bookings_ajax' );
add_action( 'wp_ajax_nopriv_get_archive_bookings', 'realty_get_archive_bookings_ajax' );

/**
 * Проверка включена ли система отзывов
 *
 * @return bool
 */
function realty_is_reviews_enabled() {
    return (bool) get_option( 'reviews_enabled', 1 );
}

/**
 * Возвращает статистику отзывов для объекта недвижимоти
 *
 * @param int $property_id
 * @return array
 */
function realty_get_property_review_stats( $property_id ) {
    return ReviewService::getInstance()->getPropertyReviewStats( intval( $property_id ) );
}

/**
 * Средняя оценка объекта недвижимости
 *
 * @param int $property_id
 * @return float
 */
function realty_get_property_review_average_rating( $property_id ) {
    $stats = realty_get_property_review_stats( $property_id );
    return round( floatval( $stats['average'] ?? 0 ), 1 );
}

/**
 * Количество опубликованных отзывов для объекта недвижимости
 *
 * @param int $property_id
 * @return int
 */
function realty_get_property_reviews_count( $property_id ) {
    $stats = realty_get_property_review_stats( $property_id );
    return intval( $stats['count'] ?? 0 );
}

/**
 * Возвращает статистику отзывов хозяина
 *
 * @param int $host_id
 * @return array
 */
function realty_get_host_review_stats( $host_id ) {
    return ReviewService::getInstance()->getHostReviewStats( intval( $host_id ) );
}

/**
 * Средняя оценка хоста
 *
 * @param int $host_id
 * @return float
 */
function realty_get_host_overall_rating( $host_id ) {
    $stats = realty_get_host_review_stats( $host_id );
    return round( floatval( $stats['average'] ?? 0 ), 1 );
}

/**
 * Количество опубликованных отзывов хоста
 *
 * @param int $host_id
 * @return int
 */
function realty_get_host_reviews_count( $host_id ) {
    $stats = realty_get_host_review_stats( $host_id );
    return intval( $stats['count'] ?? 0 );
}

/**
 * Создать отзыв по бронированию
 *
 * @param int   $booking_id
 * @param array $data
 * @return int|WP_Error
 */
function realty_create_booking_review( $booking_id, $data = array() ) {
    return ReviewService::getInstance()->createReview( intval( $booking_id ), $data );
}

/**
 * Проверяет, есть ли уже отзыв по бронированию
 *
 * @param int $booking_id
 * @return bool
 */
function realty_has_booking_review( $booking_id ) {
    return ReviewService::getInstance()->hasBookingReview( intval( $booking_id ) );
}

/**
 * Проверяет возможность оставить отзыв по бронированию
 *
 * @param int $booking_id
 * @return bool
 */
function realty_can_leave_booking_review( $booking_id ) {
    if ( ! realty_is_reviews_enabled() ) {
        return false;
    }

    $booking = get_post( $booking_id );
    if ( ! $booking || $booking->post_type !== 'booking_request' ) {
        return false;
    }

    $booking_status = get_post_meta( $booking_id, '_status', true );
    if ( $booking_status !== 'completed' ) {
        return false;
    }

    if ( realty_has_booking_review( $booking_id ) ) {
        return false;
    }

    return true;
}

/**
 * Регистрирует API маршруты для отзывов
 */
function realty_register_review_api_routes() {
    register_rest_route( 'property/v1', '/bookings/(?P<id>\d+)/review', array(
        'methods'             => 'POST',
        'callback'            => 'realty_create_booking_review_api',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
    ) );

    register_rest_route( 'property/v1', '/properties/(?P<id>\d+)/review-stats', array(
        'methods'             => 'GET',
        'callback'            => 'realty_get_property_review_stats_api',
        'permission_callback' => '__return_true',
    ) );
}
add_action( 'rest_api_init', 'realty_register_review_api_routes' );

/**
 * REST API callback для создания отзыва
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function realty_create_booking_review_api( $request ) {
    $booking_id = absint( $request->get_param( 'id' ) );
    $ratings = array(
        'price_quality' => intval( $request->get_param( 'rating_price_quality' ) ),
        'cleanliness'   => intval( $request->get_param( 'rating_cleanliness' ) ),
        'location'      => intval( $request->get_param( 'rating_location' ) ),
        'comfort'       => intval( $request->get_param( 'rating_comfort' ) ),
        'food'          => intval( $request->get_param( 'rating_food' ) ),
        'service'       => intval( $request->get_param( 'rating_service' ) ),
    );

    $comment = sanitize_textarea_field( $request->get_param( 'comment' ) );

    $result = realty_create_booking_review( $booking_id, array(
        'ratings' => $ratings,
        'comment' => $comment,
    ) );

    if ( is_wp_error( $result ) ) {
        return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 400 );
    }

    return new WP_REST_Response( array( 'review_id' => $result ), 201 );
}

/**
 * REST API callback для получения статистики рейтинга объекта
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function realty_get_property_review_stats_api( $request ) {
    $property_id = absint( $request->get_param( 'id' ) );
    $stats = realty_get_property_review_stats( $property_id );
    return new WP_REST_Response( $stats, 200 );
}

/**
 * Получить критерии оценки из справочника характеристик
 * 
 * Ищет группу с group_system_template = 'system_review'
 * и возвращает её характеристики как критерии для отзыва.
 * Если группа не найдена — возвращает дефолтный набор.
 *
 * @return array
 */
function realty_get_review_criteria() {
    $criteria = array(
        'price_quality' => 'Цена / Качество',
        'cleanliness'   => 'Чистота',
        'location'      => 'Расположение',
        'comfort'       => 'Комфорт',
        'food'          => 'Питание',
        'service'       => 'Обслуживание',
    );

    // Ищем группу с системным шаблоном system_review
    $groups = get_terms( array(
        'taxonomy'   => 'char_group',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key'   => 'group_system_template',
                'value' => 'system_review',
            ),
        ),
    ) );

    if ( empty( $groups ) || is_wp_error( $groups ) ) {
        return $criteria;
    }

    $group_id = $groups[0]->term_id;
    
    // Получаем характеристики этой группы
    $chars = get_posts( array(
        'post_type'      => 'characteristic',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'sort_order',
        'order'          => 'ASC',
        'tax_query'      => array(
            array(
                'taxonomy' => 'char_group',
                'field'    => 'term_id',
                'terms'    => $group_id,
            ),
        ),
    ) );

    if ( empty( $chars ) ) {
        return $criteria;
    }

    $dynamic_criteria = array();
    foreach ( $chars as $char ) {
        $key = sanitize_key( get_post_meta( $char->ID, 'value', true ) );
        if ( empty( $key ) ) {
            $key = sanitize_key( $char->post_title );
        }
        $label = get_post_meta( $char->ID, 'label', true );
        if ( empty( $label ) ) {
            $label = $char->post_title;
        }
        $dynamic_criteria[ $key ] = $label;
    }

    return ! empty( $dynamic_criteria ) ? $dynamic_criteria : $criteria;
}

/**
 * Seed: создать группу "Оценки" с характеристиками
 * Вызывается при активации темы или вручную
 */
function realty_seed_review_criteria_group() {
    // Проверяем, существует ли уже группа с system_review
    $existing = get_terms( array(
        'taxonomy'   => 'char_group',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key'   => 'group_system_template',
                'value' => 'system_review',
            ),
        ),
        'fields'     => 'ids',
    ) );

    if ( ! empty( $existing ) && ! is_wp_error( $existing ) ) {
        return $existing[0]; // Уже есть
    }

    // Создаём группу
    $result = wp_insert_term( 'Оценки', 'char_group', array(
        'slug'        => 'reviews',
        'description' => 'Критерии для оценки недвижимости и хоста',
    ) );

    if ( is_wp_error( $result ) ) {
        return false;
    }

    $group_id = $result['term_id'];

    // Мета группы
    update_term_meta( $group_id, 'group_key', 'reviews' );
    update_term_meta( $group_id, 'group_system_template', 'system_review' );
    update_term_meta( $group_id, 'type_ui', 'checkbox' );
    update_term_meta( $group_id, 'display_style', 'standard' );
    update_term_meta( $group_id, 'active', 1 );
    update_term_meta( $group_id, 'sort_order', 0 );
    update_term_meta( $group_id, 'use_in_filters', 0 );
    update_term_meta( $group_id, 'show_in_archive', 0 );

    // Стандартные критерии
    $default_criteria = array(
        'price_quality' => array(
            'title' => 'PRICE_QUALITY',
            'label' => 'Цена / Качество',
            'value' => 'price_quality',
            'order' => 1,
        ),
        'cleanliness' => array(
            'title' => 'CLEANLINESS',
            'label' => 'Чистота',
            'value' => 'cleanliness',
            'order' => 2,
        ),
        'location' => array(
            'title' => 'LOCATION',
            'label' => 'Расположение',
            'value' => 'location',
            'order' => 3,
        ),
        'comfort' => array(
            'title' => 'COMFORT',
            'label' => 'Комфорт',
            'value' => 'comfort',
            'order' => 4,
        ),
        'food' => array(
            'title' => 'FOOD',
            'label' => 'Питание',
            'value' => 'food',
            'order' => 5,
        ),
        'service' => array(
            'title' => 'SERVICE',
            'label' => 'Обслуживание',
            'value' => 'service',
            'order' => 6,
        ),
    );

    foreach ( $default_criteria as $key => $data ) {
        $char_id = wp_insert_post( array(
            'post_title'   => $data['title'],
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'characteristic',
        ) );

        if ( is_wp_error( $char_id ) ) {
            continue;
        }

        // Привязываем к группе
        wp_set_object_terms( $char_id, $group_id, 'char_group' );

        // Мета
        update_post_meta( $char_id, 'label', $data['label'] );
        update_post_meta( $char_id, 'value', $data['value'] );
        update_post_meta( $char_id, 'sort_order', $data['order'] );
        update_post_meta( $char_id, 'active', 1 );
        update_post_meta( $char_id, 'icon', 'star' );
        update_post_meta( $char_id, 'icon_type', 'material_symbol' );
        update_post_meta( $char_id, 'style', 'standard' );
    }

    return $group_id;
}

// Seed: создаём группу "Оценки" при первой загрузке
add_action( 'init', function() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
    }
    if ( is_admin() && current_user_can( 'manage_options' ) ) {
        realty_seed_review_criteria_group();
    }
}, 100 );

/**
 * AJAX callback для создания отзыва
 */
function realty_submit_booking_review_ajax() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Авторизация обязательна' ), 401 );
    }

    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'property_filter_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Проверка безопасности не пройдена' ), 403 );
    }

    $booking_id = absint( $_POST['booking_id'] ?? 0 );
    $ratings = array(
        'price_quality' => intval( $_POST['rating_price_quality'] ?? 0 ),
        'cleanliness'   => intval( $_POST['rating_cleanliness'] ?? 0 ),
        'location'      => intval( $_POST['rating_location'] ?? 0 ),
        'comfort'       => intval( $_POST['rating_comfort'] ?? 0 ),
        'food'          => intval( $_POST['rating_food'] ?? 0 ),
        'service'       => intval( $_POST['rating_service'] ?? 0 ),
    );
    $comment = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) );

    $result = realty_create_booking_review( $booking_id, array(
        'ratings' => $ratings,
        'comment' => $comment,
    ) );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
    }

    wp_send_json_success( array( 'review_id' => $result ) );
}
add_action( 'wp_ajax_submit_booking_review', 'realty_submit_booking_review_ajax' );
add_action( 'wp_ajax_nopriv_submit_booking_review', 'realty_submit_booking_review_ajax' );

/**
 * Сервис для обработки логики отзывов
 */
class ReviewService {
    private static $instance = null;

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    public function createReview( $booking_id, $data = array() ) {
        if ( ! realty_is_reviews_enabled() ) {
            return new WP_Error( 'reviews_disabled', 'Система отзывов отключена' );
        }

        $booking = get_post( $booking_id );
        if ( ! $booking || $booking->post_type !== 'booking_request' ) {
            return new WP_Error( 'invalid_booking', 'Некорректное бронирование' );
        }

        $current_user_id = get_current_user_id();
        if ( ! $current_user_id ) {
            return new WP_Error( 'not_authenticated', 'Требуется авторизация' );
        }

        $booking_status = get_post_meta( $booking_id, '_status', true );
        if ( $booking_status !== 'completed' ) {
            return new WP_Error( 'booking_not_completed', 'Отзыв можно оставить только после завершения бронирования' );
        }

        if ( $this->hasBookingReview( $booking_id ) ) {
            return new WP_Error( 'review_already_exists', 'Отзыв по этому бронированию уже существует' );
        }

        $client_id = get_post_meta( $booking_id, '_client_id', true );
        if ( intval( $client_id ) !== $current_user_id && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'forbidden', 'Вы не можете оставить отзыв за это бронирование' );
        }

        $ratings = $data['ratings'] ?? array();
        $ratings = array_map( 'intval', $ratings );
        $criteria = realty_get_review_criteria();
        foreach ( $criteria as $key => $label ) {
            if ( empty( $ratings[ $key ] ) || $ratings[ $key ] < 1 || $ratings[ $key ] > 10 ) {
                return new WP_Error( 'invalid_rating', 'Необходима оценка по всем параметрам от 1 до 10' );
            }
        }

        $overall = $this->calculateOverallRating( $ratings );
        $property_id = absint( get_post_meta( $booking_id, '_property_id', true ) );
        $host_id = absint( get_post_meta( $booking_id, '_owner_id', true ) );

        if ( ! $property_id ) {
            return new WP_Error( 'invalid_property', 'Не найден объект недвижимости для этого бронирования' );
        }

        $review_id = wp_insert_post( array(
            'post_type'   => 'review',
            'post_status' => 'publish',
            'post_title'  => sprintf( 'Отзыв по бронированию #%d', $booking_id ),
            'post_author' => $current_user_id,
            'post_content'=> sanitize_textarea_field( $data['comment'] ?? '' ),
        ) );

        if ( is_wp_error( $review_id ) ) {
            return $review_id;
        }

        update_post_meta( $review_id, '_booking_id', $booking_id );
        update_post_meta( $review_id, '_property_id', $property_id );
        update_post_meta( $review_id, '_host_id', $host_id );
        update_post_meta( $review_id, '_rating_price_quality', $ratings['price_quality'] );
        update_post_meta( $review_id, '_rating_cleanliness', $ratings['cleanliness'] );
        update_post_meta( $review_id, '_rating_location', $ratings['location'] );
        update_post_meta( $review_id, '_rating_comfort', $ratings['comfort'] );
        update_post_meta( $review_id, '_rating_food', $ratings['food'] );
        update_post_meta( $review_id, '_rating_service', $ratings['service'] );
        update_post_meta( $review_id, '_rating_overall', $overall );
        update_post_meta( $review_id, '_review_status', 'published' );

        $this->refreshPropertyRating( $property_id );
        if ( $host_id ) {
            $this->refreshHostRating( $host_id );
        }

        return $review_id;
    }

    public function hasBookingReview( $booking_id ) {
        $query = new WP_Query( array(
            'post_type'      => 'review',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'   => '_booking_id',
                    'value' => $booking_id,
                ),
            ),
        ) );

        $exists = $query->have_posts();
        wp_reset_postdata();
        return $exists;
    }

    public function getPropertyReviewStats( $property_id ) {
        $property_id = absint( $property_id );
        if ( ! $property_id ) {
            return array( 'property_id' => 0, 'average' => 0, 'count' => 0 );
        }

        $average = floatval( get_post_meta( $property_id, 'rating_overall', true ) );
        $count = absint( get_post_meta( $property_id, 'review_count', true ) );

        if ( $count < 1 ) {
            $stats = $this->calculatePropertyReviewStats( $property_id );
            $average = $stats['average'];
            $count = $stats['count'];
        }

        return array(
            'property_id' => $property_id,
            'average'     => round( $average, 1 ),
            'count'       => $count,
        );
    }

    public function getHostReviewStats( $host_id ) {
        $host_id = absint( $host_id );
        if ( ! $host_id ) {
            return array( 'host_id' => 0, 'average' => 0, 'count' => 0 );
        }

        $average = floatval( get_user_meta( $host_id, 'host_rating_overall', true ) );
        $count = absint( get_user_meta( $host_id, 'host_review_count', true ) );

        if ( $count < 1 ) {
            $stats = $this->calculateHostReviewStats( $host_id );
            $average = $stats['average'];
            $count = $stats['count'];
        }

        return array(
            'host_id' => $host_id,
            'average' => round( $average, 1 ),
            'count'   => $count,
        );
    }

    public function refreshPropertyRating( $property_id ) {
        $stats = $this->calculatePropertyReviewStats( $property_id );
        update_post_meta( $property_id, 'rating_overall', $stats['average'] );
        update_post_meta( $property_id, 'review_count', $stats['count'] );
        return $stats;
    }

    public function refreshHostRating( $host_id ) {
        $stats = $this->calculateHostReviewStats( $host_id );
        update_user_meta( $host_id, 'host_rating_overall', $stats['average'] );
        update_user_meta( $host_id, 'host_review_count', $stats['count'] );
        return $stats;
    }

    private function calculateOverallRating( $ratings ) {
        $values = array_values( $ratings );
        $count = count( $values );
        if ( $count === 0 ) {
            return 0;
        }
        $sum = array_sum( $values );
        return round( ( $sum / $count ), 1 );
    }

    private function calculatePropertyReviewStats( $property_id ) {
        $reviews = get_posts( array(
            'post_type'      => 'review',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_property_id',
                    'value' => $property_id,
                ),
                array(
                    'key'   => '_review_status',
                    'value' => 'published',
                ),
            ),
            'fields'         => 'ids',
        ) );

        $count = count( $reviews );
        $sum = 0;

        foreach ( $reviews as $review_id ) {
            $sum += floatval( get_post_meta( $review_id, '_rating_overall', true ) );
        }

        $average = $count ? round( $sum / $count, 1 ) : 0;
        return array( 'average' => $average, 'count' => $count );
    }

    private function calculateHostReviewStats( $host_id ) {
        $reviews = get_posts( array(
            'post_type'      => 'review',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_host_id',
                    'value' => $host_id,
                ),
                array(
                    'key'   => '_review_status',
                    'value' => 'published',
                ),
            ),
            'fields'         => 'ids',
        ) );

        $count = count( $reviews );
        $sum = 0;

        foreach ( $reviews as $review_id ) {
            $sum += floatval( get_post_meta( $review_id, '_rating_overall', true ) );
        }

        $average = $count ? round( $sum / $count, 1 ) : 0;
        return array( 'average' => $average, 'count' => $count );
    }

    /**
     * Получить архивные бронирования текущего пользователя (completed|cancelled)
     *
     * @param int   $user_id ID пользователя
     * @param array $params  Параметры: status_filter, page, per_page
     * @return array
     */
    public function getArchiveBookings( $user_id, $params = array() ) {
        $status_filter = sanitize_text_field( $params['status_filter'] ?? '' );
        $page = max( 1, intval( $params['page'] ?? 1 ) );
        $per_page = max( 1, min( 50, intval( $params['per_page'] ?? 10 ) ) );

        $meta_query = array(
            array(
                'key'   => '_client_id',
                'value' => $user_id,
            ),
            array(
                'key'     => '_status',
                'value'   => array( 'completed', 'cancelled' ),
                'compare' => 'IN',
            ),
        );

        // Фильтр по статусу
        if ( in_array( $status_filter, array( 'completed', 'cancelled' ), true ) ) {
            $meta_query[1]['value'] = $status_filter;
            $meta_query[1]['compare'] = '=';
        }

        $query = new WP_Query( array(
            'post_type'      => 'booking_request',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'meta_query'     => $meta_query,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $bookings = array();
        foreach ( $query->posts as $post ) {
            $property_id = absint( get_post_meta( $post->ID, '_property_id', true ) );
            $property_title = '';
            $property_url = '';
            if ( $property_id ) {
                $property_title = get_the_title( $property_id );
                $property_url = get_permalink( $property_id );
            }

            $checkin = get_post_meta( $post->ID, '_checkin_date', true );
            $checkout = get_post_meta( $post->ID, '_checkout_date', true );
            $status = get_post_meta( $post->ID, '_status', true );
            $has_review = $this->hasBookingReview( $post->ID );
            $review_id = 0;
            if ( $has_review ) {
                $review_query = get_posts( array(
                    'post_type'      => 'review',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'meta_query'     => array(
                        array( 'key' => '_booking_id', 'value' => $post->ID ),
                    ),
                ) );
                $review_id = $review_query ? $review_query[0] : 0;
            }

            $bookings[] = array(
                'id'             => $post->ID,
                'property_id'    => $property_id,
                'property_title' => $property_title ?: '(объект удалён)',
                'property_url'   => $property_url,
                'checkin_date'   => $checkin ?: '',
                'checkout_date'  => $checkout ?: '',
                'status'         => $status,
                'has_review'     => $has_review,
                'review_id'      => $review_id,
            );
        }

        wp_reset_postdata();

        return array(
            'bookings'    => $bookings,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page,
            'per_page'    => $per_page,
        );
    }
}
