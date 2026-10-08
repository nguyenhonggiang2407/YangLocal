<?php get_header(); ?>
<?php
$archive_url = get_post_type_archive_link( 'yl_place' );

/**
 * Resolve a curated list without ever inventing fallback records.
 */
$yl_home_posts_by_slugs = static function( $slugs ) {
    $posts = array();
    foreach ( (array) $slugs as $slug ) {
        $post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'yl_place' );
        if ( $post && 'publish' === $post->post_status ) {
            $posts[] = $post;
        }
    }
    return $posts;
};

$yl_render_editorial_media = static function( $post_id, $class = '' ) {
    $maps_url = function_exists( 'yl_get_google_maps_url' ) ? yl_get_google_maps_url( $post_id ) : '';
    $cover    = function_exists( 'yl_place_cover_info' ) ? yl_place_cover_info( $post_id, 'city' ) : array( 'url'=>yl_demo_image_url($post_id,'city'), 'label'=>'', 'alt'=>'Ảnh '.get_the_title($post_id), 'fallback_url'=>yl_neutral_fallback_image_url('city') );
    $image    = ! empty( $cover['url'] ) ? $cover['url'] : yl_neutral_fallback_image_url( 'city' );
    ?>
    <button type="button" class="yl-cultural-media yl-place-photo-trigger <?php echo esc_attr( $class ); ?>" data-yl-place-photos data-post-id="<?php echo esc_attr( $post_id ); ?>" data-title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" data-maps-url="<?php echo esc_url( $maps_url ); ?>" aria-label="Xem ảnh của <?php echo esc_attr( get_the_title( $post_id ) ); ?>">
        <img src="<?php echo esc_url( $image ); ?>" data-fallback-src="<?php echo esc_url( ! empty( $cover['fallback_url'] ) ? $cover['fallback_url'] : yl_neutral_fallback_image_url('city') ); ?>" alt="<?php echo esc_attr( !empty($cover['alt']) ? $cover['alt'] : 'Ảnh tham khảo cho '.get_the_title($post_id) ); ?>" loading="lazy" decoding="async">
        <?php if ( ! empty( $cover['label'] ) ) : ?><span class="yl-image-badge"><?php echo esc_html( $cover['label'] ); ?></span><?php endif; ?>
    </button>
    <?php
};
?>

<section class="yl-hero yl-hero--editorial">
    <div class="yl-container yl-hero__grid yl-hero__grid--editorial">
        <div class="yl-hero__content">
            <p class="yl-eyebrow">Hà Nội cho sinh viên</p>
            <h1>Hôm nay ăn gì, học đâu, đi đâu?</h1>
            <p class="yl-hero__lede">Đi đâu, ăn gì, học ở đâu — có YangLocal.</p>
            <form class="yl-hero-search" method="get" action="<?php echo esc_url( $archive_url ); ?>">
                <input type="search" name="q" aria-label="Tìm địa điểm" placeholder="Thử “café gần HUBT dưới 50K”...">
                <button class="yl-button" type="submit"><?php echo yl_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Tìm</button>
            </form>
            <div class="yl-home-chips" aria-label="Nhu cầu phổ biến">
                <a href="<?php echo esc_url( add_query_arg( array( 'price'=>'50000' ), $archive_url ) ); ?>">Ăn dưới 50K</a>
                <a href="<?php echo esc_url( add_query_arg( array( 'category'=>'cafe','wifi'=>'1' ), $archive_url ) ); ?>">Café Wi‑Fi</a>
                <a href="<?php echo esc_url( home_url( '/gan-truong/' ) ); ?>">Gần trường</a>
                <a href="<?php echo esc_url( add_query_arg( 'category', 'vui-choi', $archive_url ) ); ?>">Đi chơi</a>
                <a href="<?php echo esc_url( add_query_arg( 'category', 'bao-tang', $archive_url ) ); ?>">Bảo tàng</a>
                <a href="<?php echo esc_url( add_query_arg( 'category', 'lich-su-di-san', $archive_url ) ); ?>">Hà Nội lịch sử</a>
            </div>
        </div>
        <div class="yl-hero-collage" aria-label="Một vài lát cắt Hà Nội" data-yl-hero-build="7.24.1-r16p4">
            <?php
            // Public export uses the bundled illustrations, not omitted venue photos.
            $yl_hero_base_uri = trailingslashit( get_template_directory_uri() ) . 'assets/images/';
            $yl_hero_images = array(
                array(
                    'src'     => 'fallback-cafe.svg',
                    'srcset'  => array(),
                    'alt'     => 'Hình minh họa chủ đề café',
                    'caption' => 'Minh họa café',
                    'width'   => 1200,
                    'height'  => 800,
                    'class'   => 'yl-hero-collage__main',
                ),
                array(
                    'src'     => 'fallback-city.svg',
                    'srcset'  => array(),
                    'alt'     => 'Hình minh họa chủ đề khám phá thành phố',
                    'caption' => 'Minh họa đi chơi',
                    'width'   => 1200,
                    'height'  => 900,
                    'class'   => '',
                ),
                array(
                    'src'     => 'fallback-food.svg',
                    'srcset'  => array(),
                    'alt'     => 'Hình minh họa chủ đề ăn uống',
                    'caption' => 'Minh họa ăn uống',
                    'width'   => 1200,
                    'height'  => 900,
                    'class'   => '',
                ),
            );
            foreach ( $yl_hero_images as $yl_hero_index => $yl_hero_image ) :
                $yl_src = add_query_arg( 'v', YL_THEME_ASSET_VERSION, $yl_hero_base_uri . $yl_hero_image['src'] );
                $yl_srcset_parts = array();
                foreach ( $yl_hero_image['srcset'] as $yl_width => $yl_filename ) {
                    $yl_srcset_parts[] = esc_url( add_query_arg( 'v', YL_THEME_ASSET_VERSION, $yl_hero_base_uri . $yl_filename ) ) . ' ' . absint( $yl_width ) . 'w';
                }
                $yl_srcset = implode( ', ', $yl_srcset_parts );
            ?>
            <figure<?php echo $yl_hero_image['class'] ? ' class="' . esc_attr( $yl_hero_image['class'] ) . '"' : ''; ?> data-image-source="YangLocal bundled illustration"><img src="<?php echo esc_url( $yl_src ); ?>" alt="<?php echo esc_attr( $yl_hero_image['alt'] ); ?>" width="<?php echo esc_attr( $yl_hero_image['width'] ); ?>" height="<?php echo esc_attr( $yl_hero_image['height'] ); ?>" <?php echo 0 === $yl_hero_index ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async"><figcaption><?php echo esc_html( $yl_hero_image['caption'] ); ?></figcaption></figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="yl-section yl-section--surface">
    <div class="yl-container">
        <div class="yl-section-heading"><div><p class="yl-eyebrow">Đáng lưu tuần này</p><h2>Một vài chỗ dễ dùng ngay.</h2><p>Một vài địa điểm có hình ảnh dễ hình dung và thông tin đủ để quyết định nhanh.</p></div><a class="yl-section-link" href="<?php echo esc_url( $archive_url ); ?>">Khám phá thêm →</a></div>
        <?php
        $weekly_ids = function_exists( 'yl_get_trusted_photo_place_ids' ) ? yl_get_trusted_photo_place_ids(
            array(
                'tax_query' => array(
                    array( 'taxonomy' => 'place_category', 'field' => 'slug', 'terms' => array( 'quan-an', 'cafe', 'hoc-tap', 'an-vat' ) ),
                ),
            ),
            4
        ) : array();
        ?>
        <?php if ( $weekly_ids ) : $weekly = new WP_Query( array( 'post_type'=>'yl_place','post_status'=>'publish','post__in'=>$weekly_ids,'orderby'=>'post__in','posts_per_page'=>count($weekly_ids),'no_found_rows'=>true ) ); ?>
            <div class="yl-weekly-grid"><?php $yl_weekly_index=0; while ( $weekly->have_posts() ) : $weekly->the_post(); ?><div class="yl-weekly-grid__item<?php echo 0 === $yl_weekly_index ? ' is-featured' : ''; ?>"><?php get_template_part( 'template-parts/place-card' ); ?></div><?php $yl_weekly_index++; endwhile; ?></div><?php wp_reset_postdata(); ?>
        <?php else : ?><div class="yl-empty-state"><p>Chưa có đủ lựa chọn phù hợp cho mục này. Bạn có thể mở Khám phá để xem thêm.</p><a class="yl-button yl-button--ghost" href="<?php echo esc_url( $archive_url ); ?>">Mở Khám phá</a></div><?php endif; ?>
    </div>
</section>

<!-- DO NOT REGRESS: approved YangLocal recommendation section. -->
<section class="yl-section yl-recommend-home" id="yanglocal-goi-y">
    <div class="yl-container">
        <div class="yl-section-heading">
            <div><p class="yl-eyebrow">YangLocal gợi ý</p><h2>Lần đầu ở Hà Nội, nên đi đâu?</h2></div>
            <div class="yl-section-heading__side"><p>Không cần lịch dày. Đây là những điểm YangLocal ưu tiên nếu bạn muốn cảm được Hà Nội mà vẫn dễ đi và hợp lịch sinh viên.</p><a class="yl-text-link" href="<?php echo esc_url( add_query_arg( 'recommended', '1', $archive_url ) ); ?>">Xem tất cả gợi ý →</a></div>
        </div>
        <?php $yl_recommended = function_exists( 'yl_get_recommended_places' ) ? yl_get_recommended_places( 6 ) : null; ?>
        <?php if ( $yl_recommended && $yl_recommended->have_posts() ) : ?>
        <div class="yl-recommend-grid">
            <?php while ( $yl_recommended->have_posts() ) : $yl_recommended->the_post(); get_template_part( 'template-parts/recommend-card' ); endwhile; wp_reset_postdata(); ?>
        </div>
        <?php endif; ?>
        <?php
        $yl_route_posts = $yl_home_posts_by_slugs( array( 'nha-tho-lon-ha-noi', 'khong-gian-di-bo-hoan-kiem', 'cho-dem-pho-co-ha-noi' ) );
        ?>
        <?php if ( count( $yl_route_posts ) >= 2 ) : ?>
        <aside class="yl-recommend-route">
            <div><p class="yl-eyebrow">Một lịch dễ đi</p><h3>1 buổi Hoàn Kiếm cho người chưa biết bắt đầu từ đâu</h3><p>Đi từ chiều sang tối: ghé Nhà thờ Lớn, dạo Hồ Gươm rồi nối sang Phố cổ. Nếu là cuối tuần, có thể kết ở chợ đêm.</p></div>
            <ol><?php foreach ( $yl_route_posts as $yl_route_index => $yl_route_post ) : ?><li><span><?php echo esc_html( $yl_route_index + 1 ); ?></span><a href="<?php echo esc_url( get_permalink( $yl_route_post ) ); ?>"><?php echo esc_html( get_the_title( $yl_route_post ) ); ?></a></li><?php endforeach; ?></ol>
        </aside>
        <?php endif; ?>
    </div>
</section>

<section class="yl-section yl-campus-home yl-section--surface">
    <div class="yl-container">
        <div class="yl-section-heading"><div><p class="yl-eyebrow">Quanh trường</p><h2>Bạn học trường nào?</h2><p>Chọn trường để xem nhanh chỗ ăn, café, học và dịch vụ quanh khu vực bạn đi học.</p></div><a class="yl-text-link" href="<?php echo esc_url( home_url( '/gan-truong/' ) ); ?>">Tất cả trường →</a></div>
        <?php if ( function_exists( 'yl_render_school_picker' ) ) : ?><form class="yl-campus-home-search yl-campus-search" data-yl-campus-jump data-base="<?php echo esc_url( home_url( '/gan-truong/' ) ); ?>"><?php echo yl_render_school_picker( 'school', '', 'yl-home-school' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><button class="yl-button" type="submit">Xem quanh trường</button></form><?php endif; ?>
        <div class="yl-campus-home-grid">
        <?php $campus_slugs = array( 'hubt', 'dh-bach-khoa', 'dh-kinh-te-qd', 'dh-xay-dung-ha-noi', 'dh-luat-ha-noi', 'dh-ngoai-thuong' ); foreach ( $campus_slugs as $campus_slug ) : $campus_term = get_term_by( 'slug', $campus_slug, 'near_school' ); if ( ! $campus_term || is_wp_error( $campus_term ) ) { continue; } ?>
            <a class="yl-school-index-card<?php echo 'hubt' === $campus_slug ? ' is-priority' : ''; ?>" href="<?php echo esc_url( get_term_link( $campus_term ) ); ?>"><span class="yl-eyebrow"><?php echo esc_html( function_exists( 'yl_get_school_meta' ) ? yl_get_school_meta( $campus_term, 'short_name', $campus_term->name ) : $campus_term->name ); ?></span><strong><?php echo esc_html( $campus_term->name ); ?></strong><p><?php echo esc_html( get_term_meta( $campus_term->term_id, 'yl_school_description', true ) ?: $campus_term->description ); ?></p><small><?php echo esc_html( function_exists( 'yl_school_place_coverage_label' ) ? yl_school_place_coverage_label( $campus_term ) : number_format_i18n( $campus_term->count ) . ' địa điểm' ); ?></small></a>
        <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="yl-section">
    <div class="yl-container">
        <div class="yl-section-heading"><div><p class="yl-eyebrow">Ngân sách sinh viên</p><h2>Có 50K thì đi đâu?</h2><p>Một vài lựa chọn có giá tham khảo rõ để bạn nhìn nhanh rồi quyết định.</p></div><a class="yl-section-link" href="<?php echo esc_url( add_query_arg( 'price', '50000', $archive_url ) ); ?>">Xem tất cả dưới 50K →</a></div>
        <?php
        $budget_ids = function_exists( 'yl_get_trusted_photo_place_ids' ) ? yl_get_trusted_photo_place_ids(
            array(
                'tax_query'  => array( array( 'taxonomy'=>'place_category','field'=>'slug','terms'=>array('quan-an','an-vat','cafe') ) ),
                'meta_query' => array(
                    'relation'=>'AND',
                    array( 'key'=>'_yl_price_from','value'=>0,'compare'=>'>','type'=>'NUMERIC' ),
                    array( 'key'=>'_yl_price_from','value'=>50000,'compare'=>'<=','type'=>'NUMERIC' ),
                ),
            ), 4
        ) : array();
        ?>
        <?php if ( $budget_ids ) : $budget_places = new WP_Query( array( 'post_type'=>'yl_place','post_status'=>'publish','post__in'=>$budget_ids,'orderby'=>'post__in','posts_per_page'=>count($budget_ids),'no_found_rows'=>true ) ); ?><div class="yl-home-rail yl-home-rail--budget"><?php while ( $budget_places->have_posts() ) : $budget_places->the_post(); get_template_part( 'template-parts/place-card' ); endwhile; ?></div><?php wp_reset_postdata(); else : ?><div class="yl-empty-state"><p>Chưa đủ lựa chọn phù hợp cho hàng này. Mở danh sách dưới 50K để xem thêm.</p></div><?php endif; ?>
    </div>
</section>

<section class="yl-section yl-section--surface">
    <div class="yl-container">
        <div class="yl-section-heading"><div><p class="yl-eyebrow">Mang laptop đi đâu?</p><h2>Chọn chỗ dễ ngồi lâu, không chỉ đẹp để chụp.</h2><p>Ưu tiên Wi‑Fi, ổ điện, bàn ghế và độ yên khi thông tin đủ rõ — để đến nơi còn học được thật.</p></div><a class="yl-section-link" href="<?php echo esc_url( add_query_arg( array( 'wifi'=>'1' ), $archive_url ) ); ?>">Tìm chỗ mang laptop →</a></div>
        <?php
        $laptop_ids = function_exists( 'yl_get_trusted_photo_place_ids' ) ? yl_get_trusted_photo_place_ids(
            array(
                'tax_query'  => array( array( 'taxonomy'=>'place_category','field'=>'slug','terms'=>array('hoc-tap','cafe') ) ),
                'meta_query' => array(
                    'relation' => 'OR',
                    array( 'key'=>'_yl_wifi','value'=>'1' ),
                    array( 'key'=>'_yl_power_outlet','value'=>'1' ),
                    array( 'key'=>'_yl_quiet','value'=>'1' ),
                ),
            ), 4
        ) : array();
        ?>
        <?php if ( $laptop_ids ) : $laptop_places = new WP_Query( array( 'post_type'=>'yl_place','post_status'=>'publish','post__in'=>$laptop_ids,'orderby'=>'post__in','posts_per_page'=>count($laptop_ids),'no_found_rows'=>true ) ); ?><div class="yl-home-rail yl-home-rail--utility"><?php while ( $laptop_places->have_posts() ) : $laptop_places->the_post(); get_template_part( 'template-parts/place-card' ); endwhile; ?></div><?php wp_reset_postdata(); endif; ?>
        <div class="yl-study-filters yl-study-filters--footer"><a href="<?php echo esc_url( add_query_arg( array( 'wifi'=>'1' ), $archive_url ) ); ?>">Có Wi‑Fi</a><a href="<?php echo esc_url( add_query_arg( array( 'power_outlet'=>'1' ), $archive_url ) ); ?>">Có ổ điện</a><a href="<?php echo esc_url( add_query_arg( array( 'quiet'=>'1' ), $archive_url ) ); ?>">Yên tĩnh</a></div>
    </div>
</section>

<section class="yl-section yl-shopping-home" data-yl-shopping-build="7.24.1-r6">
    <div class="yl-container">
        <div class="yl-section-heading"><div><p class="yl-eyebrow">Mua sắm · ăn uống · đi chơi</p><h2>TTTM dễ đi ở Hà Nội.</h2><p>Một vài lựa chọn cho hôm cần gom nhiều việc vào cùng một chỗ.</p></div><a class="yl-section-link" href="<?php echo esc_url( add_query_arg( 'category', 'mua-sam', $archive_url ) ); ?>">Xem tất cả TTTM →</a></div>
        <?php
        $mall_posts = $yl_home_posts_by_slugs( array( 'aeon-mall-long-bien', 'vincom-mega-mall-royal-city', 'lotte-mall-west-lake-hanoi', 'trang-tien-plaza' ) );
        $mall_ids = wp_list_pluck( $mall_posts, 'ID' );
        $shopping_places = $mall_ids ? new WP_Query( array( 'post_type'=>'yl_place','post_status'=>'publish','post__in'=>$mall_ids,'orderby'=>'post__in','posts_per_page'=>count($mall_ids),'no_found_rows'=>true ) ) : null;
        ?>
        <?php if ( $shopping_places && $shopping_places->have_posts() ) : ?><div class="yl-shopping-feature-grid"><?php while ( $shopping_places->have_posts() ) : $shopping_places->the_post(); get_template_part( 'template-parts/place-card' ); endwhile; ?></div><?php wp_reset_postdata(); endif; ?>
    </div>
</section>

<section class="yl-section yl-history-home yl-section--dark">
    <div class="yl-container">
        <div class="yl-section-heading"><div><p class="yl-eyebrow">Hà Nội không chỉ có café</p><h2>Chạm vào lịch sử Hà Nội.</h2><p>Có những nơi đi qua là thấy thành phố này có nhiều lớp lịch sử hơn mình tưởng.</p></div><a class="yl-section-link" href="<?php echo esc_url( add_query_arg( 'category', 'lich-su-di-san', $archive_url ) ); ?>">Xem Lịch sử & Di sản →</a></div>
        <?php
        $history_posts = $yl_home_posts_by_slugs( array( 'lang-chu-tich-ho-chi-minh', 'hoang-thanh-thang-long', 'di-tich-nha-tu-hoa-lo', 'den-ngoc-son', 'cot-co-ha-noi' ) );
        $history_feature = $history_posts ? array_shift( $history_posts ) : null;
        ?>
        <?php if ( $history_feature ) : $history_id = $history_feature->ID; ?>
        <div class="yl-history-layout">
            <article class="yl-history-feature">
                <?php $yl_render_editorial_media( $history_id, 'yl-history-feature__media' ); ?>
                <div class="yl-history-feature__body"><p class="yl-eyebrow">Một buổi ở Ba Đình</p><h3><a href="<?php echo esc_url( get_permalink( $history_id ) ); ?>"><?php echo esc_html( get_the_title( $history_id ) ); ?></a></h3><p><?php echo esc_html( get_the_excerpt( $history_id ) ); ?></p><div class="yl-history-feature__actions"><a class="yl-button" href="<?php echo esc_url( get_permalink( $history_id ) ); ?>">Xem trước khi đi</a><?php $hm = yl_get_google_maps_url( $history_id ); if ( $hm ) : ?><a class="yl-button yl-button--ghost" href="<?php echo esc_url( $hm ); ?>" target="_blank" rel="noopener noreferrer">Chỉ đường</a><?php endif; ?></div></div>
            </article>
            <div class="yl-history-secondary">
                <?php foreach ( $history_posts as $history_post ) : ?><article><span><?php echo esc_html( function_exists( 'yl_place_primary_district' ) && yl_place_primary_district( $history_post->ID ) ? yl_place_primary_district( $history_post->ID )->name : 'Hà Nội' ); ?></span><h3><a href="<?php echo esc_url( get_permalink( $history_post ) ); ?>"><?php echo esc_html( get_the_title( $history_post ) ); ?></a></h3><p><?php echo esc_html( wp_trim_words( get_the_excerpt( $history_post ), 20 ) ); ?></p><a class="yl-text-link" href="<?php echo esc_url( get_permalink( $history_post ) ); ?>">Xem chi tiết →</a></article><?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php $ba_dinh_route = $yl_home_posts_by_slugs( array( 'lang-chu-tich-ho-chi-minh','khu-di-tich-chu-tich-ho-chi-minh-phu-chu-tich','chua-mot-cot','bao-tang-ho-chi-minh' ) ); ?>
        <?php if ( count( $ba_dinh_route ) >= 3 ) : ?><aside class="yl-cultural-route"><div><strong>Một buổi ở Ba Đình</strong><span>Đi theo một cụm gần nhau thay vì chạy khắp thành phố.</span></div><ol><?php foreach ( $ba_dinh_route as $i => $route_post ) : ?><li><span><?php echo esc_html( $i + 1 ); ?></span><a href="<?php echo esc_url( get_permalink( $route_post ) ); ?>"><?php echo esc_html( get_the_title( $route_post ) ); ?></a></li><?php endforeach; ?></ol></aside><?php endif; ?>
    </div>
</section>

<section class="yl-section yl-museum-home yl-section--surface">
    <div class="yl-container">
        <div class="yl-section-heading"><div><p class="yl-eyebrow">Một chiều đi bảo tàng</p><h2>Một buổi đi bảo tàng.</h2><p>Mỗi nơi kể một lát cắt khác của Hà Nội và Việt Nam; giờ, vé chỉ hiện khi đã có nguồn kiểm tra.</p></div><a class="yl-section-link" href="<?php echo esc_url( add_query_arg( 'category', 'bao-tang', $archive_url ) ); ?>">Xem tất cả bảo tàng →</a></div>
        <?php $museum_posts = $yl_home_posts_by_slugs( array( 'bao-tang-ho-chi-minh','bao-tang-dan-toc-hoc-viet-nam','bao-tang-phu-nu-viet-nam','bao-tang-my-thuat-viet-nam','bao-tang-lich-su-quoc-gia' ) ); ?>
        <div class="yl-museum-grid yl-museum-strip">
        <?php foreach ( $museum_posts as $museum_post ) : $mid = $museum_post->ID; $district = function_exists( 'yl_place_primary_district' ) ? yl_place_primary_district( $mid ) : null; $duration = get_post_meta( $mid, '_yl_visit_duration', true ); $topic = get_post_meta( $mid, '_yl_topic', true ); ?>
            <article class="yl-museum-card"><?php $yl_render_editorial_media( $mid, 'yl-museum-card__media' ); ?><div class="yl-museum-card__body"><p><?php echo esc_html( $district ? $district->name : 'Hà Nội' ); ?><?php if ( $topic ) : ?> · <?php echo esc_html( $topic ); ?><?php endif; ?></p><h3><a href="<?php echo esc_url( get_permalink( $mid ) ); ?>"><?php echo esc_html( get_the_title( $mid ) ); ?></a></h3><?php if ( $duration ) : ?><span class="yl-museum-card__duration"><?php echo esc_html( $duration ); ?></span><?php endif; ?><a class="yl-text-link" href="<?php echo esc_url( get_permalink( $mid ) ); ?>">Xem trước khi đi →</a></div></article>
        <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="yl-section">
    <div class="yl-container">
        <?php $yl_posts_page_id = (int) get_option( 'page_for_posts' ); $yl_guide_url = $yl_posts_page_id ? get_permalink( $yl_posts_page_id ) : home_url( '/?post_type=post' ); ?>
        <div class="yl-section-heading"><div><p class="yl-eyebrow">Cẩm nang</p><h2>Gợi ý thật, viết cho sinh viên.</h2><p>Ưu tiên các bài ngắn, dễ áp dụng, đủ thực tế để dùng trước khi đi chứ không chỉ để đọc cho vui.</p></div><a class="yl-section-link" href="<?php echo esc_url( $yl_guide_url ); ?>">Xem cẩm nang →</a></div>
        <?php $articles = new WP_Query( array( 'post_type'=>'post','post_status'=>'publish','posts_per_page'=>6,'ignore_sticky_posts'=>true,'no_found_rows'=>true ) ); ?>
        <?php if ( $articles->have_posts() ) : $article_items = $articles->posts; ?>
        <div class="yl-editorial">
            <?php $lead = $article_items[0]; setup_postdata( $lead ); ?><article class="yl-editorial-lead"><img src="<?php echo esc_url( yl_demo_image_url( $lead->ID, yl_fallback_key_for_post( $lead->ID, 'study' ) ) ); ?>" alt="<?php echo esc_attr( get_the_title( $lead ) ); ?>" loading="lazy"><div class="yl-editorial-lead__content"><p class="yl-eyebrow" style="color:#fff;">Bài chọn</p><h2><a href="<?php echo esc_url( get_permalink( $lead ) ); ?>"><?php echo esc_html( get_the_title( $lead ) ); ?></a></h2></div></article>
            <div class="yl-editorial-list"><?php for ( $i=1; $i<count($article_items); $i++ ) : $item=$article_items[$i]; ?><article class="yl-editorial-item"><small>Cẩm nang</small><h3><a href="<?php echo esc_url( get_permalink( $item ) ); ?>"><?php echo esc_html( get_the_title( $item ) ); ?></a></h3><p><?php echo esc_html( wp_trim_words( get_the_excerpt( $item ), 18 ) ); ?></p></article><?php endfor; ?></div>
        </div>
            <div class="yl-student-cheatsheet" aria-label="Cẩm nang nhanh cho sinh viên">
                <article class="yl-student-cheatsheet__item">
                    <small>Sinh viên mới lên Hà Nội</small>
                    <h3>Đi đâu nếu chỉ rảnh một buổi?</h3>
                    <p>Nếu mới lên Hà Nội, hãy bắt đầu bằng 3 cụm dễ đi: Hoàn Kiếm để cảm không khí trung tâm, Ba Đình để hiểu thêm lịch sử, và một quán café gần trường để có chỗ ngồi học thật sự dùng được.</p>
                </article>
                <article class="yl-student-cheatsheet__item">
                    <small>Chi tiêu hợp lý</small>
                    <h3>Ưu tiên chỗ rõ giá, rõ giờ, rõ đường đi</h3>
                    <p>YangLocal đang ưu tiên các điểm có giá tham khảo, giờ mở cửa và link Maps rõ ràng để bớt cảnh đi xa rồi đến nơi mới biết đóng cửa hoặc giá không hợp túi tiền.</p>
                </article>
                <article class="yl-student-cheatsheet__item">
                    <small>Checklist trước khi đi</small>
                    <h3>Nhìn nhanh 3 thứ: giờ mở cửa, khoảng cách, ảnh thật</h3>
                    <p>Đây là cách nhanh nhất để tránh các địa điểm “trông đẹp trên mạng nhưng không hợp nhu cầu”. Ưu tiên ảnh thật, đúng địa điểm và nội dung đủ sát trải nghiệm sinh viên.</p>
                </article>
            </div>
<?php wp_reset_postdata(); ?>
        <?php else : ?><div class="yl-empty-state"><p>Chưa có bài cẩm nang mới.</p></div><?php endif; ?>
    </div>
</section>

<section class="yl-section yl-section--surface">
    <div class="yl-container">
        <div class="yl-section-heading"><div><p class="yl-eyebrow">Lịch sinh viên</p><h2>Tuần này có gì?</h2></div><a class="yl-section-link" href="<?php echo esc_url( get_post_type_archive_link( 'yl_event' ) ); ?>">Xem lịch sự kiện →</a></div>
        <?php $events = new WP_Query( array( 'post_type'=>'yl_event','post_status'=>'publish','posts_per_page'=>5,'meta_key'=>'_yl_event_date','orderby'=>'meta_value','order'=>'ASC','meta_query'=>array( array( 'key'=>'_yl_event_date','value'=>current_time('Y-m-d'),'compare'=>'>=','type'=>'DATE' ) ) ) ); ?>
        <?php if ( $events->have_posts() ) : ?><div class="yl-events-list"><?php while ( $events->have_posts() ) : $events->the_post(); if ( function_exists('yl_get_event_status') && 'past'===yl_get_event_status(get_the_ID()) ) { continue; } get_template_part('template-parts/event-row'); endwhile; ?></div><?php wp_reset_postdata(); else : ?><div class="yl-empty-state"><h2>Tuần này chưa có lịch mới.</h2><p>Xem vài địa điểm đáng đi trong lúc chờ sự kiện tiếp theo.</p><a class="yl-button yl-button--ghost" href="<?php echo esc_url( add_query_arg( 'recommended', '1', $archive_url ) ); ?>">Xem YangLocal gợi ý</a></div><?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
