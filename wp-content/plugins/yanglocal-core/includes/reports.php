<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function yl_submission_rate_limit_key( $scope ) {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
    return 'yl_' . sanitize_key( $scope ) . '_' . substr( hash( 'sha256', wp_salt( 'nonce' ) . '|' . $ip ), 0, 24 );
}

function yl_report_form_shortcode( $atts = array() ) {
    $atts = shortcode_atts( array( 'place_id' => 0 ), $atts, 'yanglocal_report_place' );
    $place_id = absint( $atts['place_id'] );
    if ( ! $place_id && is_singular( 'yl_place' ) ) {
        $place_id = get_queried_object_id();
    }
    if ( ! $place_id || 'yl_place' !== get_post_type( $place_id ) ) {
        return '';
    }

    $status = isset( $_GET['yl_report'] ) ? sanitize_key( wp_unslash( $_GET['yl_report'] ) ) : '';
    ob_start();
    ?>
    <div class="yl-correction-box" id="thong-tin-chua-dung">
        <h3>Thông tin chưa đúng?</h3>
        <p>Cho YangLocal biết phần nào cần kiểm tra lại. Góp ý được xem trước khi có bất kỳ thay đổi nào.</p>
        <?php if ( 'success' === $status ) : ?><div class="yl-notice yl-notice--success">Đã nhận góp ý. Cảm ơn bạn.</div><?php elseif ( 'limited' === $status ) : ?><div class="yl-notice">Bạn vừa gửi góp ý. Hãy thử lại sau ít phút.</div><?php elseif ( 'error' === $status ) : ?><div class="yl-notice yl-notice--error">Chưa thể lưu góp ý. Vui lòng thử lại.</div><?php endif; ?>
        <form class="yl-correction-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="yl_submit_place_report">
            <input type="hidden" name="place_id" value="<?php echo esc_attr( $place_id ); ?>">
            <?php wp_nonce_field( 'yl_submit_place_report_' . $place_id, 'yl_report_nonce' ); ?>
            <label><span>Loại vấn đề</span><select name="reason" required><option value="">Chọn lý do</option><option value="address">Sai địa chỉ</option><option value="price">Sai giá</option><option value="hours">Sai giờ mở cửa</option><option value="closed">Địa điểm đã đóng</option><option value="amenity">Tiện ích chưa đúng</option><option value="other">Khác</option></select></label>
            <label><span>Chi tiết</span><textarea name="message" rows="4" maxlength="1200" required placeholder="Bạn thấy thông tin nào cần sửa?"></textarea></label>
            <label><span>Email (không bắt buộc)</span><input type="email" name="email" maxlength="190"></label>
            <div class="yl-honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"></div>
            <button class="yl-button yl-button--ghost" type="submit">Gửi góp ý</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'yanglocal_report_place', 'yl_report_form_shortcode' );

function yl_handle_place_report() {
    $place_id = isset( $_POST['place_id'] ) ? absint( $_POST['place_id'] ) : 0;
    if ( ! $place_id || 'yl_place' !== get_post_type( $place_id ) ) {
        wp_die( esc_html__( 'Địa điểm không hợp lệ.', 'yanglocal' ), 400 );
    }
    $nonce = isset( $_POST['yl_report_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['yl_report_nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'yl_submit_place_report_' . $place_id ) ) {
        wp_die( esc_html__( 'Phiên gửi góp ý không hợp lệ.', 'yanglocal' ), 403 );
    }
    if ( ! empty( $_POST['website'] ) ) {
        wp_safe_redirect( get_permalink( $place_id ) );
        exit;
    }

    $limit_key = yl_submission_rate_limit_key( 'report' );
    if ( get_transient( $limit_key ) ) {
        wp_safe_redirect( add_query_arg( 'yl_report', 'limited', get_permalink( $place_id ) ) . '#thong-tin-chua-dung' );
        exit;
    }

    $allowed_reasons = array( 'address', 'price', 'hours', 'closed', 'amenity', 'other' );
    $reason = isset( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : '';
    if ( ! in_array( $reason, $allowed_reasons, true ) ) {
        $reason = 'other';
    }
    $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
    $email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    if ( ! $message ) {
        wp_die( esc_html__( 'Hãy nhập nội dung góp ý.', 'yanglocal' ), 400 );
    }

    $report_id = wp_insert_post(
        array(
            'post_type'    => 'yl_report',
            'post_status'  => 'pending',
            'post_title'   => 'Góp ý · ' . get_the_title( $place_id ),
            'post_content' => $message,
        ),
        true
    );
    if ( is_wp_error( $report_id ) || ! $report_id ) {
        wp_safe_redirect( add_query_arg( 'yl_report', 'error', get_permalink( $place_id ) ) . '#thong-tin-chua-dung' );
        exit;
    }
    update_post_meta( $report_id, '_yl_report_place_id', $place_id );
    update_post_meta( $report_id, '_yl_report_reason', $reason );
    update_post_meta( $report_id, '_yl_report_email', $email );
    set_transient( $limit_key, 1, 10 * MINUTE_IN_SECONDS );
    wp_safe_redirect( add_query_arg( 'yl_report', 'success', get_permalink( $place_id ) ) . '#thong-tin-chua-dung' );
    exit;
}
add_action( 'admin_post_yl_submit_place_report', 'yl_handle_place_report' );
add_action( 'admin_post_nopriv_yl_submit_place_report', 'yl_handle_place_report' );

function yl_submit_place_shortcode() {
    $status = isset( $_GET['yl_submit'] ) ? sanitize_key( wp_unslash( $_GET['yl_submit'] ) ) : '';
    ob_start();
    ?>
    <section class="yl-submit-place">
        <header class="yl-page-header"><p class="yl-eyebrow">Cộng đồng YangLocal</p><h1>Đề xuất địa điểm</h1><p>YangLocal sẽ kiểm tra nguồn trước khi đưa địa điểm lên website.</p></header>
        <?php if ( 'success' === $status ) : ?><div class="yl-notice yl-notice--success">Đã nhận đề xuất. Cảm ơn bạn.</div><?php elseif ( 'limited' === $status ) : ?><div class="yl-notice">Bạn vừa gửi đề xuất. Hãy thử lại sau ít phút.</div><?php elseif ( 'error' === $status ) : ?><div class="yl-notice yl-notice--error">Chưa thể lưu đề xuất. Vui lòng thử lại.</div><?php endif; ?>
        <form class="yl-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="yl_submit_place_suggestion">
            <?php wp_nonce_field( 'yl_submit_place_suggestion', 'yl_submit_place_nonce' ); ?>
            <label>Tên địa điểm<input type="text" name="place_name" maxlength="160" required></label>
            <label>Danh mục<input type="text" name="category" maxlength="80" placeholder="Café, học tập, quán ăn…"></label>
            <label>Khu vực<input type="text" name="district" maxlength="80"></label>
            <label>Địa chỉ<input type="text" name="address" maxlength="220"></label>
            <label>Nguồn tham khảo<input type="url" name="source_url" maxlength="500" placeholder="https://..."></label>
            <label>Ghi chú<textarea name="note" rows="5" maxlength="1600"></textarea></label>
            <div class="yl-honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"></div>
            <button class="yl-button" type="submit">Gửi đề xuất</button>
        </form>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode( 'yanglocal_submit_place', 'yl_submit_place_shortcode' );

function yl_handle_place_suggestion() {
    $nonce = isset( $_POST['yl_submit_place_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['yl_submit_place_nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'yl_submit_place_suggestion' ) ) {
        wp_die( esc_html__( 'Phiên gửi đề xuất không hợp lệ.', 'yanglocal' ), 403 );
    }
    if ( ! empty( $_POST['website'] ) ) {
        wp_safe_redirect( home_url( '/de-xuat-dia-diem/' ) );
        exit;
    }
    $limit_key = yl_submission_rate_limit_key( 'submit' );
    if ( get_transient( $limit_key ) ) {
        wp_safe_redirect( add_query_arg( 'yl_submit', 'limited', home_url( '/de-xuat-dia-diem/' ) ) );
        exit;
    }

    $name = isset( $_POST['place_name'] ) ? sanitize_text_field( wp_unslash( $_POST['place_name'] ) ) : '';
    if ( ! $name ) {
        wp_die( esc_html__( 'Hãy nhập tên địa điểm.', 'yanglocal' ), 400 );
    }
    $post_id = wp_insert_post( array( 'post_type' => 'yl_submission', 'post_status' => 'pending', 'post_title' => $name ), true );
    if ( is_wp_error( $post_id ) || ! $post_id ) {
        wp_safe_redirect( add_query_arg( 'yl_submit', 'error', home_url( '/de-xuat-dia-diem/' ) ) );
        exit;
    }
    update_post_meta( $post_id, '_yl_submission_category', isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '' );
    update_post_meta( $post_id, '_yl_submission_district', isset( $_POST['district'] ) ? sanitize_text_field( wp_unslash( $_POST['district'] ) ) : '' );
    update_post_meta( $post_id, '_yl_submission_address', isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '' );
    update_post_meta( $post_id, '_yl_submission_source_url', isset( $_POST['source_url'] ) ? esc_url_raw( wp_unslash( $_POST['source_url'] ) ) : '' );
    update_post_meta( $post_id, '_yl_submission_note', isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '' );
    set_transient( $limit_key, 1, 10 * MINUTE_IN_SECONDS );
    wp_safe_redirect( add_query_arg( 'yl_submit', 'success', home_url( '/de-xuat-dia-diem/' ) ) );
    exit;
}
add_action( 'admin_post_yl_submit_place_suggestion', 'yl_handle_place_suggestion' );
add_action( 'admin_post_nopriv_yl_submit_place_suggestion', 'yl_handle_place_suggestion' );
