<?php
/**
 * The template for displaying any single post.
 *
 */

$elementId = getPostMenuActive();

get_header(); // This fxn gets the header.php file and renders it ?>
	<div class="col-full" id="<?=$elementId?>">
        <?php get_template_part('template-parts/component/breadcrumbs', null, array('current_page' => get_the_title())); ?>
        <div class="theme-content post-article">
               <div id="content" role="main">
                <?php if ( have_posts() ) :
                    // Do we have any posts in the databse that match our query?
                    ?>
                    <?php while ( have_posts() ) : the_post();
                    // If we have a post to show, start a loop that will display it
                    $pods = pods('post',get_the_id());
                    $website = $pods->field('website');
                    $stack = '';//$pods->field('stack');
                    $client = $pods->field('client');
                    ?>
                    <article class="post">
                        <h1 class="title border"><?php the_title(); // Display the title of the post ?></h1>
                        <div class="post-meta">
                            <?php the_time('m.d.Y'); // Display the time it was published ?>
                            <?php // the_author(); Uncomment this and it will display the post author ?>

                        </div><!--/post-meta -->
                        <div class="post-meta-fields">
                            <?php if(!empty($website)): ?>
                                <div class="post_meta">
                                    <div class="label">Веб сайт:</div>
                                    <a href="<?= $website ?>" target="_blank"><?= $website ?></a>
                                </div>
                            <?php endif; ?>
                            <?php if(!empty($stack)): ?>
                                <div class="post_meta">
                                    <div class="label">Стек:</div>
                                    <div class="desc"><?= $stack ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if(!empty($client)): ?>
                                <div class="post_meta">
                                    <div class="label">Клиент:</div>
                                    <div class="desc"><?= $client ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="the-content">
                            <?php the_content();
                            // This call the main content of the post, the stuff in the main text box while composing.
                            // This will wrap everything in p tags
                            ?>

                            <?php wp_link_pages(); // This will display pagination links, if applicable to the post ?>
                        </div><!-- the-content -->
                        <?php if(false) : ?>
                            <div class="meta clearfix">
                                <div class="category"><?php echo get_the_category_list(); // Display the categories this post belongs to, as links ?></div>
                                <div class="tags"><?php echo get_the_tag_list( '| &nbsp;', '&nbsp;' ); // Display the tags this post has, as links separated by spaces and pipes ?></div>
                            </div><!-- Meta -->
                        <?php endif; ?>
                    </article>

                <?php endwhile; // OK, let's stop the post loop once we've displayed it ?>

                    <?php
                    // Woocommerce products API
                    if (function_exists('do_shortcode') && shortcode_exists('woo_api_selected_products')) {
                        echo do_shortcode('[woo_api_selected_products]');
                    }
                    // If comments are open or we have at least one comment, load up the default comment template provided by Wordpress
                    if ( comments_open() || '0' != get_comments_number() )
                        comments_template( '', true );
                    ?>
                <?php else : // Well, if there are no posts to display and loop through, let's apologize to the reader (also your 404 error) ?>

                    <article class="post error">
                        <h1 class="404">Nothing has been posted like that yet</h1>
                    </article>

                <?php endif; // OK, I think that takes care of both scenarios (having a post or not having a post to show) ?>

            </div><!-- #content .site-content -->
            <?php if(is_active_sidebar( 'post-widget' )): ?>
               <div class="sidebar"><?php  get_template_part('template-parts/component/post-aside'); ?></div>
            <?php endif; ?>
        </div>
	</div><!-- #primary .content-area -->
<?php get_footer(); // This fxn gets the footer.php file and renders it ?>
