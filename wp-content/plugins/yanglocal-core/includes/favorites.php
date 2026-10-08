<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function yl_get_user_favorites( $user_id = 0 ) {
    $user_id = $user_id ? (int) $user_id : get_current_user_id();
    if ( ! $user_id ) {
        return array();
    }
    $favorites = get_user_meta( $user_id, '_yl_favorites', true );
    return is_array( $favorites ) ? array_values( array_unique( array_map( 'absint', $favorites ) ) ) : array();
}

function yl_ajax_toggle_favorite() {
    check_ajax_referer( 'yl_favorite_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'code' => 'guest', 'message' => 'Dùng localStorage cho khách.' ), 401 );
    }

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( ! $post_id || 'yl_place' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
        wp_send_json_error( array( 'message' => 'Địa điểm không hợp lệ.' ), 400 );
    }

    $user_id = get_current_user_id();
    $favorites = yl_get_user_favorites( $user_id );
    $index = array_search( $post_id, $favorites, true );
    $saved = false;
    if ( false === $index ) {
        $favorites[] = $post_id;
        $saved = true;
    } else {
        unset( $favorites[ $index ] );
    }
    update_user_meta( $user_id, '_yl_favorites', array_values( $favorites ) );
    wp_send_json_success( array( 'saved' => $saved, 'ids' => array_values( $favorites ) ) );
}
add_action( 'wp_ajax_yl_toggle_favorite', 'yl_ajax_toggle_favorite' );

function yl_saved_shortcode() {
    ob_start();
    echo '<div class="yl-saved-page" data-yl-saved-page>';
    echo '<header class="yl-page-header"><p class="yl-eyebrow">Danh sách cá nhân</p><h1>Địa điểm đã lưu</h1><p>Lưu nhanh những nơi bạn muốn quay lại xem sau.</p></header>';
    echo '<div class="yl-saved-grid" data-yl-saved-grid>';

    if ( is_user_logged_in() ) {
        $ids = yl_get_user_favorites();
        if ( $ids ) {
            $query = new WP_Query( array( 'post_type' => 'yl_place', 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => 50 ) );
            while ( $query->have_posts() ) {
                $query->the_post();
                get_template_part( 'template-parts/place-card' );
            }
            wp_reset_postdata();
        } else {
            echo '<div class="yl-empty-state"><h2>Bạn chưa lưu địa điểm nào.</h2><p>Thử vào trang Khám phá và bấm biểu tượng trái tim ở một địa điểm bạn quan tâm.</p><a class="yl-button" href="' . esc_url( get_post_type_archive_link( 'yl_place' ) ) . '">Khám phá địa điểm</a></div>';
        }
    } else {
        echo '<noscript><div class="yl-empty-state"><h2>Cần JavaScript để xem mục đã lưu của khách.</h2><p>Bạn vẫn có thể đăng nhập để đồng bộ danh sách bằng tài khoản YangLocal.</p></div></noscript>';
        echo '<div class="yl-empty-state" data-yl-guest-saved-loading><p>Đang đọc danh sách đã lưu trên trình duyệt…</p></div>';
    }
    echo '</div></div>';
    return ob_get_clean();
}
add_shortcode( 'yanglocal_saved', 'yl_saved_shortcode' );

function yl_ajax_guest_saved_cards() {
    check_ajax_referer( 'yl_favorite_nonce', 'nonce' );
    $raw_ids = isset( $_POST['ids'] ) ? (array) $_POST['ids'] : array();
    $ids = array_slice( array_values( array_unique( array_filter( array_map( 'absint', $raw_ids ) ) ) ), 0, 50 );
    if ( ! $ids ) {
        wp_send_json_success( array( 'html' => '' ) );
    }
    $query = new WP_Query( array( 'post_type' => 'yl_place', 'post_status' => 'publish', 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => 50 ) );
    ob_start();
    while ( $query->have_posts() ) {
        $query->the_post();
        get_template_part( 'template-parts/place-card' );
    }
    wp_reset_postdata();
    wp_send_json_success( array( 'html' => ob_get_clean() ) );
}
add_action( 'wp_ajax_nopriv_yl_guest_saved_cards', 'yl_ajax_guest_saved_cards' );
