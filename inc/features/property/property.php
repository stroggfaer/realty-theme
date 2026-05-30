<?php
/**
 * Фича Недвижимости
 * Объединяет: список, фильтрацию, AJAX, шорткоды
 */

if (!defined('ABSPATH')) {
    exit;
}

class PropertyFeature {

    private static $PER_LIMIT = 10;

    private static $instance = null;
    private $nonceAction = 'property_filter_nonce';

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Инициализация всех хуков
     */
    public function init() {
        $this->initAjax();
        $this->initShortcodes();
        $this->initQueryMods();
    }

    // ============================================================
    // CORE: Бизнес-логика
    // ============================================================

    /**
     * Получение списка недвижимости с фильтрацией
     * Основная функция - заменяет properties_list()
     * 
     * @param array $params Параметры фильтрации
     * @return array
     */
    public function getList(array $params = []): array {

        $defaults = [
            'posts_per_page' => self::$PER_LIMIT,
            'paged' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $params = wp_parse_args($params, $defaults);

        // Базовые параметры запроса
        $args = [
            'post_type' => 'property',
            'post_status' => 'publish',
            'posts_per_page' => $params['posts_per_page'],
            'paged' => $params['paged'],
            'orderby' => $params['orderby'],
            'order' => $params['order'],
        ];

        // Локация
        $location = $params['location'] ?? '';
        $location_slug = $params['location_slug'] ?? '';
        $location_term = null;
        if (!empty($location_slug)) {
            $location_term = get_term_by('slug', $location_slug, 'location');
            if (!$location_term) {
                // Попробовать с sanitize_title (латиница)
                $location_term = get_term_by('slug', sanitize_title($location_slug), 'location');
            }
        } elseif (!empty($location)) {
            $location_term = get_term_by('slug', sanitize_title($location), 'location');
            if (!$location_term) {
                $location_term = get_term_by('name', sanitize_text_field($location), 'location');
            }
        }
        if ($location_term) {
            $args['tax_query'] = [[
                'taxonomy' => 'location',
                'field' => 'term_id',
                'terms' => $location_term->term_id,
            ]];
        }

        // Тип жилья
        if (!empty($params['property_types']) && is_array($params['property_types'])) {
            $type_ids = array_map('intval', $params['property_types']);
            $type_clause = [
                'taxonomy' => 'property_type',
                'field'    => 'term_id',
                'terms'    => $type_ids,
                'operator' => 'IN',
            ];
            if (!empty($args['tax_query'])) {
                $args['tax_query'][] = $type_clause;
                $args['tax_query']['relation'] = 'AND';
            } else {
                $args['tax_query'] = [$type_clause];
            }
        }

        // Цена
        $meta_query = [];
        if (!empty($params['price_min'])) {
            $meta_query[] = [
                'key' => 'price',
                'value' => intval($params['price_min']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
        if (!empty($params['price_max'])) {
            $meta_query[] = [
                'key' => 'price',
                'value' => intval($params['price_max']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        // Гости - суммируем все типы гостей динамически
        $guest_types = get_option('property_guest_types', []);
        
        // Debug: логируем что происходит
        error_log('[Property Filter] Guest types from option: ' . print_r($guest_types, true));
        error_log('[Property Filter] Params received: ' . print_r(array_keys($params), true));
        
        // Если настройки еще не созданы, используем пустой массив (не хардкод!)
        $total_guests = 0;
        
        if (!empty($guest_types) && is_array($guest_types)) {
            foreach ($guest_types as $guest_type) {
                $guest_name = $guest_type['name'] ?? '';
                if (!empty($guest_name) && !empty($params[$guest_name])) {
                    $guest_value = intval($params[$guest_name]);
                    $total_guests += $guest_value;
                    error_log("[Property Filter] Adding guest type '{$guest_name}': {$guest_value}");
                }
            }
        }
        
        error_log("[Property Filter] Total guests calculated: {$total_guests}");
        
        if ($total_guests > 0) {
            $meta_query[] = [
                'key' => 'guests_count',
                'value' => $total_guests,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
            error_log("[Property Filter] Meta query added: guests_count >= {$total_guests}");
        } else {
            error_log("[Property Filter] No guest filter applied (total_guests = 0)");
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        // Выполняем запрос
        $query = new WP_Query($args);

        // Сохраняем общее количество найденных записей ДО фильтрации по характеристикам
        $total_found = $query->found_posts;
        
        // Фильтрация по характеристикам (после основного запроса)
        // Логика OR: объект должен иметь ХОТЯ БЫ ОДНУ из выбранных характеристик
        $filtered_posts = $query->posts;
        $has_characteristics_filter = !empty($params['characteristics']) && is_array($params['characteristics']);
        
        if ($has_characteristics_filter) {
            $characteristics = array_map('intval', $params['characteristics']);
            
            // Если есть фильтр по характеристикам, нужно получить ВСЕ записи для правильного подсчета
            // Создаем новый запрос без пагинации
            $args_all = $args;
            $args_all['posts_per_page'] = -1;
            $args_all['paged'] = 1;
            $query_all = new WP_Query($args_all);
            
            // Фильтруем ВСЕ записи
            $all_filtered = array_filter($query_all->posts, function($post) use ($characteristics) {
                $saved = get_post_meta($post->ID, 'property_characteristics', true);
                
                if (!is_array($saved) || empty($saved)) {
                    return false;
                }
                
                $property_char_ids = array_column($saved, 'characteristic_id');
                $property_char_ids = array_map('intval', $property_char_ids);
                
                // ХОТЯ БЫ ОДНА характеристика должна совпадать
                foreach ($characteristics as $char_id) {
                    if (in_array($char_id, $property_char_ids, true)) {
                        return true;
                    }
                }
                return false;
            });
            
            $all_filtered = array_values($all_filtered);
            
            // Обновляем total на основе отфильтрованных записей
            $total_found = count($all_filtered);
            
            // Применяем пагинацию к отфильтрованным записям
            $offset = ($params['paged'] - 1) * $params['posts_per_page'];
            $filtered_posts = array_slice($all_filtered, $offset, $params['posts_per_page']);
        }

        // Подготавливаем координаты для карты
        $coordinates = [];

        foreach ($filtered_posts as $post) {
            $lat = get_post_meta($post->ID, 'latitude', true);
            $lng = get_post_meta($post->ID, 'longitude', true);
            if ($lat && $lng) {
                // Получаем изображения из Pods (используем существующую функцию)
                $gallery_data = function_exists('pod_get_gallery_data') 
                    ? pod_get_gallery_data($post->ID, 'property-teaser', false) 
                    : [];
                
                // Берем первое изображение как thumbnail
                $first_image = !empty($gallery_data) ? ($gallery_data[0]['src'] ?? '') : '';
                
                // Ограничиваем галерею до 5 изображений для popover
                $images = array_slice($gallery_data, 0, 5);
                
                // Получаем стандартный thumbnail (fallback)
                $thumbnail = get_the_post_thumbnail_url($post->ID, 'property-teaser');

                $is_favorite = false;
                if (is_user_logged_in()) {
                    $favorites = get_user_meta(get_current_user_id(), '_real_property_favorites', true);
                    $favorites = is_array($favorites) ? $favorites : array();
                    $is_favorite = in_array($post->ID, $favorites, true);
                }

                $coordinates[] = [
                    'id' => $post->ID,
                    'lat' => $lat,
                    'lng' => $lng,
                    'title' => $post->post_title,
                    'price' => number_format((float) get_post_meta($post->ID, 'price', true), 0, '.', ' '),
                    'price_period' => get_post_meta($post->ID, 'hours_limit', true) ?: 'сутки',
                    // Thumbnail: сначала images[0], потом стандартный thumbnail, потом пустая строка
                    'thumbnail' => $first_image ?: ($thumbnail ?: ''),
                    // Массив изображений для слайдера (максимум 5)
                    'images' => array_map(function($img) {
                        return $img['src'] ?? '';
                    }, $images),
                    'rating' => 4.1, // временно статика
                    'is_favorite' => $is_favorite, // временно статика
                    'permalink' => get_permalink($post->ID),
                ];
            }
        }

        return [
            'posts' => $filtered_posts,
            'total' => $total_found,
            'max_pages' => ceil($total_found / $params['posts_per_page']),
            'coordinates' => $coordinates,
        ];
    }

    /**
     * Единая точка получения и нормализации всех фильтров
     *
     * @param string $context 'get' | 'post' | 'request'  — откуда брать данные
     * @param array  $overrides Дополнительные/принудительные значения (удобно для AJAX)
     * @return array Нормализованные фильтры + служебные поля
     */
    public function formatRequestParams(string $context = 'request', array $overrides = []): array
    {
        // 1. Определяем источник данных
        $source = match (strtolower($context)) {
            'post'    => $_POST,
            'get'     => $_GET,
            default   => $_REQUEST,
        };

        $term = get_queried_object();

        $location = '';
        $location_slug = '';

        if (!empty($term) && !empty($term->slug) && !empty($term->name)) {
            $location = $term->name;
            $location_slug = $term->slug;
        }


        // 2. Базовые значения + overrides
        // Динамически добавляем guest типы из настроек
        $guest_types = get_option('property_guest_types', []);
        $defaults = [
                'location'      => $location,
                'location_slug' => $location_slug,
                'checkin_date'  => '',
                'checkout_date' => '',
                'price_min'     => 0,
                'price_max'     => 0,
                'paged'         => 1,
                'characteristics' => [],
                'property_types'  => [],
        ];
        
        // Добавляем динамические guest типы с дефолтными значениями
        if (!empty($guest_types) && is_array($guest_types)) {
            foreach ($guest_types as $guest_type) {
                $guest_name = $guest_type['name'] ?? '';
                $guest_min = $guest_type['min'] ?? 0;
                if (!empty($guest_name)) {
                    // Для GET контекста используем min, для POST - 0
                    $defaults[$guest_name] = ($context === 'get') ? $guest_min : 0;
                }
            }
        }
        
        $filters = array_merge($defaults, $overrides);

        // 3. Локация — самая сложная часть, делаем аккуратно
        $loc_raw = $source['location']      ?? $filters['location'];
        $loc_slug = $source['location_slug'] ?? $filters['location_slug'];

        if ($loc_raw !== '') {
            $filters['location'] = sanitize_text_field(wp_unslash($loc_raw));
        }
        if ($loc_slug !== '') {
            // Декодируем URL, обрабатывая двойную кодировку
            $decoded_slug = urldecode($loc_slug);
            if (preg_match('/%[0-9A-Fa-f]{2}/', $decoded_slug)) {
                $decoded_slug = urldecode($decoded_slug);
            }
            $filters['location_slug'] = sanitize_text_field($decoded_slug);
            if (empty($filters['location'])) {
                $filters['location'] = $filters['location_slug'];
            }
        }

        // 4. Даты — простая проверка формата
        foreach (['checkin_date', 'checkout_date'] as $key) {
            $val = $source[$key] ?? $filters[$key];
            if ($val && preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                $filters[$key] = sanitize_text_field($val);
            }
        }

        // 5. Числовые поля с минимальными значениями
        // Динамически собираем все guest типы
        $numeric_fields = [
                'price_min' => ['min' => 0],
                'price_max' => ['min' => 0],
                'paged'     => ['min' => 1],
        ];
        
        // Добавляем guest типы в numeric_fields
        if (!empty($guest_types) && is_array($guest_types)) {
            foreach ($guest_types as $guest_type) {
                $guest_name = $guest_type['name'] ?? '';
                $guest_min = $guest_type['min'] ?? 0;
                if (!empty($guest_name)) {
                    $numeric_fields[$guest_name] = [
                        'min' => ($context === 'get') ? $guest_min : 0,
                        'default' => ($context === 'get') ? $guest_min : 0
                    ];
                }
            }
        }

        foreach ($numeric_fields as $key => $rules) {
            $val = $source[$key] ?? $filters[$key];
            if (isset($source['page']) && $key === 'paged') {  // поддержка AJAX page
                $val = $source['page'];
            }
            $filters[$key] = max($rules['min'] ?? 0, intval($val ?: $rules['default'] ?? 0));
        }

        // 6. Защита от мусора
        // Динамически собираем ключи для фильтрации
        $numeric_keys_to_filter = ['paged', 'price_min', 'price_max'];
        if (!empty($guest_types) && is_array($guest_types)) {
            foreach ($guest_types as $guest_type) {
                $guest_name = $guest_type['name'] ?? '';
                if (!empty($guest_name)) {
                    $numeric_keys_to_filter[] = $guest_name;
                }
            }
        }
        
        $filters = array_filter($filters, function ($v, $k) use ($numeric_keys_to_filter) {
            if (in_array($k, $numeric_keys_to_filter)) {
                return $v > 0; // убираем нулевые числовые, если не нужны
            }
            // Не удаляем массивы (characteristics)
            if (is_array($v)) {
                return true;
            }
            return $v !== '';
        }, ARRAY_FILTER_USE_BOTH);

        // 7. Обработка характеристик
        if (isset($source['characteristics'])) {
            $chars = $source['characteristics'];
            
            // Если пришла строка JSON, декодируем
            if (is_string($chars)) {
                $decoded = json_decode($chars, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $chars = $decoded;
                }
            }
            
            // Преобразуем в массив целых чисел
            if (is_array($chars)) {
                $chars = array_map('intval', array_filter($chars, 'is_numeric'));
                if (!empty($chars)) {
                    $filters['characteristics'] = $chars;
                }
            }
        }

        // 8. Обработка типов жилья
        if (isset($source['property_types'])) {
            $types = $source['property_types'];
            if (is_string($types)) {
                $decoded = json_decode($types, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $types = $decoded;
                }
            }
            if (is_array($types)) {
                $types = array_map('intval', array_filter($types, 'is_numeric'));
                if (!empty($types)) {
                    $filters['property_types'] = $types;
                }
            }
        }

        return $filters;
    }

    /**
     * Получение списка недвижимости из GET параметров
     * Используется для рендеринга на странице архива
     * 
     * @return array
     */
    public function getListFromGet(): array {
        $filters = $this->formatRequestParams('GET');
        return $this->getList($filters);
    }

    /**
     * Получение активных фильтров из GET
     * 
     * @return array
     */
    public function getCurrentFilters(): array {
        $params = $this->formatRequestParams('GET');
        $filters = [];

        // Локация
        if (!empty($params['location_slug'])) {
            $loc_slug = $params['location_slug'];
            $term = get_term_by('slug', $loc_slug, 'location');
            if (!$term) {
                $term = get_term_by('slug', sanitize_title($loc_slug), 'location');
            }
            if ($term && !is_wp_error($term)) {
                $filters[] = [
                    'key' => 'location',
                    'label' => 'Город',
                    'value' => $term->name,
                    'raw' => $loc_slug,
                    'type' => 'taxonomy',
                ];
            }
        } elseif (!empty($params['location'])) {
            $loc_slug = urldecode($params['location']);
            $term = get_term_by('slug', sanitize_text_field($loc_slug), 'location');
            if (!$term) {
                $term = get_term_by('name', sanitize_text_field($loc_slug), 'location');
            }
            if ($term && !is_wp_error($term)) {
                $filters[] = [
                    'key' => 'location',
                    'label' => 'Город',
                    'value' => $term->name,
                    'raw' => $loc_slug,
                    'type' => 'taxonomy',
                ];
            }
        }

        // Даты
        if (!empty($params['checkin_date'])) {
            $filters[] = [
                'key' => 'checkin_date',
                'label' => 'Заезд',
                'value' => esc_html($params['checkin_date']),
                'type' => 'date',
            ];
        }

        if (!empty($params['checkout_date'])) {
            $filters[] = [
                'key' => 'checkout_date',
                'label' => 'Выезд',
                'value' => esc_html($params['checkout_date']),
                'type' => 'date',
            ];
        }

        // Гости - динамическая обработка (без хардкода!)
        $guest_types = get_option('property_guest_types', []);
        
        if (!empty($guest_types) && is_array($guest_types)) {
            foreach ($guest_types as $guest_type) {
                $guest_name = $guest_type['name'] ?? '';
                $guest_label = $guest_type['label'] ?? $guest_name;
                
                if (!empty($guest_name) && !empty($params[$guest_name])) {
                    $filters[] = [
                        'key' => $guest_name,
                        'label' => $guest_label,
                        'value' => $params[$guest_name],
                        'type' => 'guests',
                    ];
                }
            }
        }

        return $filters;
    }

    private function initQueryMods() {
        add_action('pre_get_posts', [$this, 'modify_property_archive_query']);
    }

    /**
     * Модификация основного запроса для архива property
     */
    public function modify_property_archive_query($query) {
        if (!is_admin() && $query->is_main_query() && is_post_type_archive('property')) {
            $params = $this->formatRequestParams('GET');

            // Устанавливаем пагинацию и сортировку
            $query->set('posts_per_page', $params['posts_per_page'] ?? self::$PER_LIMIT);
            $query->set('paged', $params['paged'] ?? 1);
            $query->set('orderby', $params['orderby'] ?? 'date');
            $query->set('order', $params['order'] ?? 'DESC');

            // Локация
            $location = $params['location'] ?? '';
            $location_slug = $params['location_slug'] ?? '';
            $location_term = null;
            if (!empty($location_slug)) {
                $location_term = get_term_by('slug', $location_slug, 'location');
                if (!$location_term) {
                    $location_term = get_term_by('slug', sanitize_title($location_slug), 'location');
                }
            } elseif (!empty($location)) {
                $location_term = get_term_by('slug', sanitize_title($location), 'location');
                if (!$location_term) {
                    $location_term = get_term_by('name', sanitize_text_field($location), 'location');
                }
            }
            if ($location_term) {
                $query->set('tax_query', [[
                    'taxonomy' => 'location',
                    'field' => 'term_id',
                    'terms' => $location_term->term_id,
                ]]);
            }

            // Тип жилья
            if (!empty($params['property_types']) && is_array($params['property_types'])) {
                $type_ids = array_map('intval', $params['property_types']);
                $type_clause = [
                    'taxonomy' => 'property_type',
                    'field' => 'term_id',
                    'terms' => $type_ids,
                    'operator' => 'IN',
                ];
                $existing_tax = $query->get('tax_query') ?: [];
                if (!empty($existing_tax)) {
                    $existing_tax[] = $type_clause;
                    $existing_tax['relation'] = 'AND';
                } else {
                    $existing_tax = [$type_clause];
                }
                $query->set('tax_query', $existing_tax);
            }

            // Цена и гости
            $meta_query = [];
            if (!empty($params['price_min'])) {
                $meta_query[] = [
                    'key' => 'price',
                    'value' => intval($params['price_min']),
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ];
            }
            if (!empty($params['price_max'])) {
                $meta_query[] = [
                    'key' => 'price',
                    'value' => intval($params['price_max']),
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                ];
            }
            
            // Гости - суммируем все типы гостей динамически
            $guest_types = get_option('property_guest_types', []);
            
            $total_guests = 0;
            
            if (!empty($guest_types) && is_array($guest_types)) {
                foreach ($guest_types as $guest_type) {
                    $guest_name = $guest_type['name'] ?? '';
                    if (!empty($guest_name) && !empty($params[$guest_name])) {
                        $total_guests += intval($params[$guest_name]);
                    }
                }
            }
            
            if ($total_guests > 0) {
                $meta_query[] = [
                    'key' => 'guests_count',
                    'value' => $total_guests,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ];
            }
            if (!empty($meta_query)) {
                $query->set('meta_query', $meta_query);
            }

            // Характеристики не модифицируем в основном запросе, так как логика OR сложная
        }
    }

    // ============================================================
    // AJAX: Обработчики
    // ============================================================

    private function initAjax() {
        // Новый action
        add_action('wp_ajax_property_filter', [$this, 'ajaxFilter']);
        add_action('wp_ajax_nopriv_property_filter', [$this, 'ajaxFilter']);
        
        // Обратная совместимость - старый action
        add_action('wp_ajax_filter_properties_custom', [$this, 'ajaxFilter']);
        add_action('wp_ajax_nopriv_filter_properties_custom', [$this, 'ajaxFilter']);
        
        // Автодополнение локаций
        add_action('wp_ajax_location_autocomplete', [$this, 'ajaxLocationAutocomplete']);
        add_action('wp_ajax_nopriv_location_autocomplete', [$this, 'ajaxLocationAutocomplete']);
        add_action('wp_ajax_get_locations_autocomplete', [$this, 'ajaxLocationAutocomplete']);
        add_action('wp_ajax_nopriv_get_locations_autocomplete', [$this, 'ajaxLocationAutocomplete']);
    }

    /**
     * AJAX обработчик фильтрации
     */
    public function ajaxFilterOld() {
        // Проверка nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $this->nonceAction)) {
            wp_send_json_error('Security check failed');
        }

        // Получаем параметры через унифицированный метод
        $params = $this->formatRequestParams('POST');
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $params['page'] = $page;

        // Получаем результаты
        $result = $this->getList($params);

        ob_start();

        if (!empty($result['posts'])) {
            echo '<div class="properties-grid">';
            foreach ($result['posts'] as $post) {
                setup_postdata($post);
                $pod = pods('property', $post->ID);
                get_template_part('template-parts/component/property-card', null, ['pod' => $pod]);
            }
            echo '</div>';

            // Пагинация
            $pagination = '';
            $posts_per_page = $params['posts_per_page'] ?? 12;
            $total_pages = ceil($result['total'] / $posts_per_page);
            if ($total_pages > 1) {
                $pagination = '<ul class="pods-pagination">';
                for ($i = 1; $i <= min($total_pages, 5); $i++) {
                    if ($i == $page) {
                        $pagination .= '<li><span class="current">' . $i . '</span></li>';
                    } else {
                        $pagination .= '<li><a href="#" data-page="' . $i . '">' . $i . '</a></li>';
                    }
                }
                $pagination .= '</ul>';
            }

            wp_send_json_success([
                'html' => ob_get_clean(),
                'count' => $result['total'],
                'total_pages' => $total_pages,
                'pagination' => $pagination,
                'coordinates' => $result['coordinates'],
            ]);
        } else {
            wp_send_json_success([
                'html' => '<p>Недвижимость не найдена.</p>',
                'count' => 0,
                'total_pages' => 0,
                'pagination' => '',
                'coordinates' => [],
            ]);
        }
    }
    public function ajaxFilter() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $this->nonceAction)) {
            wp_send_json_error('Security check failed');
        }

        $params = $this->formatRequestParams('POST');

        $page     = max(1, intval($_POST['page'] ?? 1));
        $per_page = max(self::$PER_LIMIT, intval($_POST['per_page'] ?? self::$PER_LIMIT));

        $params['paged']          = $page;
        $params['posts_per_page'] = $per_page;

        $result = $this->getList($params);

        ob_start();
        if (!empty($result['posts'])) {
            foreach ($result['posts'] as $post) {
                setup_postdata($post);
                $pod = pods('property', $post->ID);
                get_template_part('template-parts/component/property-card', null, ['pod' => $pod, 'show_map_location' => true]);
            }
            $html = ob_get_clean();
        } else {
            $html = '';
            ob_end_clean();
        }

        $total_pages = $result['max_pages'] ?? ceil($result['total'] / $per_page);

        wp_send_json_success([
                'html' => $html,
                'meta' => [
                        'current_page' => (int)$page,
                        'page_count'   => (int)$total_pages,
                        'per_page'     => (int)$per_page,
                        'total'        => (int)$result['total']
                ],
                'coordinates' => $result['coordinates'] ?? []
        ]);
    }

    /**
     * AJAX автодополнение локаций
     */
    public function ajaxLocationAutocomplete() {
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], $this->nonceAction)) {
            wp_send_json_error('Security check failed');
        }

        $search_term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        if (empty($search_term)) {
            wp_send_json_error('Empty search term');
        }

        $locations = get_terms([
            'taxonomy' => 'location',
            'name__like' => $search_term,
            'hide_empty' => false,
            'number' => 10,
        ]);

        $results = [];
        foreach ($locations as $location) {
            $results[] = [
                'label' => $location->name,
                'value' => $location->slug,
            ];
        }

        wp_send_json($results);
    }

    // ============================================================
    // RENDER: HTML вывод
    // ============================================================

    private function initShortcodes() {
        add_shortcode('property_filter', [$this, 'shortcodeFilter']);
    }

    /**
     * Шорткод [property_filter]
     */
    public function shortcodeFilter($atts): string {
        $term = get_queried_object();
        $location = '';
        if (!empty($term) && !empty($term->slug) && !empty($term->name)) {
            $location = $term->name;
        }

        $context = $atts['context'] ?? 'default';

        $active_filters = get_option('property_active_filters', [
            'location', 'checkin_date', 'checkout_date', 'adults', 'children',
        ]);

        $price_range = get_option('property_price_range', [
            'min' => 0,
            'max' => 1000000,
            'step' => 0,
        ]);

        $container_id = ($context === 'archive') ? 'property-filter-vue-archive' : 'property-filter-vue';

        $config = [
                'price_range' => $price_range,
                'guests' => array_values(get_option('property_guest_types', [
                    // Fallback defaults if option doesn't exist
                    [
                        'name' => 'adults',
                        'label' => 'Взрослые',
                        'desc' => 'от 13 лет',
                        'min' => 1,
                        'max' => 10,
                    ],
                    [
                        'name' => 'children',
                        'label' => 'Дети',
                        'desc' => 'от 2 до 12 лет',
                        'min' => 0,
                        'max' => 8,
                    ]
                ])),
                'filter_characteristics' => realty_get_filter_characteristics(),
                'property_types' => realty_get_property_types_for_filter(),
                'location' => $location
        ];

        $data = [
                'containerId' => $container_id,
                'activeFilters' => $active_filters,
                'initFilters' => $this->getCurrentFilters(),
                'priceRange' => $price_range,
                'config' => $config,
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce($this->nonceAction),
        ];

        $propertyFilterData = wp_json_encode(
            $data,
            JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        ob_start();
        ?>
        <div id="<?php echo esc_attr($container_id); ?>"></div>

        <script type="module">
            const { ref } = Vue;
            const { createAppModule, PropertyFilter } = window.VueAppModule;
            const app = createAppModule({
                components: {
                    PropertyFilter
                },
                template: `
                    <property-filter
                        :context="propertyFilterData.containerId"
                        :active-filters="propertyFilterData.activeFilters"
                        :init-filters="propertyFilterData.initFilters"
                        :price-range="propertyFilterData.priceRange"
                        :config="propertyFilterData.config"
                        :ajax-url="propertyFilterData.ajaxUrl"
                        :nonce="propertyFilterData.nonce"
                    />
                `,
                setup() {
                    const propertyFilterData = ref(<?=$propertyFilterData?>);
                    return {
                        propertyFilterData
                    };
                }
            });

            app.mount('#<?php echo esc_attr($container_id); ?>');
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Рендер формы для главной страницы (GET форма)
     */
    public function renderHomeForm($args = []): string {
        $active_filters = get_option('property_active_filters', [
            'location', 'checkin_date', 'checkout_date', 'adults', 'children',
        ]);

        $location = null;

        if (!empty($args['location']) && $args['location'] instanceof WP_Term) {
            $location = [
                    'id'   => (int) $args['location']->term_id,
                    'name' => $args['location']->name,
                    'slug' => $args['location']->slug,
            ];
        }

        $config = [
            'guests' => array_values(get_option('property_guest_types', [
                // Fallback defaults if option doesn't exist
                [
                    'name' => 'adults',
                    'label' => 'Взрослые',
                    'desc' => 'от 13 лет',
                    'min' => 1,
                    'max' => 10,
                ],
                [
                    'name' => 'children',
                    'label' => 'Дети',
                    'desc' => 'от 2 до 12 лет',
                    'min' => 0,
                    'max' => 8,
                ]
            ]))
        ];

        $data = [
            'activeFilters' => $active_filters,
            'config' => $config,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->nonceAction),
            'location' => $location
        ];

        $propertyFilterData = wp_json_encode(
            $data,
            JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        ob_start();
        ?>
        <div id="property-home-filter-vue"></div>

        <script type="module">
            (function() {
                const { ref } = Vue;
                const { createAppModule, HomePropertyFilter } = window.VueAppModule;
                
                const homeApp = createAppModule({
                    components: {
                        HomePropertyFilter
                    },
                    template: `
                        <home-property-filter
                            :active-filters="propertyFilterData.activeFilters"
                            :config="propertyFilterData.config"
                            :ajax-url="propertyFilterData.ajaxUrl"
                            :nonce="propertyFilterData.nonce"
                            :location="propertyFilterData.location"
                        />
                    `,
                    setup() {
                        const propertyFilterData = ref(<?php echo $propertyFilterData; ?>);
                        return {
                            propertyFilterData
                        };
                    }
                });

                homeApp.mount('#property-home-filter-vue');
            })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Рендер списка недвижимости (для страницы архива)
     */
    public function renderList(): string {
        $result = $this->getListFromGet();

        ob_start();

        if (!empty($result['posts'])) {
            echo '<div class="properties-grid">';
            while (have_posts()) {
                the_post();
                $pod = pods('property', get_the_ID());
                get_template_part('template-parts/component/property-card', null, ['pod' => $pod, 'show_map_location' => true]);
            }
            echo '</div>';

            echo '<div class="results-info">';
            $total_obj = declension(['объект', 'объекта', 'объектов'], $result['total']);
            echo '<p>Всего ' . $result['total'] . ' ' . $total_obj[1] . '</p>';
            echo '</div>';

            // Пагинация
            global $wp_query;
            if ($wp_query->max_num_pages > 1) {
                echo '<div class="archive-pagination">';
                echo '<ul class="pods-pagination">';

                $current_page = max(1, get_query_var('paged'));

                if ($current_page > 1) {
                    echo '<li><a href="' . esc_url(get_pagenum_link($current_page - 1)) . '">&laquo;</a></li>';
                }

                for ($i = 1; $i <= min($wp_query->max_num_pages, 5); $i++) {
                    if ($i == $current_page) {
                        echo '<li><span class="current">' . $i . '</span></li>';
                    } else {
                        echo '<li><a href="' . esc_url(get_pagenum_link($i)) . '">' . $i . '</a></li>';
                    }
                }

                if ($current_page < $wp_query->max_num_pages) {
                    echo '<li><a href="' . esc_url(get_pagenum_link($current_page + 1)) . '">&raquo;</a></li>';
                }

                echo '</ul>';
                echo '</div>';
            }

            wp_reset_postdata();
        } else {
            echo '<div class="no-results">';
            echo '<p>Недвижимость не найдена.</p>';
            if (current_user_can('administrator')) {
                echo '<p style="color: red; font-size: 12px;">Отладка: post_type=property, статус=publish.</p>';
            }
            echo '</div>';
        }

        return ob_get_clean();
    }


    // ============================================================
    // Для обратной совместимости - функции-обёртки
    // ============================================================

    /**
     * Обёртка для обратной совместимости - заменяет properties_list()
     */
    public function properties_list($params = array()) {
        return $this->getList($params);
    }

    /**
     * Обёртка для обратной совместимости
     */
    public function property_filter_render_form($context = 'default') {
        return $this->shortcodeFilter(['context' => $context]);
    }

    /**
     * Обёртка для обратной совместимости
     */
    public function property_filter_render_home_form() {
        return $this->renderHomeForm();
    }

    /**
     * Обёртка для обратной совместимости
     *
     * @param array $params Параметры фильтрации. Если пустой - использует GET-параметры
     */
    public function property_filter_render_list() {
        $result = $this->getListFromGet();

        ob_start();
        if (!empty($result['posts'])) {
            foreach ($result['posts'] as $post) {
                setup_postdata($post);
                $pod = pods('property', $post->ID);
                get_template_part('template-parts/component/property-card', null, ['pod' => $pod, 'show_map_location' => true]);
            }
            $html = ob_get_clean();
        } else {
            $html = '<div class="no-results">Недвижимость не найдена.</div>';
            ob_end_clean();
        }

        wp_reset_postdata();

        $page = max(1, get_query_var('paged', 1));
        $per_page = get_option('posts_per_page', self::$PER_LIMIT);
        $total_pages = $result['max_pages'] ?? ceil($result['total'] / $per_page);

        return [
            'html' => $html,
            'meta' => [
                'current_page' => (int)$page,
                'page_count'   => (int)$total_pages,
                'per_page'     => (int)$per_page,
                'total'        => (int)$result['total']
            ],
            'coordinates' => $result['coordinates'] ?? []
        ];
    }

    /**
     * Обёртка для обратной совместимости - get_current_filters_data()
     */
    public function get_current_filters_data() {
        return $this->getCurrentFilters();
    }
}
