<?php
/**
 * Вспомогательные функции темы
 * Общие утилиты не привязанные к конкретной фиче
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Очистка телефона от лишних символов
 */
function str_phone($str) {
    if (empty($str)) return '';
    return preg_replace('/[^0-9]/', '', $str);
}

/**
 * Получение данных галереи Pods
 * 
 * @param int|object $post_or_pod  ID поста / Pods-объект / WP_Post
 * @param string $size         Размер изображения
 * @param bool $cover_only     true = только первое фото / заглушка
 * @return array
 */
function pod_get_gallery_data($post_or_pod, string $size = 'large', bool $cover_only = false): array {
    // Нормализация входных данных
    if (is_numeric($post_or_pod)) {
        $post_id = (int)$post_or_pod;
        $pod = function_exists('pods') ? pods('property', $post_id) : null;
    } elseif (is_object($post_or_pod)) {
        if (method_exists($post_or_pod, 'field')) {
            $pod = $post_or_pod;
            $post_id = $pod->id() ?: get_the_ID();
        } else {
            $post_id = $post_or_pod->ID ?? get_the_ID();
            $pod = function_exists('pods') ? pods('property', $post_id) : null;
        }
    } else {
        $post_id = get_the_ID();
        $pod = function_exists('pods') ? pods('property', $post_id) : null;
    }

    // Нет Pods
    if (!$pod || !$pod->valid()) {
        return $cover_only ? pod_placeholder($post_id) : [];
    }

    $raw = $pod->field('gallery');
    if (empty($raw)) {
        return $cover_only ? pod_placeholder($post_id) : [];
    }

    $items = (array)$raw;
    $title = get_the_title($post_id);

    foreach ($items as $item) {
        $id = 0;
        if (is_array($item)) $id = (int)($item['ID'] ?? 0);
        elseif (is_object($item)) $id = (int)($item->ID ?? 0);
        elseif (is_numeric($item)) $id = (int)$item;

        if ($id < 1) continue;

        $img = wp_get_attachment_image_src($id, $size);
        if (!$img || empty($img[0])) continue;

        $data = [
            'id' => $id,
            'src' => esc_url($img[0]),
            'alt' => esc_attr(get_post_meta($id, '_wp_attachment_image_alt', true) ?: $title),
            'width' => (int)($img[1] ?? 0),
            'height' => (int)($img[2] ?? 0),
        ];

        if ($cover_only) {
            return $data;
        }

        $result[] = $data;
    }

    if ($cover_only) {
        return pod_placeholder($post_id);
    }

    return $result ?? [];
}

/**
 * Заглушка изображения
 */
function pod_placeholder(int $post_id): array {
    $url = empty_img_placeholder();
    $title = get_the_title($post_id);
    return [
        'id' => 0,
        'src' => $url,
        'alt' => esc_attr($title),
        'width' => 0,
        'height' => 0,
    ];
}

/**
 * URL заглушки изображения
 */
function empty_img_placeholder(): string {
    return esc_url(get_template_directory_uri() . '/assets/images/no_image.jpg');
}

/**
 * Валидация дат заезда и выезда
 */
function validate_property_dates($checkin_date, $checkout_date = '') {
    $errors = [];

    $checkin_ts = strtotime($checkin_date);
    if ($checkin_ts === false) {
        $errors[] = 'Неверный формат даты заезда';
    } else {
        $today = strtotime(date('Y-m-d'));
        if ($checkin_ts < $today) {
            $errors[] = 'Дата заезда не может быть в прошлом';
        }
    }

    if (!empty($checkout_date)) {
        $checkout_ts = strtotime($checkout_date);
        if ($checkout_ts === false) {
            $errors[] = 'Неверный формат даты выезда';
        } else {
            if ($checkin_ts !== false && $checkout_ts < $checkin_ts) {
                $errors[] = 'Дата выезда не может быть раньше даты заезда';
            }
        }
    }

    return $errors;
}

/**
 * Валидация количества гостей
 */
function validate_guest_numbers($adults = 0, $children = 0) {
    $errors = [];

    if ($adults < 1) {
        $errors[] = 'Количество взрослых должно быть не менее 1';
    } elseif ($adults > 10) {
        $errors[] = 'Количество взрослых не может превышать 10';
    }

    if ($children < 0) {
        $errors[] = 'Количество детей не может быть отрицательным';
    } elseif ($children > 8) {
        $errors[] = 'Количество детей не может превышать 8';
    }

    return $errors;
}


/**
 * Склонение числительных
 */
function declension($words, $number) {
    $number = abs($number) % 100;
    $num = $number % 10;
    if ($number > 10 && $number < 20) {
        return [$number, $words[2]];
    } elseif ($num > 1 && $num < 5) {
        return [$number, $words[1]];
    } elseif ($num == 1) {
        return [$number, $words[0]];
    } else {
        return [$number, $words[2]];
    }
}
/**
 * Получить характеристики недвижимости для фронтенда
 *
 * Функция возвращает характеристики объекта недвижимости в разных форматах:
 * - плоский массив (по умолчанию)
 * - сгруппированный по группам
 * - конкретную группу по ключу
 *
 * Поддерживает лимит, фильтрацию по стилю группы и кэширование.
 *
 * @param int         $property_id   ID поста недвижимости
 * @param string|null $group_style   Фильтр по стилю группы (circle|standard|prohibited|text) или null
 * @param array       $options       Дополнительные параметры:
 *                                  - type (string)      Тип вывода:
 *                                      flat (по умолчанию) — плоский массив
 *                                      all               — все группы
 *                                      one               — одна группа по ключу
 *                                  - limit (int)        Лимит характеристик (по умолчанию 20)
 *                                  - group_key (string) Ключ группы (slug) для type=one
 *                                  - cache (bool)       Использовать кэш (по умолчанию: !is_admin())
 *                                  - cache_ttl (int)    Время жизни кэша в секундах (по умолчанию 300)
 *
 * @return array Возвращает:
 *               - flat: плоский массив характеристик
 *               - all: массив групп с вложенными характеристиками
 *               - one: массив одной группы или пустой массив
 */
function realty_get_property_characteristicsOld($property_id, $group_style = null, $options = []) {

    $options = wp_parse_args($options, [
        'type'        => 'flat', // flat | all | one
        'limit'       => 12,
        'group_key'   => null,
        'system_temp' => null,
    ]);

    $saved = get_post_meta($property_id, 'property_characteristics', true);

    if (!is_array($saved) || empty($saved)) {
        return [];
    }

    // сортировка
    usort($saved, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

    // ===== SYSTEM TEMP =====
    $allowed_group_ids = [];

    if (!empty($options['system_temp'])) {
        $terms = get_terms([
            'taxonomy'   => 'char_group',
            'meta_key'   => 'group_system_template',
            'meta_value' => $options['system_temp'],
            'hide_empty' => false,
        ]);

        if (!is_wp_error($terms)) {
            $allowed_group_ids = array_column($terms, 'term_id');
        }
    }

    $groups = [];
    $flat   = [];

    // кеши (чтобы не дергать WP 100 раз)
    $post_cache = [];
    $term_cache = [];
    $term_meta_cache = [];

    // ===== ОСНОВНОЙ ПРОХОД =====
    foreach ($saved as $item) {

        $char_id = (int)($item['characteristic_id'] ?? 0);
        if (!$char_id) continue;

        // POST
        if (!isset($post_cache[$char_id])) {
            $post_cache[$char_id] = get_post($char_id);
        }

        $char = $post_cache[$char_id];
        if (!$char || $char->post_status !== 'publish') continue;

        // TERMS
        if (!isset($term_cache[$char_id])) {
            $terms = get_the_terms($char_id, 'char_group');
            $term_cache[$char_id] = (!empty($terms) && !is_wp_error($terms)) ? $terms[0] : null;
        }

        $term = $term_cache[$char_id];
        $group_id = $term ? $term->term_id : 0;

        // system filter
        if (!empty($allowed_group_ids) && !in_array($group_id, $allowed_group_ids, true)) {
            continue;
        }

        // TERM META
        if ($group_id && !isset($term_meta_cache[$group_id])) {
            $term_meta_cache[$group_id] = get_term_meta($group_id);
        }

        $term_meta = $group_id ? $term_meta_cache[$group_id] : [];

        $group_key     = $term_meta['group_key'][0] ?? ($term->slug ?? 'default');
        $group_name    = $term->name ?? '';
        $display_style = $term_meta['display_style'][0] ?? 'standard';
        $active        = $term_meta['active'][0] ?? 1;

        // фильтры
        if ($group_style && $display_style !== $group_style) continue;
        if (!$active) continue;
        if ($options['group_key'] && $options['group_key'] !== $group_key) continue;

        // DATA
        $char_data = [
            'characteristic_id' => $char_id,
            'group_id'          => $group_id,
            'group_name'        => $group_name,
            'group_key'         => $group_key,
            'display_style'     => $display_style,
            'title'             => $char->post_title,
            'icon'              => get_post_meta($char_id, 'icon', true),
            'icon_type'         => get_post_meta($char_id, 'icon_type', true),
            'media_id'          => get_post_meta($char_id, 'media_id', true),
            'label'             => get_post_meta($char_id, 'label', true),
            'value'             => get_post_meta($char_id, 'value', true),
            'hint'              => get_post_meta($char_id, 'hint', true),
            'order'             => $item['order'] ?? 0,
        ];

        // ===== FLAT =====
        if ($options['type'] === 'flat') {
            $flat[] = $char_data;
            continue;
        }

        // ===== GROUP =====
        if (!isset($groups[$group_key])) {
            $groups[$group_key] = [
                'group_id' => $group_id,
                'group_name' => $group_name,
                'group_key' => $group_key,
                'display_style' => $display_style,
                'total' => 0, // считаем ниже
                'characteristics' => []
            ];
        }

        $groups[$group_key]['characteristics'][] = $char_data;
        $groups[$group_key]['total']++; // считаем ДО limit
    }

    // ===== LIMIT (ПОСЛЕ ФИЛЬТРАЦИИ!) =====
    if ($options['type'] === 'flat') {
        $flat = array_slice($flat, 0, (int)$options['limit']);
        return $flat;
    }

    // GROUP LIMIT
    foreach ($groups as &$group) {
        $group['characteristics'] = array_slice(
            $group['characteristics'],
            0,
            (int)$options['limit']
        );
    }
    unset($group);

    // ===== OUTPUT =====
    if ($options['type'] === 'one') {
        $key = $options['group_key'] ?? '';
        return $groups[$key] ?? [];
    }

    return array_values($groups);
}
function realty_get_property_characteristicsddd($property_id, $group_style = null, $options = []) {

    $options = wp_parse_args($options, [
        'type'        => 'flat', // flat | all | one
        'limit'       => 12,
        'group_key'   => null,
        'system_temp' => null,
    ]);

    $saved = get_post_meta($property_id, 'property_characteristics', true);

    if (!is_array($saved) || empty($saved)) {
        return [];
    }

    usort($saved, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

    // ===== SYSTEM TEMP =====
    $allowed_group_ids = [];

    if (!empty($options['system_temp'])) {
        $terms = get_terms([
            'taxonomy'   => 'char_group',
            'meta_key'   => 'group_system_template',
            'meta_value' => $options['system_temp'],
            'hide_empty' => false,
        ]);

        if (!is_wp_error($terms)) {
            $allowed_group_ids = array_column($terms, 'term_id');
        }
    }

    $groups = [];
    $flat   = [];

    $post_cache = [];
    $term_cache = [];
    $term_meta_cache = [];

    foreach ($saved as $item) {

        $char_id = (int)($item['characteristic_id'] ?? 0);
        if (!$char_id) continue;

        if (!isset($post_cache[$char_id])) {
            $post_cache[$char_id] = get_post($char_id);
        }

        $char = $post_cache[$char_id];
        if (!$char || $char->post_status !== 'publish') continue;

        if (!isset($term_cache[$char_id])) {
            $terms = get_the_terms($char_id, 'char_group');
            $term_cache[$char_id] = (!empty($terms) && !is_wp_error($terms)) ? $terms[0] : null;
        }

        $term = $term_cache[$char_id];
        $group_id = $term ? $term->term_id : 0;

        if (!empty($allowed_group_ids) && !in_array($group_id, $allowed_group_ids, true)) {
            continue;
        }

        if ($group_id && !isset($term_meta_cache[$group_id])) {
            $term_meta_cache[$group_id] = get_term_meta($group_id);
        }

        $term_meta = $group_id ? $term_meta_cache[$group_id] : [];

        $group_key     = $term_meta['group_key'][0] ?? ($term->slug ?? 'default');
        $group_name    = $term->name ?? '';
        $display_style = $term_meta['display_style'][0] ?? 'standard';
        $active        = $term_meta['active'][0] ?? 1;

        if ($group_style && $display_style !== $group_style) continue;
        if (!$active) continue;
        if ($options['group_key'] && $options['group_key'] !== $group_key) continue;

        $char_data = [
            'characteristic_id' => $char_id,
            'group_id'          => $group_id,
            'group_name'        => $group_name,
            'group_key'         => $group_key,
            'display_style'     => $display_style,
            'title'             => $char->post_title,
            'icon'              => get_post_meta($char_id, 'icon', true),
            'icon_type'         => get_post_meta($char_id, 'icon_type', true),
            'media_id'          => get_post_meta($char_id, 'media_id', true),
            'label'             => get_post_meta($char_id, 'label', true),
            'value'             => get_post_meta($char_id, 'value', true),
            'hint'              => get_post_meta($char_id, 'hint', true),
            'order'             => $item['order'] ?? 0,
        ];

        if ($options['type'] === 'flat') {
            $flat[] = $char_data;
            continue;
        }

        if (!isset($groups[$group_key])) {
            $groups[$group_key] = [
                'group_id' => $group_id,
                'group_name' => $group_name,
                'group_key' => $group_key,
                'display_style' => $display_style,
                'total' => 0,
                'characteristics' => []
            ];
        }

        $groups[$group_key]['characteristics'][] = $char_data;
        $groups[$group_key]['total']++;
    }

    // ===== LIMIT (ТОЛЬКО ДЛЯ ВЫВОДА) =====
    if ($options['type'] === 'flat') {
        return $options['limit']
            ? array_slice($flat, 0, (int)$options['limit'])
            : $flat;
    }

    foreach ($groups as &$group) {
        if ($options['limit']) {
            $group['characteristics'] = array_slice(
                $group['characteristics'],
                0,
                (int)$options['limit']
            );
        }
    }
    unset($group);

    if ($options['type'] === 'one') {
        return $groups[$options['group_key']] ?? [];
    }

    return array_values($groups);
}

/**
 * Получить характеристики недвижимости для фронтенда
 *
 * @param int         $property_id   ID поста недвижимости
 * @param string|null $group_style   Фильтр по стилю группы
 * @param array       $options       Параметры: type, limit, group_key, system_temp, page, per_page, last_count
 *
 * @return array Схема: [data => [], meta => [...], filters => []]
 */
/**
 * Получить характеристики недвижимости (логика)
 * * @param int         $property_id
 * @param string|null $group_style
 * @param array       $options
 * @return array      Схема: [data, meta, filters]
 */
/**
 * Получить характеристики недвижимости (логика)
 */
function realty_get_property_characteristics($property_id, $group_style = null, $options = []) {
    $options = wp_parse_args($options, [
        'type'        => 'flat',     // flat | all | one
        'limit'        => 12,        // Сколько записей вернуть
        'last_count'  => 0,          // Смещение (сколько уже загружено)
        'page'        => 1,
        'group_key'   => null,
        'group_id'    => null,
        'system_temp' => null,
        'cache'       => true,
    ]);

    $response = [
        'data'    => [],
        'meta'    => [
            'current_page' => (int)$options['page'],
            'page_count'   => 0,
            'per_page'     => (int)$options['limit'],
            'total'        => 0,
            'last_count'   => (int)$options['last_count'],
        ],
        'filters' => [],
    ];

    $saved = get_post_meta($property_id, 'property_characteristics', true);
    if (!is_array($saved) || empty($saved)) return $response;

    // 1. Сортировка исходного массива
    usort($saved, fn($a, $b) => (int)($a['order'] ?? 0) - (int)($b['order'] ?? 0));

    $filtered_pool = [];
    $group_totals = [];
    $available_filters = [];

    // Оптимизация: предзагрузка кэша метаданных для всех связанных постов
    $all_char_ids = array_column($saved, 'characteristic_id');
    update_meta_cache('post', $all_char_ids);

    // 2. Фильтрация и сбор данных
    foreach ($saved as $item) {
        $char_id = absint($item['characteristic_id'] ?? 0);
        if (!$char_id) continue;

        $terms = get_the_terms($char_id, 'char_group');
        $term = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : null;
        $g_id = $term ? $term->term_id : 0;

        // Фильтр по ID группы
        if (!empty($options['group_id']) && (int)$options['group_id'] !== $g_id) continue;

        $g_meta = $g_id ? get_term_meta($g_id) : [];
        $g_key = $g_meta['group_key'][0] ?? ($term ? $term->slug : 'default');

        // Фильтр по system_temp
        if (!empty($options['system_temp'])) {
            $g_temp = get_term_meta($g_id, 'group_system_template', true);
            if ($g_temp !== $options['system_temp']) continue;
        }

        $char_post = get_post($char_id);
        if (!$char_post || $char_post->post_status !== 'publish') continue;

        $char_data = [
            'characteristic_id' => $char_id,
            'title'      => $char_post->post_title,
            'group_name' => $term->name ?? '',
            'group_key'  => $g_key,
            'g_id'       => $g_id,
            'icon'       => get_post_meta($char_id, 'icon', true),
            'value'      => get_post_meta($char_id, 'value', true),
            'label'      => get_post_meta($char_id, 'label', true),
            'order'      => (int)$item['order']
        ];

        $filtered_pool[] = $char_data;

        // Накопление мета-данных
        $group_totals[$g_key] = ($group_totals[$g_key] ?? 0) + 1;
        if (!empty($g_meta['use_in_filters'][0]) && !isset($available_filters[$g_key])) {
            $available_filters[$g_key] = [
                'group_id'   => $g_id,
                'group_name' => $term->name,
                'group_key'  => $g_key
            ];
        }
    }

    $total_filtered = count($filtered_pool);
    $response['meta']['total'] = $total_filtered;
    $response['meta']['page_count'] = ceil($total_filtered / (int)$options['limit']);
    $response['filters'] = array_values($available_filters);

    // 3. Логика среза (Pagination vs AJAX Last Count)
    // Если есть last_count, возвращает последние last_count элементов
    if ((int)$options['last_count'] > 0) {
        $offset = $total_filtered - (int)$options['last_count'];
        $limit  = (int)$options['last_count'];
    } else {
        $offset = ((int)$options['page'] - 1) * (int)$options['limit'];
        $limit  = (int)$options['limit'];
    }

    $sliced_items = array_slice($filtered_pool, $offset, $limit);

    // 4. Группировка результата
    if ($options['type'] === 'flat') {
        $response['data'] = $sliced_items;
    } else {
        $groups = [];
        foreach ($sliced_items as $item) {
            $key = $item['group_key'];
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'group_id'   => $item['g_id'],
                    'group_name' => $item['group_name'],
                    'group_key'  => $key,
                    'total'      => $group_totals[$key] ?? 0,
                    'characteristics' => []
                ];
            }
            unset($item['g_id']); // Чистим вспомогательные данные
            $groups[$key]['characteristics'][] = $item;
        }

        if ($options['type'] === 'one') {
            $active_key = $options['group_key'] ?: key($groups);
            $response['data'] = $groups[$active_key] ?? (reset($groups) ?: []);
        } else {
            $response['data'] = array_values($groups);
        }
    }

    // Возврат данных в зависимости от контекста
    $is_rest = defined('REST_REQUEST') && REST_REQUEST;
    if (wp_doing_ajax() || $is_rest || $options['type'] === 'all') {
        return $response;
    }

    return ($options['type'] === 'one') ? $response['data'] : $response;
}

/**
 * Рендер иконки характеристики
 *
 * @param array $char_data Массив данных характеристики
 * @return string HTML иконки
 */
function realty_render_characteristic_icon($char_data, $className = '') {
    $icon_type = $char_data['icon_type'] ?? 'material_symbol"';

    if ($icon_type === 'upload' && !empty($char_data['media_id'])) {
        $url = wp_get_attachment_url($char_data['media_id']);
        if ($url) {
            return '<img src="' . esc_url($url) . '" alt="">';
        }
    }
    $icon = $char_data['icon'] ?? 'help';
    return '<span class="material-symbols-outlined '.$className.'">' . esc_html($icon) . '</span>';
}

/**
 * Получить характеристики для фильтров
 * 
 * Возвращает группы характеристик с use_in_filters = 1 и active = 1
 * отсортированные по sort_order
 *
 * @return array Массив групп с характеристиками
 */
function realty_get_filter_characteristics() {
    // Получить группы с use_in_filters = 1 и active = 1
    $groups = get_terms([
        'taxonomy' => 'char_group',
        'hide_empty' => false,
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'use_in_filters',
                'value' => '1',
                'compare' => '='
            ],
            [
                'key' => 'active',
                'value' => '1',
                'compare' => '='
            ]
        ],
        'meta_key' => 'sort_order',
        'orderby' => 'meta_value_num',
        'order' => 'ASC'
    ]);

    if (is_wp_error($groups) || empty($groups)) {
        return [];
    }

    $result = [];

    foreach ($groups as $group) {
        // Получить характеристики для группы
        $chars = get_posts([
            'post_type' => 'characteristic',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => [
                [
                    'taxonomy' => 'char_group',
                    'field' => 'term_id',
                    'terms' => $group->term_id
                ]
            ]
        ]);

        if (empty($chars)) {
            continue;
        }

        // Получить тип UI для группы
        $type_ui = get_term_meta($group->term_id, 'type_ui', true) ?: 'checkbox';

        $characteristics = [];
        foreach ($chars as $char) {
            $characteristics[] = [
                'id' => $char->ID,
                'title' => $char->post_title,
                'icon' => get_post_meta($char->ID, 'icon', true),
                'icon_type' => get_post_meta($char->ID, 'icon_type', true),
            ];
        }

        $result[] = [
            'group_id' => $group->term_id,
            'group_name' => $group->name,
            'group_key' => get_term_meta($group->term_id, 'group_key', true) ?: $group->slug,
            'type_ui' => $type_ui,
            'characteristics' => $characteristics
        ];
    }

    return $result;
}

/**
 * Возвращает список типов жилья для фильтра
 *
 * @return array [ ['id' => int, 'name' => string, 'slug' => string], ... ]
 */
function realty_get_property_types_for_filter(): array {
    $terms = get_terms([
        'taxonomy'   => 'property_type',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $result = [];
    foreach ($terms as $term) {
        $result[] = [
            'id'   => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
        ];
    }

    return $result;
}

/**
 * Получить характеристики из справочника по system_template группы
 *
 * Используется в метабоксах и там, где нет сохранённых характеристик объекта.
 *
 * @param string $system_template Значение group_system_template (например 'hours_limit')
 * @return array [ ['value' => post_title, 'label' => label|post_title], ... ]
 */
function realty_get_chars_by_system_template( $system_template ) {
    $groups = get_terms( [
        'taxonomy'   => 'char_group',
        'hide_empty' => false,
        'meta_query' => [ [ 'key' => 'group_system_template', 'value' => $system_template ] ],
    ] );

    if ( empty( $groups ) || is_wp_error( $groups ) ) return [];

    $chars = get_posts( [
        'post_type'      => 'characteristic',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'sort_order',
        'order'          => 'ASC',
        'tax_query'      => [ [ 'taxonomy' => 'char_group', 'field' => 'term_id', 'terms' => $groups[0]->term_id ] ],
    ] );

    return array_map( fn( $c ) => [
        'value' => $c->post_title,
        'label' => get_post_meta( $c->ID, 'label', true ) ?: $c->post_title,
    ], $chars );
}

/**
 * @param WP_Term $term
 * @return string
 */
function location_thumbnail_template(WP_Term $term): string {
    $thumbnail = get_template_directory_uri() . '/assets/images/no_city.png';

    if (function_exists('pods')) {
        $pod = pods('location', $term->term_id);

        if ($pod) {
            $thumbnail_field = $pod->field('thumbnail');

            if (!empty($thumbnail_field)) {
                $thumbnail = is_array($thumbnail_field)
                    ? ($thumbnail_field['guid'] ?? $thumbnail)
                    : $thumbnail_field;
            }
        }
    }

    return $thumbnail;
}

/**
 * Получает текст о длительном проживании для конкретного объекта недвижимости
 * 
 * Сначала проверяет индивидуальный текст объекта, затем fallback на дефолтный
 *
 * @param int $property_id ID объекта недвижимости
 * @return string Текст о длительном проживании
 */
function realty_get_property_long_stay_text( $property_id = 0 ) {
    // Проверяем индивидуальный текст объекта
    if ( $property_id > 0 ) {
        $property_text = get_post_meta( $property_id, 'property_long_stay_info_text', true );
        if ( ! empty( $property_text ) ) {
            return $property_text;
        }
    }
    
    // Fallback на дефолтный текст из настроек
    $default_text = 'Длительное проживание — это возможность арендовать жилье на срок от 1 месяца и более. При долгосрочной аренде вы можете рассчитывать на специальные условия и скидки от хозяина недвижимости. Свяжитесь с хозяином для уточнения деталей.';
    
    return get_option( 'property_long_stay_info_text', $default_text );
}


/**
 * Получить статусы бронирования
 * 
 * Возвращает массив статусов для использования в админке и на фронте
 * 
 * @return array Массив статусов с ключами: label, class
 */
function realty_get_booking_statuses() {
    return array(
        'pending'     => array( 'label' => 'Ожидание', 'class' => 'pending' ),
        'new'         => array( 'label' => 'Новая заявка', 'class' => 'new' ),
        'in_progress' => array( 'label' => 'В процессе', 'class' => 'in_progress' ),
        'confirmed'   => array( 'label' => 'Подтверждено', 'class' => 'confirmed' ),
        'checkin'     => array( 'label' => 'Заселение', 'class' => 'checkin' ),
        'completed'   => array( 'label' => 'Завершено', 'class' => 'completed' ),
        'cancelled'   => array( 'label' => 'Отменено', 'class' => 'cancelled' ),
    );
}

/**
 * Получить данные конкретного статуса бронирования
 * 
 * @param string $status_key Ключ статуса
 * @return array Данные статуса или дефолтный
 */
function realty_get_booking_status_data( $status_key ) {
    $statuses = realty_get_booking_statuses();
    return isset( $statuses[ $status_key ] ) ? $statuses[ $status_key ] : $statuses['new'];
}

/**
 * Получить типы гостей из настроек
 * 
 * Возвращает массив типов гостей с fallback на дефолтные значения
 * 
 * @return array Массив типов гостей
 */
function realty_get_guest_types_config() {
    return get_option( 'property_guest_types', array(
        array( 'name' => 'adults', 'label' => 'Взрослые', 'desc' => 'от 13 лет', 'min' => 1, 'max' => 10, 'enabled' => true ),
        array( 'name' => 'children', 'label' => 'Дети', 'desc' => 'от 2 до 12 лет', 'min' => 0, 'max' => 8, 'enabled' => true ),
    ) );
}

/**
 * Получить значения гостей из мета-полей сообщения
 * 
 * @param int $message_id ID сообщения
 * @return array Массив значений гостей [ 'adults' => 2, 'children' => 1, ... ]
 */
function realty_get_guests_values_from_message( $message_id ) {
    $guest_types = realty_get_guest_types_config();
    $guests_values = array();
    $guests_labels = array();
    $has_guests = false;
    
    if ( ! empty( $guest_types ) && is_array( $guest_types ) ) {
        foreach ( $guest_types as $guest_type ) {
            if ( empty( $guest_type['enabled'] ) ) {
                continue;
            }
            $guest_name = $guest_type['name'];
            $guest_value = (int) get_post_meta( $message_id, '_' . $guest_name, true );
            $guests_values[ $guest_name ] = array(
                'value' => $guest_value,
                'label' => $guest_type['label'],
                'min'   => $guest_type['min'] ?? 0,
                'max'   => $guest_type['max'] ?? 10,
            );
            $guests_labels[ $guest_name ] = $guest_type['label'];
            if ( $guest_value > 0 ) {
                $has_guests = true;
            }
        }
    }
    
    // Legacy support для старых сообщений с adults/children
    if ( empty( $guests_values ) ) {
        $adults = (int) get_post_meta( $message_id, '_adults', true );
        $children = (int) get_post_meta( $message_id, '_children', true );
        if ( $adults > 0 || $children > 0 ) {
            $guests_values['adults'] = array( 'value' => $adults, 'label' => 'Взрослые', 'min' => 1, 'max' => 10 );
            $guests_values['children'] = array( 'value' => $children, 'label' => 'Дети', 'min' => 0, 'max' => 10 );
            $guests_labels['adults'] = 'Взрослые';
            $guests_labels['children'] = 'Дети';
            $has_guests = ( $adults > 0 || $children > 0 );
        }
    }
    
    return array(
        'values' => $guests_values,
        'labels' => $guests_labels,
        'has_guests' => $has_guests,
    );
}

/**
 * Сформировать текст гостей для отображения
 * 
 * @param array $guests_values Массив значений гостей
 * @param array $guests_labels Массив меток гостей
 * @return string Текст вида "2 Взрослые, 1 Дети"
 */
function realty_format_guests_text( $guests_values, $guests_labels ) {
    $parts = array();
    foreach ( $guests_values as $guest_name => $guest_data ) {
        $value = is_array( $guest_data ) ? ( $guest_data['value'] ?? 0 ) : $guest_data;
        if ( $value > 0 ) {
            $label = $guests_labels[ $guest_name ] ?? $guest_data['label'] ?? '';
            $parts[] = $value . ' ' . $label;
        }
    }
    return implode( ', ', $parts );
}

// ============================================================
// SESSION функции для временного хранения параметров бронирования
// Fallback на WordPress Transients API
// ============================================================

/**
 * Инициализация SESSION для бронирований
 * 
 * Вызывается автоматически при первом обращении.
 * Пробует использовать $_SESSION, fallback на transients.
 */
function realty_init_booking_session() {
    // Пробуем нативный SESSION
    if ( session_status() === PHP_SESSION_NONE ) {
        // Проверяем можно ли запустить session
        if ( ! headers_sent() ) {
            @session_start();
        }
    }
}
add_action( 'init', 'realty_init_booking_session', 1 );

/**
 * Создание временной сессии бронирования
 * 
 * @deprecated 1.0.0 Использовать booking_request со статусом pending
 * @see realty_create_booking_thread()
 * 
 * Сохраняет параметры бронирования до отправки первого сообщения.
 * Использует SESSION с fallback на transients (24 часа TTL).
 * 
 * @param int   $property_id  ID объекта недвижимости
 * @param array $data         Данные бронирования (checkin_date, guests_count, etc.)
 * @return bool Успешность сохранения
 */
function realty_start_booking_session( $property_id, $data = array() ) {
    _deprecated_function( __FUNCTION__, '1.0.0', 'realty_create_booking_thread' );
    return false;
}

/**
 * Получение временной сессии бронирования
 * 
 * @deprecated 1.0.0 booking_request теперь хранится в БД
 * 
 * @return array|false Данные сессии или false если не существует
 */
function realty_get_booking_session() {
    _deprecated_function( __FUNCTION__, '1.0.0' );
    return false;
}

/**
 * Очистка временной сессии бронирования
 * 
 * @deprecated 1.0.0 SESSION больше не используется
 * 
 * Вызывается после создания booking_request.
 */
function realty_clear_booking_session() {
    _deprecated_function( __FUNCTION__, '1.0.0' );
}
