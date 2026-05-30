<?php
/**
 * Метабокс "Характеристики" для объектов недвижимости
 * 
 * Двухколоночный UI для выбора характеристик из справочника:
 * - Слева: группы характеристик
 * - Справа: характеристики выбранной группы
 * - Drag-and-drop сортировка выбранных
 * 
 * @package Realty_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Регистрация метабокса "Характеристики" для CPT property
 */
function realty_register_characteristics_metabox() {
    add_meta_box(
        'property_characteristics_metabox',
        __( 'Характеристики', 'realty-theme' ),
        'realty_render_characteristics_metabox',
        'property',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'realty_register_characteristics_metabox' );

/**
 * Рендер метабокса "Характеристики"
 * 
 * @param WP_Post $post Объект поста
 */
function realty_render_characteristics_metabox( $post ) {
    // Получаем сохранённые характеристики
    $saved_characteristics = get_post_meta( $post->ID, 'property_characteristics', true );
    if ( ! is_array( $saved_characteristics ) ) {
        $saved_characteristics = array();
    }
    
    // Получаем все группы характеристик
    $groups = get_terms( array(
        'taxonomy'   => 'char_group',
        'hide_empty' => false,
        'orderby'    => 'meta_value_num',
        'meta_key'   => 'sort_order',
        'order'      => 'ASC',
    ) );

    // Подсчет выбранных характеристик в группах для отображения счетчиков
    $group_counts = array();
    foreach ( $saved_characteristics as $char_data ) {
        $group_id = isset( $char_data['group_id'] ) ? absint( $char_data['group_id'] ) : 0;
        if ( ! $group_id && isset( $char_data['characteristic_id'] ) ) {
            $char_terms = get_the_terms( absint( $char_data['characteristic_id'] ), 'char_group' );
            $group_id = ! empty( $char_terms ) && ! is_wp_error( $char_terms ) ? absint( $char_terms[0]->term_id ) : 0;
        }
        if ( $group_id ) {
            if ( ! isset( $group_counts[ $group_id ] ) ) {
                $group_counts[ $group_id ] = 0;
            }
            $group_counts[ $group_id ]++;
        }
    }
    
    // Nonce для безопасности
    wp_nonce_field( 'realty_save_characteristics', 'realty_characteristics_nonce' );
    
    ?>
    <div class="property-characteristics-metabox" id="property-characteristics-metabox">
        <div class="metabox-columns">
            <!-- Левая колонка: Группы -->
            <div class="metabox-column-left">
                <h3><?php esc_html_e( 'Группы', 'realty-theme' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Кликните для выбора', 'realty-theme' ); ?></p>
                <ul class="groups-list" id="char-groups-list">
                    <?php if ( ! empty( $groups ) ) : ?>
                        <?php foreach ( $groups as $index => $group ) : ?>
                            <?php $count = isset( $group_counts[ $group->term_id ] ) ? absint( $group_counts[ $group->term_id ] ) : 0; ?>
                            <li class="group-item<?php echo $index === 0 ? ' active' : ''; ?>" 
                                data-group-id="<?php echo esc_attr( $group->term_id ); ?>"
                                data-group-name="<?php echo esc_attr( $group->name ); ?>">
                                <span class="group-name"><?php echo esc_html( $group->name ); ?></span>
                                <span class="group-count" id="count-<?php echo esc_attr( $group->term_id ); ?>"><?php echo esc_html( $count ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li class="no-groups-notice">
                            <?php esc_html_e( 'Группы не созданы. Создайте их в Справочнике характеристик.', 'realty-theme' ); ?>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Выбранные характеристики -->
                <h3><?php esc_html_e( 'Выбранные', 'realty-theme' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Кликните + в группе для добавления', 'realty-theme' ); ?></p>
                <div class="selected-groups" id="selected-groups-list">
                    <?php 
                    // Группируем выбранные характеристики по группам
                    if ( ! empty( $saved_characteristics ) ) :
                        // Добавляем textarea с данными для сохранения
                        $json_value = htmlspecialchars( json_encode( $saved_characteristics ), ENT_QUOTES, 'UTF-8' );
                        ?>
                        <textarea name="property_characteristics_data" style="display:none;"><?php echo $json_value; ?></textarea>
                        <?php
                        $grouped = array();
                        foreach ( $saved_characteristics as $char_data ) :
                            $char_id = isset( $char_data['characteristic_id'] ) ? absint( $char_data['characteristic_id'] ) : 0;
                            if ( ! $char_id ) continue;
                            
                            $char = get_post( $char_id );
                            if ( ! $char ) continue;
                            
                            // Используем group_id из данных, если есть, иначе из терминов
                            $group_id = isset( $char_data['group_id'] ) ? absint( $char_data['group_id'] ) : 0;
                            if ( ! $group_id ) {
                                $char_terms = get_the_terms( $char_id, 'char_group' );
                                $group_id = ! empty( $char_terms ) && ! is_wp_error( $char_terms ) ? $char_terms[0]->term_id : 0;
                            }
                            $group_name = '';
                            if ( $group_id ) {
                                $group_term = get_term( $group_id, 'char_group' );
                                $group_name = $group_term && ! is_wp_error( $group_term ) ? $group_term->name : 'Без группы';
                            }
                            
                            if ( ! isset( $grouped[$group_id] ) ) {
                                $grouped[$group_id] = array(
                                    'name' => $group_name,
                                    'items' => array()
                                );
                            }
                            
                            $grouped[$group_id]['items'][] = array(
                                'id' => $char_id,
                                'post' => $char,
                                'order' => isset( $char_data['order'] ) ? $char_data['order'] : 0
                            );
                        endforeach;
                        
                        // Сортируем группы по минимальному order характеристик в них
                        uasort( $grouped, function( $a, $b ) {
                            $min_order_a = min( array_column( $a['items'], 'order' ) );
                            $min_order_b = min( array_column( $b['items'], 'order' ) );
                            return $min_order_a - $min_order_b;
                        } );
                        
                        // Выводим группы
                        foreach ( $grouped as $group_id => $group_data ) :
                            // Сортируем характеристики внутри группы по order
                            usort( $group_data['items'], function( $a, $b ) {
                                return $a['order'] - $b['order'];
                            } );
                            ?>
                            <div class="selected-group" data-group-id="<?php echo esc_attr( $group_id ); ?>">
                                <div class="selected-group-header">
                                    <span class="dashicons dashicons-menu drag-handle"></span>
                                    <span class="group-name"><?php echo esc_html( $group_data['name'] ); ?></span>
                                </div>
                                <ul class="selected-items">
                                    <?php foreach ( $group_data['items'] as $item ) : 
                                        $char_id = $item['id'];
                                        $char = $item['post'];
                                        $icon = get_post_meta( $char_id, 'icon', true );
                                        $icon_type = get_post_meta( $char_id, 'icon_type', true );
                                        $media_id = get_post_meta( $char_id, 'media_id', true );
                                        ?>
                                        <li class="selected-item" data-char-id="<?php echo esc_attr( $char_id ); ?>">
                                            <span class="char-icon">
                                                <?php 
                                                if ( $icon_type === 'upload' && $media_id ) {
                                                    echo wp_get_attachment_image( $media_id, array( 24, 24 ), false );
                                                } elseif ( $icon ) {
                                                    echo '<span class="material-symbols-outlined">' . esc_html( $icon ) . '</span>';
                                                }else {
                                                    echo '<span class="dashicons dashicons-format-image"></span>';
                                                }
                                                ?>
                                            </span>
                                            <span class="char-title"><?php echo esc_html( $char->post_title ); ?></span>
                                            <span class="remove-btn" data-char-id="<?php echo esc_attr( $char_id ); ?>">&times;</span>
                                            <input type="hidden" name="property_characteristics[<?php echo esc_attr( $group_id ); ?>][]" value="<?php echo esc_attr( $char_id ); ?>">
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php
                        endforeach;
                    else : ?>
                        <p class="no-selection-notice">
                            <?php esc_html_e( 'Кликните на характеристику для добавления', 'realty-theme' ); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Правая колонка: Характеристики -->
            <div class="metabox-column-right">
                <h3><?php esc_html_e( 'Доступные характеристики', 'realty-theme' ); ?></h3>
                <div class="available-controls">
                    <input type="text" id="characteristics-search" class="characteristics-search" 
                           placeholder="<?php esc_attr_e( 'Поиск характеристик...', 'realty-theme' ); ?>">
                    <select id="characteristics-per-page" class="characteristics-per-page">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="-1"><?php esc_html_e( 'Все', 'realty-theme' ); ?></option>
                    </select>
                </div>
                <div class="available-characteristics" id="available-characteristics-list">
                    <?php if ( ! empty( $groups ) ) : ?>
                        <?php 
                        // Показываем характеристики первой группы по умолчанию
                        $first_group_id = $groups[0]->term_id;
                        $characteristics = realty_get_characteristics_by_group( $first_group_id );
                        ?>
                        <?php if ( ! empty( $characteristics ) ) : ?>
                            <ul class="characteristics-source-list">
                                <?php foreach ( $characteristics as $char ) : 
                                    // Проверяем, уже ли выбрана
                                    $is_selected = in_array( $char->ID, array_column( $saved_characteristics, 'characteristic_id' ) );
                                    ?>
                                    <li class="characteristic-item<?php echo $is_selected ? ' selected' : ''; ?>" 
                                        data-char-id="<?php echo esc_attr( $char->ID ); ?>">
                                        <span class="dashicons dashicons-menu drag-handle"></span>
                                        <span class="char-icon">
                                            <?php 
                                            $icon = get_post_meta( $char->ID, 'icon', true );
                                            $icon_type = get_post_meta( $char->ID, 'icon_type', true );
                                            $media_id = get_post_meta( $char->ID, 'media_id', true );
                                            
                                            if ( $icon_type === 'upload' && $media_id ) {
                                                echo wp_get_attachment_image( $media_id, array( 24, 24 ), false );
                                            } elseif ( $icon ) {
                                                echo '<span class="material-symbols-outlined">' . esc_html( $icon ) . '</span>';
                                            }
                                            ?>
                                        </span>
                                        <span class="char-title"><?php echo esc_html( $char->post_title ); ?></span>
                                        <?php if ( $is_selected ) : ?>
                                            <span class="already-selected">✓</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p class="no-characteristics-notice">
                                <?php esc_html_e( 'В этой группе нет характеристик.', 'realty-theme' ); ?>
                            </p>
                        <?php endif; ?>
                    <?php else : ?>
                        <p class="no-groups-available">
                            <?php esc_html_e( 'Справочник характеристик пуст. Создайте группы и характеристики в Справочнике.', 'realty-theme' ); ?>
                            <a href="<?php echo admin_url( 'edit.php?post_type=property&page=characteristics-reference' ); ?>">
                                <?php esc_html_e( 'Перейти к Справочнику', 'realty-theme' ); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Получение характеристик по группе
 * 
 * @param int $group_id ID термина группы
 * @return array Массив объектов постов характеристик
 */
function realty_get_characteristics_by_group( $group_id ) {
    $args = array(
        'post_type'      => 'characteristic',
        'post_status'   => 'publish',
        'posts_per_page' => -1,
        'tax_query'      => array(
            array(
                'taxonomy' => 'char_group',
                'field'    => 'term_id',
                'terms'   => $group_id,
            ),
        ),
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'sort_order',
        'order'         => 'ASC',
    );
    
    $query = new WP_Query( $args );
    return $query->posts;
}

/**
 * Сохранение характеристик объекта недвижимости
 * 
 * @param int $post_id ID поста
 */
function realty_save_property_characteristics( $post_id ) {
    error_log('=== SAVE FUNCTION CALLED post_id: ' . $post_id . ' ===');
    
    // Проверка_autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    // Проверка nonce
    if ( ! isset( $_POST['realty_characteristics_nonce'] ) ) {
        return;
    }
    
    if ( ! wp_verify_nonce( $_POST['realty_characteristics_nonce'], 'realty_save_characteristics' ) ) {
        return;
    }
    
    // Проверка прав
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // Отладка - что приходит
    error_log('property_characteristics_data: ' . (isset($_POST['property_characteristics_data']) ? $_POST['property_characteristics_data'] : 'EMPTY'));
    error_log('property_characteristics: ' . (isset($_POST['property_characteristics']) ? print_r($_POST['property_characteristics'], true) : 'EMPTY'));
    
    // Сохранение характеристик - используем JSON (игнорируем старый массив если есть JSON)
    $json_data = isset( $_POST['property_characteristics_data'] ) ? stripslashes( $_POST['property_characteristics_data'] ) : '';
    error_log('json_data after stripslashes: ' . $json_data);
    
    $data = array();
    
    // Новый формат JSON (приоритет)
    if ( ! empty( $json_data ) ) {
        $decoded_json = html_entity_decode( $json_data );
        error_log('decoded_json: ' . $decoded_json);
        $decoded = json_decode( $decoded_json, true );
        $decode_count = is_array($decoded) ? count($decoded) : 0;
        error_log('JSON decoded: ' . (is_array($decoded) ? 'OK' : 'FAIL') . ' count: ' . $decode_count);
        if ( is_array( $decoded ) && $decode_count > 0 ) {
            $data = $decoded;
            error_log('Using JSON data: ' . count($data) . ' items');
        }
    }
    
    if ( ! empty( $data ) ) {
        // Обновляем мета строго одним значением
        $result = update_post_meta( $post_id, 'property_characteristics', $data );
        error_log('Saved to post_meta: ' . ($result !== false ? 'OK' : 'FAIL') . ' post_id: ' . $post_id . ' count: ' . count($data));
    } else {
        delete_post_meta( $post_id, 'property_characteristics' );
        error_log('No data - deleted meta');
    }
}
add_action( 'save_post_property', 'realty_save_property_characteristics' );

/**
 * AJAX: Получение характеристик по группе для метабокса
 */
add_action( 'wp_ajax_realty_get_characteristics_by_group', 'realty_ajax_get_characteristics_by_group' );
add_action( 'wp_ajax_nopriv_realty_get_characteristics_by_group', 'realty_ajax_get_characteristics_by_group' );

function realty_ajax_get_characteristics_by_group() {
    // Проверка nonce
    if ( ! check_ajax_referer( 'realty_characteristics_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed' ) );
    }
    
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
    $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
    
    if ( ! $group_id ) {
        wp_send_json_error( array( 'message' => 'Group ID required' ) );
    }
    
    $args = array(
        'post_type'      => 'characteristic',
        'post_status'   => 'publish',
        'posts_per_page' => $per_page,
        'paged'        => $page,
        'tax_query'     => array(
            array(
                'taxonomy' => 'char_group',
                'field'    => 'term_id',
                'terms'   => $group_id,
            ),
        ),
        'orderby'       => 'meta_value_num',
        'meta_key'      => 'sort_order',
        'order'         => 'ASC',
    );
    
    // Добавляем поиск
    if ( $search ) {
        $args['s'] = $search;
    }
    
    $query = new WP_Query( $args );
    $total = $query->found_posts;
    $total_pages = $query->max_num_pages;
    
    $result = array();
    foreach ( $query->posts as $char ) {
        $icon = get_post_meta( $char->ID, 'icon', true );
        $icon_type = get_post_meta( $char->ID, 'icon_type', true );
        $media_id = get_post_meta( $char->ID, 'media_id', true );
        $label = get_post_meta( $char->ID, 'label', true );
        $value = get_post_meta( $char->ID, 'value', true );
        $hint = get_post_meta( $char->ID, 'hint', true );
        
        $media_url = '';
        if ( $icon_type === 'upload' && $media_id ) {
            $media_url = wp_get_attachment_url( $media_id );
        }
        
        $result[] = array(
            'ID'         => $char->ID,
            'title'      => $char->post_title,
            'icon'       => $icon,
            'icon_type'  => $icon_type,
            'media_url'  => $media_url,
            'label'      => $label,
            'value'      => $value,
            'hint'       => $hint,
        );
    }
    
    wp_send_json_success( array(
        'items'        => $result,
        'total'        => $total,
        'total_pages'  => $total_pages,
        'current_page' => $page,
    ) );
}

/**
 * AJAX: Сохранение характеристик объекта
 */
add_action( 'wp_ajax_realty_save_property_characteristics', 'realty_ajax_save_property_characteristics' );

function realty_ajax_save_property_characteristics() {
    // Проверка nonce
    if ( ! check_ajax_referer( 'realty_characteristics_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed' ) );
    }
    
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    
    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => 'Post ID required' ) );
    }
    
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }
    
    $characteristics = isset( $_POST['characteristics'] ) ? array_map( 'absint', $_POST['characteristics'] ) : array();
    $characteristics = array_filter( $characteristics );
    
    // Формируем массив с порядком
    $data = array();
    foreach ( $characteristics as $index => $char_id ) {
        $data[] = array(
            'characteristic_id' => $char_id,
            'order'            => $index + 1,
        );
    }
    
    if ( ! empty( $data ) ) {
        update_post_meta( $post_id, 'property_characteristics', $data );
    } else {
        delete_post_meta( $post_id, 'property_characteristics' );
    }
    
    wp_send_json_success();
}

/**
 * Подключение CSS и JS для метабокса характеристик
 * 
 * @param string $hook Текущая страница админки
 */
function realty_enqueue_characteristics_metabox_assets( $hook ) {
    $screen = get_current_screen();
    
    // Подключаем только на странице редактирования property
    if ( $screen->post_type !== 'property' || $screen->base !== 'post' ) {
        return;
    }
    
    // jQuery UI
    wp_enqueue_script( 'jquery-ui-core' );
    wp_enqueue_script( 'jquery-ui-sortable' );
    
    // Подключаем JS
    wp_enqueue_script(
        'realty-property-characteristics',
        get_template_directory_uri() . '/inc/admin/assets/js/property-characteristics.js',
        array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ),
        '1.0.0',
        true
    );
    // Material Symbols CDN
    wp_enqueue_style(
            'material-symbols-outlined',
            'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0',
            array(),
            '1.0.0'
    );
    // Подключаем CSS
    wp_enqueue_style(
        'realty-property-characteristics',
        get_template_directory_uri() . '/inc/admin/assets/css/property-characteristics.css',
        array(),
        '1.0.0'
    );

    wp_add_inline_style( 'realty-property-characteristics', '
        .pods-currency-container { position: relative; display: inline-flex; align-items: center; width: 100%; }
        .pods-currency-container .pods-currency-sign { position: absolute; left: 8px; z-index: 1; pointer-events: none; font-style: normal; color: #555; font-family: inherit; font-size: inherit; background: none; border: none; padding: 0; }
        .pods-currency-container .pods-form-ui-field-type-currency { padding-left: 24px !important; width: 100%; box-sizing: border-box; }
    ' );
    
    // Localize данные
    wp_localize_script( 'realty-property-characteristics', 'realtyCharacteristicsData', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'realty_characteristics_nonce' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'realty_enqueue_characteristics_metabox_assets' );

function realty_save_hours_limit( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! isset( $_POST['realty_hours_limit_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['realty_hours_limit_nonce'], 'realty_save_hours_limit' ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $value = isset( $_POST['property_hours_limit'] ) ? sanitize_text_field( $_POST['property_hours_limit'] ) : '';
    update_post_meta( $post_id, 'hours_limit', $value );
}
add_action( 'save_post_property', 'realty_save_hours_limit' );

/**
 * Встраиваем select hours_limit прямо после поля price в метабоксе Pods
 * через хук pods_meta_meta_post_post_row_price
 */
function realty_render_hours_limit_inline( $post, $field, $pod ) {
    $saved   = get_post_meta( $post->ID, 'hours_limit', true );
    $options = realty_get_chars_by_system_template( 'hours_limit' );
    ?>
    <tr class="form-field pods-field-input">
        <th scope="row">
            <label for="property_hours_limit"><?php esc_html_e( 'Период цены', 'realty-theme' ); ?></label>
        </th>
        <td>
            <select id="property_hours_limit" name="property_hours_limit" style="width:100%;max-width:400px;">
                <option value=""><?php esc_html_e( '---', 'realty-theme' ); ?></option>
                <?php foreach ( $options as $opt ) : ?>
                    <option value="<?php echo esc_attr( $opt['value'] ); ?>" <?php selected( $saved, $opt['value'] ); ?>>
                        <?php echo esc_html( $opt['label'] ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ( empty( $options ) ) : ?>
                <p class="description"><?php esc_html_e( 'Создайте группу с шаблоном «Период аренды» в Справочнике', 'realty-theme' ); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
add_action( 'pods_meta_meta_post_post_row_price', 'realty_render_hours_limit_inline', 10, 3 );
