<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function yl_add_meta_boxes() {
    add_meta_box( 'yl_place_details', 'Thông tin địa điểm', 'yl_render_place_meta_box', 'yl_place', 'normal', 'high' );
    add_meta_box( 'yl_event_details', 'Thông tin sự kiện', 'yl_render_event_meta_box', 'yl_event', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'yl_add_meta_boxes' );

function yl_meta_input( $post_id, $key, $label, $type = 'text', $placeholder = '' ) {
    $value = get_post_meta( $post_id, '_yl_' . $key, true );
    printf(
        '<p><label for="yl_%1$s"><strong>%2$s</strong></label><br><input class="widefat" type="%3$s" id="yl_%1$s" name="yl_%1$s" value="%4$s" placeholder="%5$s"></p>',
        esc_attr( $key ),
        esc_html( $label ),
        esc_attr( $type ),
        esc_attr( $value ),
        esc_attr( $placeholder )
    );
}

function yl_meta_textarea( $post_id, $key, $label, $placeholder = '' ) {
    $value = get_post_meta( $post_id, '_yl_' . $key, true );
    printf(
        '<p><label for="yl_%1$s"><strong>%2$s</strong></label><br><textarea class="widefat" rows="4" id="yl_%1$s" name="yl_%1$s" placeholder="%4$s">%3$s</textarea></p>',
        esc_attr( $key ),
        esc_html( $label ),
        esc_textarea( $value ),
        esc_attr( $placeholder )
    );
}

function yl_meta_checkbox( $post_id, $key, $label ) {
    $value = get_post_meta( $post_id, '_yl_' . $key, true );
    printf(
        '<label style="display:inline-block;margin:0 18px 10px 0;"><input type="checkbox" name="yl_%1$s" value="1" %2$s> %3$s</label>',
        esc_attr( $key ),
        checked( $value, '1', false ),
        esc_html( $label )
    );
}


function yl_meta_select( $post_id, $key, $label, $options ) {
    $value = get_post_meta( $post_id, '_yl_' . $key, true );
    echo '<p><label for="yl_' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label><br><select class="widefat" id="yl_' . esc_attr( $key ) . '" name="yl_' . esc_attr( $key ) . '">';
    foreach ( $options as $option_value => $option_label ) {
        echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
    }
    echo '</select></p>';
}

function yl_render_place_meta_box( $post ) {
    wp_nonce_field( 'yl_save_place_meta', 'yl_place_meta_nonce' );

    echo '<h3>Thông tin cơ bản</h3>';
    yl_meta_input( $post->ID, 'address', 'Địa chỉ' );
    yl_meta_input( $post->ID, 'price_from', 'Giá tham khảo từ (VNĐ)', 'number', '45000' );
    yl_meta_input( $post->ID, 'phone', 'Điện thoại (không bắt buộc)', 'text' );
    yl_meta_input( $post->ID, 'website', 'Website chính thức', 'url' );
    yl_meta_input( $post->ID, 'source_url', 'Nguồn kiểm tra thông tin', 'url', 'https://...' );
    yl_meta_input( $post->ID, 'source_checked', 'Ngày kiểm tra nguồn', 'date' );
    yl_meta_input( $post->ID, 'opening_note', 'Ghi chú giờ mở cửa', 'text', 'Chỉ nhập khi có nguồn đáng tin' );
    yl_meta_textarea( $post->ID, 'student_notes', 'Mẹo sinh viên / ghi chú biên tập', 'Chỉ nhập nhận xét có cơ sở; không bịa khung giờ đông/vắng.' );

    echo '<h3>Lịch sử / bảo tàng / biên tập</h3>';
    yl_meta_input( $post->ID, 'topic', 'Chủ đề hiển thị', 'text', 'Ví dụ: Lịch sử · kiến trúc' );
    yl_meta_input( $post->ID, 'visit_duration', 'Thời lượng gợi ý', 'text', 'Ví dụ: Phù hợp dành 1–2 giờ' );
    yl_meta_textarea( $post->ID, 'ticket_note', 'Thông tin vé', 'Chỉ nhập khi có nguồn chính thức; nếu chưa rõ hãy để trống.' );
    yl_meta_textarea( $post->ID, 'visit_note', 'Điều nên biết trước khi đi', 'Thông tin thực tế, ngắn gọn, không viết quảng cáo.' );
    yl_meta_textarea( $post->ID, 'rules_note', 'Quy định / lưu ý', 'Dùng cho không gian trang nghiêm, tôn giáo, an ninh hoặc an toàn.' );
    yl_meta_textarea( $post->ID, 'search_aliases', 'Tên gọi khác để tìm kiếm', 'Mỗi dòng một alias, ví dụ: Lăng Bác' );
    yl_meta_input( $post->ID, 'itinerary_cluster', 'Nhóm lịch trình nội bộ', 'text', 'Ví dụ: ba-dinh' );
    yl_meta_input( $post->ID, 'related_slugs', 'Địa điểm liên quan (slug, phân cách dấu phẩy)', 'text', 'slug-a,slug-b' );
    yl_meta_select( $post->ID, 'source_type', 'Loại nguồn chính', array( ''=>'Chưa phân loại', 'official'=>'Cơ quan/đơn vị chính thức', 'government'=>'Cơ quan nhà nước', 'tourism_authority'=>'Cơ quan du lịch/văn hóa', 'museum_official'=>'Bảo tàng chính thức', 'other'=>'Nguồn khác' ) );

    echo '<h3>Vị trí & bản đồ</h3>';
    yl_meta_input( $post->ID, 'latitude', 'Vĩ độ', 'text', '21.0285' );
    yl_meta_input( $post->ID, 'longitude', 'Kinh độ', 'text', '105.8542' );
    yl_meta_input( $post->ID, 'coordinate_source_url', 'Nguồn tọa độ', 'url', 'https://...' );
    echo '<p>';
    yl_meta_checkbox( $post->ID, 'coord_verified', 'Tọa độ đã đối chiếu thủ công' );
    echo '</p>';
    yl_meta_input( $post->ID, 'google_maps_url', 'Google Maps URL (để trống sẽ tự tạo từ tên + địa chỉ)', 'url', 'https://www.google.com/maps/...' );
    yl_meta_input( $post->ID, 'google_place_id', 'Google Place ID (nếu đã xác minh)', 'text', 'ChIJ…' );
    yl_meta_input( $post->ID, 'tiktok_url', 'TikTok trực tiếp của địa điểm (chỉ nhập khi đã đối chiếu)', 'url', 'https://www.tiktok.com/@.../video/...' );
    yl_meta_input( $post->ID, 'tiktok_search_url', 'TikTok search URL (chỉ dùng nội bộ để tìm nội dung)', 'url', 'https://www.tiktok.com/search?q=...' );

    echo '<h3>Hình ảnh</h3>';
    yl_meta_input( $post->ID, 'image_caption', 'Ghi chú ảnh', 'text', 'Ví dụ: Ảnh tham khảo sát không gian café hoặc ảnh đúng địa điểm.' );
    yl_meta_input( $post->ID, 'image_source_url', 'Nguồn ảnh', 'url', 'https://www.pexels.com/...' );
    yl_meta_input( $post->ID, 'image_verification_url', 'Nguồn đối chiếu địa điểm/ảnh', 'url', 'https://...' );
    yl_meta_select( $post->ID, 'image_match_level', 'Mức độ khớp ảnh', array( 'exact_venue'=>'Đúng địa điểm', 'exact_dish'=>'Đúng món / hoạt động', 'close_match'=>'Rất sát nội dung', 'category'=>'Chỉ đúng danh mục', 'fallback'=>'Fallback – cần kiểm tra' ) );
    yl_meta_select( $post->ID, 'image_reuse_status', 'Quyền tái sử dụng ảnh', array( ''=>'Chưa xác nhận', 'cc'=>'Creative Commons', 'public_domain'=>'Public domain', 'permission'=>'Đã có quyền/cho phép', 'licensed'=>'Nguồn có license phù hợp' ) );
    yl_meta_select( $post->ID, 'image_review_status', 'Trạng thái QA ảnh', array( 'verified_exact_venue'=>'PASS · Ảnh đúng địa điểm', 'needs_real_place_photo'=>'Cần ảnh thật địa điểm', 'pass_exact'=>'PASS · Đúng địa điểm + provenance (legacy)', 'pass_dish'=>'PASS · Đúng món/hoạt động (legacy)', 'pass_disclosed_illustration'=>'PASS · Minh họa đã công khai (legacy)', 'review_provenance'=>'Cần kiểm tra provenance' ) );
    yl_meta_input( $post->ID, 'image_audited', 'Ngày rà ảnh gần nhất', 'date' );
    yl_meta_input( $post->ID, 'image_search_query', 'Truy vấn dùng để kiểm tra ảnh', 'text', 'Ví dụ: Thư viện Đại học Luật Hà Nội' );
    yl_meta_textarea( $post->ID, 'external_gallery_urls', 'Gallery URL ngoài (mỗi dòng một ảnh)', 'Mỗi dòng 1 ảnh. v7.23 chỉ hiển thị/localize trực tiếp gallery exact có quyền tái sử dụng rõ; ưu tiên Wikimedia Commons hoặc local Media Library.' );
    yl_meta_textarea( $post->ID, 'media_discovery_note', 'Ghi chú ảnh/video thực tế', 'TikTok/Google Maps chỉ để tham khảo UGC; không dùng làm nguồn xác minh duy nhất.' );
    $gallery_ids = get_post_meta( $post->ID, '_yl_gallery_ids', true );
    echo '<p><strong>Gallery ảnh</strong></p>';
    echo '<input type="hidden" id="yl_gallery_ids" name="yl_gallery_ids" value="' . esc_attr( $gallery_ids ) . '">';
    echo '<div data-yl-gallery-preview style="margin-bottom:8px;">';
    if ( $gallery_ids ) {
        foreach ( array_filter( array_map( 'absint', explode( ',', $gallery_ids ) ) ) as $attachment_id ) {
            echo wp_get_attachment_image( $attachment_id, 'thumbnail', false, array( 'style' => 'width:86px;height:64px;object-fit:cover;margin:0 6px 6px 0;border-radius:4px;' ) );
        }
    }
    echo '</div><p><button type="button" class="button" data-yl-gallery-pick>Chọn ảnh thư viện</button> <button type="button" class="button-link-delete" data-yl-gallery-clear>Xóa gallery</button></p>';

    echo '<h3>YangLocal đề xuất</h3>';
    echo '<p>';
    yl_meta_checkbox( $post->ID, 'recommended', 'Đưa địa điểm này vào mục “YangLocal đề xuất”' );
    echo '</p>';
    yl_meta_input( $post->ID, 'recommend_score', 'Độ ưu tiên nội bộ (0–100)', 'number', '90' );
    yl_meta_input( $post->ID, 'recommend_badge', 'Nhãn gợi ý', 'text', 'Ví dụ: Nên đi ít nhất 1 lần' );
    yl_meta_textarea( $post->ID, 'recommend_reason', 'Vì sao YangLocal đề xuất', 'Viết ngắn, cụ thể và hữu ích cho sinh viên.' );
    yl_meta_input( $post->ID, 'recommend_best_time', 'Thời điểm hợp nhất', 'text', 'Ví dụ: Chiều muộn hoặc tối cuối tuần' );
    yl_meta_input( $post->ID, 'recommend_duration', 'Thời lượng gợi ý', 'text', 'Ví dụ: 1–2 giờ' );

    echo '<h3>Tiện ích</h3><p>';
    yl_meta_checkbox( $post->ID, 'student_friendly', 'Hợp túi tiền sinh viên' );
    yl_meta_checkbox( $post->ID, 'wifi', 'Có Wi‑Fi' );
    yl_meta_checkbox( $post->ID, 'power_outlet', 'Có ổ điện' );
    yl_meta_checkbox( $post->ID, 'quiet', 'Yên tĩnh' );
    yl_meta_checkbox( $post->ID, 'group_study', 'Làm việc nhóm' );
    yl_meta_checkbox( $post->ID, 'air_conditioning', 'Có điều hòa' );
    yl_meta_checkbox( $post->ID, 'parking', 'Có chỗ gửi xe' );
    echo '</p>';

    echo '<h3>Phù hợp với</h3><p>';
    yl_meta_checkbox( $post->ID, 'solo_study', 'Học một mình' );
    yl_meta_checkbox( $post->ID, 'long_stay', 'Ngồi lâu' );
    yl_meta_checkbox( $post->ID, 'quick_meal', 'Ăn nhanh' );
    yl_meta_checkbox( $post->ID, 'pair_visit', 'Đi 2 người' );
    yl_meta_checkbox( $post->ID, 'late_open', 'Mở muộn' );
    yl_meta_checkbox( $post->ID, 'deadline', 'Chạy deadline' );
    yl_meta_checkbox( $post->ID, 'weekend', 'Đi cuối tuần' );
    yl_meta_checkbox( $post->ID, 'group_4_6', 'Nhóm 4–6 người' );
    echo '</p>';
}

function yl_render_event_meta_box( $post ) {
    wp_nonce_field( 'yl_save_event_meta', 'yl_event_meta_nonce' );
    yl_meta_input( $post->ID, 'event_date', 'Ngày diễn ra', 'date' );
    yl_meta_input( $post->ID, 'event_start_time', 'Giờ bắt đầu', 'time' );
    yl_meta_input( $post->ID, 'event_end_time', 'Giờ kết thúc', 'time' );
    yl_meta_input( $post->ID, 'event_venue', 'Địa điểm tổ chức' );
    yl_meta_input( $post->ID, 'event_address', 'Địa chỉ' );
    yl_meta_input( $post->ID, 'event_price', 'Giá/vé', 'text', 'Miễn phí / 50.000đ' );
    yl_meta_input( $post->ID, 'event_url', 'Link đăng ký', 'url' );
}

function yl_save_place_meta( $post_id ) {
    if ( ! isset( $_POST['yl_place_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['yl_place_meta_nonce'] ) ), 'yl_save_place_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $text_fields = array( 'address', 'phone', 'opening_note', 'source_checked', 'image_caption', 'image_search_query', 'image_audited', 'recommend_badge', 'recommend_best_time', 'recommend_duration', 'topic', 'visit_duration', 'itinerary_cluster', 'related_slugs' );
    foreach ( $text_fields as $field ) {
        $value = isset( $_POST[ 'yl_' . $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'yl_' . $field ] ) ) : '';
        update_post_meta( $post_id, '_yl_' . $field, $value );
    }

    $student_notes = isset( $_POST['yl_student_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['yl_student_notes'] ) ) : '';
    update_post_meta( $post_id, '_yl_student_notes', $student_notes );
    foreach ( array( 'ticket_note', 'visit_note', 'rules_note', 'search_aliases' ) as $textarea_field ) {
        $textarea_value = isset( $_POST[ 'yl_' . $textarea_field ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ 'yl_' . $textarea_field ] ) ) : '';
        update_post_meta( $post_id, '_yl_' . $textarea_field, $textarea_value );
    }
    $source_type = isset( $_POST['yl_source_type'] ) ? sanitize_key( wp_unslash( $_POST['yl_source_type'] ) ) : '';
    $allowed_source_types = array( '', 'official', 'government', 'tourism_authority', 'museum_official', 'other' );
    update_post_meta( $post_id, '_yl_source_type', in_array( $source_type, $allowed_source_types, true ) ? $source_type : '' );
    $recommend_reason = isset( $_POST['yl_recommend_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['yl_recommend_reason'] ) ) : '';
    update_post_meta( $post_id, '_yl_recommend_reason', $recommend_reason );
    $recommend_score = isset( $_POST['yl_recommend_score'] ) ? min( 100, absint( $_POST['yl_recommend_score'] ) ) : 0;
    update_post_meta( $post_id, '_yl_recommend_score', $recommend_score );
    $allowed_match_levels = array( 'exact_venue', 'exact_dish', 'close_match', 'category', 'fallback' );
    $image_match_level = isset( $_POST['yl_image_match_level'] ) ? sanitize_key( wp_unslash( $_POST['yl_image_match_level'] ) ) : 'fallback';
    if ( ! in_array( $image_match_level, $allowed_match_levels, true ) ) { $image_match_level = 'fallback'; }
    update_post_meta( $post_id, '_yl_image_match_level', $image_match_level );
    $reuse_status = isset( $_POST['yl_image_reuse_status'] ) ? sanitize_key( wp_unslash( $_POST['yl_image_reuse_status'] ) ) : '';
    $allowed_reuse_statuses = array( '', 'cc', 'public_domain', 'permission', 'licensed' );
    update_post_meta( $post_id, '_yl_image_reuse_status', in_array( $reuse_status, $allowed_reuse_statuses, true ) ? $reuse_status : '' );
    $image_review_status = isset( $_POST['yl_image_review_status'] ) ? sanitize_key( wp_unslash( $_POST['yl_image_review_status'] ) ) : '';
    $allowed_review_statuses = array( 'verified_exact_venue', 'needs_real_place_photo', 'pass_exact', 'pass_dish', 'pass_disclosed_illustration', 'review_provenance' );
    update_post_meta( $post_id, '_yl_image_review_status', in_array( $image_review_status, $allowed_review_statuses, true ) ? $image_review_status : '' );

    $media_note = isset( $_POST['yl_media_discovery_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['yl_media_discovery_note'] ) ) : '';
    update_post_meta( $post_id, '_yl_media_discovery_note', $media_note );

    $price = isset( $_POST['yl_price_from'] ) ? absint( $_POST['yl_price_from'] ) : 0;
    update_post_meta( $post_id, '_yl_price_from', $price );

    if ( isset( $_POST['yl_external_gallery_urls'] ) ) {
        $gallery_lines = array();
        foreach ( preg_split( '/[\r\n]+/', wp_unslash( $_POST['yl_external_gallery_urls'] ) ) as $gallery_url ) {
            $gallery_url = esc_url_raw( trim( $gallery_url ) );
            if ( $gallery_url ) { $gallery_lines[] = $gallery_url; }
        }
        update_post_meta( $post_id, '_yl_external_gallery_urls', implode( "\n", array_values( array_unique( $gallery_lines ) ) ) );
    }

    $google_place_id = isset( $_POST['yl_google_place_id'] ) ? sanitize_text_field( wp_unslash( $_POST['yl_google_place_id'] ) ) : '';
    if ( $google_place_id && ! preg_match( '/^[A-Za-z0-9_-]{10,160}$/', $google_place_id ) ) { $google_place_id = ''; }
    update_post_meta( $post_id, '_yl_google_place_id', $google_place_id );

    foreach ( array( 'website', 'source_url', 'image_source_url', 'image_verification_url', 'coordinate_source_url', 'google_maps_url', 'tiktok_url', 'tiktok_search_url' ) as $url_field ) {
        $url = isset( $_POST[ 'yl_' . $url_field ] ) ? esc_url_raw( wp_unslash( $_POST[ 'yl_' . $url_field ] ) ) : '';
        update_post_meta( $post_id, '_yl_' . $url_field, $url );
    }

    foreach ( array( 'latitude', 'longitude' ) as $field ) {
        $raw = isset( $_POST[ 'yl_' . $field ] ) ? str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST[ 'yl_' . $field ] ) ) ) : '';
        $value = is_numeric( $raw ) ? (string) (float) $raw : '';
        update_post_meta( $post_id, '_yl_' . $field, $value );
    }

    $gallery_raw = isset( $_POST['yl_gallery_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['yl_gallery_ids'] ) ) : '';
    $gallery_ids = array_filter( array_map( 'absint', explode( ',', $gallery_raw ) ) );
    update_post_meta( $post_id, '_yl_gallery_ids', implode( ',', $gallery_ids ) );

    $checkboxes = array( 'coord_verified', 'student_friendly', 'wifi', 'power_outlet', 'quiet', 'group_study', 'air_conditioning', 'parking', 'solo_study', 'long_stay', 'quick_meal', 'pair_visit', 'late_open', 'deadline', 'weekend', 'group_4_6', 'recommended' );
    foreach ( $checkboxes as $field ) {
        update_post_meta( $post_id, '_yl_' . $field, isset( $_POST[ 'yl_' . $field ] ) ? '1' : '0' );
    }
}
add_action( 'save_post_yl_place', 'yl_save_place_meta' );

function yl_save_event_meta( $post_id ) {
    if ( ! isset( $_POST['yl_event_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['yl_event_meta_nonce'] ) ), 'yl_save_event_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $text = array( 'event_date', 'event_start_time', 'event_end_time', 'event_venue', 'event_address', 'event_price' );
    foreach ( $text as $field ) {
        $value = isset( $_POST[ 'yl_' . $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'yl_' . $field ] ) ) : '';
        update_post_meta( $post_id, '_yl_' . $field, $value );
    }
    $url = isset( $_POST['yl_event_url'] ) ? esc_url_raw( wp_unslash( $_POST['yl_event_url'] ) ) : '';
    update_post_meta( $post_id, '_yl_event_url', $url );
}
add_action( 'save_post_yl_event', 'yl_save_event_meta' );


function yl_admin_place_assets( $hook ) {
    $screen = get_current_screen();
    if ( ! $screen || 'yl_place' !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script( 'yl-admin-gallery', YL_CORE_URL . 'assets/admin.js', array( 'jquery' ), YL_CORE_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'yl_admin_place_assets' );


function yl_place_image_admin_columns( $columns ) {
    $columns['yl_image_match'] = 'Ảnh';
    return $columns;
}
add_filter( 'manage_yl_place_posts_columns', 'yl_place_image_admin_columns' );

function yl_place_image_admin_column_content( $column, $post_id ) {
    if ( 'yl_image_match' !== $column ) { return; }
    $level = get_post_meta( $post_id, '_yl_image_match_level', true );
    $labels = array( 'exact_venue'=>'Đúng địa điểm', 'exact_dish'=>'Đúng món', 'close_match'=>'Sát nội dung', 'category'=>'Chỉ danh mục', 'fallback'=>'Cần kiểm tra' );
    echo esc_html( isset( $labels[ $level ] ) ? $labels[ $level ] : 'Cần kiểm tra' );
}
add_action( 'manage_yl_place_posts_custom_column', 'yl_place_image_admin_column_content', 10, 2 );

function yl_place_image_admin_filter() {
    global $typenow;
    if ( 'yl_place' !== $typenow ) { return; }
    $current = isset( $_GET['yl_image_quality'] ) ? sanitize_key( wp_unslash( $_GET['yl_image_quality'] ) ) : '';
    echo '<select name="yl_image_quality"><option value="">Tất cả mức ảnh</option><option value="needs_review" ' . selected( $current, 'needs_review', false ) . '>Ảnh cần kiểm tra</option><option value="exact_venue" ' . selected( $current, 'exact_venue', false ) . '>Đúng địa điểm</option><option value="exact_dish" ' . selected( $current, 'exact_dish', false ) . '>Đúng món</option><option value="close_match" ' . selected( $current, 'close_match', false ) . '>Sát nội dung</option></select>';
}
add_action( 'restrict_manage_posts', 'yl_place_image_admin_filter' );

function yl_place_image_admin_filter_query( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() || 'yl_place' !== $query->get( 'post_type' ) ) { return; }
    $filter = isset( $_GET['yl_image_quality'] ) ? sanitize_key( wp_unslash( $_GET['yl_image_quality'] ) ) : '';
    if ( 'needs_review' === $filter ) {
        $query->set( 'meta_query', array( 'relation'=>'OR', array( 'key'=>'_yl_image_match_level', 'value'=>array('category','fallback'), 'compare'=>'IN' ), array( 'key'=>'_yl_image_match_level', 'compare'=>'NOT EXISTS' ) ) );
    } elseif ( in_array( $filter, array('exact_venue','exact_dish','close_match'), true ) ) {
        $query->set( 'meta_key', '_yl_image_match_level' );
        $query->set( 'meta_value', $filter );
    }
}
add_action( 'pre_get_posts', 'yl_place_image_admin_filter_query' );
