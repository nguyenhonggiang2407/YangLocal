<?php
$post_id        = get_the_ID();
$category       = function_exists( 'yl_place_primary_category' ) ? yl_place_primary_category( $post_id ) : null;
$district       = function_exists( 'yl_place_primary_district' ) ? yl_place_primary_district( $post_id ) : null;
$price          = get_post_meta( $post_id, '_yl_price_from', true );
$address        = get_post_meta( $post_id, '_yl_address', true );
$opening        = get_post_meta( $post_id, '_yl_opening_note', true );
$phone          = get_post_meta( $post_id, '_yl_phone', true );
$rating         = function_exists( 'yl_get_rating_summary' ) ? yl_get_rating_summary( $post_id ) : array( 'average'=>0, 'count'=>0 );
$school_terms   = wp_get_post_terms( $post_id, 'near_school' );
$school_label   = '';
if ( ! is_wp_error( $school_terms ) && $school_terms ) {
    $preferred_school = null;
    foreach ( $school_terms as $school_term ) {
        if ( 'hubt' === $school_term->slug ) { $preferred_school = $school_term; break; }
        if ( null === $preferred_school ) { $preferred_school = $school_term; }
    }
    if ( $preferred_school ) {
        $school_label = function_exists( 'yl_get_school_meta' ) ? yl_get_school_meta( $preferred_school, 'short_name', $preferred_school->name ) : $preferred_school->name;
    }
}

$fallback_key   = function_exists( 'yl_fallback_key_for_post' ) ? yl_fallback_key_for_post( $post_id, 'cafe' ) : 'cafe';
$cover          = function_exists( 'yl_place_cover_info' ) ? yl_place_cover_info( $post_id, $fallback_key ) : array( 'url'=>yl_demo_image_url($post_id,$fallback_key),'label'=>'','alt'=>'Ảnh '.get_the_title($post_id),'fallback_url'=>yl_fallback_image_url($fallback_key),'is_exact'=>false );
$image          = ! empty( $cover['url'] ) ? $cover['url'] : yl_fallback_image_url( $fallback_key );
$fallback_image = ! empty( $cover['fallback_url'] ) ? $cover['fallback_url'] : yl_neutral_fallback_image_url( $fallback_key );
$image_alt      = ! empty( $cover['alt'] ) ? $cover['alt'] : 'Ảnh tham khảo cho ' . get_the_title( $post_id );
$image_label    = ! empty( $cover['label'] ) ? $cover['label'] : '';
$image_srcset   = ! empty( $cover['srcset'] ) ? $cover['srcset'] : '';
$image_sizes    = ! empty( $cover['sizes'] ) ? $cover['sizes'] : '';
$is_honest_fallback = isset( $cover['status'] ) && 'FALLBACK' === $cover['status'];
$image_state    = ! empty( $cover['state'] ) ? $cover['state'] : ( $is_honest_fallback ? 'MISSING' : 'CONTEXTUAL' );
$is_service     = $category && 'dich-vu' === $category->slug;
$is_shopping    = $category && 'mua-sam' === $category->slug;
$maps_url       = function_exists( 'yl_get_google_maps_url' ) ? yl_get_google_maps_url( $post_id ) : '';
$shopping_monogram = $is_shopping && function_exists( 'yl_place_monogram' ) ? yl_place_monogram( get_the_title( $post_id ) ) : '';
$shopping_district = $district ? $district->name : 'Hà Nội';
$phone_href     = function_exists( 'yl_phone_href' ) ? yl_phone_href( $phone ) : '';
$recommendation = function_exists( 'yl_get_recommendation_info' ) ? yl_get_recommendation_info( $post_id ) : array( 'recommended'=>false );
$tag_map = array( 'wifi'=>'Wi‑Fi','power_outlet'=>'Ổ điện','quiet'=>'Yên tĩnh','group_study'=>'Học nhóm','late_open'=>'Mở muộn','quick_meal'=>'Ăn nhanh','weekend'=>'Cuối tuần','group_4_6'=>'Đi nhóm' );
$tags = array();
foreach ( $tag_map as $key=>$label ) {
    if ( '1' === (string) get_post_meta( $post_id, '_yl_' . $key, true ) ) { $tags[]=$label; }
    if ( count( $tags ) >= 2 ) { break; }
}
?>
<article class="yl-place-card<?php echo $is_service ? ' yl-place-card--utility' : ''; ?><?php echo $is_shopping ? ' yl-place-card--shopping' : ''; ?><?php echo $is_honest_fallback ? ' yl-place-card--no-photo' : ''; ?>" data-yl-image-state="<?php echo esc_attr( $image_state ); ?>" data-yl-card-build="r6">
    <?php if ( $is_honest_fallback && $is_shopping ) : ?>
        <div class="yl-shopping-no-image" data-yl-no-image-design="shopping-editorial-r5" role="img" aria-label="<?php echo esc_attr( get_the_title( $post_id ) . ' — Chưa có ảnh địa điểm đã xác minh' ); ?>">
            <span class="yl-shopping-no-image__architecture" aria-hidden="true"></span>
            <span class="yl-shopping-no-image__topline"><span class="yl-shopping-no-image__icon" aria-hidden="true"><?php echo yl_icon( 'shop' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span>Mua sắm · <?php echo esc_html( $shopping_district ); ?></span></span>
            <span class="yl-shopping-no-image__monogram" aria-hidden="true"><?php echo esc_html( $shopping_monogram ); ?></span>
            <span class="yl-shopping-no-image__name"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>
            <small>Chưa có ảnh địa điểm đã xác minh</small>
        </div>
    <?php elseif ( $is_honest_fallback ) : ?>
        <button type="button" class="yl-place-card__photo-action" data-yl-place-photos data-post-id="<?php echo esc_attr( $post_id ); ?>" data-title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" data-maps-url="<?php echo esc_url( $maps_url ); ?>" aria-label="Kiểm tra ảnh và vị trí của <?php echo esc_attr( get_the_title( $post_id ) ); ?>">
            <?php echo yl_icon( 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <span><strong><?php echo esc_html( $category ? $category->name : 'Địa điểm' ); ?></strong><small>Chưa có ảnh địa điểm đã xác minh</small></span>
        </button>
    <?php else : ?>
        <button type="button" class="yl-place-card__image<?php echo $is_service ? ' yl-utility-card__image' : ''; ?> yl-place-photo-trigger" data-yl-place-photos data-post-id="<?php echo esc_attr( $post_id ); ?>" data-title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" data-maps-url="<?php echo esc_url( $maps_url ); ?>" aria-label="Xem ảnh của <?php echo esc_attr( get_the_title( $post_id ) ); ?>">
            <img src="<?php echo esc_url( $image ); ?>"<?php if ( $image_srcset ) : ?> srcset="<?php echo esc_attr( $image_srcset ); ?>" sizes="<?php echo esc_attr( $image_sizes ); ?>"<?php endif; ?> data-fallback-src="<?php echo esc_url( $fallback_image ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy" decoding="async">
            <?php if ( $image_label ) : ?><span class="yl-image-badge"><?php echo esc_html( $image_label ); ?></span><?php endif; ?>
        </button>
    <?php endif; ?>
    <button class="yl-favorite-button" type="button" data-yl-favorite data-post-id="<?php echo esc_attr( $post_id ); ?>" aria-label="Lưu lại" aria-pressed="false"><?php echo yl_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
    <div class="yl-place-card__body<?php echo $is_service ? ' yl-utility-card__body' : ''; ?>">
        <?php if ( $is_service ) : ?>
            <div class="yl-utility-card__top"><span class="yl-utility-card__icon" aria-hidden="true"><?php echo yl_icon( function_exists( 'yl_service_icon_name' ) ? yl_service_icon_name( $post_id ) : 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><div><span class="yl-utility-card__type">Dịch vụ quanh campus</span></div></div>
        <?php else : ?>
            <div class="yl-place-card__meta"><span><?php echo $category ? esc_html( $category->name ) : 'Địa điểm'; ?></span><?php if ( $rating['count'] > 0 ) : ?><span class="yl-rating"><?php echo yl_icon( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( number_format_i18n( $rating['average'], 1 ) ); ?> · <?php echo esc_html( $rating['count'] ); ?></span><?php endif; ?></div>
        <?php endif; ?>

        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <?php if ( $is_service ) : ?>
            <?php if ( $address ) : ?><p class="yl-utility-card__address"><?php echo yl_icon( 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $address ); ?></span></p><?php endif; ?>
            <?php if ( $opening ) : ?><p class="yl-utility-card__fact"><?php echo yl_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $opening ); ?></span></p><?php endif; ?>
            <?php if ( $phone ) : ?><p class="yl-utility-card__fact"><?php echo yl_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $phone ); ?></span></p><?php endif; ?>
            <div class="yl-utility-card__actions"><?php if ( $maps_url ) : ?><a class="yl-button yl-button--compact" href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer">Chỉ đường</a><?php endif; ?><?php if ( $phone_href ) : ?><a class="yl-button yl-button--ghost yl-button--compact" href="<?php echo esc_attr( $phone_href ); ?>">Gọi</a><?php endif; ?><a class="yl-text-link" href="<?php the_permalink(); ?>">Chi tiết →</a></div>
        <?php else : ?>
            <p class="yl-place-card__sub"><?php if ( $school_label && ! $is_shopping ) : ?>Gần <?php echo esc_html( $school_label ); ?> · <?php endif; ?><?php echo $district ? esc_html( $district->name ) : 'Hà Nội'; ?><?php if ( $price && ! $is_shopping ) : ?> · <?php echo esc_html( function_exists( 'yl_get_price_label' ) ? yl_get_price_label( $price ) : number_format_i18n( $price ) . 'đ' ); ?><?php endif; ?></p>
            <?php if ( $is_shopping && $address ) : ?><p class="yl-place-card__shopping-address"><?php echo yl_icon( 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $address ); ?></span></p><?php endif; ?>
            <?php if ( $tags ) : ?><div class="yl-place-card__tags"><?php foreach ( $tags as $tag ) : ?><span><?php echo esc_html( $tag ); ?></span><?php endforeach; ?></div><?php endif; ?>
        <?php endif; ?>
    </div>
</article>
