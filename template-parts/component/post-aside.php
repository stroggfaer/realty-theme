<?php
/**
 * Created Home
 * User: Rendzhi
 * Date: 23.09.2019
 * Time: 8:23
 */
$pods = pods('site');
$site = $pods->fetch();
?>
<div class="content_aside">
    <?php if(is_active_sidebar( 'post-widget' )): ?>
        <?php dynamic_sidebar( 'post-widget' ); ?>
    <?php endif; ?>
</div>
