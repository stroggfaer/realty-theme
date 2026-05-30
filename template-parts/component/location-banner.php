<?php
/**
 * Шаблон баннер города
 *
 * @param object $term WP_Term объект локации
 */
if (!defined('ABSPATH')) {
    exit;
}
$term = $args['term'] ?? null;

$thumbnail = location_thumbnail_template($term);
?>
<div class="location-banner">
    <div class="location-banner__content">
        <?php if ($thumbnail) : ?>
            <div class="location-banner__thumbnail">
                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($term->name); ?>">
            </div>
        <?php endif; ?>
        <div class="location-banner__info">
            <h1 class="location-banner__title"><?php echo esc_html($term->name); ?></h1>
            <?php if (!empty($term->description)) : ?>
                <div class="location-banner__description"><?php echo wp_kses_post(wpautop($term->description)); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
