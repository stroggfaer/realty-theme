<?php
/**
 * Шаблон: Галерея объекта недвижимости
 *
 * Логика:
 * - если галерея Pods пустая → показываем featured image (если есть)
 * - иначе → всегда карусель Splide (даже если 1 фото)
 */

if (!defined('ABSPATH')) {
    exit;
}

$pod = $args['pod'] ?? null;
if (!$pod) {
    return;
}

$post_id    = get_the_ID();
$post_title = get_the_title($post_id);

// Получаем данные галереи
$gallery_data = pod_get_gallery_data($pod, 'large', false);

// Если совсем пусто → подставляем заглушку как один элемент
if (empty($gallery_data)) {
    $gallery_data = [[
            'id'     => 0,
            'src'    => empty_img_placeholder(),
            'alt'    => esc_attr($post_title),
            'width'  => 0,
            'height' => 0,
    ]];
}
$count = count($gallery_data);
?>
    <div class="property-gallery-container">
        <?php if ($count === 1): ?>
            <div class="property-gallery-single">
                <img src="<?= esc_url($gallery_data[0]['src']) ?>"
                     alt="<?= esc_attr($gallery_data[0]['alt']) ?>"
                     loading="lazy"
                   >
            </div>
        <?php else: ?>
            <!-- 2+ фото → карусель с превью -->
            <div class="property-gallery">
                <div id="main-slider" class="splide main-slider-wrapper" role="group" aria-label="Галерея объекта">
                    <div class="splide__track">
                        <ul class="splide__list">
                            <?php foreach ($gallery_data as $item): ?>
                                <li class="splide__slide">
                                    <img src="<?= esc_url($item['src']) ?>"
                                         alt="<?= esc_attr($item['alt']) ?>"
                                         loading="lazy"
                                        >
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div id="thumbnail-slider" class="splide thumb-slider-wrapper" role="group" aria-label="Превью галереи">
                    <div class="splide__track">
                        <ul class="splide__list">
                            <?php foreach ($gallery_data as $item): ?>
                                <li class="splide__slide">
                                    <img src="<?= esc_url($item['thumbnail'] ?? $item['src']) ?>"
                                         alt="<?= esc_attr($item['alt']) ?>"
                                         loading="lazy">
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php