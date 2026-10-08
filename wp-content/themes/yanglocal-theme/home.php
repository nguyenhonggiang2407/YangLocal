<?php
get_header();
$yl_topic       = function_exists( 'yl_guide_current_topic' ) ? yl_guide_current_topic() : '';
$yl_topics      = function_exists( 'yl_guide_topics' ) ? yl_guide_topics() : array();
$yl_featured_id = function_exists( 'yl_get_guide_featured_id' ) ? yl_get_guide_featured_id() : 0;
?>
<div class="yl-page-shell yl-guide-page">
    <section class="yl-guide-hero" aria-labelledby="yl-guide-title">
        <div class="yl-container">
            <p class="yl-eyebrow">Cẩm nang sinh viên Hà Nội</p>
            <h1 id="yl-guide-title">Sống ở Hà Nội dễ hơn một chút.</h1>
            <p class="yl-guide-hero__lede">Những kinh nghiệm nhỏ về ăn uống, học tập, đi chơi và đời sống sinh viên — viết ngắn, rõ và đủ dùng trước khi bạn ra khỏi nhà.</p>

            <?php if ( $yl_topics ) : ?>
                <nav class="yl-guide-topics" aria-label="Chủ đề Cẩm nang">
                    <div class="yl-guide-topics__track">
                        <?php foreach ( $yl_topics as $yl_slug => $yl_item ) : ?>
                            <?php
                            $yl_active = (string) $yl_slug === (string) $yl_topic;
                            $yl_url    = function_exists( 'yl_guide_archive_url' ) ? yl_guide_archive_url( $yl_slug ) : home_url( '/cam-nang/' );
                            ?>
                            <a class="yl-guide-topic<?php echo $yl_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $yl_url ); ?>"<?php echo $yl_active ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $yl_item['label'] ); ?></a>
                        <?php endforeach; ?>
                    </div>
                </nav>
            <?php endif; ?>
        </div>
    </section>

    <?php if ( ! $yl_topic && $yl_featured_id ) : ?>
        <?php
        $yl_featured = get_post( $yl_featured_id );
        if ( $yl_featured ) :
            setup_postdata( $yl_featured );
            $yl_featured_category = function_exists( 'yl_guide_primary_category' ) ? yl_guide_primary_category( $yl_featured_id ) : null;
            $yl_featured_image    = function_exists( 'yl_demo_image_url' ) ? yl_demo_image_url( $yl_featured_id, 'city' ) : get_the_post_thumbnail_url( $yl_featured_id, 'yl-editorial' );
        ?>
            <section class="yl-guide-start" aria-labelledby="yl-guide-start-title">
                <div class="yl-container">
                    <div class="yl-guide-section-heading">
                        <p class="yl-eyebrow">Bắt đầu từ đây</p>
                        <h2 id="yl-guide-start-title">Một bài nên đọc trước</h2>
                    </div>
                    <article class="yl-guide-feature">
                        <a class="yl-guide-feature__media" href="<?php the_permalink(); ?>" aria-label="Đọc <?php echo esc_attr( get_the_title() ); ?>">
                            <img src="<?php echo esc_url( $yl_featured_image ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="eager" decoding="async" onerror="this.onerror=null;this.src='<?php echo esc_js( get_template_directory_uri() . '/assets/images/fallback-city.svg' ); ?>';">
                        </a>
                        <div class="yl-guide-feature__body">
                            <?php if ( $yl_featured_category ) : ?><span class="yl-guide-kicker"><?php echo esc_html( $yl_featured_category->name ); ?></span><?php endif; ?>
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 34 ) ); ?></p>
                            <div class="yl-guide-meta">
                                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></time>
                                <span aria-hidden="true">·</span>
                                <span><?php echo esc_html( function_exists( 'yl_guide_reading_time' ) ? yl_guide_reading_time( $yl_featured_id ) : '' ); ?></span>
                            </div>
                            <a class="yl-guide-read-link" href="<?php the_permalink(); ?>">Đọc cẩm nang <span aria-hidden="true">→</span></a>
                        </div>
                    </article>
                </div>
            </section>
        <?php
            wp_reset_postdata();
        endif;
        ?>
    <?php endif; ?>

    <section class="yl-guide-list" aria-labelledby="yl-guide-list-title">
        <div class="yl-container">
            <div class="yl-guide-section-heading yl-guide-section-heading--row">
                <div>
                    <p class="yl-eyebrow"><?php echo $yl_topic ? 'Đang lọc theo chủ đề' : 'Cẩm nang mới'; ?></p>
                    <h2 id="yl-guide-list-title"><?php
                        if ( $yl_topic && isset( $yl_topics[ $yl_topic ] ) ) {
                            echo esc_html( $yl_topics[ $yl_topic ]['label'] );
                        } else {
                            echo 'Đọc tiếp';
                        }
                    ?></h2>
                </div>
                <?php if ( $yl_topic && function_exists( 'yl_guide_archive_url' ) ) : ?>
                    <a class="yl-section-link" href="<?php echo esc_url( yl_guide_archive_url() ); ?>">Xem tất cả</a>
                <?php endif; ?>
            </div>

            <?php if ( have_posts() ) : ?>
                <div class="yl-guide-grid">
                    <?php while ( have_posts() ) : the_post(); ?>
                        <?php get_template_part( 'template-parts/guide-card' ); ?>
                    <?php endwhile; ?>
                </div>
                <div class="yl-pagination yl-guide-pagination" aria-label="Phân trang Cẩm nang">
                    <?php
                    the_posts_pagination(
                        array(
                            'mid_size'           => 1,
                            'prev_text'          => '<span aria-hidden="true">←</span> Trước',
                            'next_text'          => 'Sau <span aria-hidden="true">→</span>',
                            'screen_reader_text' => 'Điều hướng trang Cẩm nang',
                            'aria_label'         => 'Các trang Cẩm nang',
                            'add_args'           => $yl_topic ? array( 'guide_topic' => $yl_topic ) : false,
                        )
                    );
                    ?>
                </div>
            <?php else : ?>
                <div class="yl-empty-state">
                    <h2>Chưa có bài phù hợp.</h2>
                    <p>Thử một chủ đề khác hoặc quay lại toàn bộ Cẩm nang.</p>
                    <?php if ( function_exists( 'yl_guide_archive_url' ) ) : ?><a class="yl-button yl-button--ghost" href="<?php echo esc_url( yl_guide_archive_url() ); ?>">Xem tất cả bài</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php get_footer(); ?>
