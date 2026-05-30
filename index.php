<?php

get_header();
$elementId = getPostMenuActive();
$cat_ID = get_query_var('cat'); // Получаем ID текущей рубрики
$cat_name = get_query_var('category_name'); // Получаем ярлык
$search = !empty($_GET['s']) ? $_GET['s'] : null;

?>

<main id="site-content-rubric" role="main" class="col-full content-rubric">
    <?php get_template_part('template-parts/component/breadcrumbs', null, array('current_page' => ($search ? 'Поиск' : single_cat_title('', false)))); ?>
    <?php if(!empty($search)): ?>
        <h1 class="title">Поиск: <?=$search?></h1>
    <?php else: ?>
        <h1 class="title"><?php single_cat_title(''); ?></h1>
    <?php endif; ?>

    <div class="theme-content">
        <div class="sidebar"><?php  get_template_part('template-parts/component/aside'); ?></div>
        <section class="section-pages-list" >
            <?php if ( have_posts() ) : ?>
                <div class="list">
                    <?php get_template_part('template-parts/component/rubric-list'); ?>
                </div>
            <?php
            else :
                get_template_part( 'template-parts/content/content', 'none' );
            endif;
            wp_reset_postdata(); // Очистка
            ?>
        </section>
    </div>
</main><!-- #site-content -->
<?php get_footer(); // This fxn gets the footer.php file and renders it ?>
