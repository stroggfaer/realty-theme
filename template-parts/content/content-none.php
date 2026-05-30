<?php
/**
 * Template part for displaying a message that posts cannot be found
 *
 * @package YourTheme
 */

?>

<div class="no-posts">
    <p><?php esc_html_e('В этой категории пока нет записей.', 'your-theme-textdomain'); ?></p>
    <p>
        <?php esc_html_e('Может быть, вас заинтересуют другие разделы нашего сайта:', 'your-theme-textdomain'); ?>
    </p>
    <ul>
        <?php
        // Вывод ссылок на другие категории
        $categories = get_categories(array(
            'orderby' => 'name',
            'order'   => 'ASC',
            'hide_empty' => true,
        ));
        foreach ( $categories as $category ) {
            echo '<li><a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a></li>';
        }
        ?>
    </ul>
</div>
