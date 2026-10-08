<?php get_header(); ?>
<div class="yl-page-shell"><div class="yl-container"><header class="yl-page-header"><p class="yl-eyebrow">Lịch sinh viên</p><h1>Có gì sắp tới?</h1><p>Workshop, buổi chia sẻ và hoạt động cộng đồng được sắp theo thời gian. Sự kiện đã qua tự động rời khỏi danh sách sắp tới.</p></header>
<?php
$event_page = max( 1, get_query_var( 'paged' ) );
$upcoming = new WP_Query( array( 'post_type'=>'yl_event','post_status'=>'publish','posts_per_page'=>12,'paged'=>$event_page,'meta_key'=>'_yl_event_date','orderby'=>'meta_value','order'=>'ASC','meta_query'=>array( array( 'key'=>'_yl_event_date','value'=>current_time('Y-m-d'),'compare'=>'>=','type'=>'DATE' ) ) ) );
?>
<?php if ( $upcoming->have_posts() ) : ?><div class="yl-events-list"><?php while ( $upcoming->have_posts() ) : $upcoming->the_post(); if ( function_exists( 'yl_get_event_status' ) && 'past' === yl_get_event_status( get_the_ID() ) ) { continue; } get_template_part( 'template-parts/event-row' ); endwhile; ?></div><?php if ( $upcoming->max_num_pages > 1 ) : ?><nav class="yl-pagination"><?php echo paginate_links( array( 'total'=>$upcoming->max_num_pages,'current'=>$event_page ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></nav><?php endif; ?><?php wp_reset_postdata(); else : ?><div class="yl-empty-state"><h2>Tuần này chưa có lịch mới.</h2><p>Xem vài địa điểm đáng đi trong lúc chờ sự kiện tiếp theo.</p><a class="yl-button yl-button--ghost" href="<?php echo esc_url( add_query_arg( 'recommended', '1', get_post_type_archive_link( 'yl_place' ) ) ); ?>">Xem YangLocal gợi ý</a></div><?php endif; ?>
</div></div>
<?php get_footer(); ?>
