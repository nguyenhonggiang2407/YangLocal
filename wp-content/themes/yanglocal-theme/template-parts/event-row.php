<?php
$post_id = get_the_ID();
$date    = get_post_meta( $post_id, '_yl_event_date', true );
$venue   = get_post_meta( $post_id, '_yl_event_venue', true );
$price   = get_post_meta( $post_id, '_yl_event_price', true );
$ts      = $date ? strtotime( $date ) : false;
?>
<article class="yl-event-row">
    <div class="yl-event-date"><?php if ( $ts ) : ?><strong><?php echo esc_html( wp_date( 'd', $ts ) ); ?></strong><?php echo esc_html( wp_date( 'm/Y', $ts ) ); ?><?php else : ?>Sắp tới<?php endif; ?></div>
    <div><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html( $venue ?: wp_trim_words( get_the_excerpt(), 18 ) ); ?></p></div>
    <div class="yl-event-price"><?php echo esc_html( $price ?: 'Xem chi tiết' ); ?></div>
</article>
