<?php
/**
 * Aside сайдбар
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Определяем текущую страницу для активного пункта меню
$current_page = '';
if ( isset( $_SERVER['REQUEST_URI'] ) ) {
    $request_path = trim( parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ), '/' );
    if ( strpos( $request_path, 'my/' ) === 0 ) {
        $current_page = substr( $request_path, 3 ); // Убираем 'my/'
        if ( strpos( $current_page, '/' ) !== false ) {
            $current_page = substr( $current_page, 0, strpos( $current_page, '/' ) );
        }
    }
}
// Если путь просто '/my' или '/my/', считаем это dashboard
if ( empty( $current_page ) ) {
    $current_page = 'dashboard';
}
?>
<aside class="my-sidebar">
    <div class="wrap-sidebar">
        <nav class="my-sidebar-nav">
            <a href="<?php echo esc_url( home_url( '/my/dashboard/' ) ); ?>" class="my-sidebar-item<?php echo $current_page === 'dashboard' ? ' my-sidebar-item--active' : ''; ?>">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="my-sidebar-label">Главная</span>
            </a>
            <a href="<?php echo esc_url( home_url( '/my/favorites/' ) ); ?>" class="my-sidebar-item<?php echo $current_page === 'favorites' ? ' my-sidebar-item--active' : ''; ?>">
                <span class="material-symbols-outlined">favorite</span>
                <span class="my-sidebar-label">Избраный</span>
            </a>
            <a href="<?php echo esc_url( home_url( '/my/settings/' ) ); ?>" class="my-sidebar-item<?php echo $current_page === 'settings' ? ' my-sidebar-item--active' : ''; ?>">
                <span class="material-symbols-outlined">settings</span>
                <span class="my-sidebar-label">Настройки</span>
            </a>
        </nav>
        <div class="my-sidebar-footer">
            <a href="<?php echo esc_url( home_url( '/help/' ) ); ?>" class="my-sidebar-item">
                <span class="material-symbols-outlined">help</span>
                <span class="my-sidebar-label">Помощь</span>
            </a>
            <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="my-sidebar-item my-sidebar-item--logout">
                <span class="material-symbols-outlined">logout</span>
                <span class="my-sidebar-label">Выйти</span>
            </a>
        </div>
    </div>
</aside>
