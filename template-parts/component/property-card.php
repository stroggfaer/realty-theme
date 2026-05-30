<?php
/**
 * Шаблон карточки недвижимости
 *
 * @param object $pod Pods объект недвижимости
 */
if (!defined('ABSPATH')) {
    exit;
}

$pod = $args['pod'] ?? null;
if (!$pod) {
    return;
}

$property_id = $pod->id();
$property_title = $pod->display('post_title');
$property_content = $pod->display('post_content');
$property_price = $pod->field('price');
$property_address = $pod->field('address');
$property_lat = $pod->field('latitude');
$property_lng = $pod->field('longitude');

// Получаем термины для таксономий
$property_locations = $pod->field('location');
$property_types = $pod->field('property_type');

// Получаем дополнительные данные фильтрации
$max_adults = get_post_meta($property_id, '_max_adults', true);
$max_children = get_post_meta($property_id, '_max_children', true);

// Получаем изображение
$thumbnail_url = get_the_post_thumbnail_url($property_id, 'property-teaser');
$gallery_data = pod_get_gallery_data($pod, 'property-teaser', true);

if (!$thumbnail_url) {
    $thumbnail_url = $gallery_data['src'];
}

// Проверяем статус избранного
$is_favorite = false;
if (is_user_logged_in()) {
    $favorites = get_user_meta(get_current_user_id(), '_real_property_favorites', true);
    $favorites = is_array($favorites) ? $favorites : array();
    $is_favorite = in_array($property_id, $favorites, true);
}

// Проверяем показ кнопки карты (по умолчанию false)
$show_map_location = $args['show_map_location'] ?? false;
?>
<div class="property-card" data-id="<?php echo esc_attr($property_id); ?>"
    data-lat="<?php echo esc_attr($property_lat); ?>" data-lng="<?php echo esc_attr($property_lng); ?>">
    <div class="property-thumbnail">
        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($property_title); ?>">
        <div class="property-actions">
            <?php if ($show_map_location && $property_lat && $property_lng) : ?>
            <button type="button" class="map-location js-map-location" title="Показать на карте" data-id="<?php echo esc_attr($property_id); ?>" data-lat="<?php echo esc_attr($property_lat); ?>" data-lng="<?php echo esc_attr($property_lng); ?>">
                <span class="material-symbols-outlined">location_on</span>
            </button>
            <?php endif; ?>
            <button type="button" class="favorite js-favorite <?php echo $is_favorite ? 'active' : ''; ?>" title="Добавить в избранное" data-property-id="<?php echo esc_attr($property_id); ?>" data-is-favorite="<?php echo $is_favorite ? '1' : '0'; ?>">
                <span class="material-symbols-outlined"><?php echo $is_favorite ? 'favorite' : 'favorite_border'; ?></span>
            </button>
        </div>
        <a href="<?php echo esc_url(get_permalink($property_id)); ?>" class="property-link"></a>
    </div>
    <div class="property-content" >
        <div class="entry-header">
            <h3 class="entry-title">
                <a href="<?php echo esc_url(get_permalink($property_id)); ?>"><?php echo esc_html($property_title); ?></a>
            </h3>
            <div class="rating"><span class="material-symbols-outlined">star</span> 4.1</div>
        </div>
        <div class="property-meta">
            <?php if ($property_address || $property_locations) : ?>
                <div class="property-address"><span class="material-symbols-outlined">location_on</span><?php
                    $location_names = array();
                    if (is_array($property_locations)) {
                        foreach ($property_locations as $location) {
                            $location_names[] = $location['name'];
                        }
                    } else {
                        $location_names[] = $property_locations['name'];
                    }
                    echo esc_html(implode(', ', $location_names)).', ';
                    ?> <?= !empty($property_address) ? esc_html($property_address) : ''?></div>
            <?php endif; ?>
            <?php if ($property_price) : ?>
                <div class="property-price"><?php echo number_format($property_price, 0, '', ' '); ?> ₽<span class="hint"> / сутки</span></div>
            <?php endif; ?>
        </div>
    </div>
</div>