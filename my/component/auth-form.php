<?php
/**
 * Template part for rendering auth form
 * Модуль "Мой кабинет" для темы Realty Theme
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// WordPress автоматически извлекает $args в переменные
$default_tab = $args['default_tab'] ?? 'login';

$app_data = array(
    'defaultTab'    => $default_tab,
    'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
    'loginNonce'    => wp_create_nonce( 'my_cabinet_login_nonce' ),
    'registerNonce' => wp_create_nonce( 'my_cabinet_register_nonce' ),
    'redirectUrl'   => home_url( '/my/dashboard/' ),
);

$app_data_json = wp_json_encode( $app_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );
?>
<div id="auth-form-app" class="auth-page">
    <div class="auth-page__hero">
        <h1 class="auth-page__title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
        <p class="auth-page__subtitle"><?php esc_html_e( 'Простой доступ к вашему личному кабинету и связи с хозяином', 'realty-theme' ); ?></p>
    </div>

    <div class="auth-page__card">
        <div class="auth-tabs">
            <button
                class="auth-tabs__btn"
                :class="{ 'auth-tabs__btn--active': activeTab === 'login' }"
                @click="activeTab = 'login'"
                type="button"
            >Войти</button>
            <button
                class="auth-tabs__btn"
                :class="{ 'auth-tabs__btn--active': activeTab === 'register' }"
                @click="activeTab = 'register'"
                type="button"
            >Регистрация</button>
        </div>

        <div v-if="activeTab === 'login'" class="auth-form auth-form--login" key="login">
            <auth-login
                :app-data="appData"
                @switch-to-register="activeTab = 'register'"
            ></auth-login>
        </div>

        <div v-if="activeTab === 'register'" class="auth-form auth-form--register" key="register">
            <auth-registration
                :app-data="appData"
                @switch-to-login="activeTab = 'login'"
            ></auth-registration>
        </div>
    </div>
</div>

<script type="module">
    const appData = <?= $app_data_json; ?>;
    const { createAppModule, AuthLogin, AuthRegistration } = window.VueAppModule;
    const { ref } = Vue;

    const AuthFormApp = createAppModule({
        setup() {
            const activeTab = ref(appData.defaultTab || 'login');

            return {
                activeTab,
                appData,
            };
        },
    });

    // Регистрируем компоненты форм
    AuthFormApp.component('AuthLogin', AuthLogin);
    AuthFormApp.component('AuthRegistration', AuthRegistration);

    AuthFormApp.mount('#auth-form-app');
</script>

