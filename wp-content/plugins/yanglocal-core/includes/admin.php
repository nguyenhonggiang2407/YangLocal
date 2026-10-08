<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function yl_add_dashboard_widget() {
    if ( current_user_can( 'edit_posts' ) ) {
        wp_add_dashboard_widget( 'yl_dashboard_widget', 'YangLocal Campus', 'yl_render_dashboard_widget' );
    }
}
add_action( 'wp_dashboard_setup', 'yl_add_dashboard_widget' );

function yl_count_place_freshness() {
    $ids = get_posts(
        array(
            'post_type'      => 'yl_place',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        )
    );
    $counts = array( 'fresh' => 0, 'acceptable' => 0, 'review' => 0, 'stale' => 0, 'unknown' => 0 );
    foreach ( $ids as $post_id ) {
        $freshness = yl_get_source_freshness( $post_id );
        if ( isset( $counts[ $freshness['status'] ] ) ) {
            $counts[ $freshness['status'] ]++;
        }
    }
    return $counts;
}

function yl_render_dashboard_widget() {
    $places = wp_count_posts( 'yl_place' );
    $fresh  = yl_count_place_freshness();
    $upcoming = new WP_Query(
        array(
            'post_type'      => 'yl_event',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array( array( 'key' => '_yl_event_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ),
        )
    );
    $school_terms = get_terms( array( 'taxonomy' => 'near_school', 'hide_empty' => false ) );
    $school_verified = 0; $school_pending = 0; $school_review = 0; $school_stale = 0;
    if ( ! is_wp_error( $school_terms ) ) {
        foreach ( $school_terms as $school_term ) {
            if ( function_exists( 'yl_get_school_meta' ) && '1' === yl_get_school_meta( $school_term, 'verified', '0' ) ) { $school_verified++; } else { $school_pending++; }
            if ( function_exists( 'yl_get_school_freshness' ) ) { $sf = yl_get_school_freshness( $school_term ); if ( 'review' === $sf['status'] ) { $school_review++; } elseif ( 'stale' === $sf['status'] ) { $school_stale++; } }
        }
    }

    $pending_reports = wp_count_posts( 'yl_report' );
    $report_count    = isset( $pending_reports->pending ) ? (int) $pending_reports->pending : 0;

    echo '<ul style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:0;">';
    echo '<li><strong style="font-size:20px">' . esc_html( (int) $places->publish ) . '</strong><br>Địa điểm</li>';
    $coord_verified = (int) ( new WP_Query( array( 'post_type'=>'yl_place','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','no_found_rows'=>false,'meta_key'=>'_yl_coord_verified','meta_value'=>'1' ) ) )->found_posts;
    $image_exact = (int) ( new WP_Query( array( 'post_type'=>'yl_place','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','no_found_rows'=>false,'meta_key'=>'_yl_image_match_level','meta_value'=>'exact_venue' ) ) )->found_posts;
    echo '<li><strong style="font-size:20px">' . esc_html( (int) $fresh['acceptable'] ) . '</strong><br>Dữ liệu 30–90 ngày</li>';
    echo '<li><strong style="font-size:20px">' . esc_html( (int) $fresh['review'] ) . '</strong><br>Nên kiểm tra lại 91–180 ngày</li>';
    echo '<li><strong style="font-size:20px">' . esc_html( (int) $fresh['stale'] ) . '</strong><br>Dữ liệu &gt;180 ngày</li>';
    echo '<li><strong style="font-size:20px">' . esc_html( $coord_verified ) . '</strong><br>Place có tọa độ xác minh</li>';
    echo '<li><strong style="font-size:20px">' . esc_html( $image_exact ) . '</strong><br>Ảnh đúng địa điểm</li>';
    echo '<li><strong style="font-size:20px">' . esc_html( (int) $upcoming->found_posts ) . '</strong><br>Sự kiện sắp tới</li>';
    echo '<li><strong style="font-size:20px">' . esc_html( $report_count ) . '</strong><br>Góp ý đang chờ</li>';
    echo '<li><strong style="font-size:20px">' . esc_html( $school_verified ) . '</strong><br>Campus đã xác minh</li>';
    echo '<li><strong style="font-size:20px">' . esc_html( $school_pending ) . '</strong><br>Campus chờ bổ sung</li>';
    echo '<li><strong style="font-size:20px">' . esc_html( $school_review + $school_stale ) . '</strong><br>Campus cần kiểm tra lại</li>';
    echo '</ul>';
}

function yl_place_admin_columns( $columns ) {
    $columns['yl_freshness'] = 'Độ mới dữ liệu';
    return $columns;
}
add_filter( 'manage_yl_place_posts_columns', 'yl_place_admin_columns' );

function yl_place_admin_column_content( $column, $post_id ) {
    if ( 'yl_freshness' !== $column ) {
        return;
    }
    $freshness = yl_get_source_freshness( $post_id );
    $checked   = get_post_meta( $post_id, '_yl_source_checked', true );
    echo '<strong>' . esc_html( $freshness['label'] ) . '</strong>';
    if ( $checked ) {
        echo '<br><small>' . esc_html( $checked ) . '</small>';
    }
}
add_action( 'manage_yl_place_posts_custom_column', 'yl_place_admin_column_content', 10, 2 );

function yl_place_freshness_filter() {
    global $typenow;
    if ( 'yl_place' !== $typenow ) {
        return;
    }
    $current = isset( $_GET['yl_freshness'] ) ? sanitize_key( wp_unslash( $_GET['yl_freshness'] ) ) : '';
    echo '<select name="yl_freshness"><option value="">Tất cả độ mới</option>';
    foreach ( array( 'fresh' => 'Mới kiểm tra (<30 ngày)', 'acceptable' => 'Còn khá mới (30–90 ngày)', 'review' => 'Nên kiểm tra lại (91–180 ngày)', 'stale' => 'Dữ liệu đã cũ (>180 ngày)', 'unknown' => 'Chưa có ngày' ) as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '" ' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}
add_action( 'restrict_manage_posts', 'yl_place_freshness_filter' );

function yl_filter_places_by_freshness( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() || 'yl_place' !== $query->get( 'post_type' ) ) {
        return;
    }
    $filter = isset( $_GET['yl_freshness'] ) ? sanitize_key( wp_unslash( $_GET['yl_freshness'] ) ) : '';
    if ( ! $filter ) {
        return;
    }
    $today     = current_time( 'Y-m-d' );
    $days30    = wp_date( 'Y-m-d', current_time( 'timestamp' ) - 30 * DAY_IN_SECONDS );
    $days90    = wp_date( 'Y-m-d', current_time( 'timestamp' ) - 90 * DAY_IN_SECONDS );
    $days180   = wp_date( 'Y-m-d', current_time( 'timestamp' ) - 180 * DAY_IN_SECONDS );
    if ( 'fresh' === $filter ) {
        $query->set( 'meta_query', array( array( 'key' => '_yl_source_checked', 'value' => array( $days30, $today ), 'compare' => 'BETWEEN', 'type' => 'DATE' ) ) );
    } elseif ( 'acceptable' === $filter ) {
        $query->set( 'meta_query', array( array( 'key' => '_yl_source_checked', 'value' => array( $days90, $days30 ), 'compare' => 'BETWEEN', 'type' => 'DATE' ) ) );
    } elseif ( 'review' === $filter ) {
        $query->set( 'meta_query', array( array( 'key' => '_yl_source_checked', 'value' => array( $days180, $days90 ), 'compare' => 'BETWEEN', 'type' => 'DATE' ) ) );
    } elseif ( 'stale' === $filter ) {
        $query->set( 'meta_query', array( array( 'key' => '_yl_source_checked', 'value' => $days180, 'compare' => '<', 'type' => 'DATE' ) ) );
    } elseif ( 'unknown' === $filter ) {
        $query->set( 'meta_query', array( 'relation' => 'OR', array( 'key' => '_yl_source_checked', 'compare' => 'NOT EXISTS' ), array( 'key' => '_yl_source_checked', 'value' => '', 'compare' => '=' ) ) );
    }
}
add_action( 'pre_get_posts', 'yl_filter_places_by_freshness' );

function yl_message_columns( $columns ) {
    $columns['yl_sender'] = 'Người gửi';
    $columns['yl_email']  = 'Email';
    return $columns;
}
add_filter( 'manage_yl_message_posts_columns', 'yl_message_columns' );

function yl_message_column_content( $column, $post_id ) {
    if ( 'yl_sender' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_yl_contact_name', true ) );
    }
    if ( 'yl_email' === $column ) {
        $email = get_post_meta( $post_id, '_yl_contact_email', true );
        echo $email ? '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' : '—';
    }
}
add_action( 'manage_yl_message_posts_custom_column', 'yl_message_column_content', 10, 2 );

function yl_report_columns( $columns ) {
    $columns['yl_place']  = 'Địa điểm';
    $columns['yl_reason'] = 'Lý do';
    return $columns;
}
add_filter( 'manage_yl_report_posts_columns', 'yl_report_columns' );

function yl_report_column_content( $column, $post_id ) {
    if ( 'yl_place' === $column ) {
        $place_id = absint( get_post_meta( $post_id, '_yl_report_place_id', true ) );
        echo $place_id ? '<a href="' . esc_url( get_edit_post_link( $place_id ) ) . '">' . esc_html( get_the_title( $place_id ) ) . '</a>' : '—';
    }
    if ( 'yl_reason' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_yl_report_reason', true ) );
    }
}
add_action( 'manage_yl_report_posts_custom_column', 'yl_report_column_content', 10, 2 );
