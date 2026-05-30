<?php
/**
 * Настройки темы
 *
 * @package RealtyTheme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Кастомный вывод настроек темы
 *
 * @since 1.0.0
 */
function realty_render_theme_settings() {
    ?>
    <h2><?php esc_html_e( 'Настройки темы', 'realty-theme' ); ?></h2>

    <div class="realty-tab-description">
        <p><?php esc_html_e( 'Общие настройки темы Недвижимость.', 'realty-theme' ); ?></p>
    </div>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="realty_logo_type">
                    <?php esc_html_e( 'Тип логотипа:', 'realty-theme' ); ?>
                </label>
            </th>
            <td>
                <select name="realty_logo_type" id="realty_logo_type">
                    <option value="text" <?php selected( get_option( 'realty_logo_type', 'text' ), 'text' ); ?>>
                        <?php esc_html_e( 'Текстовый логотип', 'realty-theme' ); ?>
                    </option>
                    <option value="image" <?php selected( get_option( 'realty_logo_type', 'text' ), 'image' ); ?>>
                        <?php esc_html_e( 'Изображение', 'realty-theme' ); ?>
                    </option>
                </select>
                <p class="description">
                    <?php esc_html_e( 'Выберите тип отображения логотипа.', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="realty_company_name">
                    <?php esc_html_e( 'Название компании:', 'realty-theme' ); ?>
                </label>
            </th>
            <td>
                <input type="text" name="realty_company_name" id="realty_company_name"
                    value="<?php echo esc_attr( get_option( 'realty_company_name', __( 'Недвижимость', 'realty-theme' ) ) ); ?>"
                    class="regular-text" />
                <p class="description">
                    <?php esc_html_e( 'Введите название компании для текстового логотипа.', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="realty_copyright_text">
                    <?php esc_html_e( 'Текст копирайта:', 'realty-theme' ); ?>
                </label>
            </th>
            <td>
                <input type="text" name="realty_copyright_text" id="realty_copyright_text"
                    value="<?php echo esc_attr( get_option( 'realty_copyright_text', '' ) ); ?>"
                    class="regular-text" />
                <p class="description">
                    <?php esc_html_e( 'Текст для футера сайта.', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

// Вывод настроек при включении файла
realty_render_theme_settings();
