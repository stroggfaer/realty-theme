<?php
/**
 * Шаблон characteristics
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
// options
$options = [
        'per_page' => 12,
        'type' => 'all',
        'system_temp' => 'system_popular_services',
];
$property_id = $pod->id();


$property = realty_get_property_characteristics($property_id, 'standard', $options);
$group_characteristics = $property['data'];

if (empty($group_characteristics)) {
    return;
}

?>
<div class="section-property property-key-characteristics">
    <?php foreach ($group_characteristics as $group): ?>
        <h3 class="title"><?php echo esc_html($group['group_name']); ?></h3>
        <div class="characteristics-grid__com">
            <?php foreach ($group['characteristics'] as $index => $characteristics): ?>
                <div class="characteristic-item">
                    <?php echo realty_render_characteristic_icon($characteristics); ?>
                    <span class="label"><?php echo esc_html($characteristics['title']); ?></span>
                    <span class="value"><?php echo esc_html($characteristics['value']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php $counts = esc_html($property['meta']['total'] - count($group['characteristics'])); ?>
        <?php if ($counts > 0): ?>
            <div class="action">
                <a
                        href="#"
                        class="bold border all js-modal-characteristics"
                        data-property-id="<?php echo esc_attr($group['group_id']); ?>"
                        data-property-last-count="<?php echo $counts; ?>"
                >
                    Показать все (<?php echo $counts; ?>)
                </a>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
