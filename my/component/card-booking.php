<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================
// АРХИТЕКТУРА: Booking Request — единственный источник данных
// ============================================================
// Все данные (даты, статус, гости) читаются из booking_request.
// SESSION имеет приоритет только если status = "new".
// URL параметры НЕ влияют на отображение данных бронирования.
// Единственный параметр: property_id (для поиска booking_request).
// ============================================================

// Получаем параметры из $args
$property_id    = $args['property_id'] ?? 0;
$owner_id       = $args['owner_id'] ?? 0;
$args_booking_id = $args['booking_id'] ?? 0;
$args_status    = $args['booking_status'] ?? 'new';
$args_thread_id = $args['thread_id'] ?? '';

// Инициализируем переменные с дефолтными значениями
$booking_status = $args_status;
$checkin_date   = '';
$checkout_date  = '';
$guests_text    = '';
$booking_found  = false;
$booking_id     = $args_booking_id;
$thread_id      = $args_thread_id;

// ============================================================
// ШАГ 1: Если booking_id передан из dashboard — используем его
// ============================================================
if ( $booking_id > 0 ) {
    error_log( '[card-booking] ШАГ 1: booking_id=' . $booking_id . ' передан из dashboard' );
    $booking_data = realty_get_booking_data( $booking_id );

    if ( $booking_data ) {
        error_log( '[card-booking] booking_data found: ' . print_r( $booking_data, true ) );
        $booking_found = true;
        $booking_status = $booking_data['status'];
        $checkin_date = $booking_data['checkin_date'];
        $checkout_date = $booking_data['checkout_date'];
        $thread_id = $booking_data['thread_id'];
        
        // Проверяем есть ли данные в booking_request
        $booking_has_data = ! empty( $checkin_date ) || ! empty( $booking_data['guests_count'] );
        
        // Форматируем гостей
        $guests_count = $booking_data['guests_count'];
        if ( ! empty( $guests_count ) && is_array( $guests_count ) ) {
            $guests_parts = array();
            $guest_types_config = realty_get_guest_types_config();
            
            foreach ( $guest_types_config as $guest_type ) {
                if ( empty( $guest_type['enabled'] ) ) {
                    continue;
                }
                $guest_name = $guest_type['name'];
                $guest_value = $guests_count[ $guest_name ] ?? 0;
                
                if ( $guest_value > 0 ) {
                    $guests_parts[] = $guest_value . ' ' . $guest_type['label'];
                }
            }
            
            $guests_text = implode( ', ', $guests_parts );
        }
        
        // Если guests_count пустой И статус=new И есть SESSION — используем SESSION для гостей
        error_log( '[card-booking] Проверка SESSION для гостей: guests_count=' . ( empty( $booking_data['guests_count'] ) ? 'EMPTY' : 'HAS_DATA' ) . ', status=' . $booking_status );
        
        if ( empty( $booking_data['guests_count'] ) && $booking_status === 'new' ) {
            $session_data = realty_get_booking_session();
            error_log( '[card-booking] SESSION для гостей: ' . ( $session_data ? 'FOUND' : 'NOT FOUND' ) );
            
            if ( $session_data && $session_data['property_id'] == $property_id ) {
                error_log( '[card-booking] guests_count пустой, используем SESSION для гостей' );
                error_log( '[card-booking] SESSION guests_count raw: ' . ( $session_data['guests_count'] ?? 'NOT SET' ) );
                
                // Обновляем даты из SESSION если они пустые
                if ( empty( $checkin_date ) ) {
                    $checkin_date = $session_data['checkin_date'] ?? '';
                }
                if ( empty( $checkout_date ) ) {
                    $checkout_date = $session_data['checkout_date'] ?? '';
                }
                
                // Декодируем гостей из SESSION
                $guests_count = array();
                if ( ! empty( $session_data['guests_count'] ) ) {
                    $decoded = $session_data['guests_count'];
                    if ( is_string( $decoded ) ) {
                        $decoded = json_decode( $decoded, true );
                    }
                    if ( is_string( $decoded ) ) {
                        $decoded = json_decode( $decoded, true );
                    }
                    if ( is_array( $decoded ) ) {
                        $guests_count = $decoded;
                    }
                }
                
                // Форматируем текст гостей
                $guests_parts = array();
                $guest_types_config = realty_get_guest_types_config();
                foreach ( $guest_types_config as $guest_type ) {
                    if ( empty( $guest_type['enabled'] ) ) {
                        continue;
                    }
                    $guest_name = $guest_type['name'];
                    $guest_value = $guests_count[ $guest_name ] ?? 0;
                    if ( $guest_value > 0 ) {
                        $guests_parts[] = $guest_value . ' ' . $guest_type['label'];
                    }
                }
                $guests_text = implode( ', ', $guests_parts );
                error_log( '[card-booking] Guests from SESSION: ' . $guests_text );
            }
        }
    }
}

// ============================================================
// ШАГ 2: Если booking_id нет — проверяем SESSION
// ============================================================
if ( ! $booking_found ) {
    $session_data = realty_get_booking_session();
    
    // DEBUG: Логируем SESSION
    error_log( '[card-booking] SESSION check: args[property_id]=' . $property_id . ', session=' . ( $session_data ? 'FOUND' : 'EMPTY' ) );
    if ( $session_data ) {
        error_log( '[card-booking] SESSION data: ' . print_r( $session_data, true ) );
        error_log( '[card-booking] SESSION property_id match? ' . ( $session_data['property_id'] == $property_id ? 'YES' : 'NO - SESSION has ' . $session_data['property_id'] ) );
    }
    
    if ( $session_data && $property_id > 0 ) {
        // SESSION есть — используем его данные (для status=new)
        // ВАЖНО: Проверяем что property_id из URL совпадает с SESSION
        if ( $session_data['property_id'] != $property_id ) {
            error_log( '[card-booking] property_id mismatch! URL=' . $property_id . ', SESSION=' . $session_data['property_id'] . ' - SKIPPING' );
        } else {
            error_log( '[card-booking] Using SESSION data for property_id=' . $property_id );
        $booking_status = 'new';
        $checkin_date = $session_data['checkin_date'] ?? '';
        $checkout_date = $session_data['checkout_date'] ?? '';
        
        // Декодируем гостей
        $guests_count = array();
        if ( ! empty( $session_data['guests_count'] ) ) {
            // guests_count может быть уже JSON строкой или массивом
            $decoded = $session_data['guests_count'];
            
            // Если строка - декодируем JSON
            if ( is_string( $decoded ) ) {
                $decoded = json_decode( $decoded, true );
            }
            
            // Если все еще строка (двойное кодирование) - декодируем еще раз
            if ( is_string( $decoded ) ) {
                $decoded = json_decode( $decoded, true );
            }
            
            if ( is_array( $decoded ) ) {
                $guests_count = $decoded;
            }
            
            error_log( '[card-booking] Guests decoded: ' . print_r( $guests_count, true ) );
        }
        
        // Fallback на legacy
        if ( empty( $guests_count ) ) {
            error_log( '[card-booking] Guests empty, trying legacy format' );
            if ( isset( $session_data['adults'] ) && $session_data['adults'] > 0 ) {
                $guests_count['adults'] = $session_data['adults'];
            }
            if ( isset( $session_data['children'] ) && $session_data['children'] > 0 ) {
                $guests_count['children'] = $session_data['children'];
            }
        }
        
        // Форматируем текст гостей
        $guests_parts = array();
        $guest_types_config = realty_get_guest_types_config();
        
        foreach ( $guest_types_config as $guest_type ) {
            if ( empty( $guest_type['enabled'] ) ) {
                continue;
            }
            $guest_name = $guest_type['name'];
            $guest_value = $guests_count[ $guest_name ] ?? 0;
            
            if ( $guest_value > 0 ) {
                $guests_parts[] = $guest_value . ' ' . $guest_type['label'];
            }
        }
        
        $guests_text = implode( ', ', $guests_parts );
        } // close else (property_id match)
    } // close if (session_data && property_id > 0)
}

// ============================================================
// ШАГ 3: Если сообщение НЕ найдено — карточка пустая
// ============================================================
// Это нормальная ситуация:
// - Клиент ещё не отправил сообщение
// - Или thread_id не соответствует ни одному сообщению
// Карточка покажет только информацию об объекте (property),
// но не покажет даты/статус/гостей (так как их нет в БД).
// ============================================================

// Получаем статус из хелпера
$current_status = realty_get_booking_status_data( $booking_status );

// Получить данные объекта недвижимости (всегда из БД)
$property_data = null;
$property_price = '';
$property_location = '';
$property_address = '';
$property_lat = '';
$property_lng = '';
$property_rating = '4.9';
$property_reviews = '128';

if ( $property_id > 0 ) {
    $property = get_post( $property_id );
    if ( $property ) {
        // Цена
        $price_raw = get_post_meta($property_id, 'price', true);
        if ($price_raw) {
            $property_price = number_format((float) $price_raw, 0, '.', ' ') . ' ₽';
        }
        
        // Локация (город из таксономии)
        $location_terms = get_the_terms($property_id, 'location');
        if ($location_terms && !is_wp_error($location_terms)) {
            $property_location = $location_terms[0]->name;
        }
        
        // Адрес из Pods поля
        $pod = pods('property', $property_id);
        if ($pod && $pod->exists()) {
            $property_address = $pod->field('address') ?? '';
            $property_lat = $pod->field('latitude') ?? '';
            $property_lng = $pod->field('longitude') ?? '';
        }
        
        // Хозяин
        $author_id = get_post_field('post_author', $property_id);
        $owner_name = '';
        if ($author_id) {
            $first_name = get_the_author_meta('first_name', (int) $author_id);
            $last_name = get_the_author_meta('last_name', (int) $author_id);
            
            if ($first_name || $last_name) {
                $owner_name = trim($first_name . ' ' . $last_name);
            } else {
                $owner_name = get_the_author_meta('display_name', (int) $author_id);
            }
        }
        
        $property_data = array(
            'title'    => $property->post_title,
            'image'    => get_the_post_thumbnail_url($property_id, 'property-teaser'),
            'author_id' => $author_id,
            'owner_name' => $owner_name
        );
    }
}

// ============================================================
// ШАГ 4: Форматируем даты для отображения
// ============================================================
$formatted_checkin  = '';
$formatted_checkout = '';

if ( $checkin_date ) {
    $timestamp = strtotime( $checkin_date );
    if ( $timestamp ) {
        setlocale( LC_TIME, 'ru_RU.UTF-8' );
        $formatted_checkin = strftime( '%d %B, %Y', $timestamp );
        $formatted_checkin = rtrim( $formatted_checkin, '.' );
    }
}

if ( $checkout_date ) {
    $timestamp = strtotime( $checkout_date );
    if ( $timestamp ) {
        setlocale( LC_TIME, 'ru_RU.UTF-8' );
        $formatted_checkout = strftime( '%d %B, %Y', $timestamp );
        $formatted_checkout = rtrim( $formatted_checkout, '.' );
    }
}
?>

<div class="my-booking-card">
    <!-- Заголовок карточки -->
    <div class="my-booking-header">
        <div class="my-booking-image">
            <?php if ($property_data && $property_data['image']) : ?>
                <img src="<?php echo esc_url($property_data['image']); ?>" alt="<?php echo esc_attr($property_data['title']); ?>" />
            <?php endif; ?>
        </div>
        <div class="my-booking-info">
            <div class="my-booking-rating">
                <span class="material-symbols-outlined my-rating-star">star</span>
                <span class="my-rating-value"><?php echo esc_html($property_rating); ?> (<?php echo esc_html($property_reviews); ?>)</span>
            </div>
            <h2 class="my-booking-title">
                <?php echo $property_data ? esc_html($property_data['title']) : 'Объект недвижимости'; ?>
            </h2>
            <div class="my-booking-status status-badge status-<?php echo esc_attr( $current_status['class'] ); ?>">
                <?php echo esc_html( strtoupper( $current_status['label'] ) ); ?>
            </div>
        </div>
        <?php if ($property_price) : ?>
        <div class="my-booking-price">
            <div class="my-price-amount"><?php echo esc_html($property_price); ?></div>
            <div class="my-price-period"><?php esc_html_e('ЗА СУТКИ', 'realty-theme'); ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Детали проживания -->
    <div class="my-booking-details">
        <div class="my-details-section">
            <h3 class="my-details-title"><?php esc_html_e('ДЕТАЛИ ПРОЖИВАНИЯ', 'realty-theme'); ?></h3>
            
            <?php if ($formatted_checkin) : ?>
            <div class="my-details-row">
                <span class="my-details-label"><?php esc_html_e('Дата заезда', 'realty-theme'); ?></span>
                <span class="my-details-value"><?php echo esc_html($formatted_checkin); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($formatted_checkout) : ?>
            <div class="my-details-row">
                <span class="my-details-label"><?php esc_html_e('Дата выезда', 'realty-theme'); ?></span>
                <span class="my-details-value"><?php echo esc_html($formatted_checkout); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($guests_text) : ?>
            <div class="my-details-row">
                <span class="my-details-label"><?php esc_html_e('Гости', 'realty-theme'); ?></span>
                <span class="my-details-value"><?php echo esc_html($guests_text); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($property_data && $property_data['owner_name']) : ?>
            <div class="my-details-row">
                <span class="my-details-label"><?php esc_html_e('Хозяин', 'realty-theme'); ?></span>
                <span class="my-details-value my-details-value--link my-host-name-trigger" 
                      data-owner-id="<?php echo esc_attr($property_data['author_id']); ?>"
                      data-owner-name="<?php echo esc_attr($property_data['owner_name']); ?>">
                    <?php echo esc_html($property_data['owner_name']); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($property_address) : ?>
        <div class="my-details-section">
            <h3 class="my-details-title"><?php esc_html_e('РАСПОЛОЖЕНИЕ', 'realty-theme'); ?></h3>
            <?php if ($property_location) : ?>
                <div class="my-details-row">
                    <span class="my-details-label"><?php esc_html_e('Город', 'realty-theme'); ?></span>
                    <span class="my-details-value"><?php echo esc_html($property_location); ?></span>
                </div>
            <?php endif; ?>
            <div class="my-details-row">
                <span class="my-details-label"><?php esc_html_e('Адрес', 'realty-theme'); ?></span>
                <span class="my-details-value"><?php echo esc_html($property_address); ?></span>
            </div>
            <div class="my-details-row">
                <a href="#" class="my-details-link" id="show-property-map" data-lat="<?php echo esc_attr($property_lat); ?>" data-lng="<?php echo esc_attr($property_lng); ?>" data-address="<?php echo esc_attr($property_address); ?>">
                    <span class="material-symbols-outlined">navigation</span>
                    <span><?php esc_html_e('Показать маршрут', 'realty-theme'); ?></span>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Vue 3 Map Modal -->
<div id="vue-property-map-modal">
    <el-dialog v-model="visible" :title="'Местоположение: <?php echo esc_html($property_location); ?>, <?php echo esc_html($property_address); ?>'" width="800">
        <app-content-map 
            v-if="visible && propertyLat && propertyLng"
            :latitude="propertyLat" 
            :longitude="propertyLng"
            map-id="property-map-modal"
            :zoom="15"
            height="500px"
        />
    </el-dialog>
</div>

<!-- Vue 3 Host Profile Modal -->
<div id="vue-host-profile-modal"></div>

<script type="module">
    (function() {
        const { createAppModule, AppContentMap, HostProfileModal } = window.VueAppModule;
        const { ref, onMounted } = Vue;

        // Map Modal App
        const AppPropertyMapModal = createAppModule({
            components: {
                AppContentMap
            },
            setup() {
                const visible = ref(false);
                const propertyLat = ref(0);
                const propertyLng = ref(0);

                onMounted(() => {
                    const trigger = document.getElementById('show-property-map');
                    if (trigger) {
                        trigger.addEventListener('click', function(e) {
                            e.preventDefault();
                            const lat = this.dataset.lat;
                            const lng = this.dataset.lng;
                            if (lat && lng) {
                                propertyLat.value = parseFloat(lat);
                                propertyLng.value = parseFloat(lng);
                                visible.value = true;
                            }
                        });
                    }
                });

                return {
                    visible,
                    propertyLat,
                    propertyLng
                };
            }
        });

        AppPropertyMapModal.component('el-dialog', window.VueAppModule.ElDialog);
        AppPropertyMapModal.mount('#vue-property-map-modal');

        // Host Profile Modal App
        const AppHostProfileModal = createAppModule({
            components: {
                HostProfileModal
            },
            setup() {
                const hostProfileRef = ref(null);

                onMounted(() => {
                    // Listen for clicks on host name
                    document.addEventListener('click', function(e) {
                        const hostTrigger = e.target.closest('.my-host-name-trigger');
                        if (hostTrigger) {
                            e.preventDefault();
                            const ownerId = hostTrigger.dataset.ownerId;
                            const ownerName = hostTrigger.dataset.ownerName;
                            
                            if (hostProfileRef.value) {
                                hostProfileRef.value.openModal(ownerId, ownerName);
                            }
                        }
                    });
                });

                return {
                    hostProfileRef
                };
            },
            template: '<host-profile-modal ref="hostProfileRef" />'
        });

        AppHostProfileModal.component('el-dialog', window.VueAppModule.ElDialog);
        AppHostProfileModal.mount('#vue-host-profile-modal');
    })();
</script>
