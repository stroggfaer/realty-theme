<?php

// favicon
function my_theme_setup() {
    add_theme_support('site-icon');
}
add_action('after_setup_theme', 'my_theme_setup');

/**
* Добавляет новую колонку 'Миниатюра' в список объектов недвижимости в админ-панели.
*
* @param array $columns Существующие колонки.
* @return array Модифицированный массив колонок.
*/
function add_property_thumbnail_column($columns) {
   $new_columns = array();
   foreach ($columns as $key => $title) {
       $new_columns[$key] = $title;
       // Добавляем колонку 'Миниатюра' после колонки 'Заголовок'
       if ($key === 'title') {
           $new_columns['property_thumbnail'] = __('Миниатюра', 'realty-theme');
       }
   }
   return $new_columns;
}
add_filter('manage_property_posts_columns', 'add_property_thumbnail_column');

/**
* Отображает содержимое для кастомной колонки 'Миниатюра'.
*
* @param string $column_name Имя текущей колонки.
* @param int    $post_id     ID текущего поста.
*/
function display_property_thumbnail_column($column_name, $post_id) {
   if ($column_name === 'property_thumbnail') {
       if (has_post_thumbnail($post_id)) {
           // Если есть миниатюра, выводим ее
           echo '<img src="' . get_the_post_thumbnail_url($post_id, 'thumbnail') . '" style="width: 80px; height: 80px; object-fit: cover;" />';
       } else {
           // Если миниатюры нет, выводим заглушку
           echo '<img src="' . get_template_directory_uri() . '/assets/images/no_image.jpg" alt="Заглушка" style="width: 80px; height: auto;" />';
       }
   }
}
add_action('manage_property_posts_custom_column', 'display_property_thumbnail_column', 10, 2);
