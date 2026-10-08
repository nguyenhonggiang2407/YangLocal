<?php get_header(); ?>
<?php while ( have_posts() ) : the_post(); ?>
<?php
$post_id        = get_the_ID();
$category       = yl_place_primary_category( $post_id );
$district       = yl_place_primary_district( $post_id );
$categories     = wp_get_post_terms( $post_id, 'place_category', array( 'fields'=>'slugs' ) );
$categories     = is_wp_error( $categories ) ? array() : $categories;
$is_history     = in_array( 'lich-su-di-san', $categories, true );
$is_museum      = in_array( 'bao-tang', $categories, true );
$is_cultural    = $is_history || $is_museum;
$rating         = function_exists( 'yl_get_rating_summary' ) ? yl_get_rating_summary( $post_id ) : array( 'average'=>0, 'count'=>0 );
$address        = get_post_meta( $post_id, '_yl_address', true );
$price          = absint( get_post_meta( $post_id, '_yl_price_from', true ) );
$opening        = get_post_meta( $post_id, '_yl_opening_note', true );
$phone          = get_post_meta( $post_id, '_yl_phone', true );
$phone_href     = function_exists( 'yl_phone_href' ) ? yl_phone_href( $phone ) : '';
$website        = get_post_meta( $post_id, '_yl_website', true );
$source_url     = get_post_meta( $post_id, '_yl_source_url', true );
$source_type    = get_post_meta( $post_id, '_yl_source_type', true );
$source_checked = get_post_meta( $post_id, '_yl_source_checked', true );
$student_notes  = get_post_meta( $post_id, '_yl_student_notes', true );
$school_terms   = wp_get_post_terms( $post_id, 'near_school' );
$image_source   = get_post_meta( $post_id, '_yl_image_source_url', true );
$lat            = get_post_meta( $post_id, '_yl_latitude', true );
$lng            = get_post_meta( $post_id, '_yl_longitude', true );
$coord_verified = '1' === (string) get_post_meta( $post_id, '_yl_coord_verified', true );
$fallback_key   = function_exists( 'yl_fallback_key_for_post' ) ? yl_fallback_key_for_post( $post_id, 'city' ) : 'city';
$cover          = function_exists( 'yl_place_cover_info' ) ? yl_place_cover_info( $post_id, $fallback_key ) : array( 'url'=>yl_demo_image_url($post_id,$fallback_key),'label'=>'','alt'=>'Ảnh '.get_the_title($post_id),'fallback_url'=>yl_neutral_fallback_image_url($fallback_key),'is_exact'=>false );
$gallery        = yl_place_gallery_urls( $post_id );
$has_photo      = ! empty( $cover['is_exact'] );
$maps_url       = function_exists( 'yl_get_google_maps_url' ) ? yl_get_google_maps_url( $post_id ) : '';
$directions_url = $coord_verified && is_numeric( $lat ) && is_numeric( $lng ) ? 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $lat . ',' . $lng ) : ( $address ? 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $address ) : $maps_url );
$recommendation = function_exists( 'yl_get_recommendation_info' ) ? yl_get_recommendation_info( $post_id ) : array( 'recommended'=>false );

$topic          = get_post_meta( $post_id, '_yl_topic', true );
$visit_duration = get_post_meta( $post_id, '_yl_visit_duration', true );
$ticket_note    = get_post_meta( $post_id, '_yl_ticket_note', true );
$visit_note     = get_post_meta( $post_id, '_yl_visit_note', true );
$rules_note     = get_post_meta( $post_id, '_yl_rules_note', true );
$related_slugs  = array_values( array_filter( array_map( 'sanitize_title', preg_split( '/\r\n|\r|\n|,/', (string) get_post_meta( $post_id, '_yl_related_slugs', true ) ) ) ) );

if ( ! $gallery && ! empty( $cover['url'] ) ) {
    $gallery = array( $cover['url'] );
}
$image         = isset( $gallery[0] ) ? $gallery[0] : '';
$gallery_count = count( $gallery );
$image_label   = ! empty( $cover['label'] ) ? $cover['label'] : '';
$image_alt     = ! empty( $cover['alt'] ) ? $cover['alt'] : 'Ảnh tham khảo cho ' . get_the_title( $post_id );
$image_srcset  = ! empty( $cover['srcset'] ) ? $cover['srcset'] : '';
$image_sizes   = ! empty( $cover['detail_sizes'] ) ? $cover['detail_sizes'] : ( ! empty( $cover['sizes'] ) ? $cover['sizes'] : '' );
$image_fallback= ! empty( $cover['fallback_url'] ) ? $cover['fallback_url'] : yl_neutral_fallback_image_url( $fallback_key );
$is_honest_fallback = isset( $cover['status'] ) && 'FALLBACK' === $cover['status'];
$image_state    = ! empty( $cover['state'] ) ? $cover['state'] : ( $is_honest_fallback ? 'MISSING' : 'CONTEXTUAL' );
?>
<article class="yl-single-place<?php echo $is_cultural ? ' yl-single-place--cultural' : ''; ?>" data-yl-template-version="7.24.1" data-yl-build="r6" data-yl-image-state="<?php echo esc_attr( $image_state ); ?>"><div class="yl-container">
    <nav class="yl-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a><span>/</span><a href="<?php echo esc_url( get_post_type_archive_link( 'yl_place' ) ); ?>">Khám phá</a><span>/</span><span aria-current="page"><?php the_title(); ?></span></nav>

    <div class="yl-single-head">
        <div>
            <p class="yl-eyebrow"><?php echo esc_html( $is_museum ? 'Bảo tàng' : ( $is_history ? 'Lịch sử & Di sản' : ( $category ? $category->name : 'Địa điểm' ) ) ); ?></p>
            <h1 class="yl-single-title"><?php the_title(); ?></h1>
            <div class="yl-single-sub"><?php if ( $district ) : ?><span><?php echo esc_html( $district->name ); ?></span><?php endif; ?><?php if ( $topic ) : ?><span><?php echo esc_html( $topic ); ?></span><?php endif; ?><?php if ( $rating['count'] > 0 ) : ?><span class="yl-rating"><?php echo yl_icon( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( number_format_i18n( $rating['average'], 1 ) ); ?> · <?php echo esc_html( $rating['count'] ); ?> đánh giá YangLocal</span><?php endif; ?></div>
        </div>
        <div class="yl-single-actions">
            <button class="yl-button yl-button--ghost" type="button" data-yl-favorite data-post-id="<?php echo esc_attr( $post_id ); ?>" aria-label="Lưu <?php echo esc_attr( get_the_title() ); ?>" aria-pressed="false"><?php echo yl_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Lưu lại</button>
            <?php if ( $directions_url ) : ?><a class="yl-button" href="<?php echo esc_url( $directions_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo yl_icon( 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Chỉ đường</a><?php endif; ?>
        </div>
    </div>

    <?php if ( $gallery_count > 0 ) : ?>
        <div class="yl-gallery yl-gallery--photo-trigger yl-gallery--count-<?php echo esc_attr( min( 3, max( 1, $gallery_count ) ) ); ?>" data-yl-place-photos data-post-id="<?php echo esc_attr( $post_id ); ?>" data-title="<?php echo esc_attr( get_the_title() ); ?>" data-maps-url="<?php echo esc_url( $maps_url ); ?>" role="button" tabindex="0" aria-label="Xem tất cả ảnh của <?php echo esc_attr( get_the_title() ); ?>">
            <div class="yl-gallery__main"><?php if ( $is_honest_fallback ) : ?><span class="yl-detail-no-image"><span class="yl-detail-no-image__icon" aria-hidden="true"><?php echo yl_icon( 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><strong>Chưa có ảnh địa điểm đã xác minh</strong><span>YangLocal giữ phần này ở trạng thái không ảnh thay vì dùng hình một địa điểm khác.</span></span><?php else : ?><img src="<?php echo esc_url( $image ); ?>"<?php if ( $image_srcset ) : ?> srcset="<?php echo esc_attr( $image_srcset ); ?>" sizes="<?php echo esc_attr( $image_sizes ); ?>"<?php endif; ?> data-fallback-src="<?php echo esc_url( $image_fallback ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" decoding="async"><?php if ( $image_label ) : ?><span class="yl-image-badge yl-image-badge--detail"><?php echo esc_html( $image_label ); ?></span><?php endif; ?><?php endif; ?></div>
            <?php if ( ! $is_honest_fallback && $gallery_count > 1 ) : ?><div class="yl-gallery__side"><?php foreach ( array_slice( $gallery, 1, 2 ) as $gallery_index => $gallery_url ) : ?><img src="<?php echo esc_url( $gallery_url ); ?>" data-fallback-src="<?php echo esc_url( $image_fallback ); ?>" alt="<?php echo esc_attr( 'Góc tham quan khác tại ' . get_the_title() ); ?>" loading="lazy" decoding="async"><?php endforeach; ?></div><?php endif; ?>
        </div>
        <div class="yl-image-note"><button type="button" class="yl-text-link yl-photo-more-button" data-yl-place-photos data-post-id="<?php echo esc_attr( $post_id ); ?>" data-title="<?php echo esc_attr( get_the_title() ); ?>" data-maps-url="<?php echo esc_url( $maps_url ); ?>">Xem tất cả ảnh</button><?php if ( $maps_url ) : ?><a class="yl-text-link" href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer">Xem thêm trên Google Maps ↗</a><?php endif; ?></div>
    <?php endif; ?>

    <div class="yl-place-layout"><div class="yl-prose">
        <?php if ( $address ) : ?><p class="yl-place-address"><strong>Địa chỉ:</strong> <?php echo esc_html( $address ); ?></p><?php endif; ?>
        <section class="yl-detail-section yl-detail-intro"><h2>Vì sao đáng ghé</h2><div class="yl-detail-copy"><?php the_content(); ?></div></section>

        <?php if ( ! empty( $recommendation['recommended'] ) ) : ?>
        <section class="yl-detail-section yl-recommend-detail">
            <div class="yl-recommend-detail__head"><span>YangLocal đề xuất</span><?php if ( ! empty( $recommendation['badge'] ) ) : ?><strong><?php echo esc_html( $recommendation['badge'] ); ?></strong><?php endif; ?></div>
            <h2>Vì sao nên ghé?</h2>
            <?php if ( ! empty( $recommendation['reason'] ) ) : ?><p><?php echo esc_html( $recommendation['reason'] ); ?></p><?php endif; ?>
            <div class="yl-recommend-detail__facts"><?php if ( ! empty( $recommendation['best_time'] ) ) : ?><div><strong>Thời điểm hợp</strong><span><?php echo esc_html( $recommendation['best_time'] ); ?></span></div><?php endif; ?><?php if ( ! empty( $recommendation['duration'] ) ) : ?><div><strong>Nên dành</strong><span><?php echo esc_html( $recommendation['duration'] ); ?></span></div><?php endif; ?></div>
        </section>
        <?php endif; ?>

        <?php if ( $is_cultural && ( $visit_duration || $ticket_note || $visit_note || $rules_note ) ) : ?>
        <section class="yl-detail-section yl-cultural-before">
            <p class="yl-eyebrow">Trước khi đi</p><h2>Điều nên biết</h2>
            <div class="yl-cultural-facts">
                <?php if ( $visit_duration ) : ?><div><strong>Thời gian tham quan</strong><span><?php echo esc_html( $visit_duration ); ?></span></div><?php endif; ?>
                <?php if ( $ticket_note ) : ?><div><strong>Vé</strong><span><?php echo esc_html( $ticket_note ); ?></span></div><?php endif; ?>
                <?php if ( $opening ) : ?><div><strong>Giờ tham khảo</strong><span><?php echo esc_html( $opening ); ?></span></div><?php endif; ?>
            </div>
            <?php if ( $visit_note ) : ?><div class="yl-cultural-note"><strong>Hợp khi</strong><p><?php echo esc_html( $visit_note ); ?></p></div><?php endif; ?>
            <?php if ( $rules_note ) : ?><div class="yl-cultural-note"><strong>Lưu ý</strong><p><?php echo esc_html( $rules_note ); ?></p></div><?php endif; ?>
        </section>
        <?php endif; ?>

        <?php
        $fit_labels = array(
            'solo_study'=>'Học một mình','group_study'=>'Học / làm việc nhóm','pair_visit'=>'Đi 2 người','long_stay'=>'Ngồi lâu','quick_meal'=>'Ăn nhanh','late_open'=>'Đi muộn','deadline'=>'Chạy deadline','weekend'=>'Đi cuối tuần','group_4_6'=>'Nhóm 4–6 người',
        );
        $fit = array();
        foreach ( $fit_labels as $key=>$label ) { if ( '1' === (string) get_post_meta( $post_id, '_yl_'.$key, true ) ) { $fit[]=$label; } }
        ?>
        <?php if ( $fit ) : ?><section class="yl-detail-section"><h2>Phù hợp với</h2><div class="yl-fit-list"><?php foreach ( $fit as $label ) : ?><span><?php echo esc_html( $label ); ?></span><?php endforeach; ?></div></section><?php endif; ?>

        <?php if ( ! $is_cultural ) : ?>
        <?php
        $amenities = array( 'wifi'=>array('Có Wi‑Fi','wifi'),'power_outlet'=>array('Có ổ điện','bolt'),'quiet'=>array('Không gian yên tĩnh','map'),'group_study'=>array('Phù hợp làm việc nhóm','users'),'air_conditioning'=>array('Có điều hòa','map'),'parking'=>array('Có chỗ gửi xe','map') );
        $amenity_rows = array();
        foreach ( $amenities as $key=>$data ) { if ( '1' === get_post_meta( $post_id, '_yl_'.$key, true ) ) { $amenity_rows[]=$data; } }
        ?>
        <?php if ( $amenity_rows ) : ?><section class="yl-detail-section"><h2>Tiện ích</h2><div class="yl-amenities"><?php foreach ( $amenity_rows as $data ) : ?><div class="yl-amenity"><?php echo yl_icon( $data[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $data[0] ); ?></span></div><?php endforeach; ?></div></section><?php endif; ?>
        <?php endif; ?>

        <?php if ( $student_notes ) : ?><section class="yl-detail-section"><h2><?php echo $is_cultural ? 'Góc nhìn sinh viên' : 'Mẹo sinh viên'; ?></h2><div class="yl-student-note"><?php echo wpautop( esc_html( $student_notes ) ); ?></div></section><?php endif; ?>

        <?php if ( ! is_wp_error( $school_terms ) && $school_terms ) : ?><section class="yl-detail-section"><h2>Gợi ý theo khu vực trường</h2><div class="yl-near-school-list"><?php foreach ( $school_terms as $school_term ) : $distance = function_exists('yl_get_distance_to_school') ? yl_get_distance_to_school($post_id,$school_term) : null; ?><a href="<?php echo esc_url( get_term_link( $school_term ) ); ?>"><strong><?php echo esc_html( $school_term->name ); ?></strong><span><?php echo null !== $distance ? esc_html( yl_format_distance( $distance ) ) : 'Xem gợi ý quanh campus'; ?></span></a><?php endforeach; ?></div></section><?php endif; ?>

        <section class="yl-detail-section yl-location-section"><h2>Bản đồ</h2>
        <?php if ( $coord_verified && is_numeric( $lat ) && is_numeric( $lng ) ) :
            $map_lat = (float) $lat;
            $map_lng = (float) $lng;
            $map_pad = 0.006;
            $osm_embed = add_query_arg(
                array(
                    'bbox'   => ( $map_lng - $map_pad ) . ',' . ( $map_lat - $map_pad ) . ',' . ( $map_lng + $map_pad ) . ',' . ( $map_lat + $map_pad ),
                    'layer'  => 'mapnik',
                    'marker' => $map_lat . ',' . $map_lng,
                ),
                'https://www.openstreetmap.org/export/embed.html'
            );
            $osm_link = add_query_arg( array( 'mlat' => $map_lat, 'mlon' => $map_lng, 'zoom' => 16 ), 'https://www.openstreetmap.org/' );
        ?>
        <div class="yl-map" data-yl-map data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $lng ); ?>" data-name="<?php echo esc_attr( get_the_title() ); ?>" aria-label="Bản đồ vị trí <?php echo esc_attr( get_the_title() ); ?>" aria-busy="true"></div>
        <div class="yl-map-fallback" data-yl-map-fallback hidden>
            <p><strong>Bản đồ tương tác chưa tải được.</strong> Bạn vẫn có thể xem vị trí bằng OpenStreetMap hoặc mở Google Maps bên dưới.</p>
            <iframe class="yl-map-fallback__frame" data-src="<?php echo esc_url( $osm_embed ); ?>" title="Bản đồ dự phòng <?php echo esc_attr( get_the_title() ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <p class="yl-map-fallback__actions"><a class="yl-text-link" href="<?php echo esc_url( $osm_link ); ?>" target="_blank" rel="noopener">Mở OpenStreetMap →</a></p>
        </div>
        <?php else : ?><div class="yl-map-fallback yl-map-fallback--compact"><?php if ( $address ) : ?><p><strong>Địa chỉ</strong><br><?php echo esc_html( $address ); ?></p><?php endif; ?></div><?php endif; ?>
        <?php if ( $directions_url || $maps_url ) : ?><div class="yl-map-actions"><?php if ( $directions_url ) : ?><a class="yl-button" href="<?php echo esc_url( $directions_url ); ?>" target="_blank" rel="noopener noreferrer">Chỉ đường</a><?php endif; ?><?php if ( $maps_url ) : ?><a class="yl-button yl-button--ghost" href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer">Xem trên Google Maps</a><?php endif; ?></div><?php endif; ?>
        </section>

        <?php $external_links = function_exists( 'yl_render_external_place_links' ) ? yl_render_external_place_links( $post_id, 'yl-external-actions yl-external-actions--detail', false ) : ''; ?>
        <?php if ( $external_links ) : ?><section class="yl-detail-section yl-explore-more"><h2>Xem thêm về địa điểm</h2><p>Muốn kiểm tra đường đi, xem nội dung xã hội hoặc trang chính thức thì mở từ đây.</p><?php echo $external_links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></section><?php endif; ?>

        <?php if ( $source_url || $image_source ) : ?>
        <details class="yl-source-note yl-provenance">
            <summary>Nguồn & cập nhật</summary>
            <div class="yl-provenance__body">
                <?php if ( $source_url ) : ?><p><strong>Nguồn thông tin:</strong> <a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener noreferrer">Mở nguồn</a></p><?php endif; ?>
                <?php if ( $source_checked ) : ?><p><strong>Cập nhật:</strong> <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $source_checked ) ) ); ?></p><?php endif; ?>
                <?php if ( $image_source ) : ?><p><strong>Nguồn ảnh:</strong> <a href="<?php echo esc_url( $image_source ); ?>" target="_blank" rel="noopener noreferrer">Xem nguồn ảnh</a></p><?php endif; ?>
                <p>Giờ, vé, dịch vụ và quy định có thể thay đổi. YangLocal không tự tạo rating, review hay dữ liệu lịch sử khi chưa có nguồn.</p>
            </div>
        </details>
        <?php endif; ?>

        <?php echo do_shortcode( '[yanglocal_report_place place_id="' . absint( $post_id ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php if ( comments_open() || get_comments_number() ) : ?><section class="yl-comments"><?php comments_template(); ?></section><?php endif; ?>
    </div>

    <aside class="yl-info-card" aria-label="Thông tin nhanh"><p class="yl-eyebrow">Thông tin nhanh</p><dl>
        <?php if ( $district ) : ?><div class="yl-info-card__row"><dt>Khu vực</dt><dd><?php echo esc_html( $district->name ); ?></dd></div><?php endif; ?>
        <?php if ( ! $is_cultural && $price ) : ?><div class="yl-info-card__row"><dt>Giá từ</dt><dd><?php echo esc_html( number_format_i18n( $price ).'đ' ); ?></dd></div><?php endif; ?>
        <?php if ( $visit_duration ) : ?><div class="yl-info-card__row"><dt>Thời gian</dt><dd><?php echo esc_html( $visit_duration ); ?></dd></div><?php endif; ?>
        <?php if ( $opening ) : ?><div class="yl-info-card__row"><dt>Giờ</dt><dd><?php echo esc_html( $opening ); ?></dd></div><?php endif; ?>
        <?php if ( $ticket_note ) : ?><div class="yl-info-card__row"><dt>Vé</dt><dd><?php echo esc_html( $ticket_note ); ?></dd></div><?php endif; ?>
        <?php if ( $phone ) : ?><div class="yl-info-card__row"><dt>Điện thoại</dt><dd><?php if ( $phone_href ) : ?><a href="<?php echo esc_attr( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a><?php else : ?><?php echo esc_html( $phone ); ?><?php endif; ?></dd></div><?php endif; ?>
    </dl><div class="yl-info-card__actions"><button class="yl-button yl-button--ghost" type="button" data-yl-favorite data-post-id="<?php echo esc_attr( $post_id ); ?>" aria-label="Lưu <?php echo esc_attr( get_the_title() ); ?>" aria-pressed="false"><?php echo yl_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Lưu</button><?php if ( $directions_url ) : ?><a class="yl-button" href="<?php echo esc_url( $directions_url ); ?>" target="_blank" rel="noopener noreferrer">Chỉ đường</a><?php endif; ?></div></aside></div>

    <?php
    $related_args = array(
        'post_type'=>'yl_place','post_status'=>'publish','posts_per_page'=>3,'post__not_in'=>array($post_id),'no_found_rows'=>true,
    );
    if ( $related_slugs ) {
        $related_ids = array();
        foreach ( $related_slugs as $related_slug ) {
            $related_post = get_page_by_path( $related_slug, OBJECT, 'yl_place' );
            if ( $related_post && (int)$related_post->ID !== $post_id ) { $related_ids[]=(int)$related_post->ID; }
        }
        if ( $related_ids ) { $related_args['post__in']=$related_ids; $related_args['orderby']='post__in'; $related_args['posts_per_page']=min(4,count($related_ids)); }
    }
    if ( empty( $related_args['post__in'] ) ) {
        $related_args['orderby']='date'; $related_args['order']='DESC';
        if ( $category ) { $related_args['tax_query']=array( array('taxonomy'=>'place_category','field'=>'term_id','terms'=>$category->term_id) ); }
    }
    $related = new WP_Query( $related_args );
    ?>
    <?php if ( $related->have_posts() ) : ?><section class="yl-related"><div class="yl-section-heading yl-section-heading--compact"><div><p class="yl-eyebrow">Đi cùng tuyến</p><h2><?php echo $is_cultural ? 'Ghé thêm trong cùng một buổi' : 'Địa điểm liên quan'; ?></h2></div></div><?php if ( $is_cultural ) : ?><ol class="yl-itinerary-strip"><?php $route_i=1; while ( $related->have_posts() ) : $related->the_post(); ?><li><span><?php echo esc_html( $route_i ); ?></span><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li><?php $route_i++; endwhile; ?></ol><?php else : ?><div class="yl-place-grid yl-place-grid--three"><?php while ( $related->have_posts() ) : $related->the_post(); get_template_part( 'template-parts/place-card' ); endwhile; ?></div><?php endif; ?></section><?php wp_reset_postdata(); endif; ?>
</div></article>
<?php endwhile; ?>
<?php get_footer(); ?>
