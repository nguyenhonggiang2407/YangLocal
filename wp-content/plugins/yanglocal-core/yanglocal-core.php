<?php
/**
 * Plugin Name: YangLocal Core
 * Description: Core features for YangLocal: places, events, reviews, favorites, filters and lightweight admin tools.
 * Version: 7.24.1
 * Requires at least: 7.1
 * Requires PHP: 7.4
 * Author: YangLocal
 * Text Domain: yanglocal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'YL_CORE_VERSION', '7.24.1' );
define( 'YL_CORE_FILE', __FILE__ );
define( 'YL_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'YL_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once YL_CORE_PATH . 'includes/post-types.php';
require_once YL_CORE_PATH . 'includes/helpers.php';
require_once YL_CORE_PATH . 'includes/place-photos.php';
require_once YL_CORE_PATH . 'includes/schools.php';
require_once YL_CORE_PATH . 'includes/meta-boxes.php';
require_once YL_CORE_PATH . 'includes/reviews.php';
require_once YL_CORE_PATH . 'includes/favorites.php';
require_once YL_CORE_PATH . 'includes/search-filter.php';
require_once YL_CORE_PATH . 'includes/contact.php';
require_once YL_CORE_PATH . 'includes/auth.php';
require_once YL_CORE_PATH . 'includes/reports.php';
require_once YL_CORE_PATH . 'includes/admin.php';
require_once YL_CORE_PATH . 'includes/importer.php';

function yl_core_init() {
    yl_register_post_types();
    yl_register_taxonomies();
}
add_action( 'init', 'yl_core_init' );

function yl_core_activate() {
    yl_register_post_types();
    yl_register_taxonomies();
    yl_sync_school_terms();
    yl_create_default_pages();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'yl_core_activate' );

function yl_core_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'yl_core_deactivate' );

function yl_maybe_upgrade() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( YL_CORE_VERSION === get_option( 'yl_core_db_version' ) ) {
        return;
    }
    yl_register_post_types();
    yl_register_taxonomies();
    yl_sync_school_terms();
    yl_create_default_pages();
    update_option( 'yl_core_db_version', YL_CORE_VERSION );
    flush_rewrite_rules();
}
add_action( 'admin_init', 'yl_maybe_upgrade' );

function yl_create_default_pages() {
    $pages = array(
        'trang-chu' => array( 'title' => 'Trang chủ', 'content' => '' ),
        'cam-nang'  => array( 'title' => 'Cẩm nang', 'content' => '' ),
        'da-luu'    => array( 'title' => 'Đã lưu', 'content' => '[yanglocal_saved]' ),
        'lien-he'   => array( 'title' => 'Liên hệ', 'content' => '[yanglocal_contact]' ),
        'gan-truong' => array( 'title' => 'Gần trường', 'content' => '[yanglocal_schools]' ),
        'dang-nhap' => array( 'title' => 'Đăng nhập', 'content' => '[yanglocal_login]' ),
        'de-xuat-dia-diem' => array( 'title' => 'Đề xuất địa điểm', 'content' => '[yanglocal_submit_place]' ),
        've-yanglocal' => array(
            'title'   => 'Về YangLocal',
            'content' => '<h2>YangLocal là gì?</h2><p>YangLocal là local guide Hà Nội dành cho sinh viên và những người muốn tìm một nơi phù hợp mà không phải mở quá nhiều tab.</p><h2>Vì sao có YangLocal?</h2><p>Đi ăn, tìm chỗ học, mua sắm hay dành một buổi khám phá Hà Nội đều cần thông tin ngắn gọn và đủ tin cậy. YangLocal ưu tiên địa chỉ, nguồn cập nhật, ảnh đúng địa điểm và đường đi thay vì nhồi thật nhiều dữ liệu chưa kiểm tra.</p><h2>Dành cho ai?</h2><p>Sinh viên quanh các campus Hà Nội, người mới đến thành phố và bất kỳ ai cần một danh sách địa điểm có thể dùng thật.</p><h2>Dữ liệu được chọn thế nào?</h2><p>Thông tin được đối chiếu từ nguồn chính thức hoặc nguồn công khai phù hợp. Trường nào chưa đủ dữ liệu sẽ được ghi là đang xác minh; giờ, giá và vé có thể thay đổi nên YangLocal luôn để đường dẫn nguồn để kiểm tra lại.</p><h2>Một project sinh viên hướng đến sử dụng thật</h2><p>YangLocal được phát triển như một sản phẩm học tập nhưng mục tiêu là có thể mở ra và dùng được: ít placeholder, không fake rating và không dùng ảnh quán khác để lấp vào địa điểm chưa có ảnh.</p>',
        ),
        'dieu-khoan' => array(
            'title'   => 'Điều khoản',
            'content' => '<p>Nội dung trên YangLocal có mục đích tham khảo. Giá, giờ hoạt động và thông tin địa điểm có thể thay đổi; hãy xác minh trước khi sử dụng như thông tin chính thức.</p>',
        ),
        'chinh-sach-rieng-tu' => array(
            'title'   => 'Chính sách riêng tư',
            'content' => '<p>YangLocal chỉ thu thập thông tin cần thiết cho các chức năng bạn chủ động sử dụng, như tài khoản, địa điểm đã lưu và nội dung biểu mẫu liên hệ.</p><p>Thông tin bạn gửi qua biểu mẫu chỉ được dùng để xử lý yêu cầu hoặc góp ý tương ứng.</p>',
        ),
    );

    $created_ids = array();
    foreach ( $pages as $slug => $data ) {
        $existing = get_page_by_path( $slug, OBJECT, 'page' );
        if ( $existing ) {
            $created_ids[ $slug ] = (int) $existing->ID;
            $expected = isset( $data['content'] ) ? trim( (string) $data['content'] ) : '';
            if ( 've-yanglocal' === $slug && $expected ) {
                $current = trim( (string) $existing->post_content );
                if ( '' === $current || false !== strpos( $current, 'Website ưu tiên thông tin ngắn gọn' ) || false !== strpos( $current, 'local guide dành cho sinh viên tại Hà Nội' ) ) {
                    wp_update_post( array( 'ID' => $existing->ID, 'post_content' => $expected ) );
                }
            }
            if ( $expected && '[' === substr( $expected, 0, 1 ) ) {
                $tag = trim( $expected, "[] \t\n\r\0\x0B" );
                $current = trim( (string) $existing->post_content );
                $looks_like_legacy_system_page = '' === $current || (bool) preg_match( '/^\[yanglocal_[a-z0-9_]+\]$/', $current );
                if ( $looks_like_legacy_system_page && ! has_shortcode( $current, $tag ) ) {
                    wp_update_post( array( 'ID' => $existing->ID, 'post_content' => $expected ) );
                }
            }
            continue;
        }

        $page_id = wp_insert_post(
            array(
                'post_title'   => $data['title'],
                'post_name'    => $slug,
                'post_content' => $data['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
            )
        );

        if ( ! is_wp_error( $page_id ) ) {
            $created_ids[ $slug ] = (int) $page_id;
        }
    }

    if ( ! empty( $created_ids['trang-chu'] ) ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', (int) $created_ids['trang-chu'] );
    }
    if ( ! empty( $created_ids['cam-nang'] ) ) {
        update_option( 'page_for_posts', (int) $created_ids['cam-nang'] );
    }
    if ( ! empty( $created_ids['chinh-sach-rieng-tu'] ) && ! get_option( 'wp_page_for_privacy_policy' ) ) {
        update_option( 'wp_page_for_privacy_policy', (int) $created_ids['chinh-sach-rieng-tu'] );
    }
}
