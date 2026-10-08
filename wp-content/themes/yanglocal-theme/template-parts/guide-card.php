<?php
$yl_card_id       = get_the_ID();
$yl_card_category = function_exists( 'yl_guide_primary_category' ) ? yl_guide_primary_category( $yl_card_id ) : null;
$yl_card_image    = function_exists( 'yl_demo_image_url' ) ? yl_demo_image_url( $yl_card_id, 'city' ) : get_the_post_thumbnail_url( $yl_card_id, 'yl-card' );
?>
<article class="yl-guide-card">
    <a class="yl-guide-card__media" href="<?php the_permalink(); ?>" aria-label="Đọc <?php echo esc_attr( get_the_title() ); ?>">
        <img src="<?php echo esc_url( $yl_card_image ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='<?php echo esc_js( get_template_directory_uri() . '/assets/images/fallback-city.svg' ); ?>';">
    </a>
    <div class="yl-guide-card__body">
        <?php if ( $yl_card_category ) : ?><span class="yl-guide-kicker"><?php echo esc_html( $yl_card_category->name ); ?></span><?php endif; ?>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 27 ) ); ?></p>
        <div class="yl-guide-card__footer">
            <div class="yl-guide-meta">
                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></time>
                <span aria-hidden="true">·</span>
                <span><?php echo esc_html( function_exists( 'yl_guide_reading_time' ) ? yl_guide_reading_time( $yl_card_id ) : '' ); ?></span>
            </div>
            <a class="yl-guide-read-link" href="<?php the_permalink(); ?>">Đọc bài <span aria-hidden="true">→</span></a>
        </div>
    </div>
</article>
