<?php
/**
 * Reusable шаблон пустого состояния (Empty State)
 *
 * @param array $args {
 *     Параметры empty state.
 *
 *     @type string $icon             Material Symbols icon name. Default 'info'.
 *     @type string $title            Заголовок. Required.
 *     @type string $description      Описание. Default empty.
 *     @type string $button_text      Текст кнопки. Default empty.
 *     @type string $button_url       URL кнопки. Default empty.
 *     @type string $button_class     CSS класс кнопки. Default 'button__com'.
 *     @type string $additional_class Дополнительные CSS классы контейнера. Default empty.
 * }
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$defaults = array(
    'icon'             => 'info',
    'title'            => '',
    'description'      => '',
    'button_text'      => '',
    'button_url'       => '',
    'button_class'     => 'button__com',
    'additional_class' => '',
);

$args = isset( $args ) ? wp_parse_args( $args, $defaults ) : $defaults;
?>

<div class="my-empty-state <?php echo esc_attr( $args['additional_class'] ); ?>">
    <span class="material-symbols-outlined my-empty-icon"><?php echo esc_html( $args['icon'] ); ?></span>
    <?php if ( ! empty( $args['title'] ) ) : ?>
        <h3 class="my-empty-title"><?php echo esc_html( $args['title'] ); ?></h3>
    <?php endif; ?>
    <?php if ( ! empty( $args['description'] ) ) : ?>
        <p class="my-empty-text"><?php echo esc_html( $args['description'] ); ?></p>
    <?php endif; ?>
    <?php if ( ! empty( $args['button_text'] ) && ! empty( $args['button_url'] ) ) : ?>
        <a href="<?php echo esc_url( $args['button_url'] ); ?>" class="<?php echo esc_attr( $args['button_class'] ); ?>">
            <?php echo esc_html( $args['button_text'] ); ?>
        </a>
    <?php endif; ?>
</div>
