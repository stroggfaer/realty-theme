<?php
/**
 * Template part
 * Модуль "Глобальный" для темы Realty Theme
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// WordPress автоматически извлекает $args в переменные
$app_data = array(
    'defaultTab'    => 'login',
    'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
    'loginNonce'    => wp_create_nonce( 'my_cabinet_login_nonce' ),
    'redirectUrl'   => esc_url_raw( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) ),
);

$app_data_json = wp_json_encode( $app_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );
?>
<div id="site-app">
    <div class="js-modal-auth">
        <el-dialog
                v-if="dialogParams.mode === 'auth-modal'"
                v-model="dialogParams.isVisible"
                title="Вход в аккаунт"
                width="600px"
                :close-on-click-modal="true"
                destroy-on-close
                @close="onAuthClose"

        >
            <auth-login :app-data="appData"  is-modal ></auth-login>
        </el-dialog>
    </div>

</div>

<script type="module">
    const appData = <?= $app_data_json; ?>;
    const { createAppModule, AuthLogin, useModal, ElDialog, ElForm, ElFormItem, ElInput, ElButton, ElAlert } = window.VueAppModule;
    const { ref, reactive, onMounted, nextTick } = Vue;
    const SiteApp = createAppModule({
        setup() {
            const { dialogParams, onDialogClose } = useModal();

            const onAuthClose = () => {
                onDialogClose();
            };
            const initJQuery = () => {
                if (typeof jQuery === 'undefined') {
                    console.error('jQuery не загружен!');
                    return;
                }
                jQuery(document).on('click', '.js-modal-auth', function(e) {
                    e.preventDefault();
                    dialogParams.isVisible = true;
                    dialogParams.mode = 'auth-modal';
                });
            };

            onMounted(() => {
                nextTick(initJQuery);
            });

            return {
                appData,
                dialogParams,
                onAuthClose,
            };
        },
    });

    // Регистрируем компоненты
    SiteApp.component('el-dialog', ElDialog);
    SiteApp.component('el-form', ElForm);
    SiteApp.component('el-form-item', ElFormItem);
    SiteApp.component('el-input', ElInput);
    SiteApp.component('el-button', ElButton);
    SiteApp.component('el-alert', ElAlert);
    SiteApp.component('AuthLogin', AuthLogin);
    SiteApp.mount('#site-app');
</script>

