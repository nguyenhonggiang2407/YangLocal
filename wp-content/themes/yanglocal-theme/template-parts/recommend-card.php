<?php
$post_id = get_the_ID();
$info = function_exists( 'yl_get_recommendation_info' ) ? yl_get_recommendation_info( $post_id ) : array();
$category = function_exists( 'yl_place_primary_category' ) ? yl_place_primary_category( $post_id ) : null;
$district = function_exists( 'yl_place_primary_district' ) ? yl_place_primary_district( $post_id ) : null;
$fallback_key = function_exists( 'yl_fallback_key_for_post' ) ? yl_fallback_key_for_post( $post_id, 'city' ) : 'city';
$cover = function_exists( 'yl_place_cover_info' ) ? yl_place_cover_info( $post_id, $fallback_key ) : array( 'url'=>yl_demo_image_url($post_id,$fallback_key),'label'=>'','alt'=>'Ảnh '.get_the_title($post_id),'fallback_url'=>yl_fallback_image_url($fallback_key) );
$image = ! empty($cover['url']) ? $cover['url'] : yl_fallback_image_url($fallback_key);
$is_honest_fallback = isset( $cover['status'] ) && 'FALLBACK' === $cover['status'];
$image_state = ! empty( $cover['state'] ) ? $cover['state'] : ( $is_honest_fallback ? 'MISSING' : 'CONTEXTUAL' );
$maps_url = function_exists( 'yl_get_google_maps_url' ) ? yl_get_google_maps_url( $post_id ) : '';
$is_shopping = $category && 'mua-sam' === $category->slug;
$shopping_monogram = $is_shopping && function_exists( 'yl_place_monogram' ) ? yl_place_monogram( get_the_title( $post_id ) ) : '';
$shopping_district = $district ? $district->name : 'Hà Nội';
?>
<article class="yl-recommend-card<?php echo $is_honest_fallback ? ' yl-recommend-card--no-photo' : ''; ?><?php echo $is_shopping ? ' yl-recommend-card--shopping' : ''; ?>" data-yl-image-state="<?php echo esc_attr( $image_state ); ?>" data-yl-recommend-build="r5">
    <?php if ( ! $is_honest_fallback ) : ?>
    <button class="yl-recommend-card__media yl-place-photo-trigger" type="button" data-yl-place-photos data-post-id="<?php echo esc_attr( $post_id ); ?>" data-title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" data-maps-url="<?php echo esc_url( $maps_url ); ?>" aria-label="Xem ảnh của <?php echo esc_attr( get_the_title( $post_id ) ); ?>">
        <img src="<?php echo esc_url( $image ); ?>" data-fallback-src="<?php echo esc_url( !empty($cover['fallback_url']) ? $cover['fallback_url'] : yl_neutral_fallback_image_url($fallback_key) ); ?>" alt="<?php echo esc_attr( !empty($cover['alt']) ? $cover['alt'] : 'Ảnh tham khảo cho '.get_the_title($post_id) ); ?>" loading="lazy" decoding="async">
        <?php if ( ! empty( $cover['label'] ) ) : ?><span class="yl-image-badge"><?php echo esc_html( $cover['label'] ); ?></span><?php endif; ?>
        <?php if ( ! empty( $info['badge'] ) ) : ?><span class="yl-recommend-card__badge"><?php echo esc_html( $info['badge'] ); ?></span><?php endif; ?>
    </button>
    <?php endif; ?>
    <?php if ( $is_honest_fallback && $is_shopping ) : ?>
    <div class="yl-shopping-no-image yl-shopping-no-image--recommend" data-yl-no-image-design="shopping-editorial-r5" role="img" aria-label="<?php echo esc_attr( get_the_title( $post_id ) . ' — Chưa có ảnh địa điểm đã xác minh' ); ?>">
        <span class="yl-shopping-no-image__architecture" aria-hidden="true"></span>
        <span class="yl-shopping-no-image__topline"><span class="yl-shopping-no-image__icon" aria-hidden="true"><?php echo yl_icon( 'shop' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span>Mua sắm · <?php echo esc_html( $shopping_district ); ?></span></span>
        <span class="yl-shopping-no-image__monogram" aria-hidden="true"><?php echo esc_html( $shopping_monogram ); ?></span>
        <span class="yl-shopping-no-image__name"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>
        <small>Chưa có ảnh địa điểm đã xác minh</small>
    </div>
    <?php endif; ?>
    <div class="yl-recommend-card__body">
        <?php if ( $is_honest_fallback && ! empty( $info['badge'] ) ) : ?><span class="yl-recommend-card__badge yl-recommend-card__badge--inline"><?php echo esc_html( $info['badge'] ); ?></span><?php endif; ?>
        <?php if ( $is_honest_fallback && ! $is_shopping ) : ?><button type="button" class="yl-place-card__photo-action yl-recommend-card__photo-action" data-yl-place-photos data-post-id="<?php echo esc_attr( $post_id ); ?>" data-title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" data-maps-url="<?php echo esc_url( $maps_url ); ?>"><?php echo yl_icon( 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><strong><?php echo esc_html( $category ? $category->name : 'Địa điểm' ); ?></strong><small>Chưa có ảnh địa điểm đã xác minh</small></span></button><?php endif; ?>
        <p class="yl-recommend-card__meta"><?php echo esc_html( ( $district ? $district->name : 'Hà Nội' ) . ( $category ? ' · ' . $category->name : '' ) ); ?></p>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <?php if ( ! empty( $info['reason'] ) ) : ?><p class="yl-recommend-card__reason"><?php echo esc_html( $info['reason'] ); ?></p><?php endif; ?>
        <?php if ( ! empty( $info['best_time'] ) || ! empty( $info['duration'] ) ) : ?><div class="yl-recommend-card__facts"><?php if ( ! empty( $info['best_time'] ) ) : ?><span><strong>Lúc đẹp:</strong> <?php echo esc_html( $info['best_time'] ); ?></span><?php endif; ?><?php if ( ! empty( $info['duration'] ) ) : ?><span><strong>Dành:</strong> <?php echo esc_html( $info['duration'] ); ?></span><?php endif; ?></div><?php endif; ?>
        <div class="yl-recommend-card__actions"><a class="yl-text-link" href="<?php the_permalink(); ?>">Xem chi tiết →</a><?php if ( $maps_url ) : ?><a class="yl-text-link" href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer">Chỉ đường ↗</a><?php endif; ?></div>
    </div>
</article>
