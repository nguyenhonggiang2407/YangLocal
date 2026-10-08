<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Branded front-end login while keeping WordPress authentication intact.
 */
function yl_login_shortcode() {
    $requested_redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/' );
    $redirect = wp_validate_redirect( $requested_redirect, home_url( '/' ) );
    $visual = trailingslashit( get_template_directory_uri() ) . 'assets/images/fallback-city.svg';
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        ob_start(); ?>
        <section class="yl-login-shell yl-login-shell--split"><div class="yl-login-visual" aria-hidden="true"><img src="<?php echo esc_url( $visual ); ?>" alt="" width="806" height="914" fetchpriority="high" decoding="async"></div><div class="yl-login-card">
            <p class="yl-eyebrow">Tài khoản YangLocal</p><h1>Chào <?php echo esc_html( $user->display_name ? $user->display_name : $user->user_login ); ?>.</h1>
            <p>Danh sách đã lưu của bạn vẫn ở đây. Chọn một nơi để tiếp tục khám phá Hà Nội.</p>
            <div class="yl-login-actions"><a class="yl-button" href="<?php echo esc_url( home_url( '/da-luu/' ) ); ?>">Địa điểm đã lưu</a><a class="yl-button yl-button--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Về trang chủ</a><a class="yl-text-link" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">Đăng xuất</a></div>
        </div></section>
        <?php return ob_get_clean();
    }
    $errors = array();
    if ( isset( $_GET['login'] ) && 'failed' === sanitize_key( wp_unslash( $_GET['login'] ) ) ) { $errors[] = 'Email/tên đăng nhập hoặc mật khẩu chưa đúng.'; }
    ob_start(); ?>
    <section class="yl-login-shell yl-login-shell--split"><div class="yl-login-visual" aria-hidden="true"><img src="<?php echo esc_url( $visual ); ?>" alt="" width="806" height="914" fetchpriority="high" decoding="async"></div><div class="yl-login-card">
        <p class="yl-eyebrow">Tài khoản YangLocal</p><h1>Chào bạn quay lại</h1><p>Đăng nhập để lưu những địa điểm bạn muốn ghé và quản lý danh sách của mình.</p>
        <?php foreach ( $errors as $error ) : ?><div class="yl-form-notice yl-form-notice--error"><?php echo esc_html( $error ); ?></div><?php endforeach; ?>
        <?php wp_login_form( array( 'echo'=>true,'redirect'=>$redirect,'form_id'=>'yl-login-form','label_username'=>'Email hoặc tên đăng nhập','label_password'=>'Mật khẩu','label_remember'=>'Ghi nhớ đăng nhập','label_log_in'=>'Đăng nhập','remember'=>true ) ); ?>
        <p class="yl-login-help"><a href="<?php echo esc_url( wp_lostpassword_url( home_url( '/dang-nhap/' ) ) ); ?>">Quên mật khẩu?</a></p>
    </div></section>
    <?php return ob_get_clean();
}
add_shortcode( 'yanglocal_login', 'yl_login_shortcode' );

function yl_login_failed_redirect( $username ) {
    if ( wp_doing_ajax() ) { return; }
    $ref = wp_get_referer();
    if ( $ref && false !== strpos( $ref, '/dang-nhap/' ) ) {
        wp_safe_redirect( add_query_arg( 'login', 'failed', home_url( '/dang-nhap/' ) ) );
        exit;
    }
}
add_action( 'wp_login_failed', 'yl_login_failed_redirect' );
