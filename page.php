<?php
/**
 * The template for displaying any single page.
 */

get_header(); ?>

<div id="primary" class="row-fluid">
	<div id="content" role="main" class="col-full">

		<?php if ( have_posts() ) : ?>

			<?php while ( have_posts() ) : the_post(); ?>

				<article class="post page-wrapper">
					<div class="page-card">
						<header class="page-header">
							<h1 class="title"><?php the_title(); ?></h1>
							<div class="page-meta">
								<span class="meta-item">
									<span class="material-symbols-outlined">calendar_today</span> <?php echo get_the_date(); ?>
								</span>
							</div>
						</header>

						<div class="the-content">
							<?php the_content(); ?>
							<?php wp_link_pages(); ?>
						</div>
					</div>
				</article>

			<?php endwhile; ?>

		<?php else : ?>

			<article class="post error">
				<h1 class="404">Nothing posted yet</h1>
			</article>

		<?php endif; ?>

	</div>
</div>

<?php get_footer(); ?>
