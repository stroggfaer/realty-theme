<?php
$pods = pods('site');
$settings = [];
if ($pods && $pods->exists()) {
    $settings = $pods->fetch();
}
?>

<footer class="footer">
    <div class="col-full">
        <!-- Колонки -->
<!--        <div class="footer__columns">-->
<!--            <div class="footer__column footer-widget-1">-->
<!--                --><?php //if(is_active_sidebar( 'footer-widget-1' )): ?>
<!--                    --><?php //dynamic_sidebar( 'footer-widget-1' ); ?>
<!--                --><?php //endif; ?>
<!--            </div>-->
<!--            <div class="footer__column footer-widget-2">-->
<!--                --><?php //if(is_active_sidebar( 'footer-widget-2' )): ?>
<!--                    --><?php //dynamic_sidebar( 'footer-widget-2' ); ?>
<!--                --><?php //endif; ?>
<!--            </div>-->
<!--            <div class="footer__column footer-widget-3">-->
<!--                --><?php //if(is_active_sidebar( 'footer-widget-3' )): ?>
<!--                    --><?php //dynamic_sidebar( 'footer-widget-3' ); ?>
<!--                --><?php //endif; ?>
<!--            </div>-->
<!--            <div class="footer__column footer-widget-4">-->
<!--                --><?php //if(is_active_sidebar( 'footer-widget-4' )): ?>
<!--                    --><?php //dynamic_sidebar( 'footer-widget-4' ); ?>
<!--                --><?php //endif; ?>
<!--            </div>-->
<!--        </div>-->

        <!-- Разделитель -->
        <!-- Нижняя часть -->
        <div class="footer__bottom">
            <p class="footer__copyright">
                &copy; <?=date('Y')?> <?php bloginfo( 'name' ); // Display the blog name ?> Все права защищены.
            </p>
            <div class="footer__social">
                <a href="#" aria-label="Global site" class="footer__social-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <line x1="12" y1="2" x2="12" y2="22"/>
                    </svg>
                </a>
                <a href="#" aria-label="Share" class="footer__social-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                        <polyline points="16 6 12 2 8 6"/>
                        <line x1="12" y1="2" x2="12" y2="15"/>
                    </svg>
                </a>
                <a href="#" aria-label="Email" class="footer__social-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22 6 12 13 2 6"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</footer>
<?php wp_footer();
?>
</body>
</html>
