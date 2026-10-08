<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function yl_normalize_search_text( $value ) {
    $value = str_replace( array( 'Đ', 'đ' ), array( 'D', 'd' ), (string) $value );
    $value = remove_accents( $value );
    $value = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
    $value = preg_replace( '/[^a-z0-9\s]+/u', ' ', $value );
    $value = preg_replace( '/\s+/u', ' ', $value );
    return trim( (string) $value );
}

function yl_get_place_meta( $post_id, $key, $default = '' ) {
    $value = get_post_meta( $post_id, '_yl_' . $key, true );
    return '' !== $value ? $value : $default;
}

function yl_is_truthy_meta( $post_id, $key ) {
    return '1' === (string) yl_get_place_meta( $post_id, $key, '0' );
}

function yl_get_google_maps_url( $post_id ) {
    $place_id = trim( (string) get_post_meta( $post_id, '_yl_google_place_id', true ) );
    if ( $place_id ) {
        $query = trim( get_the_title( $post_id ) . ' ' . yl_get_place_meta( $post_id, 'address', '' ) );
        return add_query_arg(
            array(
                'api'            => '1',
                'query'          => $query,
                'query_place_id' => $place_id,
            ),
            'https://www.google.com/maps/search/'
        );
    }
    $custom = yl_get_place_meta( $post_id, 'google_maps_url', '' );
    if ( $custom ) {
        return esc_url_raw( $custom );
    }
    $address = yl_get_place_meta( $post_id, 'address', '' );
    if ( ! $address ) {
        return '';
    }
    $query = trim( get_the_title( $post_id ) . ' ' . $address );
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
}

function yl_get_tiktok_search_url( $post_id ) {
    $custom = yl_get_place_meta( $post_id, 'tiktok_search_url', '' );
    if ( $custom ) {
        return esc_url_raw( $custom );
    }
    $query = trim( get_the_title( $post_id ) . ' Hà Nội' );
    return 'https://www.tiktok.com/search?q=' . rawurlencode( $query );
}

function yl_get_google_images_url( $post_id ) {
    $query = trim( get_the_title( $post_id ) . ' ' . yl_get_place_meta( $post_id, 'address', '' ) . ' Hà Nội' );
    return 'https://www.google.com/search?tbm=isch&q=' . rawurlencode( $query );
}

function yl_get_pinterest_search_url( $post_id ) {
    $query = trim( get_the_title( $post_id ) . ' Hà Nội' );
    return 'https://www.pinterest.com/search/pins/?q=' . rawurlencode( $query );
}

function yl_get_price_label( $value ) {
    $value = (int) $value;
    if ( $value <= 0 ) {
        return 'Chưa cập nhật';
    }
    if ( $value < 30000 ) {
        return 'Dưới 30K';
    }
    if ( $value <= 50000 ) {
        return '30–50K';
    }
    if ( $value <= 100000 ) {
        return '50–100K';
    }
    return 'Trên 100K';
}

function yl_get_event_status( $post_id, $now = null ) {
    $now   = $now ? $now : current_time( 'timestamp' );
    $date  = get_post_meta( $post_id, '_yl_event_date', true );
    $start = get_post_meta( $post_id, '_yl_event_start_time', true );
    $end   = get_post_meta( $post_id, '_yl_event_end_time', true );

    if ( ! $date ) {
        return 'unknown';
    }

    $start_ts = strtotime( $date . ' ' . ( $start ? $start : '00:00' ) );
    $end_ts   = strtotime( $date . ' ' . ( $end ? $end : '23:59' ) );

    if ( $now < $start_ts ) {
        return 'upcoming';
    }
    if ( $now <= $end_ts ) {
        return 'ongoing';
    }
    return 'past';
}

function yl_get_source_freshness( $post_id, $now = null ) {
    $checked = get_post_meta( $post_id, '_yl_source_checked', true );
    if ( ! $checked ) {
        return array( 'status' => 'unknown', 'label' => 'Chưa kiểm tra', 'days' => null );
    }

    $checked_ts = strtotime( $checked . ' 00:00:00' );
    if ( ! $checked_ts ) {
        return array( 'status' => 'unknown', 'label' => 'Chưa kiểm tra', 'days' => null );
    }

    $now  = $now ? (int) $now : current_time( 'timestamp' );
    $days = max( 0, (int) floor( ( $now - $checked_ts ) / DAY_IN_SECONDS ) );

    if ( $days < 30 ) {
        return array( 'status' => 'fresh', 'label' => 'Mới kiểm tra', 'days' => $days );
    }
    if ( $days <= 90 ) {
        return array( 'status' => 'acceptable', 'label' => 'Còn khá mới', 'days' => $days );
    }
    if ( $days <= 180 ) {
        return array( 'status' => 'review', 'label' => 'Nên kiểm tra lại', 'days' => $days );
    }
    return array( 'status' => 'stale', 'label' => 'Dữ liệu đã cũ', 'days' => $days );
}

function yl_get_image_match_info( $post_id ) {
    $level = get_post_meta( $post_id, '_yl_image_match_level', true );
    $map = array(
        'exact_venue' => array(
            'level'       => 'exact_venue',
            'label'       => 'Ảnh địa điểm',
            'short_label' => 'Ảnh địa điểm',
            'description' => 'Nguồn ảnh được đối chiếu với đúng địa điểm hoặc đúng chi nhánh.',
            'class'       => 'exact',
            'alt_prefix'  => 'Ảnh địa điểm',
        ),
        'exact_dish' => array(
            'level'       => 'exact_dish',
            'label'       => 'Ảnh đúng món',
            'short_label' => 'Đúng món',
            'description' => 'Ảnh khớp món hoặc hoạt động, nhưng không khẳng định được chụp tại đúng quán.',
            'class'       => 'dish',
            'alt_prefix'  => 'Ảnh đúng món cho',
        ),
        'close_match' => array(
            'level'       => 'close_match',
            'label'       => 'Ảnh tham khảo',
            'short_label' => 'Tham khảo',
            'description' => 'Ảnh được chọn sát loại không gian hoặc hoạt động; dùng để hình dung nhanh khi chưa có ảnh địa điểm đủ rõ.',
            'class'       => 'reference',
            'alt_prefix'  => 'Ảnh tham khảo cho',
        ),
        'category' => array(
            'level'       => 'category',
            'label'       => 'Ảnh theo danh mục',
            'short_label' => 'Theo danh mục',
            'description' => 'Ảnh chỉ đại diện cho danh mục và cần được đối chiếu thêm.',
            'class'       => 'category',
            'alt_prefix'  => 'Ảnh đại diện danh mục cho',
        ),
        'fallback' => array(
            'level'       => 'fallback',
            'label'       => 'Ảnh tham khảo',
            'short_label' => 'Tham khảo',
            'description' => 'Ảnh tham khảo theo nhóm địa điểm; hãy mở Maps nếu cần nhận diện mặt tiền mới nhất.',
            'class'       => 'reference',
            'alt_prefix'  => 'Ảnh tham khảo cho',
        ),
    );

    if ( ! isset( $map[ $level ] ) ) {
        $level = 'fallback';
    }
    return $map[ $level ];
}

function yl_get_data_trust_score( $post_id ) {
    $score = 0;
    $signals = array();

    if ( yl_get_place_meta( $post_id, 'address', '' ) ) {
        $score += 10;
        $signals[] = 'Có địa chỉ';
    }
    if ( yl_get_place_meta( $post_id, 'source_url', '' ) ) {
        $score += 18;
        $signals[] = 'Có nguồn đối chiếu';
    }

    $freshness = yl_get_source_freshness( $post_id );
    $freshness_points = array( 'fresh' => 20, 'acceptable' => 14, 'review' => 7, 'stale' => 2, 'unknown' => 0 );
    $score += isset( $freshness_points[ $freshness['status'] ] ) ? $freshness_points[ $freshness['status'] ] : 0;
    if ( 'unknown' !== $freshness['status'] ) {
        $signals[] = $freshness['label'];
    }

    if ( absint( yl_get_place_meta( $post_id, 'price_from', 0 ) ) > 0 ) {
        $score += 10;
        $signals[] = 'Có giá tham khảo';
    }
    if ( yl_get_place_meta( $post_id, 'opening_note', '' ) ) {
        $score += 8;
        $signals[] = 'Có giờ tham khảo';
    }
    if ( yl_get_place_meta( $post_id, 'website', '' ) ) {
        $score += 5;
        $signals[] = 'Có website';
    }

    $lat = yl_get_place_meta( $post_id, 'latitude', '' );
    $lng = yl_get_place_meta( $post_id, 'longitude', '' );
    if ( '1' === (string) yl_get_place_meta( $post_id, 'coord_verified', '0' ) && is_numeric( $lat ) && is_numeric( $lng ) ) {
        $score += 12;
        $signals[] = 'Tọa độ đã xác minh';
    }

    $image_info = yl_get_image_match_info( $post_id );
    $image_verification_url = yl_get_place_meta( $post_id, 'image_verification_url', '' );
    $image_points = array( 'exact_venue' => 12, 'exact_dish' => 8, 'close_match' => 4, 'category' => 2, 'fallback' => 0 );
    $score += isset( $image_points[ $image_info['level'] ] ) ? $image_points[ $image_info['level'] ] : 0;
    $signals[] = $image_info['short_label'];

    $student_signal_count = 0;
    foreach ( array( 'student_friendly', 'wifi', 'power_outlet', 'quiet', 'group_study', 'parking', 'solo_study', 'quick_meal' ) as $key ) {
        if ( yl_is_truthy_meta( $post_id, $key ) ) {
            $student_signal_count++;
        }
    }
    if ( $student_signal_count >= 2 ) {
        $score += 5;
        $signals[] = 'Có dữ liệu phục vụ sinh viên';
    }

    $score = min( 100, max( 0, (int) $score ) );
    if ( $score >= 80 ) {
        $status = 'high';
        $label = 'Cao';
    } elseif ( $score >= 60 ) {
        $status = 'good';
        $label = 'Khá';
    } elseif ( $score >= 40 ) {
        $status = 'medium';
        $label = 'Trung bình';
    } else {
        $status = 'low';
        $label = 'Cần bổ sung';
    }

    return array(
        'score'   => $score,
        'status'  => $status,
        'label'   => $label,
        'signals' => array_values( array_unique( $signals ) ),
    );
}

function yl_haversine_distance_km( $lat1, $lng1, $lat2, $lng2 ) {
    foreach ( array( $lat1, $lng1, $lat2, $lng2 ) as $value ) {
        if ( ! is_numeric( $value ) ) {
            return null;
        }
    }
    $lat1 = deg2rad( (float) $lat1 );
    $lng1 = deg2rad( (float) $lng1 );
    $lat2 = deg2rad( (float) $lat2 );
    $lng2 = deg2rad( (float) $lng2 );
    $dlat = $lat2 - $lat1;
    $dlng = $lng2 - $lng1;
    $a    = sin( $dlat / 2 ) * sin( $dlat / 2 ) + cos( $lat1 ) * cos( $lat2 ) * sin( $dlng / 2 ) * sin( $dlng / 2 );
    return 6371 * ( 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) ) );
}

function yl_format_distance( $km ) {
    if ( null === $km || ! is_numeric( $km ) ) {
        return '';
    }
    $km = (float) $km;
    if ( $km < 1 ) {
        return 'Khoảng ' . number_format_i18n( round( $km * 1000 / 50 ) * 50 ) . ' m';
    }
    return 'Khoảng ' . number_format_i18n( $km, 1 ) . ' km';
}

function yl_get_distance_to_school( $post_id, $school_term ) {
    if ( is_string( $school_term ) ) {
        $school_term = get_term_by( 'slug', sanitize_title( $school_term ), 'near_school' );
    }
    if ( ! $school_term || is_wp_error( $school_term ) ) {
        return null;
    }

    // Distance is a trust-sensitive claim. Only publish it when both coordinate sets are explicitly verified.
    if ( '1' !== (string) get_post_meta( $post_id, '_yl_coord_verified', true ) ||
         '1' !== (string) get_term_meta( $school_term->term_id, 'yl_school_coord_verified', true ) ) {
        return null;
    }

    $place_lat  = get_post_meta( $post_id, '_yl_latitude', true );
    $place_lng  = get_post_meta( $post_id, '_yl_longitude', true );
    $school_lat = get_term_meta( $school_term->term_id, 'yl_school_latitude', true );
    $school_lng = get_term_meta( $school_term->term_id, 'yl_school_longitude', true );
    return yl_haversine_distance_km( $place_lat, $place_lng, $school_lat, $school_lng );
}

/**
 * Editorial recommendation metadata for places that YangLocal actively suggests.
 * The numeric score is only an internal ordering signal; the public UI explains
 * the recommendation in human language instead of pretending it is an objective rating.
 */
function yl_get_recommendation_info( $post_id ) {
    $score = absint( yl_get_place_meta( $post_id, 'recommend_score', 0 ) );
    return array(
        'recommended' => yl_is_truthy_meta( $post_id, 'recommended' ),
        'score'       => min( 100, $score ),
        'badge'       => trim( (string) yl_get_place_meta( $post_id, 'recommend_badge', '' ) ),
        'reason'      => trim( (string) yl_get_place_meta( $post_id, 'recommend_reason', '' ) ),
        'best_time'   => trim( (string) yl_get_place_meta( $post_id, 'recommend_best_time', '' ) ),
        'duration'    => trim( (string) yl_get_place_meta( $post_id, 'recommend_duration', '' ) ),
    );
}

function yl_get_recommended_places( $limit = 6 ) {
    return new WP_Query(
        array(
            'post_type'      => 'yl_place',
            'post_status'    => 'publish',
            'posts_per_page' => max( 1, absint( $limit ) ),
            'meta_query'     => array(
                array(
                    'key'     => '_yl_recommended',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
            'meta_key'       => '_yl_recommend_score',
            'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
            'order'          => 'DESC',
            'no_found_rows'  => true,
        )
    );
}

/**
 * Return Place IDs matching a human search phrase across title/content, aliases,
 * address and editorial topic. This keeps aliases such as “Lăng Bác” or
 * “Hỏa Lò” useful without introducing a separate search index.
 */
function yl_find_place_ids_for_keyword( $keyword ) {
    $keyword = trim( sanitize_text_field( (string) $keyword ) );
    if ( '' === $keyword ) {
        return array();
    }

    $ids = array();

    $text_query = new WP_Query(
        array(
            'post_type'              => 'yl_place',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            's'                      => $keyword,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );
    $ids = array_merge( $ids, $text_query->posts );

    $meta_keys = array( '_yl_search_aliases', '_yl_address', '_yl_topic' );
    $meta_query = array( 'relation' => 'OR' );
    foreach ( $meta_keys as $meta_key ) {
        $meta_query[] = array(
            'key'     => $meta_key,
            'value'   => $keyword,
            'compare' => 'LIKE',
        );
    }
    $meta_ids = get_posts(
        array(
            'post_type'              => 'yl_place',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'meta_query'             => $meta_query,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );
    $ids = array_merge( $ids, $meta_ids );

    // A normalized pass lets common no-accent queries work even when the
    // stored aliases are accented. The data set is deliberately small enough
    // for this bounded pass and it is only executed when a user searches.
    $normalized_keyword = yl_normalize_search_text( $keyword );
    if ( $normalized_keyword ) {
        $candidate_ids = get_posts(
            array(
                'post_type'              => 'yl_place',
                'post_status'            => 'publish',
                'posts_per_page'         => -1,
                'fields'                 => 'ids',
                'no_found_rows'          => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
            )
        );
        foreach ( $candidate_ids as $candidate_id ) {
            $term_text = array();
            foreach ( array( 'place_category', 'district', 'near_school' ) as $taxonomy ) {
                $terms = wp_get_post_terms( $candidate_id, $taxonomy );
                if ( is_wp_error( $terms ) ) {
                    continue;
                }
                foreach ( $terms as $term ) {
                    $term_text[] = $term->name;
                    $term_text[] = $term->slug;
                }
            }
            $haystack = implode(
                ' ',
                array(
                    get_the_title( $candidate_id ),
                    get_post_field( 'post_excerpt', $candidate_id ),
                    get_post_meta( $candidate_id, '_yl_search_aliases', true ),
                    get_post_meta( $candidate_id, '_yl_address', true ),
                    get_post_meta( $candidate_id, '_yl_topic', true ),
                    implode( ' ', $term_text ),
                )
            );
            if ( false !== strpos( yl_normalize_search_text( $haystack ), $normalized_keyword ) ) {
                $ids[] = (int) $candidate_id;
            }
        }
    }

    return array_values( array_unique( array_map( 'absint', array_filter( $ids ) ) ) );
}

function yl_get_filtered_places( $args = array() ) {
    $defaults = array(
        'posts_per_page' => 12,
        'paged'          => max( 1, get_query_var( 'paged' ), isset( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1 ),
    );
    $args = wp_parse_args( $args, $defaults );

    $query_args = array(
        'post_type'      => 'yl_place',
        'post_status'    => 'publish',
        'posts_per_page' => (int) $args['posts_per_page'],
        'paged'          => (int) $args['paged'],
        'no_found_rows'  => false,
    );

    $tax_query = array();
    $district  = isset( $_GET['district'] ) ? sanitize_title( wp_unslash( $_GET['district'] ) ) : '';
    $category  = isset( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : '';
    $school    = isset( $_GET['school'] ) ? sanitize_title( wp_unslash( $_GET['school'] ) ) : '';
    if ( $district ) {
        $tax_query[] = array( 'taxonomy' => 'district', 'field' => 'slug', 'terms' => $district );
    }
    if ( $category ) {
        $tax_query[] = array( 'taxonomy' => 'place_category', 'field' => 'slug', 'terms' => $category );
    }
    if ( $school ) {
        $tax_query[] = array( 'taxonomy' => 'near_school', 'field' => 'slug', 'terms' => $school );
    }
    if ( $tax_query ) {
        $query_args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query );
    }

    $meta_query  = array( 'relation' => 'AND' );
    $recommended = isset( $_GET['recommended'] ) && '1' === (string) $_GET['recommended'];
    if ( $recommended ) {
        $meta_query[] = array( 'key' => '_yl_recommended', 'value' => '1', 'compare' => '=' );
    }
    $price      = isset( $_GET['price'] ) ? absint( $_GET['price'] ) : 0;
    if ( $price > 0 ) {
        $meta_query[] = array( 'key' => '_yl_price_from', 'value' => $price, 'compare' => '<=', 'type' => 'NUMERIC' );
        $meta_query[] = array( 'key' => '_yl_price_from', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC' );
    }

    $amenities = array( 'wifi', 'power_outlet', 'quiet', 'group_study', 'air_conditioning', 'parking', 'late_open' );
    foreach ( $amenities as $amenity ) {
        if ( isset( $_GET[ $amenity ] ) && '1' === (string) $_GET[ $amenity ] ) {
            $meta_query[] = array( 'key' => '_yl_' . $amenity, 'value' => '1', 'compare' => '=' );
        }
    }
    if ( count( $meta_query ) > 1 ) {
        $query_args['meta_query'] = $meta_query;
    }

    $keyword = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
    if ( $keyword ) {
        $keyword_ids = yl_find_place_ids_for_keyword( $keyword );
        $query_args['post__in'] = $keyword_ids ? $keyword_ids : array( 0 );
    }

    $sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'relevant';
    if ( 'newest' === $sort ) {
        $query_args['orderby'] = 'date';
        $query_args['order']   = 'DESC';
    } elseif ( 'rating' === $sort ) {
        $query_args['meta_key'] = '_yl_rating_average';
        $query_args['orderby']  = 'meta_value_num';
        $query_args['order']    = 'DESC';
    } else {
        // Recommendation mode uses the editorial priority; otherwise keep normal discovery ordering.
        if ( $recommended ) {
            $query_args['meta_key'] = '_yl_recommend_score';
            $query_args['orderby']  = array( 'meta_value_num' => 'DESC', 'date' => 'DESC' );
            $query_args['order']    = 'DESC';
        } elseif ( $keyword ) {
            $query_args['orderby'] = 'post__in';
            $query_args['order']   = 'ASC';
        } else {
            $query_args['orderby'] = array( 'menu_order' => 'ASC', 'date' => 'DESC' );
        }
    }

    return new WP_Query( $query_args );
}

function yl_quick_find_amenity_labels() {
    return array(
        'wifi'             => 'Có Wi‑Fi',
        'power_outlet'     => 'Có ổ điện',
        'quiet'            => 'Không gian yên tĩnh',
        'group_study'      => 'Phù hợp học/làm việc nhóm',
        'air_conditioning' => 'Có điều hòa',
        'parking'          => 'Có thông tin gửi xe',
        'late_open'        => 'Mở muộn',
    );
}

function yl_get_quick_find_results( $input = array(), $limit = 6 ) {
    $district  = isset( $input['district'] ) ? sanitize_title( $input['district'] ) : '';
    $category  = isset( $input['category'] ) ? sanitize_title( $input['category'] ) : '';
    $school    = isset( $input['school'] ) ? sanitize_title( $input['school'] ) : '';
    $price     = isset( $input['price'] ) ? absint( $input['price'] ) : 0;
    $radius    = isset( $input['radius'] ) ? (float) $input['radius'] : 0;
    $amenities = yl_quick_find_amenity_labels();
    $school_term = $school ? get_term_by( 'slug', $school, 'near_school' ) : false;
    if ( $school && ( ! $school_term || is_wp_error( $school_term ) ) ) {
        return array();
    }
    if ( ! $school_term ) {
        $radius = 0;
    }

    $query = new WP_Query( array( 'post_type'=>'yl_place','post_status'=>'publish','posts_per_page'=>-1,'no_found_rows'=>true,'update_post_term_cache'=>true,'update_post_meta_cache'=>true,'orderby'=>'date','order'=>'DESC' ) );
    $scored = array();
    foreach ( $query->posts as $post ) {
        $score=0; $matched_core=false; $amenity_match=0; $reasons=array();
        $place_districts=wp_get_post_terms($post->ID,'district',array('fields'=>'slugs'));
        $place_categories=wp_get_post_terms($post->ID,'place_category',array('fields'=>'slugs'));
        $place_schools=wp_get_post_terms($post->ID,'near_school',array('fields'=>'slugs'));
        if ( $category ) { if ( in_array($category,$place_categories,true) ) { $score+=8; $matched_core=true; $reasons[]='Đúng nhu cầu bạn chọn'; } else { continue; } }
        if ( $school && $school_term ) {
            $distance = yl_get_distance_to_school( $post->ID, $school_term );
            if ( null !== $distance ) {
                if ( $radius > 0 && $distance > $radius ) { continue; }
                if ( $distance <= .5 ) $score += 10; elseif ( $distance <= 1 ) $score += 9; elseif ( $distance <= 2 ) $score += 8; elseif ( $distance <= 5 ) $score += 6; else $score += 3;
                $matched_core=true; $reasons[] = yl_format_distance($distance) . ' từ ' . yl_get_school_meta($school_term,'short_name',$school_term->name);
            } elseif ( $radius > 0 ) {
                // A strict radius must never be satisfied by an unverified relationship-only match.
                continue;
            } elseif ( in_array($school,$place_schools,true) ) {
                $score+=7; $matched_core=true; $reasons[]='Có trong gợi ý quanh trường bạn chọn';
            } else { continue; }
        }
        if ( $district ) { if ( in_array($district,$place_districts,true) ) { $score+=6; $matched_core=true; $reasons[]='Đúng khu vực'; } else { continue; } }
        $place_price=absint(get_post_meta($post->ID,'_yl_price_from',true));
        if ( $price > 0 ) {
            // Budget is a trust-sensitive filter: unknown or over-budget prices must not be presented as a match.
            if ( $place_price <= 0 || $place_price > $price ) { continue; }
            $score += 5; $matched_core = true; $reasons[] = 'Mức giá đã ghi nhận nằm trong ngân sách';
        }
        $weights=array('power_outlet'=>3,'wifi'=>2,'quiet'=>2,'group_study'=>2,'air_conditioning'=>2,'parking'=>2,'late_open'=>2);
        $missing_required_amenity = false;
        foreach($amenities as $amenity=>$label) {
            if(!empty($input[$amenity]) && '1'===(string)$input[$amenity]) {
                if('1'===get_post_meta($post->ID,'_yl_'.$amenity,true)) {
                    $score+=isset($weights[$amenity])?$weights[$amenity]:2; $amenity_match++; $reasons[]=$label;
                } else {
                    $missing_required_amenity = true;
                    break;
                }
            }
        }
        if ( $missing_required_amenity ) { continue; }
        if('1'===get_post_meta($post->ID,'_yl_student_friendly',true)){ $score+=1; }
        $has_input=$district||$category||$school||$price||$radius;
        foreach(array_keys($amenities) as $amenity){ if(!empty($input[$amenity])){$has_input=true;break;} }
        if($has_input && $score<=0 && !$matched_core && 0===$amenity_match) continue;
        $scored[]=array('post'=>$post,'score'=>$score,'reasons'=>array_values(array_unique($reasons)));
    }
    usort($scored,function($a,$b){ if($a['score']===$b['score']) return $b['post']->ID<=>$a['post']->ID; return $b['score']<=>$a['score']; });
    return array_slice($scored,0,max(1,absint($limit)));
}

function yl_get_quick_find_relaxation_hints( $input ) {
    $hints = array();
    if ( ! empty( $input['radius'] ) && ! empty( $input['school'] ) ) {
        $hints[] = 'Thử bỏ giới hạn khoảng cách để xem cả địa điểm đã gắn quanh trường nhưng chưa có tọa độ xác minh.';
    }
    foreach ( yl_quick_find_amenity_labels() as $key => $label ) {
        if ( ! empty( $input[ $key ] ) ) {
            $hints[] = 'Thử bỏ tiêu chí “' . $label . '”.';
            break;
        }
    }
    $price = isset( $input['price'] ) ? absint( $input['price'] ) : 0;
    if ( $price && $price < 100000 ) {
        $next = $price <= 30000 ? '50K' : '100K';
        $hints[] = 'Thử nâng ngân sách lên tối đa ' . $next . '.';
    }
    if ( ! empty( $input['district'] ) && ! empty( $input['school'] ) ) {
        $hints[] = 'Thử giữ trường và bỏ giới hạn quận để mở rộng vùng lân cận.';
    }
    if ( ! $hints ) {
        $hints[] = 'Thử bỏ bớt một bộ lọc hoặc chọn khu vực lân cận.';
    }
    return array_slice( $hints, 0, 2 );
}

function yl_get_school_labels() {
    $terms=get_terms(array('taxonomy'=>'near_school','hide_empty'=>false));
    if(!is_wp_error($terms)&&$terms){$labels=array();foreach($terms as $term){$labels[$term->slug]=yl_get_school_meta($term,'short_name',$term->name);}return $labels;}
    $labels=array(); foreach(yl_school_seed_data() as $row){ if(!empty($row['slug'])) $labels[$row['slug']]=!empty($row['short_name'])?$row['short_name']:$row['name']; } return $labels;
}
