<?php
/**
 * Created Home
 * User: Rendzhi
 * Date: 23.09.2019
 * Time: 8:23
 */


if (is_page()) {
    $title = get_the_title();
    $content = apply_filters('the_content', get_the_content());
    ?>
<div class="col-full">
    <div class="theme-content">
        <div class="sidebar"><?php  get_template_part('template-parts/component/aside'); ?></div>
        <div class="wrap-content">
            <h1><?=$title?></h1>
            <div class="main_slider">
                <?php  echo do_shortcode('[smartslider3 slider="1"]'); ?>
            </div>
            <div class="section pd">
                <h2 class="title border">Статьи</h2>
                <div class="rubric_wrap">
                    <?php get_template_part('template-parts/component/rubric-list', 'main', array('per_page' => 3, 'is_pagination'=> false)); ?>
                </div>
            </div>
            <?php if(!empty($content)): ?>
                <div class="section pd">
                    <div class="text"><?=$content?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
}
?>
