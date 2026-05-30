<?php
/**
 * Бизнес-логика работы с бронированиями
 * Модуль "Мой кабинет" для темы Realty Theme
 *
 * Функции создания, получения и обновления booking_request.
 * Интеграция с SESSION для временного хранения параметров.
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Получить или создать booking_request
 * 
 * Логика:
 * 1. Если есть активная заявка (status=new) для property_id + client_id → возвращаем её
 * 2. Если есть завершенные заявки (completed/cancelled) → создаем НОВУЮ заявку
 * 3. Если заявок нет → создаем НОВУЮ заявку
 * 
 * @param int   $property_id  ID объекта недвижимости
 * @param int   $client_id    ID клиента
 * @param int   $owner_id     ID владельца
 * @param array $session_data Данные из SESSION (checkin_date, guests_count)
 * @return array|false Массив booking_id, thread_id, status или false
 */
function realty_get_or_create_booking_request( $property_id, $client_id, $owner_id, $session_data = array() ) {
    if ( ! $property_id || ! $client_id || ! $owner_id ) {
        return false;
    }

    // ШАГ 1: Ищем активную заявку (status=new)
    $active_booking = realty_find_active_booking( $property_id, $client_id );
    
    if ( $active_booking ) {
        // Активная заявка найдена — используем её
        return array(
            'booking_id' => $active_booking->ID,
            'thread_id'  => get_post_meta( $active_booking->ID, '_thread_id', true ),
            'status'     => 'new',
            'action'     => 'existing',
        );
    }

    // ШАГ 2: Проверяем наличие завершенных заявок (для логирования)
    $completed_bookings = realty_find_completed_bookings( $property_id, $client_id );
    
    // ШАГ 3: Создаем НОВУЮ заявку
    $new_thread_id = 'thread_' . $property_id . '_' . $client_id . '_' . $owner_id . '_' . time();
    
    $booking_id = wp_insert_post( array(
        'post_title'    => 'Заявка #' . $property_id . ' - ' . date( 'd.m.Y' ),
        'post_status'   => 'publish',
        'post_type'     => 'booking_request',
        'post_author'   => $client_id,
    ) );

    if ( is_wp_error( $booking_id ) ) {
        error_log( 'Booking creation error: ' . $booking_id->get_error_message() );
        return false;
    }

    // Подготовка guests_count из session_data
    $guests_count_json = '';
    if ( isset( $session_data['guests_count'] ) && ! empty( $session_data['guests_count'] ) ) {
        $guests_count_raw = $session_data['guests_count'];
        error_log( '[booking-functions] guests_count from SESSION (raw): ' . print_r( $guests_count_raw, true ) );
        
        // Если это уже строка JSON
        if ( is_string( $guests_count_raw ) ) {
            // Декодируем чтобы проверить валидность
            $decoded = json_decode( $guests_count_raw, true );
            error_log( '[booking-functions] guests_count decoded type: ' . gettype( $decoded ) );
            
            if ( is_array( $decoded ) ) {
                // Сохраняем как есть (уже валидный JSON)
                $guests_count_json = $guests_count_raw;
                error_log( '[booking-functions] guests_count is valid JSON, saving as-is' );
            } else {
                error_log( '[booking-functions] DECODE FAILED - invalid JSON string' );
            }
        } elseif ( is_array( $guests_count_raw ) ) {
            // Уже массив — кодируем
            $guests_count_json = wp_json_encode( $guests_count_raw );
            error_log( '[booking-functions] guests_count was array, encoded: ' . $guests_count_json );
        }
    } elseif ( isset( $session_data['adults'] ) || isset( $session_data['children'] ) ) {
        // Legacy support
        $guests = array();
        if ( isset( $session_data['adults'] ) && $session_data['adults'] > 0 ) {
            $guests['adults'] = $session_data['adults'];
        }
        if ( isset( $session_data['children'] ) && $session_data['children'] > 0 ) {
            $guests['children'] = $session_data['children'];
        }
        $guests_count_json = ! empty( $guests ) ? wp_json_encode( $guests ) : '';
        if ( $guests_count_json ) {
            error_log( '[booking-functions] guests_count from legacy: ' . $guests_count_json );
        }
    } else {
        error_log( '[booking-functions] guests_count NOT FOUND in session_data' );
    }

    // Сохраняем мета-поля
    update_post_meta( $booking_id, '_property_id', $property_id );
    update_post_meta( $booking_id, '_owner_id', $owner_id );
    update_post_meta( $booking_id, '_client_id', $client_id );
    update_post_meta( $booking_id, '_thread_id', $new_thread_id );
    update_post_meta( $booking_id, '_checkin_date', sanitize_text_field( $session_data['checkin_date'] ?? '' ) );
    update_post_meta( $booking_id, '_checkout_date', sanitize_text_field( $session_data['checkout_date'] ?? '' ) );
    update_post_meta( $booking_id, '_guests_count', $guests_count_json );
    update_post_meta( $booking_id, '_status', 'new' );
    update_post_meta( $booking_id, '_created_date', current_time( 'mysql' ) );
    
    // DEBUG: Проверяем что сохранилось
    $saved_guests = get_post_meta( $booking_id, '_guests_count', true );
    $saved_checkin = get_post_meta( $booking_id, '_checkin_date', true );
    $saved_checkout = get_post_meta( $booking_id, '_checkout_date', true );
    error_log( '[booking-functions] Created booking_id=' . $booking_id );
    error_log( '[booking-functions] guests_count to save: ' . $guests_count_json );
    error_log( '[booking-functions] guests_count saved in DB: ' . $saved_guests );
    error_log( '[booking-functions] checkin_date saved: ' . $saved_checkin );
    error_log( '[booking-functions] checkout_date saved: ' . $saved_checkout );

    return array(
        'booking_id' => $booking_id,
        'thread_id'  => $new_thread_id,
        'status'     => 'new',
        'action'     => 'created',
    );
}

/**
 * Обновить параметры booking_request из SESSION
 * 
 * Обновляет даты и гостей ТОЛЬКО если статус = "new".
 * 
 * @param int   $booking_id   ID заявки
 * @param array $session_data Данные из SESSION
 * @return bool Успешность обновления
 */
function realty_update_booking_from_session( $booking_id, $session_data = array() ) {
    if ( ! $booking_id ) {
        return false;
    }

    // Проверяем существование заявки
    $booking = get_post( $booking_id );
    if ( ! $booking || $booking->post_type !== 'booking_request' ) {
        return false;
    }

    // Проверяем статус
    $status = get_post_meta( $booking_id, '_status', true );
    if ( $status !== 'new' ) {
        // Заблокировано — нельзя редактировать
        return false;
    }

    // Обновляем даты
    if ( isset( $session_data['checkin_date'] ) && ! empty( $session_data['checkin_date'] ) ) {
        update_post_meta( $booking_id, '_checkin_date', sanitize_text_field( $session_data['checkin_date'] ) );
    }
    
    if ( isset( $session_data['checkout_date'] ) && ! empty( $session_data['checkout_date'] ) ) {
        update_post_meta( $booking_id, '_checkout_date', sanitize_text_field( $session_data['checkout_date'] ) );
    }

    // Обновляем guests_count
    error_log( '[booking-update] session_data guests_count: ' . ( $session_data['guests_count'] ?? 'NOT SET' ) );
    
    if ( isset( $session_data['guests_count'] ) && ! empty( $session_data['guests_count'] ) ) {
        $decoded = $session_data['guests_count'];
        
        // Первый json_decode
        if ( is_string( $decoded ) ) {
            $decoded = json_decode( $decoded, true );
        }
        
        // Второй json_decode (если двойное кодирование)
        if ( is_string( $decoded ) ) {
            $decoded = json_decode( $decoded, true );
        }
        
        error_log( '[booking-update] final decoded result: ' . print_r( $decoded, true ) );
        
        if ( is_array( $decoded ) ) {
            $encoded = wp_json_encode( $decoded );
            update_post_meta( $booking_id, '_guests_count', $encoded );
            
            // Проверяем что сохранилось
            $saved = get_post_meta( $booking_id, '_guests_count', true );
            error_log( '[booking-update] guests_count saved: ' . $saved );
        }
    } elseif ( isset( $session_data['adults'] ) || isset( $session_data['children'] ) ) {
        // Legacy support
        $guests = array();
        if ( isset( $session_data['adults'] ) && $session_data['adults'] > 0 ) {
            $guests['adults'] = $session_data['adults'];
        }
        if ( isset( $session_data['children'] ) && $session_data['children'] > 0 ) {
            $guests['children'] = $session_data['children'];
        }
        if ( ! empty( $guests ) ) {
            update_post_meta( $booking_id, '_guests_count', wp_json_encode( $guests ) );
        }
    }

    return true;
}

/**
 * Найти активную заявку (status=new)
 * 
 * @param int $property_id ID объекта
 * @param int $client_id   ID клиента
 * @return WP_Post|false Объект заявки или false
 */
function realty_find_active_booking( $property_id, $client_id ) {
    $query = new WP_Query( array(
        'post_type'      => 'booking_request',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'   => '_property_id',
                'value' => $property_id,
            ),
            array(
                'key'   => '_client_id',
                'value' => $client_id,
            ),
            array(
                'key'   => '_status',
                'value' => 'new',
            ),
        ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    if ( $query->have_posts() ) {
        $booking = $query->posts[0];
        wp_reset_postdata();
        return $booking;
    }

    return false;
}

/**
 * Найти booking_request со статусом pending
 * 
 * @param int $property_id ID объекта
 * @param int $client_id   ID клиента
 * @return WP_Post|false Объект заявки или false
 */
function realty_find_pending_booking( $property_id, $client_id ) {
    $query = new WP_Query( array(
        'post_type'      => 'booking_request',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array( 'key' => '_property_id', 'value' => $property_id ),
            array( 'key' => '_client_id', 'value' => $client_id ),
            array( 'key' => '_status', 'value' => 'pending' ),
        ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    if ( $query->have_posts() ) {
        $booking = $query->posts[0];
        wp_reset_postdata();
        return $booking;
    }

    return false;
}

/**
 * Найти завершенные заявки (completed/cancelled)
 * 
 * @param int $property_id ID объекта
 * @param int $client_id   ID клиента
 * @return array Массив WP_Post объектов
 */
function realty_find_completed_bookings( $property_id, $client_id ) {
    $query = new WP_Query( array(
        'post_type'      => 'booking_request',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'   => '_property_id',
                'value' => $property_id,
            ),
            array(
                'key'   => '_client_id',
                'value' => $client_id,
            ),
            array(
                'key'     => '_status',
                'value'   => array( 'completed', 'cancelled' ),
                'compare' => 'IN',
            ),
        ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    $bookings = $query->posts;
    wp_reset_postdata();
    
    return $bookings;
}

/**
 * Получить booking_request по ID
 * 
 * @param int $booking_id ID заявки
 * @return array|false Массив данных заявки или false
 */
function realty_get_booking_data( $booking_id ) {
    if ( ! $booking_id ) {
        return false;
    }

    $booking = get_post( $booking_id );
    if ( ! $booking || $booking->post_type !== 'booking_request' ) {
        return false;
    }

    $status = get_post_meta( $booking_id, '_status', true );
    if ( empty( $status ) ) {
        $status = 'new';
    }

    // Декодируем guests_count
    $guests_count_raw = get_post_meta( $booking_id, '_guests_count', true );
    $guests_count = ! empty( $guests_count_raw ) ? json_decode( $guests_count_raw, true ) : array();
    if ( ! is_array( $guests_count ) ) {
        $guests_count = array();
    }

    return array(
        'booking_id'   => $booking_id,
        'property_id'  => (int) get_post_meta( $booking_id, '_property_id', true ),
        'owner_id'     => (int) get_post_meta( $booking_id, '_owner_id', true ),
        'client_id'    => (int) get_post_meta( $booking_id, '_client_id', true ),
        'thread_id'    => get_post_meta( $booking_id, '_thread_id', true ),
        'checkin_date' => get_post_meta( $booking_id, '_checkin_date', true ),
        'checkout_date' => get_post_meta( $booking_id, '_checkout_date', true ),
        'guests_count' => $guests_count,
        'status'       => $status,
        'created_date' => get_post_meta( $booking_id, '_created_date', true ),
    );
}

/**
 * Обновить статус booking_request
 * 
 * Только хост или админ может менять статус.
 * 
 * @param int    $booking_id ID заявки
 * @param string $new_status Новый статус
 * @param int    $user_id    ID пользователя (для проверки прав)
 * @return bool|WP_Error true или ошибка
 */
function realty_update_booking_status( $booking_id, $new_status, $user_id = 0 ) {
    if ( ! $booking_id ) {
        return new WP_Error( 'invalid_booking', 'Не указан ID заявки.' );
    }

    // Валидация статуса
    $valid_statuses = array( 'new', 'in_progress', 'confirmed', 'completed', 'cancelled' );
    if ( ! in_array( $new_status, $valid_statuses, true ) ) {
        return new WP_Error( 'invalid_status', 'Некорректный статус.' );
    }

    // Проверка прав
    if ( $user_id > 0 ) {
        $booking = get_post( $booking_id );
        if ( ! $booking ) {
            return new WP_Error( 'not_found', 'Заявка не найдена.' );
        }

        $owner_id = (int) get_post_meta( $booking_id, '_owner_id', true );
        $client_id = (int) get_post_meta( $booking_id, '_client_id', true );

        // Только хозяин объекта, клиент (только cancelled) или админ
        if ( ! current_user_can( 'manage_options' ) && $user_id !== $owner_id ) {
            if ( $user_id === $client_id && $new_status === 'cancelled' ) {
                // Клиент может только отменить
            } else {
                return new WP_Error( 'no_permission', 'Недостаточно прав.' );
            }
        }
    }

    // Обновляем статус
    update_post_meta( $booking_id, '_status', $new_status );

    return true;
}

/**
 * Создать booking_request (чат-поток) со статусом pending
 * 
 * @param int   $property_id ID объекта
 * @param int   $client_id   ID клиента
 * @param int   $owner_id    ID владельца
 * @param array $params      Параметры (checkin_date, checkout_date, guests_count)
 * @return array|false Массив booking_id, thread_id или false
 */
function realty_create_booking_thread( $property_id, $client_id, $owner_id, $params = array() ) {
    if ( ! $property_id || ! $client_id || ! $owner_id ) {
        return false;
    }

    // Генерируем уникальный thread_id
    $thread_id = 'thread_' . $property_id . '_' . $client_id . '_' . $owner_id . '_' . time() . '_' . wp_generate_password( 6, false );
    
    $booking_id = wp_insert_post( array(
        'post_title'    => 'Заявка #' . $property_id . ' - ' . date( 'd.m.Y' ),
        'post_status'   => 'publish',
        'post_type'     => 'booking_request',
        'post_author'   => $client_id,
    ) );

    if ( is_wp_error( $booking_id ) ) {
        error_log( 'Booking thread creation error: ' . $booking_id->get_error_message() );
        return false;
    }

    // Подготовка guests_count
    $guests_count_json = '';
    if ( isset( $params['guests_count'] ) && ! empty( $params['guests_count'] ) ) {
        $guests_count_raw = $params['guests_count'];
        
        if ( is_string( $guests_count_raw ) ) {
            $decoded = json_decode( $guests_count_raw, true );
            if ( is_array( $decoded ) ) {
                $guests_count_json = $guests_count_raw;
            }
        } elseif ( is_array( $guests_count_raw ) ) {
            $guests_count_json = wp_json_encode( $guests_count_raw );
        }
    }

    // Сохраняем мета-поля
    update_post_meta( $booking_id, '_property_id', $property_id );
    update_post_meta( $booking_id, '_owner_id', $owner_id );
    update_post_meta( $booking_id, '_client_id', $client_id );
    update_post_meta( $booking_id, '_thread_id', $thread_id );
    update_post_meta( $booking_id, '_checkin_date', sanitize_text_field( $params['checkin_date'] ?? '' ) );
    update_post_meta( $booking_id, '_checkout_date', sanitize_text_field( $params['checkout_date'] ?? '' ) );
    update_post_meta( $booking_id, '_guests_count', $guests_count_json );
    update_post_meta( $booking_id, '_status', 'pending' );
    update_post_meta( $booking_id, '_created_date', current_time( 'mysql' ) );

    return array(
        'booking_id' => $booking_id,
        'thread_id'  => $thread_id,
        'status'     => 'pending',
    );
}

/**
 * Обновить параметры booking_request (даты, гости)
 * 
 * @param int   $booking_id ID заявки
 * @param array $params     Новые параметры
 * @return bool Успешность
 */
function realty_update_booking_parameters( $booking_id, $params = array() ) {
    if ( ! $booking_id ) {
        return false;
    }

    $booking = get_post( $booking_id );
    if ( ! $booking || $booking->post_type !== 'booking_request' ) {
        return false;
    }

    // Обновляем даты
    if ( isset( $params['checkin_date'] ) && ! empty( $params['checkin_date'] ) ) {
        update_post_meta( $booking_id, '_checkin_date', sanitize_text_field( $params['checkin_date'] ) );
    }
    
    if ( isset( $params['checkout_date'] ) && ! empty( $params['checkout_date'] ) ) {
        update_post_meta( $booking_id, '_checkout_date', sanitize_text_field( $params['checkout_date'] ) );
    }

    // Обновляем guests_count
    if ( isset( $params['guests_count'] ) && ! empty( $params['guests_count'] ) ) {
        $guests_count_raw = $params['guests_count'];
        
        if ( is_string( $guests_count_raw ) ) {
            $decoded = json_decode( $guests_count_raw, true );
            if ( is_array( $decoded ) ) {
                update_post_meta( $booking_id, '_guests_count', $guests_count_raw );
            }
        } elseif ( is_array( $guests_count_raw ) ) {
            update_post_meta( $booking_id, '_guests_count', wp_json_encode( $guests_count_raw ) );
        }
    }

    return true;
}
