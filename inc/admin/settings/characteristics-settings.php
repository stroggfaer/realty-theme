<?php
/**
 * Страница управления справочником характеристик недвижимости
 * 
 * Позволяет администраторам создавать и управлять:
 * - Группами характеристик (char_group taxonomy)
 * - Характеристиками (characteristic CPT)
 * 
 * @package Realty_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper: получить массив опций для стиля отображения
 * 
 * Используется в форме группы и формы характеристики
 * 
 * @param string $selected Выбранное значение
 * @param bool $as_options Вернуть как HTML option теги
 * @return string|array HTML options или массив данных
 */
function realty_get_display_style_options( $selected = 'standard', $as_options = true ) {
    $styles = array(
        'circle'     => __( 'Circle — Круглая иконка с лейблом', 'realty-theme' ),
        'standard'   => __( 'Standard — Стандартная иконка и текст', 'realty-theme' ),
        'prohibited' => __( 'Prohibited — Зачеркнутый (запрет)', 'realty-theme' ),
        'text'       => __( 'Text — Только текст', 'realty-theme' ),
    );
    
    if ( ! $as_options ) {
        return $styles;
    }
    
    $html = '';
    foreach ( $styles as $value => $label ) {
        $html .= '<option value="' . esc_attr( $value ) . '" ' . selected( $selected, $value, false ) . '>';
        $html .= esc_html( $label );
        $html .= '</option>';
    }
    
    return $html;
}

/**
 * Регистрация страницы справочника характеристик в меню админки
 * 
 * Добавляет подменю "Справочник" в раздел "Недвижимость"
 */
function realty_register_characteristics_admin_page() {
    add_submenu_page(
        'edit.php?post_type=property',      // Родительское меню (Недвижимость)
        'Справочник Характеристик',          // Заголовок страницы
        'Справочник',                        // Название в меню
        'manage_options',                    // Требуемые права
        'characteristics-reference',         // Slug страницы
        'realty_characteristics_page_content' // Callback функция
    );
}
add_action( 'admin_menu', 'realty_register_characteristics_admin_page' );

/**
 * Основной контент страницы справочника характеристик
 * 
 * Отображает интерфейс управления группами и характеристиками
 */
function realty_characteristics_page_content() {
    // Проверка прав доступа
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'У вас недостаточно прав для доступа к этой странице.', 'realty-theme' ) );
    }
    
    // Получаем все группы характеристик
    $groups = get_terms( array(
        'taxonomy'   => 'char_group',
        'hide_empty' => false,
        'orderby'    => 'meta_value_num',
        'meta_key'   => 'sort_order',
        'order'      => 'ASC',
    ) );
    
    // Текущая выбранная группа
    $current_group_id = isset( $_GET['group_id'] ) ? absint( $_GET['group_id'] ) : 0;
    
    ?>
    <div class="wrap characteristics-admin-wrap">
        <h1 class="wp-heading-inline">
            <?php esc_html_e( 'Справочник Характеристик', 'realty-theme' ); ?>
        </h1>
        
        <hr class="wp-header-end">
        
        <div class="characteristics-admin-container">
            <!-- Левая колонка: Список групп -->
            <div class="characteristics-groups-panel">
                <h2><?php esc_html_e( 'Группы', 'realty-theme' ); ?></h2>
                
                <button type="button" class="button button-primary" id="add-new-group-btn">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e( 'Новая группа', 'realty-theme' ); ?>
                </button>
                
                <ul class="characteristics-groups-list">
                    <?php if ( ! empty( $groups ) && ! is_wp_error( $groups ) ) : ?>
                        <?php foreach ( $groups as $group ) : ?>
                            <?php 
                            $display_style = get_term_meta( $group->term_id, 'display_style', true ) ?: 'standard';
                            $group_key = get_term_meta( $group->term_id, 'group_key', true );
                            ?>
                            <li class="group-item <?php echo ( $group->term_id === $current_group_id ) ? 'active' : ''; ?>" 
                                data-group-id="<?php echo esc_attr( $group->term_id ); ?>"
                                data-display-style="<?php echo esc_attr( $display_style ); ?>"
                                data-group-key="<?php echo esc_attr( $group_key ); ?>">
                                <a href="<?php echo esc_url( add_query_arg( 'group_id', $group->term_id ) ); ?>">
                                    <?php echo esc_html( $group->name ); ?>
                                </a>
                                <span class="group-actions">
                                    <button type="button" class="button-link edit-group-btn" 
                                            data-group-id="<?php echo esc_attr( $group->term_id ); ?>"
                                            title="<?php esc_attr_e( 'Редактировать', 'realty-theme' ); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
                                    <button type="button" class="button-link delete-group-btn" 
                                            data-group-id="<?php echo esc_attr( $group->term_id ); ?>"
                                            data-group-name="<?php echo esc_attr( $group->name ); ?>"
                                            title="<?php esc_attr_e( 'Удалить', 'realty-theme' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li class="no-groups">
                            <?php esc_html_e( 'Нет групп. Создайте первую!', 'realty-theme' ); ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Правая колонка: Характеристики выбранной группы -->
            <div class="characteristics-values-panel">
                <?php if ( $current_group_id ) : ?>
                    <?php
                    // Получаем информацию о текущей группе
                    $current_group = get_term( $current_group_id, 'char_group' );
                    
                    if ( $current_group && ! is_wp_error( $current_group ) ) :
                    ?>
                        <h2>
                            <?php echo esc_html( $current_group->name ); ?>
                            <span class="group-meta-info">
                                <?php
                                $type_ui = get_term_meta( $current_group_id, 'type_ui', true );
                                if ( $type_ui ) {
                                    //echo ' (UI: ' . esc_html( $type_ui ) . ')';
                                }
                                ?>
                            </span>
                        </h2>
                        
                        <button type="button" class="button button-primary" id="add-new-characteristic-btn">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e( 'Добавить характеристику', 'realty-theme' ); ?>
                        </button>
                        
                        <input type="hidden" id="current-group-id" value="<?php echo esc_attr( $current_group_id ); ?>">
                        
                        <?php
                        // Пагинация
                        $paged = isset( $_GET['char_paged'] ) ? absint( $_GET['char_paged'] ) : 1;
                        $posts_per_page = 10;
                        
                        // Получаем характеристики этой группы
                        $characteristics_args = array(
                            'post_type'      => 'characteristic',
                            'posts_per_page' => $posts_per_page,
                            'paged'          => $paged,
                            'orderby'        => 'meta_value_num',
                            'meta_key'       => 'sort_order',
                            'order'          => 'ASC',
                            'tax_query'      => array(
                                array(
                                    'taxonomy' => 'char_group',
                                    'field'    => 'term_id',
                                    'terms'    => $current_group_id,
                                ),
                            ),
                        );
                        
                        $characteristics_query = new WP_Query( $characteristics_args );
                        $characteristics = $characteristics_query->posts;
                        $total_pages = $characteristics_query->max_num_pages;
                        ?>
                        
                        <?php if ( $total_pages > 1 ) : ?>
                            <div class="tablenav top">
                                <div class="tablenav-pages">
                                    <?php
                                    $pagination_args = array(
                                        'base'      => add_query_arg( 'char_paged', '%#%' ),
                                        'format'    => '',
                                        'prev_text' => __('&laquo;'),
                                        'next_text' => __('&raquo;'),
                                        'total'     => $total_pages,
                                        'current'   => $paged,
                                    );
                                    echo paginate_links( $pagination_args );
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( ! empty( $characteristics ) ) : ?>
                            <?php
                            // Получаем type_ui группы для отображения в таблице
                            $group_type_ui = get_term_meta( $current_group_id, 'type_ui', true );
                            ?>
                            <table class="wp-list-table widefat fixed striped characteristics-table">
                                <thead>
                                    <tr>
                                        <th class="col-id"><?php esc_html_e( 'ID', 'realty-theme' ); ?></th>
                                        <th class="col-order"><?php esc_html_e( 'Порядок', 'realty-theme' ); ?></th>
                                        <th class="col-icon"><?php esc_html_e( 'Иконка', 'realty-theme' ); ?></th>
                                        <th class="col-name"><?php esc_html_e( 'Название', 'realty-theme' ); ?></th>
                                        <th class="col-label"><?php esc_html_e( 'Лейбл', 'realty-theme' ); ?></th>
                                        <th class="col-value"><?php esc_html_e( 'Значение', 'realty-theme' ); ?></th>
                                        <th class="col-filter-ui"><?php esc_html_e( 'Фильтр UI', 'realty-theme' ); ?></th>
                                        <th class="col-style"><?php esc_html_e( 'Стиль', 'realty-theme' ); ?></th>
                                        <th class="col-actions"><?php esc_html_e( 'Действия', 'realty-theme' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="characteristics-sortable">
                                    <?php foreach ( $characteristics as $char ) : ?>
                                        <?php
                                        $icon      = get_post_meta( $char->ID, 'icon', true );
                                        $icon_type = get_post_meta( $char->ID, 'icon_type', true );
                                        $style     = get_post_meta( $char->ID, 'style', true );
                                        $label     = get_post_meta( $char->ID, 'label', true );
                                        $value     = get_post_meta( $char->ID, 'value', true );
                                        $sort_order = get_post_meta( $char->ID, 'sort_order', true );
                                        ?>
                                        <tr class="characteristic-row" data-characteristic-id="<?php echo esc_attr( $char->ID ); ?>">
                                            <td class="col-id">
                                                <?php echo esc_html( $char->ID ); ?>
                                            </td>
                                            <td class="col-order">
                                                <span class="dashicons dashicons-menu characteristics-drag-handle"></span>
                                                <?php echo esc_html( $sort_order ?: '—' ); ?>
                                            </td>
                                            <td class="col-icon">
                                                <?php if ( $icon_type === 'upload' ) : ?>
                                                    <?php
                                                    $media_id = get_post_meta( $char->ID, 'media_id', true );
                                                    if ( $media_id ) {
                                                        echo wp_get_attachment_image( $media_id, array( 32, 32 ) );
                                                    }
                                                    ?>
                                                <?php elseif ( $icon ) : ?>
                                                    <span class="material-symbols-outlined characteristic-icon-preview">
                                                        <?php echo esc_html( $icon ); ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="dashicons dashicons-format-image"></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="col-name">
                                                <strong><?php echo esc_html( $char->post_title ); ?></strong>
                                            </td>
                                            <td class="col-label">
                                                <?php echo esc_html( $label ?: '—' ); ?>
                                            </td>
                                            <td class="col-value">
                                                <?php echo esc_html( $value ?: '—' ); ?>
                                            </td>
                                            <td class="col-filter-ui">
                                                <span class="filter-ui-badge"><?php echo esc_html( $group_type_ui ?: '—' ); ?></span>
                                            </td>
                                            <td class="col-style">
                                                <span class="style-badge style-<?php echo esc_attr( $style ); ?>">
                                                    <?php echo esc_html( $style ?: '—' ); ?>
                                                </span>
                                            </td>
                                            <td class="col-actions">
                                                <button type="button" class="button-link edit-characteristic-btn" 
                                                        data-characteristic-id="<?php echo esc_attr( $char->ID ); ?>"
                                                        title="<?php esc_attr_e( 'Редактировать', 'realty-theme' ); ?>">
                                                    <?php esc_html_e( 'Изменить', 'realty-theme' ); ?>
                                                </button>
                                                |
                                                <button type="button" class="button-link clone-characteristic-btn" 
                                                        data-characteristic-id="<?php echo esc_attr( $char->ID ); ?>"
                                                        title="<?php esc_attr_e( 'Клонировать', 'realty-theme' ); ?>">
                                                    <?php esc_html_e( 'Клонировать', 'realty-theme' ); ?>
                                                </button>
                                                |
                                                <button type="button" class="button-link delete-characteristic-btn" 
                                                        data-characteristic-id="<?php echo esc_attr( $char->ID ); ?>"
                                                        data-characteristic-name="<?php echo esc_attr( $char->post_title ); ?>"
                                                        title="<?php esc_attr_e( 'Удалить', 'realty-theme' ); ?>">
                                                    <?php esc_html_e( 'Удалить', 'realty-theme' ); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if ( $total_pages > 1 ) : ?>
                                <div class="tablenav bottom">
                                    <div class="tablenav-pages">
                                        <?php echo paginate_links( $pagination_args ); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else : ?>
                            <div class="no-characteristics-notice">
                                <p><?php esc_html_e( 'В этой группе пока нет характеристик.', 'realty-theme' ); ?></p>
                                <p><?php esc_html_e( 'Нажмите "Добавить характеристику" чтобы создать первую.', 'realty-theme' ); ?></p>
                            </div>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                <?php else : ?>
                    <div class="no-group-selected-notice">
                        <h3><?php esc_html_e( 'Выберите группу', 'realty-theme' ); ?></h3>
                        <p><?php esc_html_e( 'Выберите группу из списка слева или создайте новую.', 'realty-theme' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Модальное окно для группы -->
        <div id="group-modal" class="characteristics-modal" style="display: none;">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="group-modal-title"><?php esc_html_e( 'Добавить группу', 'realty-theme' ); ?></h3>
                    <button type="button" class="modal-close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="group-form">
                        <?php wp_nonce_field( 'characteristic_save_nonce', 'characteristic_nonce' ); ?>
                        <input type="hidden" id="group-id" name="group_id" value="">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="group-name"><?php esc_html_e( 'Название группы', 'realty-theme' ); ?> <span class="required">*</span></label></th>
                                <td>
                                    <input type="text" id="group-name" name="group_name" class="regular-text" required>
                                    <p class="description"><?php esc_html_e( 'Обязательное поле', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="group-key"><?php esc_html_e( 'Ключ группы (group_key)', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="text" id="group-key" name="group_key" class="regular-text" pattern="[a-z0-9_]+" placeholder="group key">
                                    <p class="description"><?php esc_html_e( 'Уникальный ключ. Генерируется автоматически из названия.', 'realty-theme' ); ?></p>
                                    <button type="button" class="button" id="generate-group-key-btn"><?php esc_html_e( 'Сгенерировать', 'realty-theme' ); ?></button>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="group-system-template"><?php esc_html_e( 'Системный шаблон', 'realty-theme' ); ?></label></th>
                                <td>
                                    <select id="group-system-template" name="group_system_template">
                                        <?php foreach ( SYSTEM_CHARACTERISTIC_GROUP_OPTIONS as $option ) : ?>
                                            <option value="<?php echo esc_attr( $option['value'] ); ?>">
                                                <?php echo esc_html( $option['label'] ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Системный шаблон для вывода группы на шаблоне сайта', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="group-description"><?php esc_html_e( 'Описание', 'realty-theme' ); ?></label></th>
                                <td>
                                    <textarea id="group-description" name="group_description" class="large-text" rows="3"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="group-type-ui"><?php esc_html_e( 'Тип UI для фильтров', 'realty-theme' ); ?></label></th>
                                <td>
                                    <select id="group-type-ui" name="group_type_ui">
                                        <option value="checkbox">Checkbox (множественный выбор)</option>
                                        <option value="radio">Radio (одиночный выбор)</option>
                                        <option value="select">Select (выпадающий список)</option>
                                        <option value="multi_select">Multi Select (множественный выбор)</option>
                                        <option value="chips">Chips (теги)</option>
                                        <option value="switch">Switch (вкл/выкл)</option>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Как группа будет отображаться в фильтрах на сайте', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="group-display-style"><?php esc_html_e( 'Стиль отображения', 'realty-theme' ); ?> <span class="required">*</span></label></th>
                                <td>
                                    <select id="group-display-style" name="group_display_style" required>
                                        <?php echo realty_get_display_style_options( 'standard' ); ?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Определяет как характеристики этой группы будут отображаться на сайте', 'realty-theme' ); ?></p>
                                    <div id="group-style-warning" class="notice notice-warning inline" style="display: none; margin-top: 10px;">
                                        <p><strong><?php esc_html_e( 'Внимание:', 'realty-theme' ); ?></strong> <?php esc_html_e( 'Нельзя изменить стиль после создания характеристик. Удалите все характеристики группы для изменения.', 'realty-theme' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="group-sort-order"><?php esc_html_e( 'Порядок', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="number" id="group-sort-order" name="group_sort_order" value="0" min="0">
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Настройки', 'realty-theme' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="group-active" name="group_active" value="1" checked>
                                        <?php esc_html_e( 'Активна', 'realty-theme' ); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" id="group-use-in-filters" name="group_use_in_filters" value="1" checked>
                                        <?php esc_html_e( 'Использовать в фильтрах', 'realty-theme' ); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" id="group-show-in-archive" name="group_show_in_archive" value="1">
                                        <?php esc_html_e( 'Показывать в архиве', 'realty-theme' ); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button modal-cancel-btn"><?php esc_html_e( 'Отмена', 'realty-theme' ); ?></button>
                    <button type="button" class="button button-primary" id="save-group-btn"><?php esc_html_e( 'Сохранить', 'realty-theme' ); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Модальное окно для характеристик -->
        <div id="characteristic-modal" class="characteristics-modal" style="display: none;">
            <div class="modal-overlay"></div>
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h3 id="characteristic-modal-title"><?php esc_html_e( 'Добавить характеристику', 'realty-theme' ); ?></h3>
                    <button type="button" class="modal-close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="characteristic-form">
                        <?php wp_nonce_field( 'characteristic_save_nonce', 'characteristic_nonce' ); ?>
                        <input type="hidden" id="characteristic-id" name="characteristic_id" value="">
                        <input type="hidden" id="characteristic-group-id" name="group_id" value="<?php echo esc_attr( $current_group_id ); ?>">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="char-title"><?php esc_html_e( 'Название', 'realty-theme' ); ?> <span class="required">*</span></label></th>
                                <td>
                                    <input type="text" id="char-title" name="char_title" class="regular-text" required>
                                    <p class="description"><?php esc_html_e( 'Обязательное поле. Например: "GUESTS", "Pet Friendly"', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="char-description"><?php esc_html_e( 'Описание', 'realty-theme' ); ?></label></th>
                                <td>
                                    <textarea id="char-description" name="char_description" class="large-text" rows="2"></textarea>
                                    <p class="description"><?php esc_html_e( 'Краткое описание (опционально)', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr class="char-field-label">
                                <th><label for="char-label"><?php esc_html_e( 'Лейбл', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="text" id="char-label" name="char_label" class="regular-text">
                                    <p class="description"><?php esc_html_e( 'Отображаемый лейбл (опционально). Например: "GUESTS"', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr class="char-field-hint">
                                <th><label for="char-hint"><?php esc_html_e( 'Хинт/Подпись', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="text" id="char-hint" name="char_hint" class="regular-text">
                                    <p class="description"><?php esc_html_e( 'Подпись под названием (опционально). Например: "Allowed"', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr class="char-field-value">
                                <th><label for="char-value"><?php esc_html_e( 'Значение', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="text" id="char-value" name="char_value" class="regular-text">
                                    <p class="description"><?php esc_html_e( 'Дополнительное значение (опционально). Например: "Up to 4"', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="char-style"><?php esc_html_e( 'Стиль отображения', 'realty-theme' ); ?> <span class="required">*</span></label></th>
                                <td>
                                    <input type="text" id="char-style-display" class="regular-text" readonly style="background: #f9f9f9;">
                                    <input type="hidden" id="char-style" name="char_style" value="standard">
                                    <p class="description"><?php esc_html_e( 'Наследуется от группы. Измените стиль в настройках группы.', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr class="char-field-icon-type">
                                <th><?php esc_html_e( 'Тип иконки', 'realty-theme' ); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="radio" name="char_icon_type" value="material_symbol" checked>
                                            <?php esc_html_e( 'Material Symbol', 'realty-theme' ); ?>
                                        </label><br>
                                        <label>
                                            <input type="radio" name="char_icon_type" value="upload">
                                            <?php esc_html_e( 'Загруженный файл', 'realty-theme' ); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr id="material-symbol-row" class="char-field-icon-type">
                                <th><label for="char-icon"><?php esc_html_e( 'Material Symbol', 'realty-theme' ); ?></label></th>
                                <td>
                                    <div class="icon-picker-container">
                                        <input type="text" id="char-icon" name="char_icon" class="regular-text" placeholder="Например: group, pets, bed">
                                        <button type="button" class="button" id="preview-icon-btn"><?php esc_html_e( 'Preview', 'realty-theme' ); ?></button>
                                        <div id="icon-preview-box" class="icon-preview-box">
                                            <span class="material-symbols-outlined" style="font-size: 32px;">help</span>
                                        </div>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Введите название иконки из Material Symbols Outlined', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr id="media-upload-row" class="char-field-icon-type" style="display: none;">
                                <th><label for="char-media-id"><?php esc_html_e( 'Загрузить файл', 'realty-theme' ); ?></label></th>
                                <td>
                                    <div class="media-uploader-container">
                                        <button type="button" class="button" id="upload-media-btn"><?php esc_html_e( 'Выбрать файл', 'realty-theme' ); ?></button>
                                        <input type="hidden" id="char-media-id" name="char_media_id" value="">
                                        <div id="media-preview-box" class="media-preview-box">
                                            <p class="description"><?php esc_html_e( 'Файл не выбран', 'realty-theme' ); ?></p>
                                        </div>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Поддерживаемые форматы: PNG, SVG, JPG, GIF. Макс. размер: 1MB', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="char-parent"><?php esc_html_e( 'Родительская характеристика', 'realty-theme' ); ?></label></th>
                                <td>
                                    <select id="char-parent" name="char_parent">
                                        <option value="0"><?php esc_html_e( '— Нет (корневая)', 'realty-theme' ); ?></option>
                                        <?php
                                        // Получаем все характеристики текущей группы для выбора родителя
                                        if ( $current_group_id ) {
                                            $parent_chars = get_posts( array(
                                                'post_type'      => 'characteristic',
                                                'posts_per_page' => -1,
                                                'orderby'        => 'title',
                                                'order'          => 'ASC',
                                                'post_status'    => 'publish',
                                                'tax_query'      => array(
                                                    array(
                                                        'taxonomy' => 'char_group',
                                                        'field'    => 'term_id',
                                                        'terms'    => $current_group_id,
                                                    ),
                                                ),
                                            ) );
                                            
                                            if ( ! empty( $parent_chars ) ) {
                                                foreach ( $parent_chars as $parent_char ) {
                                                    echo '<option value="' . esc_attr( $parent_char->ID ) . '">' . esc_html( $parent_char->post_title ) . '</option>';
                                                }
                                            }
                                        }
                                        ?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Опционально. Для создания иерархии (подхарактеристики)', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="char-sort-order"><?php esc_html_e( 'Порядок', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="number" id="char-sort-order" name="char_sort_order" value="0" min="0">
                                    <p class="description"><?php esc_html_e( 'Позиция в списке (0 = автоматически)', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Настройки отображения', 'realty-theme' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="char-show-in-filter" name="char_show_in_filter" value="1">
                                        <?php esc_html_e( 'Отображать в фильтре', 'realty-theme' ); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e( 'Если включено, характеристика будет доступна в фильтрах на сайте', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Статус', 'realty-theme' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="char-active" name="char_active" value="1" checked>
                                        <?php esc_html_e( 'Активна', 'realty-theme' ); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button modal-cancel-btn"><?php esc_html_e( 'Отмена', 'realty-theme' ); ?></button>
                    <button type="button" class="button button-primary" id="save-characteristic-btn"><?php esc_html_e( 'Сохранить', 'realty-theme' ); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Styles and JS loaded via wp_enqueue in realty_enqueue_characteristics_admin_assets -->
    <?php
}

/**
 * Подключение Material Symbols для preview иконок
 */
function realty_enqueue_characteristics_admin_assets() {
    $screen = get_current_screen();
    
    if ( $screen && $screen->id === 'property_page_characteristics-reference' ) {
        // Material Symbols CDN
        wp_enqueue_style(
            'material-symbols-outlined',
            'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0',
            array(),
            '1.0.0'
        );

        // Characteristic Settings CSS
        wp_enqueue_style(
            'realty-characteristics-admin',
            get_template_directory_uri() . '/inc/admin/assets/css/characteristics.css',
            array(),
            filemtime( get_template_directory() . '/inc/admin/assets/css/characteristics.css' )
        );

        // Characteristic Settings JS
        wp_enqueue_script(
            'realty-characteristics-admin',
            get_template_directory_uri() . '/inc/admin/assets/js/characteristics.js',
            array( 'jquery' ),
            filemtime( get_template_directory() . '/inc/admin/assets/js/characteristics.js' ),
            true
        );

        // Inline script for initialization (after main script)
        $init_script = 'jQuery(document).ready(function($) {';
        $init_script .= 'if (typeof CharacteristicAdmin !== "undefined") {';
        $init_script .= 'CharacteristicAdmin.init({';
        $init_script .= 'get_group: "' . wp_create_nonce( "characteristic_get_group_nonce" ) . '",';
        $init_script .= 'get: "' . wp_create_nonce( "characteristic_get_nonce" ) . '",';
        $init_script .= 'save: "' . wp_create_nonce( "characteristic_save_nonce" ) . '",';
        $init_script .= 'media: "' . wp_create_nonce( "characteristic_media_nonce" ) . '",';
        $init_script .= 'delete_group: "' . wp_create_nonce( "characteristic_delete_group_nonce" ) . '",';
        $init_script .= 'delete_characteristic: "' . wp_create_nonce( "characteristic_delete_nonce" ) . '",';
        $init_script .= 'clone: "' . wp_create_nonce( "characteristic_save_nonce" ) . '"';
        $init_script .= '});';
        $init_script .= '}';
        $init_script .= '});';

        wp_add_inline_script( 'realty-characteristics-admin', $init_script );
        
        // Подключаем WordPress Media Uploader
        wp_enqueue_media();
    }
}
add_action( 'admin_enqueue_scripts', 'realty_enqueue_characteristics_admin_assets' );

/**
 * AJAX обработчик для сохранения/обновления групп характеристик
 * 
 * Security: nonce + current_user_can
 * Sanitization: все входные данные санитизируются
 */
function realty_ajax_save_characteristic_group() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_save_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности. Обновите страницу.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    // Санитизация входных данных
    $group_id          = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $group_name        = isset( $_POST['group_name'] ) ? sanitize_text_field( $_POST['group_name'] ) : '';
    $group_key         = isset( $_POST['group_key'] ) ? sanitize_key( $_POST['group_key'] ) : '';
    $group_description = isset( $_POST['group_description'] ) ? sanitize_textarea_field( $_POST['group_description'] ) : '';
    $group_type_ui     = isset( $_POST['group_type_ui'] ) ? sanitize_text_field( $_POST['group_type_ui'] ) : 'checkbox';
    $group_display_style = isset( $_POST['group_display_style'] ) ? sanitize_text_field( $_POST['group_display_style'] ) : 'standard';
    $group_sort_order  = isset( $_POST['group_sort_order'] ) ? absint( $_POST['group_sort_order'] ) : 0;
    $group_active      = isset( $_POST['group_active'] ) ? absint( $_POST['group_active'] ) : 1;
    $group_use_filters = isset( $_POST['group_use_in_filters'] ) ? absint( $_POST['group_use_in_filters'] ) : 1;
    $group_show_archive = isset( $_POST['group_show_in_archive'] ) ? absint( $_POST['group_show_in_archive'] ) : 0;
    $group_system_template = isset( $_POST['group_system_template'] ) ? sanitize_text_field( $_POST['group_system_template'] ) : '';
    
    // Валидация обязательных полей
    if ( empty( $group_name ) ) {
        wp_send_json_error( __( 'Название группы обязательно!', 'realty-theme' ) );
    }
    
    // Автогенерация group_key если пустой
    if ( empty( $group_key ) ) {
        $group_key = sanitize_title( $group_name );
        $group_key = preg_replace( '/[^a-z0-9_]/', '', $group_key );
        $group_key = strtolower( $group_key );
    }
    
    // Валидация group_key (только латиница, цифры, подчёркивание)
    if ( ! preg_match( '/^[a-z0-9_]+$/', $group_key ) ) {
        $group_key = preg_replace( '/[^a-z0-9_]/', '', $group_key );
        $group_key = strtolower( $group_key );
    }
    
    // Проверка уникальности group_key
    $existing_groups = get_terms( array(
        'taxonomy'   => 'char_group',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );
    
    $group_key_exists = false;
    foreach ( $existing_groups as $existing_id ) {
        if ( $existing_id === $group_id ) {
            continue;
        }
        $existing_key = get_term_meta( $existing_id, 'group_key', true );
        if ( $existing_key === $group_key ) {
            $group_key_exists = true;
            break;
        }
    }
    
    if ( $group_key_exists ) {
        $original_key = $group_key;
        $counter = 1;
        while ( $group_key_exists ) {
            $group_key = $original_key . '_' . $counter;
            $group_key_exists = false;
            foreach ( $existing_groups as $existing_id ) {
                if ( $existing_id === $group_id ) {
                    continue;
                }
                $existing_key = get_term_meta( $existing_id, 'group_key', true );
                if ( $existing_key === $group_key ) {
                    $group_key_exists = true;
                    break;
                }
            }
            $counter++;
        }
    }
    
    // Валидация system_template
    $allowed_system_templates = wp_list_pluck( SYSTEM_CHARACTERISTIC_GROUP_OPTIONS, 'value' );
    if ( ! in_array( $group_system_template, $allowed_system_templates, true ) ) {
        $group_system_template = '';
    }
    
    // Проверка уникальности group_system_template
    if ( ! empty( $group_system_template ) ) {
        $existing_with_template = get_terms( array(
            'taxonomy'   => 'char_group',
            'hide_empty' => false,
            'fields'     => 'ids',
            'meta_query' => array(
                array(
                    'key'     => 'group_system_template',
                    'value'   => $group_system_template,
                    'compare' => '=',
                ),
            ),
        ) );
        
        if ( ! empty( $existing_with_template ) ) {
            foreach ( $existing_with_template as $existing_id ) {
                if ( $existing_id !== $group_id ) {
                    wp_send_json_error( __( 'Этот системный шаблон уже используется в другой группе. Выберите другой шаблон.', 'realty-theme' ) );
                }
            }
        }
    }
    
    // Валидация type_ui
    $allowed_ui_types = array( 'checkbox', 'radio', 'select', 'multi_select', 'chips', 'switch' );
    if ( ! in_array( $group_type_ui, $allowed_ui_types, true ) ) {
        $group_type_ui = 'checkbox';
    }
    
    // Валидация display_style
    $allowed_styles = array( 'circle', 'standard', 'prohibited', 'text' );
    if ( ! in_array( $group_display_style, $allowed_styles, true ) ) {
        $group_display_style = 'standard';
    }
    
    // Создаем slug из названия
    $slug = sanitize_title( $group_name );
    
    try {
        if ( $group_id > 0 ) {
            // Обновление существующей группы
            $result = wp_update_term( $group_id, 'char_group', array(
                'name'        => $group_name,
                'slug'        => $slug,
                'description' => $group_description,
            ) );
            
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }
            
            $term_id = $group_id;
        } else {
            // Создание новой группы
            $result = wp_insert_term( $group_name, 'char_group', array(
                'slug'        => $slug,
                'description' => $group_description,
            ) );
            
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }
            
            $term_id = $result['term_id'];
        }
        
        // Сохраняем meta поля
        update_term_meta( $term_id, 'type_ui', $group_type_ui );
        update_term_meta( $term_id, 'display_style', $group_display_style );
        update_term_meta( $term_id, 'active', $group_active );
        update_term_meta( $term_id, 'sort_order', $group_sort_order );
        update_term_meta( $term_id, 'use_in_filters', $group_use_filters );
        update_term_meta( $term_id, 'show_in_archive', $group_show_archive );
        update_term_meta( $term_id, 'description_full', $group_description );
        update_term_meta( $term_id, 'group_key', $group_key );
        update_term_meta( $term_id, 'group_system_template', $group_system_template );
        
        // Успех
        wp_send_json_success( array(
            'message'  => $group_id > 0 ? __( 'Группа обновлена', 'realty-theme' ) : __( 'Группа создана', 'realty-theme' ),
            'term_id'  => $term_id,
            'group_name' => $group_name,
            'group_key' => $group_key,
        ) );
        
    } catch ( Exception $e ) {
        wp_send_json_error( __( 'Ошибка при сохранении: ', 'realty-theme' ) . $e->getMessage() );
    }
}
add_action( 'wp_ajax_characteristic_save_group', 'realty_ajax_save_characteristic_group' );

/**
 * AJAX обработчик для получения данных группы
 */
function realty_ajax_get_characteristic_group() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_get_group_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    
    if ( ! $group_id ) {
        wp_send_json_error( __( 'ID группы не указан.', 'realty-theme' ) );
    }
    
    // Получаем группу
    $group = get_term( $group_id, 'char_group' );
    
    if ( ! $group || is_wp_error( $group ) ) {
        wp_send_json_error( __( 'Группа не найдена.', 'realty-theme' ) );
    }
    
    // Получаем meta
    $has_characteristics = get_posts( array(
        'post_type'      => 'characteristic',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy' => 'char_group',
                'field'    => 'term_id',
                'terms'    => $group_id,
            ),
        ),
    ) );
    
    $data = array(
        'term_id'          => $group->term_id,
        'name'             => $group->name,
        'slug'             => $group->slug,
        'group_key'        => get_term_meta( $group_id, 'group_key', true ),
        'description'      => $group->description,
        'type_ui'          => get_term_meta( $group_id, 'type_ui', true ),
        'display_style'   => get_term_meta( $group_id, 'display_style', true ),
        'active'           => get_term_meta( $group_id, 'active', true ),
        'sort_order'       => get_term_meta( $group_id, 'sort_order', true ),
        'use_in_filters'  => get_term_meta( $group_id, 'use_in_filters', true ),
        'show_in_archive'  => get_term_meta( $group_id, 'show_in_archive', true ),
        'group_system_template' => get_term_meta( $group_id, 'group_system_template', true ),
        'has_characteristics' => ! empty( $has_characteristics ),
    );
    
    wp_send_json_success( $data );
}
add_action( 'wp_ajax_characteristic_get_group', 'realty_ajax_get_characteristic_group' );

/**
 * AJAX обработчик для удаления группы характеристик
 * 
 * Security: nonce + current_user_can
 * При удалении группы все характеристики остаются, но теряют привязку к группе
 */
function realty_ajax_delete_characteristic_group() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_delete_group_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности. Обновите страницу.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    
    if ( ! $group_id ) {
        wp_send_json_error( __( 'ID группы не указан.', 'realty-theme' ) );
    }
    
    // Проверяем, существует ли группа
    $group = get_term( $group_id, 'char_group' );
    
    if ( ! $group || is_wp_error( $group ) ) {
        wp_send_json_error( __( 'Группа не найдена.', 'realty-theme' ) );
    }
    
    try {
        // Подсчитываем характеристики в этой группе
        $characteristics = get_posts( array(
            'post_type'      => 'characteristic',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'char_group',
                    'field'    => 'term_id',
                    'terms'    => $group_id,
                ),
            ),
        ) );
        
        $char_count = count( $characteristics );
        
        // Удаляем группу
        $result = wp_delete_term( $group_id, 'char_group' );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        
        if ( ! $result ) {
            wp_send_json_error( __( 'Не удалось удалить группу.', 'realty-theme' ) );
        }
        
        // Успех
        $message = sprintf(
            __( 'Группа "%s" удалена.', 'realty-theme' ),
            $group->name
        );
        
        if ( $char_count > 0 ) {
            $message .= ' ' . sprintf(
                __( '%d характеристик(а) остались без группы.', 'realty-theme' ),
                $char_count
            );
        }
        
        wp_send_json_success( array(
            'message'         => $message,
            'group_name'      => $group->name,
            'char_count'      => $char_count,
        ) );
        
    } catch ( Exception $e ) {
        wp_send_json_error( __( 'Ошибка при удалении: ', 'realty-theme' ) . $e->getMessage() );
    }
}
add_action( 'wp_ajax_characteristic_delete_group', 'realty_ajax_delete_characteristic_group' );

/**
 * AJAX обработчик для сохранения/обновления характеристики
 * 
 * Security: nonce + current_user_can
 * Sanitization: все входные данные санитизируются
 */
function realty_ajax_save_characteristic() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_save_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности. Обновите страницу.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    // Санитизация входных данных
    $char_id         = isset( $_POST['characteristic_id'] ) ? absint( $_POST['characteristic_id'] ) : 0;
    $group_id        = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $char_title      = isset( $_POST['char_title'] ) ? sanitize_text_field( $_POST['char_title'] ) : '';
    $char_desc       = isset( $_POST['char_description'] ) ? sanitize_textarea_field( $_POST['char_description'] ) : '';
    $char_label      = isset( $_POST['char_label'] ) ? sanitize_text_field( $_POST['char_label'] ) : '';
    $char_hint       = isset( $_POST['char_hint'] ) ? sanitize_text_field( $_POST['char_hint'] ) : '';
    $char_value      = isset( $_POST['char_value'] ) ? sanitize_text_field( $_POST['char_value'] ) : '';
    $char_style      = isset( $_POST['char_style'] ) ? sanitize_text_field( $_POST['char_style'] ) : 'standard';
    $char_icon_type  = isset( $_POST['char_icon_type'] ) ? sanitize_text_field( $_POST['char_icon_type'] ) : 'material_symbol';
    $char_icon       = isset( $_POST['char_icon'] ) ? sanitize_text_field( $_POST['char_icon'] ) : '';
    $char_media_id   = isset( $_POST['char_media_id'] ) ? absint( $_POST['char_media_id'] ) : 0;
    $char_parent     = isset( $_POST['char_parent'] ) ? absint( $_POST['char_parent'] ) : 0;
    $char_sort_order = isset( $_POST['char_sort_order'] ) ? absint( $_POST['char_sort_order'] ) : 0;
    $char_show_filter = isset( $_POST['char_show_in_filter'] ) ? absint( $_POST['char_show_in_filter'] ) : 0;
    $char_status     = isset( $_POST['char_active'] ) ? sanitize_text_field( $_POST['char_active'] ) : 'publish';

    // Валидация обязательных полей
    if ( empty( $char_title ) ) {
        wp_send_json_error( __( 'Название характеристики обязательно!', 'realty-theme' ) );
    }
    
    if ( ! $group_id ) {
        wp_send_json_error( __( 'Группа не выбрана.', 'realty-theme' ) );
    }
    
    // Валидация parent характеристики
    if ( $char_parent > 0 ) {
        $parent = get_post( $char_parent );
        if ( ! $parent || $parent->post_type !== 'characteristic' ) {
            wp_send_json_error( __( 'Родительская характеристика не найдена.', 'realty-theme' ) );
        }
        $parent_groups = get_the_terms( $char_parent, 'char_group' );
        if ( is_wp_error( $parent_groups ) || empty( $parent_groups ) ) {
            wp_send_json_error( __( 'Родительская характеристика не привязана к группе.', 'realty-theme' ) );
        }
        $parent_group_id = $parent_groups[0]->term_id;
        if ( $parent_group_id != $group_id ) {
            wp_send_json_error( __( 'Родительская характеристика должна принадлежать той же группе.', 'realty-theme' ) );
        }
    }
    
    // Валидация стиля
    $allowed_styles = array( 'circle', 'standard', 'prohibited', 'text' );
    if ( ! in_array( $char_style, $allowed_styles, true ) ) {
        $char_style = 'standard';
    }
    
    // Валидация типа иконки
    $allowed_icon_types = array( 'material_symbol', 'upload' );
    if ( ! in_array( $char_icon_type, $allowed_icon_types, true ) ) {
        $char_icon_type = 'material_symbol';
    }
    
    // Если тип иконки - upload, проверяем что media_id указан
    if ( $char_icon_type === 'upload' && ! $char_media_id ) {
        wp_send_json_error( __( 'Для типа иконки "Загруженный файл" необходимо выбрать файл.', 'realty-theme' ) );
    }
    
    // Создаем slug из названия
    $post_slug = sanitize_title( $char_title );
    
    try {
        // Подготавливаем данные поста
        $post_data = array(
            'post_title'    => $char_title,
            'post_content'  => $char_desc,
            'post_status'   => $char_status,
            'post_type'     => 'characteristic',
            'post_parent'   => $char_parent,
        );
        
        if ( $char_id > 0 ) {
            // Обновление существующей характеристики
            $post_data['ID'] = $char_id;
            $post_data['post_name'] = $post_slug;
            
            $result = wp_update_post( $post_data, true );
            
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }
            
            $post_id = $result;
        } else {
            // Создание новой характеристики
            $post_data['post_name'] = $post_slug;
            
            $result = wp_insert_post( $post_data, true );
            
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }
            
            $post_id = $result;
        }
        
        // Сохраняем meta поля
        update_post_meta( $post_id, 'label', $char_label );
        update_post_meta( $post_id, 'hint', $char_hint );
        update_post_meta( $post_id, 'value', $char_value );
        update_post_meta( $post_id, 'style', $char_style );
        update_post_meta( $post_id, 'icon_type', $char_icon_type );
        update_post_meta( $post_id, 'sort_order', $char_sort_order );
        update_post_meta( $post_id, 'show_in_filter', $char_show_filter );
        
        // Сохраняем иконку в зависимости от типа
        if ( $char_icon_type === 'material_symbol' ) {
            update_post_meta( $post_id, 'icon', $char_icon );
            update_post_meta( $post_id, 'media_id', 0 );
        } else {
            update_post_meta( $post_id, 'icon', '' );
            update_post_meta( $post_id, 'media_id', $char_media_id );
        }
        
        // Привязываем к группе (таксономия)
        wp_set_object_terms( $post_id, array( intval( $group_id ) ), 'char_group' );
        
        // Успех
        wp_send_json_success( array(
            'message' => $char_id > 0 ? __( 'Характеристика обновлена', 'realty-theme' ) : __( 'Характеристика создана', 'realty-theme' ),
            'post_id' => $post_id,
        ) );
        
    } catch ( Exception $e ) {
        wp_send_json_error( __( 'Ошибка при сохранении: ', 'realty-theme' ) . $e->getMessage() );
    }
}
add_action( 'wp_ajax_characteristic_save', 'realty_ajax_save_characteristic' );

/**
 * AJAX обработчик для получения данных характеристики
 */
function realty_ajax_get_characteristic() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_get_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    $char_id = isset( $_POST['characteristic_id'] ) ? absint( $_POST['characteristic_id'] ) : 0;
    
    if ( ! $char_id ) {
        wp_send_json_error( __( 'ID характеристики не указан.', 'realty-theme' ) );
    }
    
    // Получаем характеристику
    $char = get_post( $char_id );
    
    if ( ! $char || $char->post_type !== 'characteristic' || is_wp_error( $char ) ) {
        wp_send_json_error( __( 'Характеристика не найдена.', 'realty-theme' ) );
    }
    
    // Получаем meta
    $data = array(
        'ID'             => $char->ID,
        'post_title'     => $char->post_title,
        'post_content'   => $char->post_content,
        'post_status'    => $char->post_status,
        'post_parent'    => $char->post_parent,
        'label'          => get_post_meta( $char_id, 'label', true ),
        'hint'           => get_post_meta( $char_id, 'hint', true ),
        'value'          => get_post_meta( $char_id, 'value', true ),
        'style'          => get_post_meta( $char_id, 'style', true ),
        'icon_type'      => get_post_meta( $char_id, 'icon_type', true ),
        'icon'           => get_post_meta( $char_id, 'icon', true ),
        'media_id'       => get_post_meta( $char_id, 'media_id', true ),
        'sort_order'     => get_post_meta( $char_id, 'sort_order', true ),
        'show_in_filter' => get_post_meta( $char_id, 'show_in_filter', true ),
    );
    
    wp_send_json_success( $data );
}
add_action( 'wp_ajax_characteristic_get', 'realty_ajax_get_characteristic' );

/**
 * AJAX обработчик для получения URL медиа-файла
 */
function realty_ajax_get_characteristic_media_url() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_media_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    $media_id = isset( $_POST['media_id'] ) ? absint( $_POST['media_id'] ) : 0;
    
    if ( ! $media_id ) {
        wp_send_json_error( __( 'ID медиа-файла не указан.', 'realty-theme' ) );
    }
    
    // Валидация что это реальный медиа-файл
    $attachment = get_post( $media_id );
    if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
        wp_send_json_error( __( 'Медиа-файл не найден.', 'realty-theme' ) );
    }
    
    // Получаем URL
    $url = wp_get_attachment_url( $media_id );
    
    if ( ! $url ) {
        wp_send_json_error( __( 'Медиа-файл не найден.', 'realty-theme' ) );
    }
    
    wp_send_json_success( array(
        'url' => $url,
    ) );
}
add_action( 'wp_ajax_characteristic_get_media_url', 'realty_ajax_get_characteristic_media_url' );

/**
 * AJAX обработчик для сохранения порядка характеристик
 * 
 * Security: nonce + current_user_can
 * Обновляет sort_order для всех характеристик в группе на основе нового порядка
 */
function realty_ajax_save_characteristics_order() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_save_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности. Обновите страницу.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    $order = isset( $_POST['order'] ) ? $_POST['order'] : array();
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    
    if ( empty( $order ) || ! is_array( $order ) || ! $group_id ) {
        wp_send_json_error( __( 'Неверные данные порядка.', 'realty-theme' ) );
    }
    
    try {
        // Получаем все характеристики группы в текущем порядке
        $all_chars = get_posts( array(
            'post_type'      => 'characteristic',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'sort_order',
            'order'          => 'ASC',
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'char_group',
                    'field'    => 'term_id',
                    'terms'    => $group_id,
                ),
            ),
        ) );
        
        if ( empty( $all_chars ) ) {
            wp_send_json_error( __( 'Характеристики не найдены.', 'realty-theme' ) );
        }
        
        // Создаем новый порядок: характеристики из $order в начале, затем остальные
        $new_order = array_unique( array_merge( $order, $all_chars ) );
        
        // Обновляем sort_order для всех характеристик
        foreach ( $new_order as $index => $char_id ) {
            update_post_meta( $char_id, 'sort_order', $index );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Порядок характеристик сохранен', 'realty-theme' ),
        ) );
        
    } catch ( Exception $e ) {
        wp_send_json_error( __( 'Ошибка при сохранении порядка: ', 'realty-theme' ) . $e->getMessage() );
    }
}
add_action( 'wp_ajax_characteristic_save_order', 'realty_ajax_save_characteristics_order' );

/**
 * AJAX обработчик для удаления характеристики
 * 
 * Security: nonce + current_user_can
 * Удаляет характеристику и все её дочерние характеристики
 */
function realty_ajax_delete_characteristic() {
    // Проверка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'characteristic_delete_nonce' ) ) {
        wp_send_json_error( __( 'Ошибка безопасности. Обновите страницу.', 'realty-theme' ) );
    }
    
    // Проверка прав
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    $char_id = isset( $_POST['characteristic_id'] ) ? absint( $_POST['characteristic_id'] ) : 0;
    
    if ( ! $char_id ) {
        wp_send_json_error( __( 'ID характеристики не указан.', 'realty-theme' ) );
    }
    
    // Получаем характеристику
    $characteristic = get_post( $char_id );
    
    if ( ! $characteristic || $characteristic->post_type !== 'characteristic' ) {
        wp_send_json_error( __( 'Характеристика не найдена.', 'realty-theme' ) );
    }
    
    try {
        // Удаляем все дочерние характеристики
        $children = get_posts( array(
            'post_type'      => 'characteristic',
            'post_parent'    => $char_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'post_status'    => 'any',
        ) );
        
        if ( ! empty( $children ) ) {
            foreach ( $children as $child_id ) {
                wp_delete_post( $child_id, true );
            }
        }
        
        // Удаляем саму характеристику
        $result = wp_delete_post( $char_id, true );
        
        if ( ! $result ) {
            wp_send_json_error( __( 'Не удалось удалить характеристику.', 'realty-theme' ) );
        }
        
        // Успех
        wp_send_json_success( array(
            'message' => sprintf(
                __( 'Характеристика "%s" удалена.', 'realty-theme' ),
                $characteristic->post_title
            ),
        ) );
        
    } catch ( Exception $e ) {
        wp_send_json_error( __( 'Ошибка при удалении: ', 'realty-theme' ) . $e->getMessage() );
    }
}
add_action( 'wp_ajax_characteristic_delete', 'realty_ajax_delete_characteristic' );

/**
 * AJAX обработчик для клонирования характеристики
 * 
 * Security: nonce + current_user_can
 * Создает копию характеристики с новым названием
 */
function realty_ajax_clone_characteristic() {
    check_ajax_referer( 'characteristic_save_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'У вас недостаточно прав.', 'realty-theme' ) );
    }
    
    $char_id = isset( $_POST['characteristic_id'] ) ? absint( $_POST['characteristic_id'] ) : 0;
    
    if ( ! $char_id ) {
        wp_send_json_error( __( 'ID характеристики не указан.', 'realty-theme' ) );
    }
    
    $original = get_post( $char_id );
    
    if ( ! $original || $original->post_type !== 'characteristic' ) {
        wp_send_json_error( __( 'Характеристика не найдена.', 'realty-theme' ) );
    }
    
    try {
        $new_title = $original->post_title . ' (копия)';
        
        $new_post = array(
            'post_title'    => $new_title,
            'post_content' => $original->post_content,
            'post_status'  => $original->post_status,
            'post_type'    => 'characteristic',
            'post_parent' => $original->post_parent,
        );
        
        $new_id = wp_insert_post( $new_post, true );
        
        if ( is_wp_error( $new_id ) ) {
            wp_send_json_error( $new_id->get_error_message() );
        }
        
        $meta_fields = array( 'label', 'hint', 'value', 'style', 'icon_type', 'icon', 'media_id', 'sort_order', 'show_in_filter' );
        foreach ( $meta_fields as $meta_key ) {
            $value = get_post_meta( $char_id, $meta_key, true );
            if ( $value !== '' ) {
                update_post_meta( $new_id, $meta_key, $value );
            }
        }
        
        $terms = get_the_terms( $char_id, 'char_group' );
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $term_ids = wp_list_pluck( $terms, 'term_id' );
            wp_set_object_terms( $new_id, $term_ids, 'char_group' );
        }
        
        wp_send_json_success( array(
            'message'   => sprintf( __( 'Характеристика склонирована', 'realty-theme' ) ),
            'post_id'  => $new_id,
            'new_url'  => add_query_arg( 'group_id', ! empty( $term_ids ) ? $term_ids[0] : 0 ),
        ) );
        
    } catch ( Exception $e ) {
        wp_send_json_error( __( 'Ошибка при клонировании: ', 'realty-theme' ) . $e->getMessage() );
    }
}
add_action( 'wp_ajax_characteristic_clone', 'realty_ajax_clone_characteristic' );
