<?php
/**
 * Created Home
 * User: Rendzhi
 * Date: 23.09.2019
 * Time: 8:23
 */
$pods = pods('site');
$site = [];
if ($pods && $pods->exists()) {
    $site = $pods->fetch();
}
?>
<div class="content_aside">
    <?php get_sidebar(); ?>
</div>
