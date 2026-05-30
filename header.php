<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$favicon = esc_url( get_template_directory_uri() ).'/favicon.ico';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width" />

<title>
	<?php bloginfo('name'); // show the blog name, from settings ?> |
	<?php is_front_page() ? bloginfo('description') : wp_title(''); // if we're on the home page, show the description, from the site's settings - otherwise, show the title of the post or page ?>
</title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<!--google-->
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&amp;display=block" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=block" rel="stylesheet"/>
<!--./google-->
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<?php // Loads HTML5 JavaScript file to add support for HTML5 elements in older IE versions. ?>
<!--[if lt IE 9]>
<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
<![endif]-->
<?php wp_head();
?>
</head>

<body <?php body_class(is_singular('property') ? 'single-property' : ''); ?> >
<!--Подключаем интерефейс шапку -->
<?php  get_template_part( 'template-parts/component/header'); ?>
<!--./Подключаем интерефейс шапку -->
