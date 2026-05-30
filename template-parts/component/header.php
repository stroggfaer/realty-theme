<?php
$pods = pods('site');
$settings = [];
if ($pods && $pods->exists()) {
    $settings = $pods->fetch();
}
$slot_center = apply_filters('realty_header_slot', '');
 ?>
<header id="masthead" class="site-header">
    <?php if(is_active_sidebar( 'top-header-custom-widget' )): ?>
        <?php dynamic_sidebar( 'top-header-custom-widget' ); ?>
    <?php endif; ?>
    <div class="col-full">
        <div class="col-flex">
            <a class="site-branding" href="<?php echo esc_url( home_url( '/' ) );?>">
                <?php if (function_exists('the_custom_logo')) : ?>
                    <div class="custom-logo-link" rel="home" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) );?>">
                        <?php the_custom_logo(); ?>
                    </div>
                <?php endif; ?>
                <div class="block">
                    <div class="title_logo"><?php bloginfo( 'name' ); // Display the blog name ?></div>
                    <div class="desc"><?php bloginfo( 'description' );?></div>
                </div>
            </a>
            <?php if(!empty($slot_center)): ?>
                 <?php echo $slot_center ?>
            <?php else: ?>
                <nav class="main-navigation">
                    <?php
                    wp_nav_menu([
                            'container' => false,
                            'menu_class' => 'menu',
                            'menu'=> 'header_top_menu',
                            'theme_location' => 'header_top_menu',
                    ]);
                    ?>
                </nav>
            <?php endif; ?>
            <div class="actions">
                 <?php if ( is_user_logged_in() ) : ?>
                     <?php
                     $current_user = wp_get_current_user();
                     $display_name = $current_user->display_name ?: $current_user->user_email;
                     $avatar = get_avatar( $current_user->ID, 40, '', '', array('class' => 'user-avatar') );
                     ?>
                     <!-- Уведомления: колокольчик с счетчиком (только для клиентов) -->
                     <?php if ( ! current_user_can( 'manage_options' ) ) : ?>
                     <div class="header-notifications">
                         <a href="<?php echo esc_url( home_url( '/my/dashboard/' ) ); ?>" class="header-notifications__link" title="Уведомления от хоста">
                             <span class="material-symbols-outlined header-notifications__icon">notifications</span>
                             <span class="header-notifications__counter" data-unread-count style="display: none;">0</span>
                         </a>
                     </div>
                     <?php endif; ?>
                     <div class="user-menu">
                         <a href="<?php echo esc_url( home_url( '/my/dashboard/' ) ); ?>" class="user-menu__link">
                             <div class="user-menu__avatar">
                                 <?php echo $avatar; ?>
                             </div>
                             <span class="user-menu__name"><?php echo esc_html( $display_name ); ?></span>
                         </a>
                     </div>
                 <?php else : ?>
                     <a href="<?php echo esc_url( home_url( '/my-auth/' ) ); ?>" class="button__com">Вход</a>
                     <a href="<?php echo esc_url( add_query_arg( 'tab', 'register', home_url( '/my-auth/' ) ) ); ?>" class="button__com link">Регистрация</a>
                 <?php endif; ?>
            </div>
        </div>
    </div>
</header>
