<?php
/**
 * Template part for the content page (e.g., About Us)
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('about-page'); ?>>
    <div class="page-content col-full">
        <?php the_title( '<h1>', '</h1>' ); ?>
        <div class="page-content-body">
            <?php the_content(); ?>
        </div>
    </div>
</article>