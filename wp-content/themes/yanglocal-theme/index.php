<?php get_header(); ?>
<div class="yl-page-shell"><div class="yl-container"><?php if ( have_posts() ) : ?><div class="yl-article-grid"><?php while ( have_posts() ) : the_post(); ?><article class="yl-article-card"><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></p></article><?php endwhile; ?></div><?php the_posts_pagination(); ?><?php else : ?><div class="yl-empty-state"><h2>Chưa có nội dung.</h2></div><?php endif; ?></div></div>
<?php get_footer(); ?>
