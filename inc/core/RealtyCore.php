<?php
/**
 * Базовый класс темы Realty
 * Содержит общие методы для регистрации CPT, таксономий, утилиты
 */

if (!defined('ABSPATH')) {
    exit;
}

class RealtyCore {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Регистрация всех Post Types и Taxonomies
     */
    public function registerPostTypes() {
        // CPT зарегистрированы в pods-registration.php
    }

    /**
     * Подгрузка view файла
     * 
     * @param string $name Имя файла (без расширения)
     * @param array $data Данные для передачи в view
     * @return string
     */
    public static function loadView(string $name, array $data = []): string {
        $template_path = get_template_directory() . "/views/{$name}.php";
        
        if (!file_exists($template_path)) {
            return '';
        }

        if (!empty($data)) {
            extract($data);
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Универсальный метод для получения постов
     * 
     * @param string $post_type
     * @param array $params
     * @return array
     */
    public static function getPosts(string $post_type, array $params = []): array {
        $defaults = [
            'posts_per_page' => 12,
            'paged' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $params = wp_parse_args($params, $defaults);

        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $params['posts_per_page'],
            'paged' => $params['paged'],
            'orderby' => $params['orderby'],
            'order' => $params['order'],
        ];

        // Добавляем tax_query если передан
        if (!empty($params['tax_query'])) {
            $args['tax_query'] = $params['tax_query'];
        }

        // Добавляем meta_query если передан
        if (!empty($params['meta_query'])) {
            $args['meta_query'] = $params['meta_query'];
        }

        $query = new WP_Query($args);

        return [
            'posts' => $query->posts,
            'total' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
            'query' => $query,
        ];
    }

    /**
     * Проверка и создание nonce
     */
    public static function createNonce(string $action): string {
        return wp_create_nonce($action);
    }

    /**
     * Проверка nonce
     */
    public static function verifyNonce(string $nonce, string $action): bool {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Получение URL для AJAX
     */
    public static function getAjaxUrl(): string {
        return admin_url('admin-ajax.php');
    }

    /**
     * Получение метаполя с fallback
     */
    public static function getPostMeta(int $post_id, string $key, $default = '') {
        $value = get_post_meta($post_id, $key, true);
        return !empty($value) ? $value : $default;
    }
}
