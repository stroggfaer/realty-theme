<?php
/**
 * Настройки главной страницы
 *
 * @package RealtyTheme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Кастомный вывод настроек главной страницы
 *
 * @since 1.0.0
 */
function realty_render_home_settings() {
    $characteristics   = realty_get_characteristics_with_icons();
    $selected_ids      = realty_get_home_popular_tags();
    ?>
    <h2><?php esc_html_e( 'Настройки главной страницы', 'realty-theme' ); ?></h2>

    <div class="realty-tab-description">
        <p><?php esc_html_e( 'Настройки главной страницы сайта.', 'realty-theme' ); ?></p>
    </div>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="realty_home_title">
                    <?php esc_html_e( 'Заголовок главной страницы:', 'realty-theme' ); ?>
                </label>
            </th>
            <td>
                <input type="text" name="realty_home_title" id="realty_home_title"
                    value="<?php echo esc_attr( get_option( 'realty_home_title', __( 'Найдите свой идеальный дом', 'realty-theme' ) ) ); ?>"
                    class="regular-text" />
                <p class="description">
                    <?php esc_html_e( 'Введите заголовок для главной страницы.', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="realty_home_subtitle">
                    <?php esc_html_e( 'Подзаголовок:', 'realty-theme' ); ?>
                </label>
            </th>
            <td>
                <textarea name="realty_home_subtitle" id="realty_home_subtitle" rows="3" class="large-text">
                    <?php echo esc_textarea( get_option( 'realty_home_subtitle', '' ) ); ?>
                </textarea>
                <p class="description">
                    <?php esc_html_e( 'Введите подзаголовок для главной страницы.', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="realty_home_cta_text">
                    <?php esc_html_e( 'Текст кнопки:', 'realty-theme' ); ?>
                </label>
            </th>
            <td>
                <input type="text" name="realty_home_cta_text" id="realty_home_cta_text"
                    value="<?php echo esc_attr( get_option( 'realty_home_cta_text', __( 'Начать поиск', 'realty-theme' ) ) ); ?>"
                    class="regular-text" />
                <p class="description">
                    <?php esc_html_e( 'Текст для кнопки призыва к действию.', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?php esc_html_e( 'Популярные теги (chips):', 'realty-theme' ); ?>
            </th>
            <td>
                <?php realty_render_popular_tags_multiselect( $characteristics, $selected_ids ); ?>
                <p class="description">
                    <?php esc_html_e( 'Выберите характеристики, которые будут отображаться как популярные теги на главной странице. Максимум 10.', 'realty-theme' ); ?>
                </p>
            </td>
        </tr>
    </table>

    <?php realty_popular_tags_multiselect_styles(); ?>
    <?php realty_popular_tags_multiselect_script( $characteristics, $selected_ids ); ?>
    <?php
}

/**
 * Рендер HTML-обёртки мультиселекта
 *
 * @param array $characteristics Список характеристик.
 * @param int[] $selected_ids    Выбранные ID.
 * @since 1.0.0
 */
function realty_render_popular_tags_multiselect( $characteristics, $selected_ids ) {
    ?>
    <div class="rpt-multiselect" id="rpt-multiselect">

        <!-- Выбранные теги (chips) -->
        <div class="rpt-selected" id="rpt-selected">
            <?php foreach ( $selected_ids as $id ) :
                $char = null;
                foreach ( $characteristics as $c ) {
                    if ( (int) $c['id'] === (int) $id ) { $char = $c; break; }
                }
                if ( ! $char ) continue;
            ?>
            <span class="rpt-chip" data-id="<?php echo esc_attr( $id ); ?>">
                <?php if ( $char['icon_type'] === 'upload' && $char['media_id'] ) : ?>
                    <?php echo wp_get_attachment_image( $char['media_id'], [ 16, 16 ] ); ?>
                <?php else : ?>
                    <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;"><?php echo esc_html( $char['icon'] ); ?></span>
                <?php endif; ?>
                <span class="rpt-chip-label"><?php echo esc_html( $char['label'] ); ?></span>
                <button type="button" class="rpt-chip-remove" aria-label="<?php esc_attr_e( 'Удалить', 'realty-theme' ); ?>">×</button>
                <input type="hidden" name="realty_home_popular_tags[]" value="<?php echo esc_attr( $id ); ?>">
            </span>
            <?php endforeach; ?>
        </div>

        <!-- Поле поиска -->
        <div class="rpt-input-wrap">
            <input type="text" id="rpt-search" class="rpt-search" placeholder="<?php esc_attr_e( 'Поиск характеристики...', 'realty-theme' ); ?>" autocomplete="off" />
            <span class="rpt-limit-msg" id="rpt-limit-msg" style="display:none;">
                <?php esc_html_e( 'Достигнут лимит 10 тегов', 'realty-theme' ); ?>
            </span>
        </div>

        <!-- Выпадающий список -->
        <div class="rpt-dropdown" id="rpt-dropdown" style="display:none;">
            <?php foreach ( $characteristics as $char ) : ?>
            <div class="rpt-option" data-id="<?php echo esc_attr( $char['id'] ); ?>" data-label="<?php echo esc_attr( strtolower( $char['label'] ) ); ?>">
                <?php if ( $char['icon_type'] === 'upload' && $char['media_id'] ) : ?>
                    <?php echo wp_get_attachment_image( $char['media_id'], [ 20, 20 ] ); ?>
                <?php else : ?>
                    <span class="material-symbols-outlined" style="font-size:20px;vertical-align:middle;margin-right:6px;"><?php echo esc_html( $char['icon'] ); ?></span>
                <?php endif; ?>
                <span><?php echo esc_html( $char['label'] ); ?></span>
            </div>
            <?php endforeach; ?>
            <div class="rpt-no-results" id="rpt-no-results" style="display:none;">
                <?php esc_html_e( 'Ничего не найдено', 'realty-theme' ); ?>
            </div>
        </div>

    </div>
    <?php
}

/**
 * Инлайн-стили для мультиселекта
 *
 * @since 1.0.0
 */
function realty_popular_tags_multiselect_styles() {
    ?>
    <style>
    .rpt-multiselect {
        max-width: 500px;
        position: relative;
        font-size: 13px;
    }
    .rpt-selected {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        min-height: 32px;
        margin-bottom: 6px;
    }
    .rpt-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #e7f5ff;
        border: 1px solid #72aee6;
        border-radius: 3px;
        padding: 3px 6px;
        font-size: 12px;
        line-height: 1.4;
    }
    .rpt-chip img { vertical-align: middle; }
    .rpt-chip-remove {
        background: none;
        border: none;
        cursor: pointer;
        color: #666;
        font-size: 14px;
        line-height: 1;
        padding: 0 0 0 2px;
        margin: 0;
    }
    .rpt-chip-remove:hover { color: #d63638; }
    .rpt-input-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .rpt-search {
        width: 100%;
        max-width: 300px;
        padding: 5px 8px;
        border: 1px solid #8c8f94;
        border-radius: 3px;
        font-size: 13px;
    }
    .rpt-limit-msg {
        color: #d63638;
        font-size: 12px;
    }
    .rpt-dropdown {
        position: absolute;
        top: calc(100% + 2px);
        left: 0;
        width: 100%;
        max-width: 400px;
        max-height: 240px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #8c8f94;
        border-radius: 3px;
        box-shadow: 0 2px 6px rgba(0,0,0,.15);
        z-index: 9999;
    }
    .rpt-option {
        display: flex;
        align-items: center;
        padding: 7px 10px;
        cursor: pointer;
        transition: background .1s;
    }
    .rpt-option:hover,
    .rpt-option.rpt-highlighted { background: #f0f6fc; }
    .rpt-option.rpt-selected-opt { opacity: .4; pointer-events: none; }
    .rpt-option img { margin-right: 6px; }
    .rpt-no-results {
        padding: 8px 10px;
        color: #666;
        font-style: italic;
    }
    </style>
    <?php
}

/**
 * Инлайн-скрипт мультиселекта
 *
 * @param array $characteristics Список характеристик.
 * @param int[] $selected_ids    Выбранные ID.
 * @since 1.0.0
 */
function realty_popular_tags_multiselect_script( $characteristics, $selected_ids ) {
    $chars_json    = wp_json_encode( $characteristics );
    $selected_json = wp_json_encode( array_map( 'intval', $selected_ids ) );
    ?>
    <script>
    (function () {
        const LIMIT       = 10;
        const allChars    = <?php echo $chars_json; // phpcs:ignore WordPress.Security.EscapeOutput ?>;
        let selectedIds   = <?php echo $selected_json; // phpcs:ignore WordPress.Security.EscapeOutput ?>;

        const wrap        = document.getElementById('rpt-multiselect');
        const selectedBox = document.getElementById('rpt-selected');
        const searchInput = document.getElementById('rpt-search');
        const dropdown    = document.getElementById('rpt-dropdown');
        const limitMsg    = document.getElementById('rpt-limit-msg');
        const noResults   = document.getElementById('rpt-no-results');

        // ── helpers ──────────────────────────────────────────────────────────

        function getCharById(id) {
            return allChars.find(c => c.id === id) || null;
        }

        function buildChipHTML(char) {
            const iconHTML = char.icon_type === 'upload' && char.media_id
                ? '' // img уже есть в DOM при загрузке; для динамики используем символ
                : `<span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">${escHtml(char.icon)}</span>`;
            return `<span class="rpt-chip" data-id="${char.id}">
                ${iconHTML}
                <span class="rpt-chip-label">${escHtml(char.label)}</span>
                <button type="button" class="rpt-chip-remove" aria-label="Удалить">×</button>
                <input type="hidden" name="realty_home_popular_tags[]" value="${char.id}">
            </span>`;
        }

        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function updateLimitMsg() {
            const reached = selectedIds.length >= LIMIT;
            limitMsg.style.display  = reached ? '' : 'none';
            searchInput.disabled    = reached;
        }

        function markSelectedOptions() {
            dropdown.querySelectorAll('.rpt-option').forEach(opt => {
                const id = parseInt(opt.dataset.id, 10);
                opt.classList.toggle('rpt-selected-opt', selectedIds.includes(id));
            });
        }

        // ── add / remove ─────────────────────────────────────────────────────

        function addTag(id) {
            if (selectedIds.length >= LIMIT) return;
            if (selectedIds.includes(id)) return;
            const char = getCharById(id);
            if (!char) return;

            selectedIds.push(id);
            selectedBox.insertAdjacentHTML('beforeend', buildChipHTML(char));
            markSelectedOptions();
            updateLimitMsg();
            searchInput.value = '';
            filterDropdown('');
        }

        function removeTag(id) {
            selectedIds = selectedIds.filter(i => i !== id);
            const chip = selectedBox.querySelector(`.rpt-chip[data-id="${id}"]`);
            if (chip) chip.remove();
            markSelectedOptions();
            updateLimitMsg();
        }

        // ── dropdown ─────────────────────────────────────────────────────────

        function filterDropdown(query) {
            const q = query.toLowerCase().trim();
            let visible = 0;
            dropdown.querySelectorAll('.rpt-option').forEach(opt => {
                const label = opt.dataset.label || '';
                const match = !q || label.includes(q);
                opt.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            noResults.style.display = visible === 0 ? '' : 'none';
        }

        function openDropdown() {
            if (selectedIds.length >= LIMIT) return;
            filterDropdown(searchInput.value);
            dropdown.style.display = '';
        }

        function closeDropdown() {
            dropdown.style.display = 'none';
        }

        // ── events ───────────────────────────────────────────────────────────

        searchInput.addEventListener('focus', openDropdown);
        searchInput.addEventListener('input', function () {
            filterDropdown(this.value);
            if (dropdown.style.display === 'none') openDropdown();
        });

        dropdown.addEventListener('mousedown', function (e) {
            const opt = e.target.closest('.rpt-option');
            if (!opt) return;
            e.preventDefault();
            addTag(parseInt(opt.dataset.id, 10));
        });

        selectedBox.addEventListener('click', function (e) {
            const btn = e.target.closest('.rpt-chip-remove');
            if (!btn) return;
            const chip = btn.closest('.rpt-chip');
            if (chip) removeTag(parseInt(chip.dataset.id, 10));
        });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) closeDropdown();
        });

        // ── init ─────────────────────────────────────────────────────────────
        markSelectedOptions();
        updateLimitMsg();
    })();
    </script>
    <?php
}

// Вывод настроек при включении файла
realty_render_home_settings();
