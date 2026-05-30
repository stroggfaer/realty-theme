<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('wp_ajax_get_locations_autocomplete', 'get_locations_autocomplete_callback');
add_action('wp_ajax_nopriv_get_locations_autocomplete', 'get_locations_autocomplete_callback');

function get_locations_autocomplete_callback() {
    if (!wp_verify_nonce($_POST['nonce'], 'property_filter_nonce')) {
        wp_die('Security check failed');
    }
    
    $term = sanitize_text_field($_POST['term']);
    
    $locations = get_terms(array(
        'taxonomy' => 'location',
        'hide_empty' => false,
        'search' => $term,
        'number' => 15
    ));
    
    $results = array();
    if (!is_wp_error($locations)) {
        foreach ($locations as $location) {
            $results[] = array(
                'name' => $location->name,
                'slug' => $location->slug
            );
        }
        
        usort($results, function($a, $b) use ($term) {
            $a_starts_with = stripos($a['name'], $term) === 0;
            $b_starts_with = stripos($b['name'], $term) === 0;
            
            if ($a_starts_with == $b_starts_with) {
                return strcasecmp($a['name'], $b['name']);
            }
            
            return $a_starts_with ? -1 : 1;
        });
        
        $results = array_slice($results, 0, 10);
    }
    
    wp_send_json_success($results);
}

add_action('rest_api_init', function() {
    register_rest_route('property/v1', '/characteristics/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'realty_get_property_characteristics_api',
        'permission_callback' => '__return_true',
    ]);
});


/**
 * REST API: Получить характеристики недвижимости
 *
 * Endpoint:
 * GET /wp-json/property/v1/characteristics/{id}
 *
 * Параметры запроса (GET):
 *
 * @param int    id            (обязательный) ID объекта недвижимости
 *
 * @param string type          Тип вывода:
 *                            - flat (по умолчанию) — плоский массив характеристик
 *                            - all                — сгруппированные характеристики
 *                            - one                — одна группа (требует group_key)
 *
 * @param string group_key     Ключ группы (slug/meta group_key)
 *                            Используется при type=one
 *
 * @param int    group_id      ID группы (альтернатива group_key)
 *                            Фильтрует характеристики только по этой группе
 *
 * @param string system_temp   Системный шаблон группы (meta: group_system_template)
 *                            Фильтрует группы по шаблону
 *
 * @param int    last_count    Режим "Показать ещё":
 *                            - 0 (по умолчанию) — обычная пагинация
 *                            - >0               — игнорирует pagination и limit,
 *                                                возвращает все элементы начиная с offset
 *
 * @param int    page          Номер страницы (по умолчанию 1)
 *
 * @param int    per_page      Кол-во элементов на страницу (по умолчанию 20)
 *
 *
 * Ответ:
 *
 * @return WP_REST_Response {
 *     data: array|object,
 *     meta: {
 *         current_page: int,
 *         page_count: int,
 *         per_page: int,
 *         total: int
 *     },
 *     filters: array
 * }
 */
function realty_get_property_characteristics_apiOld($request) {

    $property_id = absint($request->get_param('id'));

    $options = [
        'type'        => $request->get_param('type') ?: 'flat',
        'group_key'   => $request->get_param('group_key'),
        'group_id'    => absint($request->get_param('group_id')),
        'system_temp' => $request->get_param('system_temp'),
        'last_count'  => (int)$request->get_param('last_count'),
        'page'        => max(1, (int)$request->get_param('page')),
        'per_page'    => (int)$request->get_param('per_page') ?: 20,
    ];

    $saved = get_post_meta($property_id, 'property_characteristics', true);

    if (!is_array($saved) || empty($saved)) {
        return new WP_REST_Response([
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'page_count'   => 0,
                'per_page'     => 0,
                'total'        => 0,
            ],
            'filters' => [],
        ], 200);
    }

    usort($saved, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

    // ===== SYSTEM TEMPLATE =====
    $allowed_group_ids = [];

    if ($options['system_temp']) {
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

    $groups  = [];
    $flat    = [];
    $filters = [];

    foreach ($saved as $item) {

        $char_id = absint($item['characteristic_id'] ?? 0);
        if (!$char_id) continue;

        $char = get_post($char_id);
        if (!$char || $char->post_status !== 'publish') continue;

        $terms = get_the_terms($char_id, 'char_group');
        $term  = (!empty($terms) && !is_wp_error($terms)) ? $terms[0] : null;

        $group_id = $term ? $term->term_id : 0;

        // фильтр по system_temp
        if (!empty($allowed_group_ids) && !in_array($group_id, $allowed_group_ids, true)) {
            continue;
        }

        // фильтр по group_id
        if ($options['group_id'] && $group_id !== $options['group_id']) {
            continue;
        }

        $term_meta = $group_id ? get_term_meta($group_id) : [];

        $group_key     = $term_meta['group_key'][0] ?? ($term->slug ?? '');
        $display_style = $term_meta['display_style'][0] ?? 'standard';
        $active        = $term_meta['active'][0] ?? 1;

        if (!$active) continue;

        // фильтр по group_key
        if ($options['group_key'] && $group_key !== $options['group_key']) {
            continue;
        }

        $char_data = [
            'characteristic_id' => $char_id,
            'group_id'          => $group_id,
            'group_name'        => $term->name ?? '',
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

        // ===== GROUPED =====
        if (!isset($groups[$group_key])) {
            $groups[$group_key] = [
                'group_id'      => $group_id,
                'group_name'    => $term->name ?? '',
                'group_key'     => $group_key,
                'display_style' => $display_style,
                'characteristics' => []
            ];

            // filters
            if (!empty($term_meta['use_in_filters'][0])) {
                $filters[] = [
                    'group_id'   => $group_id,
                    'group_name' => $term->name ?? '',
                    'group_key'  => $group_key,
                ];
            }
        }

        $groups[$group_key]['characteristics'][] = $char_data;
    }

    // ===== RESULT =====
    if ($options['type'] === 'flat') {
        $result = $flat;
    } elseif ($options['type'] === 'one') {
        $result = $groups[$options['group_key']] ?? [];
    } else {
        $result = array_values($groups);
    }

    // ===== LOAD MORE MODE =====
    if ($options['last_count'] > 0 && is_array($result)) {

        $total = count($result);

        $result = array_slice($result, $options['last_count']);

        return new WP_REST_Response([
            'data' => $result,
            'meta' => [
                'current_page' => 1,
                'page_count'   => 1,
                'per_page'     => count($result),
                'total'        => $total,
            ],
            'filters' => $filters,
        ], 200);
    }

    // ===== PAGINATION =====
    $total     = is_array($result) ? count($result) : 0;
    $per_page  = $options['per_page'];
    $page      = $options['page'];
    $pages     = $per_page ? ceil($total / $per_page) : 1;

    if ($per_page) {
        $offset = ($page - 1) * $per_page;
        $result = array_slice($result, $offset, $per_page);
    }

    return new WP_REST_Response([
        'data' => $result,
        'meta' => [
            'current_page' => (int)$page,
            'page_count'   => (int)$pages,
            'per_page'     => (int)$per_page,
            'total'        => (int)$total,
        ],
        'filters' => $filters,
    ], 200);
}



/**
 * REST API: Получить характеристики недвижимости
 */
function realty_get_property_characteristics_api($request) {
    // Получаем ID из URL
    $property_id = absint($request->get_param('id'));
    // Собираем все параметры из GET запроса
    $options = [
        'type'        => $request->get_param('type') ?: 'flat',
        'group_key'   => $request->get_param('group_key'),
        'group_id'    => $request->get_param('group_id') ? absint($request->get_param('group_id')) : null,
        'system_temp' => $request->get_param('system_temp'),
        'last_count'  => $request->get_param('last_count') ? absint($request->get_param('last_count')) : 0,
        'page'        => $request->get_param('page') ? absint($request->get_param('page')) : 1,
        'per_page'    => $request->get_param('per_page') ? absint($request->get_param('per_page')) : 20,
    ];

    // Вызываем логику
    $result = realty_get_property_characteristics($property_id, null, $options);

    return new WP_REST_Response($result, 200);
}

