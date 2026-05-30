<?php
/**
 * Страница админки для управления сообщениями
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
 * Добавление раздела "Бронирование" в меню админки
 * Структура аналогична "Недвижимость":
 *   Бронирование
 *   └─ Сообщения
 */
function my_cabinet_add_messages_menu() {
    // Родительский пункт меню "Бронирование" (без callback — не кликабельный заголовок)
    add_menu_page(
        'Бронирование',
        'Бронирование',
        'manage_options',
        'booking',
        '__return_null',
        'dashicons-calendar-alt',
        6  // После Недвижимость (position 5)
    );

    // Получаем счетчик непрочитанных
    $unread_count = my_cabinet_get_unread_messages_count();

    $menu_title = $unread_count > 0 ? 'Сообщения <span class="awaiting-mod">' . $unread_count . '</span>' : 'Сообщения';

    // Подпункт "Сообщения"
    add_submenu_page(
        'booking',
        'Сообщения',
        $menu_title,
        'manage_options',
        'booking-messages',
        'my_cabinet_messages_page'
    );

    // Убираем дублирующий первый подпункт "Бронирование", который WordPress добавляет автоматически
    remove_submenu_page( 'booking', 'booking' );
}
add_action( 'admin_menu', 'my_cabinet_add_messages_menu' );

/**
 * Очистка кэша меню после изменения позиции
 */
function my_cabinet_flush_menu_cache() {
    if ( ! get_option( 'booking_menu_position_updated', false ) ) {
        delete_option( 'menu_order' );
        update_option( 'booking_menu_position_updated', true );
    }
}
add_action( 'admin_init', 'my_cabinet_flush_menu_cache' );

/**
 * Подключение CSS и JS для страницы сообщений
 */
function my_cabinet_enqueue_messages_assets( $hook ) {
    // Подключаем только на странице Сообщений (подпункт раздела Бронирование)
    // Hook suffix для подпунктов: {parent_slug}_page_{child_slug} (дефисы → подчёркивания)
    $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
    if ( 'booking-messages' !== $page ) {
        return;
    }
    
    // Получаем текущий view и thread_id для условной загрузки скриптов
    $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
    $thread_id = isset( $_GET['thread_id'] ) ? sanitize_text_field( wp_unslash( $_GET['thread_id'] ) ) : '';
    
    // CSS
    wp_enqueue_style(
        'my-cabinet-messages-admin',
        get_template_directory_uri() . '/inc/admin/assets/css/messages.css',
        array(),
        '1.0.1'
    );
    
    // JS для списка сообщений
    wp_enqueue_script(
        'my-cabinet-messages-admin',
        get_template_directory_uri() . '/inc/admin/assets/js/messages-admin.js',
        array(),
        '1.0.0',
        true
    );
    
    // JS для страницы диалога
    wp_enqueue_script(
        'my-cabinet-dialog-view',
        get_template_directory_uri() . '/inc/admin/assets/js/dialog-view.js',
        array(),
        '1.0.0',
        true
    );

    // Передаем данные в JS для страницы списка
    wp_localize_script( 'my-cabinet-messages-admin', 'myCabinetMessagesData', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'getDialogsNonce' => wp_create_nonce( 'my_cabinet_get_dialogs_nonce' ),
        'getDialogNonce' => wp_create_nonce( 'my_cabinet_get_dialog_nonce' ),
        'sendReplyNonce' => wp_create_nonce( 'my_cabinet_send_reply_nonce' ),
        'deleteMessageNonce' => wp_create_nonce( 'my_cabinet_delete_message_nonce' ),
        'markReadNonce' => wp_create_nonce( 'my_cabinet_mark_read_nonce' ),
        'currentUserId' => get_current_user_id(),
    ) );
    
    // Передаем данные в JS для страницы диалога
    if ( $view === 'dialog' && ! empty( $thread_id ) ) {
        // Ищем booking_id по thread_id
        $dialog_booking_id = 0;
        $dialog_booking_status = 'new';
        
        $booking_query = new WP_Query( array(
            'post_type'      => 'booking_request',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'     => '_thread_id',
                    'value'   => $thread_id,
                    'compare' => '=',
                ),
            ),
        ) );

        if ( $booking_query->have_posts() ) {
            $dialog_booking_id = $booking_query->posts[0]->ID;
            $dialog_booking_status = get_post_meta( $dialog_booking_id, '_status', true ) ?: 'new';
        }
        
        wp_localize_script( 'my-cabinet-dialog-view', 'dialogViewData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'threadId' => $thread_id,
            'bookingId' => $dialog_booking_id,
            'bookingStatus' => $dialog_booking_status,
            'getDialogNonce' => wp_create_nonce( 'my_cabinet_get_dialog_nonce' ),
            'sendReplyNonce' => wp_create_nonce( 'my_cabinet_send_reply_nonce' ),
            'updateBookingNonce' => wp_create_nonce( 'my_cabinet_update_booking_nonce' ),
        ) );
    }
}
add_action( 'admin_enqueue_scripts', 'my_cabinet_enqueue_messages_assets' );

/**
 * Обновление счетчика непрочитанных при загрузке админки
 */
function my_cabinet_update_unread_count() {
    global $submenu;
    
    $unread_count = my_cabinet_get_unread_messages_count();
    
    if ( $unread_count > 0 && isset( $submenu['booking'] ) ) {
        // Обновляем подпункт "Сообщения" в меню "Бронирование"
        foreach ( $submenu['booking'] as $key => $item ) {
            // Ищем подпункт booking-messages
            if ( 'booking-messages' === $item[2] ) {
                // Проверяем, не добавлен ли уже счетчик
                if ( strpos( $item[0], 'awaiting-mod' ) === false ) {
                    $submenu['booking'][ $key ][0] = 'Сообщения <span class="awaiting-mod">' . $unread_count . '</span>';
                }
                break;
            }
        }
    }
}
add_action( 'in_admin_header', 'my_cabinet_update_unread_count', 1 );

/**
 * Отображение страницы сообщений
 */
function my_cabinet_messages_page() {
    // Проверяем, нужно ли показать страницу диалога
    $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
    $thread_id = isset( $_GET['thread_id'] ) ? sanitize_text_field( wp_unslash( $_GET['thread_id'] ) ) : '';
    
    if ( $view === 'dialog' && ! empty( $thread_id ) ) {
        my_cabinet_dialog_view_page( $thread_id );
        return;
    }
    
    // Обычная страница со списком сообщений
    my_cabinet_messages_list_page();
}

/**
 * Страница списка заявок на бронирование
 */
function my_cabinet_messages_list_page() {
    // Подсчет непрочитанных сообщений
    $unread_count = my_cabinet_get_unread_messages_count();
    
    // Получаем статус из фильтра
    $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
    
    ?>
    <div class="wrap" id="my-cabinet-messages-admin">
        <h1 class="wp-heading-inline">
            Заявки на бронирование
            <?php if ( $unread_count > 0 ): ?>
                <span class="awaiting-mod"><?php echo esc_html( $unread_count ); ?></span>
            <?php endif; ?>
        </h1>
        <hr class="wp-header-end">

        <div class="messages-container">
            <div class="messages-filters">
                <h3>Фильтры</h3>
                <div class="filter-row">
                    <select id="filter-status" class="regular-text">
                        <option value="">Все статусы</option>
                        <option value="pending" <?php selected( $status_filter, 'pending' ); ?>>Ожидание</option>
                        <option value="new" <?php selected( $status_filter, 'new' ); ?>>Новая заявка</option>
                        <option value="in_progress" <?php selected( $status_filter, 'in_progress' ); ?>>В процессе</option>
                        <option value="confirmed" <?php selected( $status_filter, 'confirmed' ); ?>>Подтверждена</option>
                        <option value="completed" <?php selected( $status_filter, 'completed' ); ?>>Завершена</option>
                        <option value="cancelled" <?php selected( $status_filter, 'cancelled' ); ?>>Отменена</option>
                    </select>
                    <input type="date" id="filter-date" class="regular-text" placeholder="Дата" />
                    <input type="text" id="filter-login" class="regular-text" placeholder="Логин" />
                    <button type="button" id="apply-filters" class="button button-primary">Применить</button>
                    <button type="button" id="clear-filters" class="button">Сбросить</button>
                </div>
            </div>
            <p class="unread-notice" style="display:none;">
                    У вас новые сообщения: <strong class="unread-count">0</strong>
                </p>
            </div>

            <table class="wp-list-table widefat fixed striped messages-table">
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th class="col-login">Клиент</th>
                        <th class="col-property">Недвижимость</th>
                        <th class="col-dates">Даты заезда/выезда</th>
                        <th class="col-guests">Гости</th>
                        <th class="col-status">Статус</th>
                        <th class="col-date">Дата создания</th>
                        <th class="col-actions">Действия</th>
                    </tr>
                </thead>
                <tbody id="messages-list">
                    <tr>
                        <td colspan="8" class="loading">Загрузка...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Подсчет непрочитанных сообщений для хоста
 */
function my_cabinet_get_unread_messages_count() {
    $host_id = get_current_user_id();
    
    $args = array(
        'post_type'      => 'message',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            // Только сообщения, где хозяин - получатель
            array(
                'key'     => '_receiver_id',
                'value'   => $host_id,
                'compare' => '=',
            ),
            // Только непрочитанные (ищем '0' или false)
            array(
                'key'     => '_is_read',
                'value'   => '1',
                'compare' => '!=',
            ),
        ),
    );

    $query = new WP_Query( $args );
    $unread_count = $query->found_posts;
    
    // Debug
    error_log( "Unread messages count: $unread_count for host_id: $host_id" );
    
    return $unread_count;
}

/**
 * Страница просмотра диалога
 */
function my_cabinet_dialog_view_page( $thread_id ) {
    // ============================================================
    // НОВАЯ ЛОГИКА: Ищем booking_request по thread_id
    // ============================================================
    
    $booking_query = new WP_Query( array(
        'post_type'      => 'booking_request',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'     => '_thread_id',
                'value'   => $thread_id,
                'compare' => '=',
            ),
        ),
    ) );

    $booking_id = 0;
    $booking_data = null;
    $property_id = 0;
    $client_id = 0;
    $owner_id = 0;
    $booking_status = 'new';
    
    // DEBUG
    error_log( 'my_cabinet_dialog_view_page: thread_id=' . $thread_id );
    
    if ( $booking_query->have_posts() ) {
        $booking_id = $booking_query->posts[0]->ID;
        
        // DEBUG
        error_log( 'my_cabinet_dialog_view_page: Found booking_id=' . $booking_id );
        
        $booking_data = realty_get_booking_data( $booking_id );
        
        // DEBUG
        error_log( 'my_cabinet_dialog_view_page: booking_data=' . ( $booking_data ? 'OK' : 'NULL' ) );
        
        if ( $booking_data ) {
            $property_id = $booking_data['property_id'];
            $client_id = $booking_data['client_id'];
            $owner_id = $booking_data['owner_id'];
            $booking_status = $booking_data['status'];
        }
    } else {
        // DEBUG
        error_log( 'my_cabinet_dialog_view_page: No booking found, trying fallback to messages' );
        // Fallback: ищем по сообщениям (для обратной совместимости)
        $message_query = new WP_Query( array(
            'post_type'      => 'message',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'     => '_thread_id',
                    'value'   => $thread_id,
                    'compare' => '=',
                ),
            ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        if ( $message_query->have_posts() ) {
            $first_message = $message_query->posts[0];
            $property_id = (int) get_post_meta( $first_message->ID, '_property_id', true );
            $client_id = (int) get_post_meta( $first_message->ID, '_sender_id', true );
            $owner_id = (int) get_post_meta( $first_message->ID, '_receiver_id', true );
            
            // Проверяем есть ли привязка к booking_request
            $booking_id = (int) get_post_meta( $first_message->ID, '_booking_request_id', true );
            if ( $booking_id > 0 ) {
                $booking_data = realty_get_booking_data( $booking_id );
                if ( $booking_data ) {
                    $booking_status = $booking_data['status'];
                }
            }
        } else {
            echo '<div class="wrap"><h1>Диалог не найден</h1>';
            echo '<a href="' . admin_url( 'admin.php?page=booking-messages' ) . '" class="button">← Назад к списку</a></div>';
            return;
        }
    }

    // Получаем информацию о пользователе
    $client = get_userdata( $client_id );
    
    // Получаем booking meta из booking_data
    $checkin_date  = $booking_data ? $booking_data['checkin_date'] : '';
    $checkout_date = $booking_data ? $booking_data['checkout_date'] : '';
    $guests_count  = $booking_data ? $booking_data['guests_count'] : array();
    $has_guests    = ! empty( $guests_count );
    
    // Формируем guests_values для обратной совместимости
    $guests_values = array();
    if ( $has_guests && is_array( $guests_count ) ) {
        $guest_types_config = realty_get_guest_types_config();
        foreach ( $guest_types_config as $guest_type ) {
            if ( empty( $guest_type['enabled'] ) ) {
                continue;
            }
            $guest_name = $guest_type['name'];
            $guest_value = $guests_count[ $guest_name ] ?? 0;
            if ( $guest_value > 0 ) {
                $guests_values[ $guest_name ] = array(
                    'value' => $guest_value,
                    'label' => $guest_type['label'],
                );
            }
        }
    }
    
    // URL для возврата
    $back_url = admin_url( 'admin.php?page=booking-messages' );
    
    ?>
    <div class="wrap dialog-view-page">
        <h1 class="wp-heading-inline">
            Диалог с <?php echo $client ? esc_html( $client->display_name ) : 'Пользователем'; ?>
        </h1>
        <a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action">← Назад к списку</a>
        <hr class="wp-header-end">

        <div class="dialog-view-layout">
            <!-- Левая колонка: Чат (70%) -->
            <div class="dialog-chat-column">
                <div class="dialog-info-box">
                    <strong>Логин:</strong> <?php echo $client ? esc_html( $client->user_login ) : '—'; ?> | 
                    <strong>Email:</strong> <?php echo $client ? esc_html( $client->user_email ) : '—'; ?> | 
                    <strong>ID диалога:</strong> <?php echo esc_html( $thread_id ); ?>
                </div>

                <div id="dialog-messages-container" class="dialog-messages-view">
                    <div class="loading-messages">Загрузка сообщений...</div>
                </div>

                <div class="dialog-reply-box">
                    <h3>Ответить</h3>
                    <textarea id="dialog-reply-textarea" rows="5" placeholder="Введите ваше сообщение..."></textarea>
                    <button type="button" id="dialog-send-btn" class="button button-primary button-hero">
                        Отправить сообщение
                    </button>
                    <span id="dialog-send-status" class="send-status"></span>
                </div>
            </div>

            <!-- Правая колонка: Карточка недвижимости (30%) -->
            <div class="dialog-sidebar-column">
                <?php if ( $property_id > 0 ) : ?>
                    <?php
                    // Получаем данные объекта
                    $property_post = get_post( $property_id );
                    $property_title = $property_post ? $property_post->post_title : '';
                    $property_url = $property_post ? get_permalink( $property_id ) : '';
                    $property_thumbnail = get_the_post_thumbnail( $property_id, 'medium', array( 'class' => 'property-card-image' ) );
                    $property_price = get_post_meta( $property_id, 'price', true );
                    
                    // Получаем локацию
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
                    
                    <?php if ( $checkin_date || $checkout_date || $has_guests ) : ?>
                        <?php
                        // Получаем статус бронирования из booking_request (не из сообщения!)
                        $booking_status = 'new';
                        if ( $booking_id > 0 ) {
                            $booking_status = get_post_meta( $booking_id, '_status', true ) ?: 'new';
                        }
                        
                        // Используем хелпер для статусов
                        $booking_statuses = realty_get_booking_statuses();
                        $current_status = realty_get_booking_status_data( $booking_status );
                        
                        // Форматируем даты для input type="date"
                        $checkin_date_formatted = $checkin_date ? date( 'Y-m-d', strtotime( $checkin_date ) ) : '';
                        $checkout_date_formatted = $checkout_date ? date( 'Y-m-d', strtotime( $checkout_date ) ) : '';
                        ?>
                        
                        <div class="booking-dates-card">
                            <h3 class="booking-card-title">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                Даты бронирования
                            </h3>
                            
                            <!-- Режим просмотра -->
                            <div class="booking-view-mode" id="booking-view-mode">
                                <?php if ( $checkin_date ) : ?>
                                    <div class="booking-date-row">
                                        <span class="date-label">Заезд:</span>
                                        <span class="date-value"><?php echo date_i18n( 'd F Y', strtotime( $checkin_date ) ); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( $checkout_date ) : ?>
                                    <div class="booking-date-row">
                                        <span class="date-label">Выезд:</span>
                                        <span class="date-value"><?php echo date_i18n( 'd F Y', strtotime( $checkout_date ) ); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( $has_guests ) : ?>
                                    <div class="booking-guests-row">
                                        <span class="guests-label">Гости:</span>
                                        <span class="guests-value">
                                            <?php
                                            $guests_parts = array();
                                            foreach ( $guests_values as $guest_name => $guest_data ) {
                                                if ( $guest_data['value'] > 0 ) {
                                                    $guests_parts[] = $guest_data['value'] . ' ' . $guest_data['label'];
                                                }
                                            }
                                            echo esc_html( implode( ', ', $guests_parts ) );
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Статус бронирования -->
                                <div class="booking-status-row">
                                    <span class="status-label">Статус:</span>
                                    <span class="booking-status-badge booking-status--<?php echo esc_attr( $current_status['class'] ); ?>">
                                        <?php echo esc_html( $current_status['label'] ); ?>
                                    </span>
                                </div>
                                
                                <?php if ( ! in_array( $booking_status, array( 'pending', 'completed', 'cancelled' ), true ) ) : ?>
                                <div class="booking-edit-actions">
                                    <button type="button" id="booking-edit-btn" class="button">
                                        <span class="dashicons dashicons-edit"></span> Редактировать
                                    </button>
                                </div>
                                <?php else : ?>
                                <div class="booking-edit-actions">
                                    <?php if ( $booking_status === 'pending' ) : ?>
                                        <span class="booking-final-notice">Редактирование недоступно до подтверждения бронирования</span>
                                    <?php else : ?>
                                        <span class="booking-final-notice">Редактирование недоступно для завершённых или отменённых бронирований</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Режим редактирования (скрыт по умолчанию) -->
                            <div class="booking-edit-mode" id="booking-edit-mode" style="display: none;">
                                <div class="booking-edit-field">
                                    <label for="edit-checkin-date">Заезд:</label>
                                    <input type="date" id="edit-checkin-date" name="checkin_date" value="<?php echo esc_attr( $checkin_date_formatted ); ?>" />
                                </div>
                                
                                <div class="booking-edit-field">
                                    <label for="edit-checkout-date">Выезд:</label>
                                    <input type="date" id="edit-checkout-date" name="checkout_date" value="<?php echo esc_attr( $checkout_date_formatted ); ?>" />
                                </div>
                                
                                <?php foreach ( $guests_values as $guest_name => $guest_data ) : ?>
                                <div class="booking-edit-field">
                                    <label for="edit-<?php echo esc_attr( $guest_name ); ?>"><?php echo esc_html( $guest_data['label'] ); ?>:</label>
                                    <input type="number" 
                                           id="edit-<?php echo esc_attr( $guest_name ); ?>" 
                                           name="<?php echo esc_attr( $guest_name ); ?>" 
                                           value="<?php echo esc_attr( $guest_data['value'] ); ?>" 
                                           min="<?php echo esc_attr( $guest_data['min'] ); ?>" 
                                           max="<?php echo esc_attr( $guest_data['max'] ); ?>" />
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="booking-edit-field">
                                    <label for="edit-booking-status">Статус:</label>
                                    <select id="edit-booking-status" name="booking_status">
                                        <?php foreach ( $booking_statuses as $status_key => $status_data ) : ?>
                                            <?php if ( in_array( $status_key, array( 'pending', 'new' ), true ) ) continue; ?>
                                            <option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $booking_status, $status_key ); ?>>
                                                <?php echo esc_html( $status_data['label'] ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="booking-edit-actions">
                                    <button type="button" id="booking-save-btn" class="button button-primary">
                                        <span class="dashicons dashicons-saved"></span> Сохранить
                                    </button>
                                    <button type="button" id="booking-cancel-btn" class="button">
                                        Отмена
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Hidden поле для booking_id -->
                            <input type="hidden" id="booking-message-id" value="<?php echo esc_attr( $booking_id ); ?>" />
                        </div>
                    <?php endif; ?>
                    
                <?php else : ?>
                    <div class="no-property-info">
                        <span class="dashicons dashicons-warning"></span>
                        <p>Это сообщение не привязано к объекту недвижимости</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
