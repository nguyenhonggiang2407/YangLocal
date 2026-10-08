<?php get_header(); ?>
<div class="yl-page-shell"><div class="yl-container" style="max-width:920px;"><?php while ( have_posts() ) : the_post(); ?><article><header class="yl-page-header"><h1><?php the_title(); ?></h1></header><div class="yl-prose"><?php the_content(); ?></div></article><?php endwhile; ?></div></div>
<?php get_footer(); ?>
