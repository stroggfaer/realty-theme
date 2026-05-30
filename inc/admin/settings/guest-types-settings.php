<?php
/**
 * Настройки типов гостей для фильтрации
 *
 * @package RealtyTheme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Получает настройки типов гостей с проверкой типа данных
 *
 * @return array Массив настроек типов гостей
 */
function realty_get_guest_types() {
    $default = array(
        array(
            'name'    => 'adults',
            'label'   => 'Взрослые',
            'desc'    => 'от 13 лет',
            'min'     => 1,
            'max'     => 10,
            'enabled' => true,
        ),
        array(
            'name'    => 'children',
            'label'   => 'Дети',
            'desc'    => 'от 2 до 12 лет',
            'min'     => 0,
            'max'     => 8,
            'enabled' => true,
        ),
    );

    $guest_types = get_option( 'property_guest_types', $default );

    if ( ! is_array( $guest_types ) ) {
        return $default;
    }

    return $guest_types;
}
?>
<h2><?php esc_html_e( 'Типы гостей', 'realty-theme' ); ?></h2>

<div class="realty-tab-description">
    <p><?php esc_html_e( 'Настройте типы гостей для фильтрации объектов недвижимости. Максимум 3 типа.', 'realty-theme' ); ?></p>
</div>

<?php wp_nonce_field( 'realty_save_filter_settings', 'realty_filter_nonce' ); ?>

<?php
$guest_types = realty_get_guest_types();
$guest_count = count( array_filter( $guest_types, function( $g ) { return ! empty( $g['enabled'] ); } ) );
?>

<div id="guest-types-admin" class="guest-types-container">
    <table class="form-table" role="presentation">
        <tbody id="guest-types-list">
            <?php foreach ( $guest_types as $index => $guest ) : ?>
                <?php if ( empty( $guest['enabled'] ) ) continue; ?>
                <tr class="guest-type-row" data-index="<?php echo esc_attr( $index ); ?>">
                    <th scope="row">
                        <?php echo esc_html( $index + 1 ); ?>
                    </th>
                    <td>
                        <table class="guest-type-fields">
                            <tr>
                                <th><label><?php esc_html_e( 'ID (латиница):', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="text" 
                                           name="property_guest_types[<?php echo esc_attr( $index ); ?>][name]" 
                                           value="<?php echo esc_attr( $guest['name'] ?? '' ); ?>" 
                                           class="regular-text guest-name-field" 
                                           pattern="[a-z0-9_]+" 
                                           placeholder="например: adults" 
                                           required />
                                    <p class="description"><?php esc_html_e( 'Уникальный идентификатор: только латиница, цифры и подчеркивания', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php esc_html_e( 'Название:', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="text" 
                                           name="property_guest_types[<?php echo esc_attr( $index ); ?>][label]" 
                                           value="<?php echo esc_attr( $guest['label'] ?? '' ); ?>" 
                                           class="regular-text" 
                                           placeholder="например: Взрослые" 
                                           required />
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php esc_html_e( 'Описание:', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="text" 
                                           name="property_guest_types[<?php echo esc_attr( $index ); ?>][desc]" 
                                           value="<?php echo esc_attr( $guest['desc'] ?? '' ); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php esc_html_e( 'Например: "от 13 лет"', 'realty-theme' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php esc_html_e( 'Мин:', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="number" 
                                           name="property_guest_types[<?php echo esc_attr( $index ); ?>][min]" 
                                           value="<?php echo esc_attr( $guest['min'] ?? 0 ); ?>" 
                                           class="small-text" 
                                           min="0" 
                                           required />
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php esc_html_e( 'Макс:', 'realty-theme' ); ?></label></th>
                                <td>
                                    <input type="number" 
                                           name="property_guest_types[<?php echo esc_attr( $index ); ?>][max]" 
                                           value="<?php echo esc_attr( $guest['max'] ?? 10 ); ?>" 
                                           class="small-text" 
                                           min="1" 
                                           required />
                                </td>
                            </tr>
                            <tr>
                                <th></th>
                                <td>
                                    <button type="button" 
                                            class="button button-secondary remove-guest-type" 
                                            <?php disabled( $guest_count <= 1 ); ?>>
                                        <?php esc_html_e( 'Удалить тип', 'realty-theme' ); ?>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="guest-types-actions">
        <button type="button" 
                id="add-guest-type-btn" 
                class="button button-primary" 
                <?php disabled( $guest_count >= 3 ); ?>>
            <?php esc_html_e( '+ Добавить тип гостя', 'realty-theme' ); ?>
        </button>
        <p class="description" style="margin-top: 10px;">
            <?php 
            printf( 
                esc_html__( 'Добавлено %d из 3 типов', 'realty-theme' ), 
                $guest_count 
            ); 
            ?>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var guestTypesList = $('#guest-types-list');
    var addBtn = $('#add-guest-type-btn');
    var currentIndex = <?php echo count( $guest_types ); ?>;

    // Add new guest type
    addBtn.on('click', function() {
        var newIndex = currentIndex++;
        var newRow = `
            <tr class="guest-type-row" data-index="${newIndex}">
                <th scope="row">${newIndex + 1}</th>
                <td>
                    <table class="guest-type-fields">
                        <tr>
                            <th><label><?php esc_html_e( 'ID (латиница):', 'realty-theme' ); ?></label></th>
                            <td>
                                <input type="text" 
                                       name="property_guest_types[${newIndex}][name]" 
                                       value="" 
                                       class="regular-text guest-name-field" 
                                       pattern="[a-z0-9_]+" 
                                       placeholder="например: adults" 
                                       required />
                                <p class="description"><?php esc_html_e( 'Уникальный идентификатор: только латиница, цифры и подчеркивания', 'realty-theme' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e( 'Название:', 'realty-theme' ); ?></label></th>
                            <td>
                                <input type="text" 
                                       name="property_guest_types[${newIndex}][label]" 
                                       value="" 
                                       class="regular-text" 
                                       placeholder="например: Взрослые" 
                                       required />
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e( 'Описание:', 'realty-theme' ); ?></label></th>
                            <td>
                                <input type="text" 
                                       name="property_guest_types[${newIndex}][desc]" 
                                       value="" 
                                       class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Например: "от 13 лет"', 'realty-theme' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e( 'Мин:', 'realty-theme' ); ?></label></th>
                            <td>
                                <input type="number" 
                                       name="property_guest_types[${newIndex}][min]" 
                                       value="0" 
                                       class="small-text" 
                                       min="0" 
                                       required />
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e( 'Макс:', 'realty-theme' ); ?></label></th>
                            <td>
                                <input type="number" 
                                       name="property_guest_types[${newIndex}][max]" 
                                       value="10" 
                                       class="small-text" 
                                       min="1" 
                                       required />
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <td>
                                <button type="button" class="button button-secondary remove-guest-type">
                                    <?php esc_html_e( 'Удалить тип', 'realty-theme' ); ?>
                                </button>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        `;
        
        guestTypesList.append(newRow);
        updateRowNumbers();
        updateAddButton();
    });

    // Remove guest type
    $(document).on('click', '.remove-guest-type', function() {
        var $row = $(this).closest('.guest-type-row');
        var index = $row.data('index');
        
        if (confirm('<?php esc_html_e( 'Удалить этот тип гостя?', 'realty-theme' ); ?>')) {
            // Сначала добавляем hidden field для пометки удаления
            guestTypesList.append(
                `<input type="hidden" name="property_guest_types[${index}][enabled]" value="0" />`
            );
            
            // Потом удаляем строку
            $row.remove();
            updateRowNumbers();
            updateAddButton();
        }
    });

    // Update row numbers
    function updateRowNumbers() {
        $('.guest-type-row').each(function(index) {
            $(this).find('th:first').text(index + 1);
        });
    }

    // Update add button state
    function updateAddButton() {
        var rowCount = $('.guest-type-row').length;
        if (rowCount >= 3) {
            addBtn.prop('disabled', true);
        } else {
            addBtn.prop('disabled', false);
        }
    }

    // Validate on submit
    $('form').on('submit', function(e) {
        var names = [];
        var valid = true;

        $('.guest-type-row').each(function() {
            var $row = $(this);
            var name = $row.find('.guest-name-field').val().trim();
            var label = $row.find('input[name*="[label]"]').val().trim();

            // Проверка на пустые поля
            if (!name) {
                alert('<?php esc_html_e( 'Ошибка: ID типа гостя не может быть пустым!', 'realty-theme' ); ?>');
                $row.find('.guest-name-field').focus();
                valid = false;
                return false;
            }

            if (!label) {
                alert('<?php esc_html_e( 'Ошибка: Название типа гостя не может быть пустым!', 'realty-theme' ); ?>');
                $row.find('input[name*="[label]"]').focus();
                valid = false;
                return false;
            }

            // Проверка на уникальность
            if (names.indexOf(name) !== -1) {
                alert('<?php esc_html_e( 'Ошибка: ID типа гостя должен быть уникальным!', 'realty-theme' ); ?>');
                $row.find('.guest-name-field').focus();
                valid = false;
                return false;
            }
            names.push(name);
        });

        if (!valid) {
            e.preventDefault();
        }
    });
});
</script>

<style>
.guest-types-container {
    margin-top: 20px;
}

.guest-type-fields {
    width: 100%;
    max-width: 600px;
}

.guest-type-fields th {
    text-align: left;
    padding-right: 10px;
    width: 150px;
    vertical-align: top;
    padding-top: 8px;
}

.guest-type-fields td {
    padding: 5px 0;
}

.guest-type-row {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 15px;
    margin-bottom: 10px;
}

.guest-types-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #c3c4c7;
}
</style>
