<?php
/**
 * Универсальный шаблон Breadcrumbs
 *
 * Параметры через $args:
 * - 'current_page' (string) - текущая страница для простых breadcrumbs
 * - 'term' (WP_Term) - термин таксономии для детальной страницы термина
 * - 'taxonomy' (string) - название таксономии (по умолчанию 'location')
 * - 'taxonomy_archive_url' (string) - URL архива таксономии
 * - 'taxonomy_archive_label' (string) - название архива таксономии
 * - 'show_current_post' (bool) - показывать ли текущий пост в конце (по умолчанию true)
 */
if (!defined('ABSPATH')) {
    exit;
}

$current_page = $args['current_page'] ?? '';
$term = $args['term'] ?? null;
$taxonomy = $args['taxonomy'] ?? 'location';
$taxonomy_archive_url = $args['taxonomy_archive_url'] ?? '';
$taxonomy_archive_label = $args['taxonomy_archive_label'] ?? '';
$show_current_post = $args['show_current_post'] ?? true;

echo '<nav class="breadcrumb__com" aria-label="breadcrumb">';
echo '<a href="' . esc_url(home_url('/')) . '">Главная</a> <span>/</span> ';

// Случай 1: Простой breadcrumb с одним уровнем (для архивов)
if (!empty($current_page) && !$term && !is_singular()) {
    echo '<span class="breadcrumb_name">' . esc_html($current_page) . '</span>';
}
// Случай 2: Детальная страница термина таксономии
elseif ($term && !is_wp_error($term)) {
    // Добавляем ссылку на архив таксономии если указана
    if ($taxonomy_archive_url && $taxonomy_archive_label) {
        echo '<a href="' . esc_url($taxonomy_archive_url) . '">' . esc_html($taxonomy_archive_label) . '</a> <span>/</span> ';
    }
    
    // Выводим иерархию предков термина
    $ancestors = get_ancestors($term->term_id, $taxonomy);
    $ancestors = array_reverse($ancestors);
    foreach ($ancestors as $ancestor_id) {
        $ancestor = get_term($ancestor_id, $taxonomy);
        if ($ancestor && !is_wp_error($ancestor)) {
            $ancestor_link = get_term_link($ancestor);
            if (is_wp_error($ancestor_link)) {
                $ancestor_link = home_url('/' . $taxonomy . '/' . $ancestor->slug . '/');
            }
            echo '<a href="' . esc_url($ancestor_link) . '">' . esc_html($ancestor->name) . '</a> <span>/</span> ';
        }
    }
    
    // Текущий термин
    echo '<span class="breadcrumb_name">' . esc_html($term->name) . '</span>';
}
// Случай 3: Детальная страница поста с таксономией
elseif (is_singular() && $show_current_post) {
    $location_terms = get_the_terms(get_the_ID(), $taxonomy);
    if (!empty($location_terms) && !is_wp_error($location_terms)) {
        // Если терминов несколько, берем первый
        $term = $location_terms[0];
        
        // Добавляем ссылку на архив таксономии если указана
        if ($taxonomy_archive_url && $taxonomy_archive_label) {
            echo '<a href="' . esc_url($taxonomy_archive_url) . '">' . esc_html($taxonomy_archive_label) . '</a> <span>/</span> ';
        }
        
        // Выводим иерархию предков термина
        $ancestors = get_ancestors($term->term_id, $taxonomy);
        $ancestors = array_reverse($ancestors);
        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term($ancestor_id, $taxonomy);
            if ($ancestor && !is_wp_error($ancestor)) {
                $ancestor_link = get_term_link($ancestor);
                if (is_wp_error($ancestor_link)) {
                    $ancestor_link = home_url('/' . $taxonomy . '/' . $ancestor->slug . '/');
                }
                echo '<a href="' . esc_url($ancestor_link) . '">' . esc_html($ancestor->name) . '</a> <span>/</span> ';
            }
        }
        
        // Ссылка на термин
        $term_link = get_term_link($term);
        if (is_wp_error($term_link)) {
            $term_link = home_url('/' . $taxonomy . '/' . $term->slug . '/');
        }
        echo '<a href="' . esc_url($term_link) . '">' . esc_html($term->name) . '</a> <span>/</span> ';
        
        // Текущий пост
        echo '<span class="breadcrumb_name">' . esc_html(get_the_title()) . '</span>';
    }
}

echo '</nav>';
?>
