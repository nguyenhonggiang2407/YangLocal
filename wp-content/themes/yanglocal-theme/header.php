<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="yl-skip-link" href="#yl-main">Bỏ qua đến nội dung</a>
<div class="yl-site">
<header class="yl-header" data-yl-header>
    <div class="yl-container yl-header__inner">
        <a class="yl-brand yl-brand--image" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="YangLocal — Trang chủ">
            <img src="<?php echo esc_url( trailingslashit( get_template_directory_uri() ) . 'assets/images/brand/yanglocal-mark-v7241-r12.svg' ); ?>" alt="" width="38" height="38" aria-hidden="true"><strong>YangLocal</strong>
        </a>
        <nav id="yl-primary-nav" class="yl-nav" data-yl-nav aria-label="Điều hướng chính">
            <?php yl_theme_fallback_menu(); ?>
        </nav>
        <div class="yl-header__actions">
            <button class="yl-icon-button" type="button" data-yl-search-toggle aria-expanded="false" aria-controls="yl-header-search" aria-label="Mở tìm kiếm"><?php echo yl_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
            <a class="yl-icon-link" href="<?php echo esc_url( home_url( '/da-luu/' ) ); ?>" aria-label="Địa điểm đã lưu"><?php echo yl_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
            <a class="yl-account-link" href="<?php echo esc_url( home_url( '/dang-nhap/' ) ); ?>"><?php echo is_user_logged_in() ? 'Tài khoản' : 'Đăng nhập'; ?></a>
            <button class="yl-icon-button yl-menu-toggle" type="button" data-yl-menu-toggle aria-expanded="false" aria-controls="yl-primary-nav" aria-label="Mở menu"><?php echo yl_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
        </div>
    </div>
    <div class="yl-container">
        <form id="yl-header-search" class="yl-header-search" role="search" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'yl_place' ) ); ?>" data-yl-search-panel hidden>
            <label class="screen-reader-text" for="yl-header-search-input">Tìm địa điểm trên YangLocal</label>
            <input id="yl-header-search-input" type="search" name="q" placeholder="Tìm quán ăn, café, trường, bảo tàng..." autocomplete="search" required>
            <button class="yl-button" type="submit">Tìm</button>
        </form>
    </div>
</header>
<main id="yl-main" class="yl-main">
