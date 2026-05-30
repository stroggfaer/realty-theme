<?php
/**
 * Общие константы для админ-интерфейса
 *
 * @package Realty_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Опции для системных шаблонов групп характеристик
 *
 * Используются для привязки группы к системному шаблону вывода
 */
if ( ! defined( 'SYSTEM_CHARACTERISTIC_GROUP_OPTIONS' ) ) {
    define( 'SYSTEM_CHARACTERISTIC_GROUP_OPTIONS', array(
        array(
            'value' => '',
            'label' => '---',
        ),
        array(
            'value' => 'system_apartment',
            'label' => 'Квартира',
        ),
        array(
            'value' => 'system_popular_services',
            'label' => 'Популярные услуги',
        ),
        array(
            'value' => 'system_rules',
            'label' => 'Правила',
        ),
        array(
            'value' => 'system_accommodations',
            'label' => 'Размещения',
        ),
        array(
            'value' => 'hours_limit',
            'label' => 'Период аренды',
        ),
    ) );
}