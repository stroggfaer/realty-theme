<?php
/**
 * Страница авторизации / регистрации
 * Модуль "Мой кабинет" для темы Realty Theme
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Если пользователь уже авторизован — редиректим в кабинет
if ( is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/my/dashboard/' ) );
    exit;
}

get_header();

$default_tab = 'login';
if ( isset( $_GET['tab'] ) && 'register' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
    $default_tab = 'register';
}
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php get_template_part( 'my/component/auth-form', null, array( 'default_tab' => $default_tab ) ); ?>
    </main>
</div>

<?php
get_footer();
