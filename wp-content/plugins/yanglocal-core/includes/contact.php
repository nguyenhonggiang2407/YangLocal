<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function yl_contact_shortcode() {
    $notice = '';
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['yl_contact_action'] ) ) {
        $nonce = isset( $_POST['yl_contact_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['yl_contact_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'yl_contact_submit' ) ) {
            $notice = '<div class="yl-notice yl-notice--error">Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.</div>';
        } elseif ( ! empty( $_POST['yl_website'] ) ) {
            $notice = '<div class="yl-notice yl-notice--error">Không thể gửi biểu mẫu này.</div>';
        } else {
            $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
            $rate_key = 'yl_contact_' . md5( $ip ? $ip : 'unknown' );
            if ( get_transient( $rate_key ) ) {
                $notice = '<div class="yl-notice yl-notice--error">Bạn vừa gửi tin nhắn. Vui lòng thử lại sau ít phút.</div>';
                $name = $email = $subject = $message = '';
            } else {
            $name = isset( $_POST['yl_name'] ) ? sanitize_text_field( wp_unslash( $_POST['yl_name'] ) ) : '';
            $email = isset( $_POST['yl_email'] ) ? sanitize_email( wp_unslash( $_POST['yl_email'] ) ) : '';
            $subject = isset( $_POST['yl_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['yl_subject'] ) ) : '';
            $message = isset( $_POST['yl_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['yl_message'] ) ) : '';

            if ( ! $name || ! is_email( $email ) || ! $subject || ! $message ) {
                $notice = '<div class="yl-notice yl-notice--error">Vui lòng điền đủ thông tin và kiểm tra lại email.</div>';
            } else {
                $post_id = wp_insert_post(
                    array(
                        'post_type'    => 'yl_message',
                        'post_status'  => 'private',
                        'post_title'   => $subject . ' — ' . $name,
                        'post_content' => $message,
                    )
                );
                if ( is_wp_error( $post_id ) ) {
                    $notice = '<div class="yl-notice yl-notice--error">Chưa thể lưu tin nhắn. Vui lòng thử lại.</div>';
                } else {
                    update_post_meta( $post_id, '_yl_contact_name', $name );
                    update_post_meta( $post_id, '_yl_contact_email', $email );
                    set_transient( $rate_key, 1, 10 * MINUTE_IN_SECONDS );
                    $notice = '<div class="yl-notice yl-notice--success">Đã nhận tin nhắn. Cảm ơn bạn đã gửi góp ý cho YangLocal.</div>';
                }
            }
            }
        }
    }

    ob_start();
    ?>
    <section class="yl-contact-page">
        <header class="yl-page-header"><p class="yl-eyebrow">Liên hệ</p><h1>Có địa điểm hay muốn góp ý?</h1><p>Gửi thông tin tại đây để YangLocal có thể xem và phản hồi khi cần.</p></header>
        <?php echo wp_kses_post( $notice ); ?>
        <form class="yl-contact-form" method="post">
            <?php wp_nonce_field( 'yl_contact_submit', 'yl_contact_nonce' ); ?>
            <input type="hidden" name="yl_contact_action" value="1">
            <div class="yl-hp-field" aria-hidden="true"><input type="text" name="yl_website" value="" tabindex="-1" autocomplete="off" aria-hidden="true"></div>
            <label>Họ tên<input type="text" name="yl_name" required></label>
            <label>Email<input type="email" name="yl_email" required></label>
            <label>Chủ đề<input type="text" name="yl_subject" required></label>
            <label>Nội dung<textarea name="yl_message" rows="7" required></textarea></label>
            <button class="yl-button" type="submit">Gửi tin nhắn</button>
        </form>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode( 'yanglocal_contact', 'yl_contact_shortcode' );
