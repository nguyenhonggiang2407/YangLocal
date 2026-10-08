<?php get_header(); ?>
<div class="yl-page-shell"><div class="yl-container">
<?php while ( have_posts() ) : the_post();
    $id      = get_the_ID();
    $date    = get_post_meta( $id, '_yl_event_date', true );
    $start   = get_post_meta( $id, '_yl_event_start_time', true );
    $end     = get_post_meta( $id, '_yl_event_end_time', true );
    $venue   = get_post_meta( $id, '_yl_event_venue', true );
    $address = get_post_meta( $id, '_yl_event_address', true );
    $price   = get_post_meta( $id, '_yl_event_price', true );
    $url     = get_post_meta( $id, '_yl_event_url', true );
    $ts      = $date ? strtotime( $date ) : false;
?>
<article>
    <header class="yl-page-header">
        <p class="yl-eyebrow">Sự kiện</p>
        <h1><?php the_title(); ?></h1>
        <p><?php if ( $ts ) : ?><?php echo esc_html( wp_date( 'd/m/Y', $ts ) ); ?><?php endif; ?><?php if ( $venue ) : ?> · <?php echo esc_html( $venue ); ?><?php endif; ?></p>
    </header>
    <div class="yl-place-layout">
        <div class="yl-prose">
            <?php the_content(); ?>
            <?php if ( $address ) : ?><h2>Địa điểm</h2><p><?php echo esc_html( $address ); ?></p><?php endif; ?>
        </div>
        <aside class="yl-info-card" aria-label="Thông tin sự kiện">
            <dl>
                <div class="yl-info-card__row"><dt>Ngày</dt><dd><?php echo $ts ? esc_html( wp_date( 'd/m/Y', $ts ) ) : 'Chưa cập nhật'; ?></dd></div>
                <div class="yl-info-card__row"><dt>Giờ</dt><dd><?php echo esc_html( trim( $start . '–' . $end, '–' ) ?: 'Chưa cập nhật' ); ?></dd></div>
                <div class="yl-info-card__row"><dt>Địa điểm</dt><dd><?php echo esc_html( $venue ?: 'Chưa cập nhật' ); ?></dd></div>
                <div class="yl-info-card__row"><dt>Chi phí</dt><dd><?php echo esc_html( $price ?: 'Chưa cập nhật' ); ?></dd></div>
            </dl>
            <?php if ( $url ) : ?><a class="yl-button" style="width:100%;margin-top:16px" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">Đăng ký</a><?php endif; ?>
        </aside>
    </div>
</article>
<?php endwhile; ?>
</div></div>
<?php get_footer(); ?>
