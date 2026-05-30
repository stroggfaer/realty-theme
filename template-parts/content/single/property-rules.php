<?php
/**
 * Шаблон rules
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
$optionsRules = [
        'type' => 'one',
        'system_temp' => 'system_rules',
];
$options = [
        'type' => 'one',
        'system_temp' => 'system_accommodations',
];

$property_id = $pod->id();
$rules = realty_get_property_characteristics($property_id,'text', $optionsRules);
$system_accommodations = realty_get_property_characteristics($property_id,'prohibited', $options);




?>

<div class="section-property property-rules">
    <?php if (!empty($rules) && is_array($rules) && !empty($rules['characteristics'])): ?>
        <h3 class="title"><?php echo esc_html($rules['group_name'] ?? ''); ?></h3>
        <div class="characteristics-grid__com">
            <?php foreach ($rules['characteristics'] as $characteristics): ?>
            <div class="characteristic-item">
                <div class="label-text"><?php echo esc_html($characteristics['label']); ?></div>
                <div class="desc"><?php echo esc_html($characteristics['title']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($system_accommodations) && is_array($system_accommodations) && !empty($system_accommodations['characteristics'])): ?>
        <div class="characteristics-grid__com">
            <?php foreach ($system_accommodations['characteristics'] as $char): ?>
            <div class="characteristic-item">
                <?php echo realty_render_characteristic_icon($char, 'diagonal-strike'); ?>
                <span class="label"><?php echo esc_html($char['title']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
