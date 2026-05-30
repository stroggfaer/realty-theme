<?php
/**
 * Шаблон страницы "Настройки"
 * Модуль "Мой кабинет" для темы Realty Theme
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Данные для Vue приложения
$app_data = array(
    'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
    'profileNonce'         => wp_create_nonce( 'my_cabinet_profile_nonce' ),
    'updateProfileNonce'   => wp_create_nonce( 'my_cabinet_update_profile_nonce' ),
    'changePasswordNonce'  => wp_create_nonce( 'my_cabinet_change_password_nonce' ),
);

$app_data_json = wp_json_encode( $app_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );

get_header();
?>

<div class="my-dashboard-layout col-full">
    <!-- Боковое меню -->
    <?php get_template_part('my/component/my-sidebar'); ?>

    <!-- Основной контент -->
    <main class="my-main-content">
        <div class="my-content-wrapper">
            <h1 class="my-page-title"><?php esc_html_e( 'Настройки', 'realty-theme' ); ?></h1>
            <div class="my-content-grid">
                <div class="my-content-main">
                    <div id="settings-app" class="settings-app-container" data-app="<?php echo esc_attr( $app_data_json ); ?>">
                        <settings :app-data="appData"></settings>
                    </div>
                </div>
                <div class="my-content-sidebar">
                    <?php get_template_part('my/component/info-sidebar'); ?>
                </div>
            </div>

        </div>
    </main>
</div>

<script type="module">
    const appData = JSON.parse(document.getElementById('settings-app').dataset.app);
    const { createAppModule, Settings } = window.VueAppModule;

    const SettingsApp = createAppModule({
        setup() {
            return {
                appData,
            };
        },
    });

    // Регистрируем компонент Settings
    SettingsApp.component('Settings', Settings);

    SettingsApp.mount('#settings-app');
</script>

<?php
get_footer();
