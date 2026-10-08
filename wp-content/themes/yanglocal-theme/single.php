<?php get_header(); ?>
<div class="yl-page-shell yl-guide-single">
    <?php while ( have_posts() ) : the_post(); ?>
        <?php
        $yl_post_id        = get_the_ID();
        $yl_category       = function_exists( 'yl_guide_primary_category' ) ? yl_guide_primary_category( $yl_post_id ) : null;
        $yl_guide_url      = function_exists( 'yl_guide_archive_url' ) ? yl_guide_archive_url() : home_url( '/cam-nang/' );
        $yl_featured_image = function_exists( 'yl_demo_image_url' ) ? yl_demo_image_url( $yl_post_id, 'city' ) : get_the_post_thumbnail_url( $yl_post_id, 'yl-editorial' );
        $yl_place_ids      = function_exists( 'yl_guide_related_place_ids' ) ? yl_guide_related_place_ids( $yl_post_id, 4 ) : array();
        $yl_related_posts  = function_exists( 'yl_guide_related_posts' ) ? yl_guide_related_posts( $yl_post_id, 3 ) : array();
        ?>
        <article class="yl-guide-article">
            <header class="yl-guide-article__header yl-container">
                <nav class="yl-guide-breadcrumb" aria-label="Đường dẫn">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
                    <span aria-hidden="true">/</span>
                    <a href="<?php echo esc_url( $yl_guide_url ); ?>">Cẩm nang</a>
                    <?php if ( $yl_category ) : ?>
                        <span aria-hidden="true">/</span>
                        <a href="<?php echo esc_url( function_exists( 'yl_guide_archive_url' ) ? yl_guide_archive_url( $yl_category->slug ) : get_category_link( $yl_category ) ); ?>"><?php echo esc_html( $yl_category->name ); ?></a>
                    <?php endif; ?>
                </nav>

                <?php if ( $yl_category ) : ?><a class="yl-guide-kicker yl-guide-kicker--link" href="<?php echo esc_url( function_exists( 'yl_guide_archive_url' ) ? yl_guide_archive_url( $yl_category->slug ) : get_category_link( $yl_category ) ); ?>"><?php echo esc_html( $yl_category->name ); ?></a><?php endif; ?>
                <h1><?php the_title(); ?></h1>
                <?php if ( has_excerpt() ) : ?><p class="yl-guide-article__deck"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
                <div class="yl-guide-meta yl-guide-meta--article">
                    <span>Cập nhật <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?></time></span>
                    <span aria-hidden="true">·</span>
                    <span><?php echo esc_html( function_exists( 'yl_guide_reading_time' ) ? yl_guide_reading_time( $yl_post_id ) : '' ); ?></span>
                </div>
            </header>

            <div class="yl-container">
                <figure class="yl-guide-article__cover">
                    <img src="<?php echo esc_url( $yl_featured_image ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="eager" decoding="async" onerror="this.onerror=null;this.src='<?php echo esc_js( get_template_directory_uri() . '/assets/images/fallback-city.svg' ); ?>';">
                </figure>
            </div>

            <div class="yl-guide-reading-column">
                <div class="yl-prose yl-guide-prose">
                    <?php the_content(); ?>
                </div>
                <aside class="yl-guide-update-note" aria-label="Lưu ý cập nhật thông tin">
                    <strong>Trước khi đi</strong>
                    <p>Giá, giờ mở cửa, lịch hoạt động và quy định có thể thay đổi. Với địa điểm cụ thể, hãy mở nguồn hoặc kênh chính thức mới nhất trước khi lên đường.</p>
                </aside>
            </div>

            <?php if ( $yl_place_ids ) : ?>
                <section class="yl-guide-places" aria-labelledby="yl-guide-places-title">
                    <div class="yl-container">
                        <div class="yl-guide-section-heading">
                            <p class="yl-eyebrow">Điểm liên quan</p>
                            <h2 id="yl-guide-places-title">Mở địa điểm trong YangLocal</h2>
                        </div>
                        <div class="yl-guide-place-grid">
                            <?php foreach ( $yl_place_ids as $yl_place_id ) : ?>
                                <?php
                                $yl_place_category = function_exists( 'yl_place_primary_category' ) ? yl_place_primary_category( $yl_place_id ) : null;
                                $yl_place_district = function_exists( 'yl_place_primary_district' ) ? yl_place_primary_district( $yl_place_id ) : null;
                                $yl_place_image    = function_exists( 'yl_demo_image_url' ) ? yl_demo_image_url( $yl_place_id, 'city' ) : get_the_post_thumbnail_url( $yl_place_id, 'yl-card' );
                                ?>
                                <article class="yl-guide-place-card">
                                    <a class="yl-guide-place-card__media" href="<?php echo esc_url( get_permalink( $yl_place_id ) ); ?>" aria-label="Xem <?php echo esc_attr( get_the_title( $yl_place_id ) ); ?>">
                                        <img src="<?php echo esc_url( $yl_place_image ); ?>" alt="<?php echo esc_attr( get_the_title( $yl_place_id ) ); ?>" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='<?php echo esc_js( get_template_directory_uri() . '/assets/images/fallback-city.svg' ); ?>';">
                                    </a>
                                    <div class="yl-guide-place-card__body">
                                        <div class="yl-guide-place-card__meta">
                                            <?php if ( $yl_place_district ) : ?><span><?php echo esc_html( $yl_place_district->name ); ?></span><?php endif; ?>
                                            <?php if ( $yl_place_category ) : ?><span><?php echo esc_html( $yl_place_category->name ); ?></span><?php endif; ?>
                                        </div>
                                        <h3><a href="<?php echo esc_url( get_permalink( $yl_place_id ) ); ?>"><?php echo esc_html( get_the_title( $yl_place_id ) ); ?></a></h3>
                                        <a class="yl-guide-read-link" href="<?php echo esc_url( get_permalink( $yl_place_id ) ); ?>">Xem địa điểm <span aria-hidden="true">→</span></a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( $yl_related_posts ) : ?>
                <section class="yl-guide-related" aria-labelledby="yl-guide-related-title">
                    <div class="yl-container">
                        <div class="yl-guide-section-heading">
                            <p class="yl-eyebrow">Đọc tiếp</p>
                            <h2 id="yl-guide-related-title">Cùng chủ đề</h2>
                        </div>
                        <div class="yl-guide-grid yl-guide-grid--related">
                            <?php foreach ( $yl_related_posts as $post ) : setup_postdata( $post ); ?>
                                <?php get_template_part( 'template-parts/guide-card' ); ?>
                            <?php endforeach; wp_reset_postdata(); ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="yl-guide-cta">
                <div class="yl-container yl-guide-cta__inner">
                    <div>
                        <p class="yl-eyebrow">Đang tìm chỗ để đi?</p>
                        <h2>Chuyển từ đọc sang chọn địa điểm.</h2>
                    </div>
                    <div class="yl-guide-cta__actions">
                        <a class="yl-button" href="<?php echo esc_url( get_post_type_archive_link( 'yl_place' ) ); ?>">Khám phá địa điểm</a>
                        <a class="yl-button yl-button--ghost" href="<?php echo esc_url( home_url( '/gan-truong/' ) ); ?>">Xem quanh trường</a>
                    </div>
                </div>
            </section>
        </article>
    <?php endwhile; ?>
</div>
<?php get_footer(); ?>
