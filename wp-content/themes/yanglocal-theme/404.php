<?php get_header(); ?>
<section class="yl-404"><div class="yl-container"><p class="yl-eyebrow">404 · Lạc đường một chút</p><h1>Chỗ này YangLocal chưa tìm thấy 😅</h1><p>Quay lại khám phá một địa điểm khác nhé.</p><div class="yl-404__actions"><a class="yl-button" href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a><a class="yl-button yl-button--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'yl_place' ) ); ?>">Khám phá</a></div></div></section>
<?php get_footer(); ?>
