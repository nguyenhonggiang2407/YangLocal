<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function yl_register_importer_page() {
    add_management_page( 'Thiết lập nội dung YangLocal', 'YangLocal Setup', 'manage_options', 'yanglocal-demo-import', 'yl_render_importer_page' );
}
add_action( 'admin_menu', 'yl_register_importer_page' );

function yl_render_importer_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'yanglocal' ) );
    }

    $notice_type = '';
    $message     = '';
    $seed_items  = yl_get_demo_items();
    $seed_places = array_values( array_filter( $seed_items, static function ( $item ) { return isset( $item['post_type'] ) && 'yl_place' === $item['post_type']; } ) );
    $yl_seed_place_count = count( $seed_places );
    $yl_seed_hubt_count  = 0;
    foreach ( $seed_places as $seed_place ) {
        foreach ( isset( $seed_place['terms'] ) && is_array( $seed_place['terms'] ) ? $seed_place['terms'] : array() as $seed_term ) {
            if ( isset( $seed_term['taxonomy'], $seed_term['slug'] ) && 'near_school' === $seed_term['taxonomy'] && 'hubt' === $seed_term['slug'] ) { $yl_seed_hubt_count++; break; }
        }
    }
    $yl_priority_photo_ids = function_exists( 'yl_place_photo_priority_ids' ) ? yl_place_photo_priority_ids() : array();

    if ( isset( $_POST['yl_import_demo'] ) ) {
        check_admin_referer( 'yl_import_demo_action', 'yl_import_demo_nonce' );
        $result      = yl_import_demo_data();
        $notice_type = 'success';
        $message     = sprintf( 'Hoàn tất: %1$d nội dung mới, %2$d nội dung đã tồn tại được giữ nguyên, %3$d nội dung đã lưu trữ (bị vô hiệu trong bản công khai).', (int) $result['created'], (int) $result['skipped'], (int) $result['archived'] );
    }

    if ( isset( $_POST['yl_refresh_demo_content'] ) ) {
        check_admin_referer( 'yl_refresh_demo_content_action', 'yl_refresh_demo_content_nonce' );
        $result      = yl_refresh_demo_content();
        $notice_type = 'success';
        $message     = sprintf( 'Đã đồng bộ %1$d nội dung, tạo mới %2$d nội dung còn thiếu, %3$d lỗi/bỏ qua, %4$d nội dung đã lưu trữ (bị vô hiệu trong bản công khai).', (int) $result['updated'], (int) $result['created'], (int) $result['skipped'], (int) $result['archived'] );
    }
    ?>
    <div class="wrap">
        <h1>YangLocal · Public Demo v7.24.1</h1>
        <p>Bản mã nguồn công khai chỉ kèm 3 địa điểm hư cấu. Không sao chép dữ liệu, tài khoản, đánh giá hoặc ảnh từ website thật. Dùng trên một bản WordPress thử nghiệm riêng.</p>
        <p><strong>Nguyên tắc:</strong> field chưa xác minh được để trống thay vì bịa. Đồng bộ lại dữ liệu không xóa review hoặc favorite của người dùng.</p>
        <?php if ( $message ) : ?>
            <div class="notice notice-<?php echo esc_attr( $notice_type ); ?>"><p><?php echo esc_html( $message ); ?></p></div>
        <?php endif; ?>

        <div style="max-width:780px;display:grid;gap:18px;">
            <div style="padding:18px 20px;background:#fff;border:1px solid #dcdcde;border-radius:8px;">
                <h2 style="margin-top:0;">1) Nhập dữ liệu hư cấu</h2>
                <p>Tạo <?php echo esc_html( $yl_seed_place_count ); ?> địa điểm hư cấu (<?php echo esc_html( $yl_seed_hubt_count ); ?> địa điểm gắn trường HUBT), cùng danh mục trường được khai báo trong mã nguồn. Bộ demo không có sự kiện hay đánh giá giả.</p>
                <form method="post">
                    <?php wp_nonce_field( 'yl_import_demo_action', 'yl_import_demo_nonce' ); ?>
                    <input type="hidden" name="yl_import_demo" value="1">
                    <?php submit_button( 'Nhập dữ liệu YangLocal v7.24.1', 'primary', 'submit', false ); ?>
                </form>
            </div>

            <div style="padding:18px 20px;background:#fff;border:1px solid #dcdcde;border-radius:8px;">
                <h2 style="margin-top:0;">2) Đồng bộ dữ liệu hư cấu</h2>
                <p>Dùng sau khi cập nhật source. Nút này đồng bộ lại tiêu đề, nội dung, taxonomy và meta theo slug; không xóa review, favorite hoặc nội dung ngoài bộ seed.</p>
                <form method="post">
                    <?php wp_nonce_field( 'yl_refresh_demo_content_action', 'yl_refresh_demo_content_nonce' ); ?>
                    <input type="hidden" name="yl_refresh_demo_content" value="1">
                    <?php submit_button( 'Đồng bộ dữ liệu YangLocal v7.24.1', 'secondary', 'submit', false ); ?>
                </form>
            </div>

            <div style="padding:18px 20px;background:#fff;border:1px solid #dcdcde;border-radius:8px;">
                <h2 style="margin-top:0;">3) Localize toàn bộ cover exact còn remote / Commons</h2>
                <p>R16 P4 gom toàn bộ cover exact-venue còn remote nhưng có quyền tái sử dụng rõ ràng vào một queue. Mỗi request chỉ tải một ảnh để phù hợp HelioHost. Hãy chạy bước này sau Đồng bộ dữ liệu; các cover ưu tiên chỉ được hiển thị khi đã có bản local trong Media Library, tránh hotlink production.</p>
                <button type="button" class="button button-primary" id="yl-localize-priority-photos"<?php disabled( empty( $yl_priority_photo_ids ) ); ?>>Localize <?php echo esc_html( count( $yl_priority_photo_ids ) ); ?> cover exact</button>
                <span id="yl-priority-photo-status" style="margin-left:10px;"></span>
                <div id="yl-priority-photo-log" style="margin-top:12px;max-height:180px;overflow:auto;background:#f6f7f7;border:1px solid #dcdcde;padding:10px 12px;display:none;"></div>
                <script>
                (function(){
                    var button = document.getElementById('yl-localize-priority-photos');
                    if (!button) return;
                    var ids = <?php echo wp_json_encode( array_values( $yl_priority_photo_ids ) ); ?>;
                    var nonce = <?php echo wp_json_encode( wp_create_nonce( 'yl_localize_priority_photo' ) ); ?>;
                    var status = document.getElementById('yl-priority-photo-status');
                    var log = document.getElementById('yl-priority-photo-log');
                    function addLog(text, ok) {
                        log.style.display = 'block';
                        var row = document.createElement('div');
                        row.textContent = text;
                        row.style.padding = '3px 0';
                        row.style.color = ok ? '#1d6b3c' : '#b32d2e';
                        log.appendChild(row);
                    }
                    button.addEventListener('click', async function(){
                        if (!ids.length) return;
                        button.disabled = true;
                        var success = 0, failed = 0;
                        for (var i = 0; i < ids.length; i++) {
                            status.textContent = 'Đang xử lý ' + (i + 1) + '/' + ids.length + '…';
                            var body = new URLSearchParams();
                            body.set('action','yl_localize_priority_photo');
                            body.set('nonce',nonce);
                            body.set('post_id',String(ids[i]));
                            try {
                                var response = await fetch(ajaxurl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString()});
                                var json = await response.json();
                                if (json.success) {
                                    success++;
                                    addLog('✓ ' + json.data.title + (json.data.created ? ' — đã tải về host' : ' — đã có local, chỉ chuẩn hóa cover'), true);
                                } else {
                                    failed++;
                                    addLog('✕ ID ' + ids[i] + ' — ' + ((json.data && json.data.message) || 'Không xử lý được'), false);
                                }
                            } catch (e) {
                                failed++;
                                addLog('✕ ID ' + ids[i] + ' — lỗi kết nối: ' + e.message, false);
                            }
                        }
                        status.textContent = 'Hoàn tất: ' + success + ' OK' + (failed ? ', ' + failed + ' lỗi' : '') + '.';
                        button.textContent = failed ? 'Chạy lại ảnh lỗi' : 'Đã cố định ảnh ưu tiên';
                        button.disabled = !failed;
                    });
                })();
                </script>
            </div>

            <div style="padding:18px 20px;background:#fff;border:1px solid #dcdcde;border-radius:8px;">
                <h2 style="margin-top:0;">4) Công cụ ảnh exact toàn site</h2>
                <p>Không bắt buộc cho patch này. Dùng khi bạn muốn tiếp tục tải các ảnh exact có giấy phép phù hợp về Media Library cho toàn bộ địa điểm.</p>
                <a class="button" href="<?php echo esc_url( admin_url( 'tools.php?page=yanglocal-place-photos' ) ); ?>">Mở YangLocal · Ảnh thật</a>
            </div>
        </div>
    </div>
    <?php
}

function yl_get_demo_items() {
    $path = YL_CORE_PATH . 'data/demo-data.json';
    if ( ! file_exists( $path ) ) {
        return array();
    }

    $raw   = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
    $items = json_decode( $raw, true );
    return is_array( $items ) ? $items : array();
}

function yl_sanitize_demo_meta_value( $key, $value ) {
    if ( in_array( $key, array( '_yl_website', '_yl_event_url', '_yl_source_url', '_yl_image_source_url', '_yl_google_maps_url', '_yl_tiktok_search_url', '_yl_tiktok_url', '_yl_image_verification_url', '_yl_coordinate_source_url' ), true ) ) {
        return esc_url_raw( $value );
    }

    if ( '_yl_demo_image_url' === $key ) {
        return preg_match( '#^https?://#i', (string) $value ) ? esc_url_raw( $value ) : sanitize_file_name( $value );
    }

    if ( in_array( $key, array( '_yl_price_from', '_yl_rating_count', '_yl_event_offset_days', '_yl_recommend_score' ), true ) ) {
        return (string) absint( $value );
    }

    if ( '_yl_rating_average' === $key ) {
        return is_numeric( $value ) ? (string) (float) $value : '';
    }

    if ( in_array( $key, array( '_yl_latitude', '_yl_longitude' ), true ) ) {
        return is_numeric( $value ) ? (string) (float) $value : '';
    }

    if ( '_yl_external_gallery_urls' === $key ) {
        $safe_urls = array();
        foreach ( preg_split( '/[\r\n]+/', (string) $value ) as $gallery_url ) {
            $gallery_url = esc_url_raw( trim( $gallery_url ) );
            if ( $gallery_url ) {
                $safe_urls[] = $gallery_url;
            }
        }
        return implode( "\n", array_values( array_unique( $safe_urls ) ) );
    }

    if ( in_array( $key, array( '_yl_student_notes', '_yl_media_discovery_note', '_yl_recommend_reason' ), true ) ) {
        return sanitize_textarea_field( $value );
    }

    return sanitize_text_field( $value );
}

function yl_apply_demo_item( $post_id, $item ) {
    if ( empty( $post_id ) || empty( $item ) || ! is_array( $item ) ) {
        return;
    }

    wp_update_post(
        array(
            'ID'             => (int) $post_id,
            'post_title'     => isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '',
            'post_content'   => isset( $item['content'] ) ? wp_kses_post( $item['content'] ) : '',
            'post_excerpt'   => isset( $item['excerpt'] ) ? sanitize_textarea_field( $item['excerpt'] ) : '',
            'comment_status' => isset( $item['comment_status'] ) && 'open' === $item['comment_status'] ? 'open' : 'closed',
        )
    );

    $grouped_terms = array();
    if ( ! empty( $item['terms'] ) && is_array( $item['terms'] ) ) {
        foreach ( $item['terms'] as $term ) {
            $taxonomy  = isset( $term['taxonomy'] ) ? sanitize_key( $term['taxonomy'] ) : '';
            $term_slug = isset( $term['slug'] ) ? sanitize_title( $term['slug'] ) : '';
            $term_name = isset( $term['name'] ) ? sanitize_text_field( $term['name'] ) : $term_slug;
            if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) || ! $term_slug ) {
                continue;
            }

            $exists = term_exists( $term_slug, $taxonomy );
            if ( ! $exists ) {
                $exists = wp_insert_term( $term_name, $taxonomy, array( 'slug' => $term_slug ) );
            }
            if ( ! is_wp_error( $exists ) ) {
                if ( ! isset( $grouped_terms[ $taxonomy ] ) ) {
                    $grouped_terms[ $taxonomy ] = array();
                }
                $grouped_terms[ $taxonomy ][] = $term_slug;
            }
        }
        foreach ( $grouped_terms as $taxonomy => $term_slugs ) {
            wp_set_object_terms( $post_id, array_values( array_unique( $term_slugs ) ), $taxonomy, false );
        }
    }

    // R2: the controlled seed is authoritative for these taxonomies. If a taxonomy
    // is omitted from a record, clear legacy terms instead of leaving stale relations
    // behind after Data Sync (for example old HLU/UTC near_school relations).
    $managed_taxonomies = array();
    if ( 'yl_place' === get_post_type( $post_id ) ) {
        $managed_taxonomies = array( 'place_category', 'district', 'near_school' );
    } elseif ( 'yl_event' === get_post_type( $post_id ) ) {
        $managed_taxonomies = array( 'event_type' );
    }
    foreach ( $managed_taxonomies as $managed_taxonomy ) {
        if ( taxonomy_exists( $managed_taxonomy ) && empty( $grouped_terms[ $managed_taxonomy ] ) ) {
            wp_set_object_terms( $post_id, array(), $managed_taxonomy, false );
        }
    }

    $meta = ! empty( $item['meta'] ) && is_array( $item['meta'] ) ? $item['meta'] : array();

    if ( 'yl_place' === get_post_type( $post_id ) ) {
        $managed_booleans = array( 'student_friendly', 'wifi', 'power_outlet', 'quiet', 'group_study', 'air_conditioning', 'parking', 'solo_study', 'long_stay', 'quick_meal', 'pair_visit', 'late_open', 'deadline', 'weekend', 'group_4_6', 'recommended' );
        foreach ( $managed_booleans as $managed_boolean ) {
            if ( ! array_key_exists( '_yl_' . $managed_boolean, $meta ) ) {
                update_post_meta( $post_id, '_yl_' . $managed_boolean, '0' );
            }
        }
    }

    foreach ( $meta as $key => $value ) {
        if ( 0 !== strpos( $key, '_yl_' ) ) {
            continue;
        }
        update_post_meta( $post_id, sanitize_key( $key ), yl_sanitize_demo_meta_value( $key, $value ) );
    }

    if ( 'yl_place' === get_post_type( $post_id ) && function_exists( 'yl_refresh_rating_cache' ) ) {
        yl_refresh_rating_cache( $post_id );
    }

    if ( 'yl_event' === get_post_type( $post_id ) ) {
        $offset = isset( $meta['_yl_event_offset_days'] ) ? absint( $meta['_yl_event_offset_days'] ) : 0;
        if ( $offset > 0 ) {
            $timestamp = current_time( 'timestamp' ) + ( $offset * DAY_IN_SECONDS );
            update_post_meta( $post_id, '_yl_event_date', wp_date( 'Y-m-d', $timestamp ) );
        }
    }
}

function yl_archive_legacy_seed_content() {
    // Public demo export: preserve all existing posts.
    return 0;
}

function yl_import_demo_data() {
    if ( function_exists( 'yl_sync_school_terms' ) ) { yl_sync_school_terms(); }
    $items = yl_get_demo_items();
    if ( ! $items ) {
        return array( 'created' => 0, 'skipped' => 0, 'archived' => 0 );
    }

    $archived = yl_archive_legacy_seed_content();
    $created = 0;
    $skipped = 0;
    foreach ( $items as $item ) {
        $post_type = isset( $item['post_type'] ) ? sanitize_key( $item['post_type'] ) : 'post';
        $slug      = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
        if ( ! $slug || ! post_type_exists( $post_type ) ) {
            continue;
        }

        $existing = get_page_by_path( $slug, OBJECT, $post_type );
        if ( $existing ) {
            $skipped++;
            continue;
        }

        $post_id = wp_insert_post(
            array(
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'post_title'     => isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '',
                'post_name'      => $slug,
                'post_content'   => isset( $item['content'] ) ? wp_kses_post( $item['content'] ) : '',
                'post_excerpt'   => isset( $item['excerpt'] ) ? sanitize_textarea_field( $item['excerpt'] ) : '',
                'comment_status' => isset( $item['comment_status'] ) && 'open' === $item['comment_status'] ? 'open' : 'closed',
            ),
            true
        );
        if ( is_wp_error( $post_id ) || ! $post_id ) {
            $skipped++;
            continue;
        }

        yl_apply_demo_item( $post_id, $item );
        $created++;
    }

    return array( 'created' => $created, 'skipped' => $skipped, 'archived' => $archived );
}

function yl_refresh_demo_content() {
    if ( function_exists( 'yl_sync_school_terms' ) ) { yl_sync_school_terms(); }
    $items = yl_get_demo_items();
    if ( ! $items ) {
        return array( 'updated' => 0, 'created' => 0, 'skipped' => 0, 'archived' => 0 );
    }

    $archived = yl_archive_legacy_seed_content();
    $updated  = 0;
    $created  = 0;
    $skipped  = 0;

    foreach ( $items as $item ) {
        $post_type = isset( $item['post_type'] ) ? sanitize_key( $item['post_type'] ) : 'post';
        $slug      = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
        if ( ! $slug || ! post_type_exists( $post_type ) ) {
            $skipped++;
            continue;
        }

        $existing = get_page_by_path( $slug, OBJECT, $post_type );
        if ( $existing ) {
            yl_apply_demo_item( $existing->ID, $item );
            $updated++;
            continue;
        }

        $post_id = wp_insert_post(
            array(
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'post_title'     => isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '',
                'post_name'      => $slug,
                'post_content'   => isset( $item['content'] ) ? wp_kses_post( $item['content'] ) : '',
                'post_excerpt'   => isset( $item['excerpt'] ) ? sanitize_textarea_field( $item['excerpt'] ) : '',
                'comment_status' => isset( $item['comment_status'] ) && 'open' === $item['comment_status'] ? 'open' : 'closed',
            ),
            true
        );
        if ( is_wp_error( $post_id ) ) {
            $skipped++;
            continue;
        }
        yl_apply_demo_item( $post_id, $item );
        $created++;
    }

    return array( 'updated' => $updated, 'created' => $created, 'skipped' => $skipped, 'archived' => $archived );
}
