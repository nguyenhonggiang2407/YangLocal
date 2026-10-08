<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Real-place photo layer.
 *
 * Policy in v7.23:
 * - Cards never use generic/close-match imagery as if it were the venue.
 * - Curated exact venue photos can be shown immediately.
 * - Google Places photos are fetched only when the user opens the photo viewer.
 * - Google photo resource names are never cached; Place IDs may be cached in post meta.
 */

function yl_place_photos_api_key() {
    return trim( (string) get_option( 'yl_google_places_api_key', '' ) );
}

function yl_place_photos_settings_init() {
    register_setting(
        'yl_place_photos_settings',
        'yl_google_places_api_key',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        )
    );
}
add_action( 'admin_init', 'yl_place_photos_settings_init' );

function yl_place_photos_admin_menu() {
    add_management_page(
        'YangLocal · Ảnh thật địa điểm',
        'YangLocal · Ảnh thật',
        'manage_options',
        'yanglocal-place-photos',
        'yl_place_photos_settings_page'
    );
}
add_action( 'admin_menu', 'yl_place_photos_admin_menu' );

/**
 * Only auto-copy remote media when the seed explicitly points to a reusable
 * source. For v7.23 this is conservative by design: Wikimedia upload URLs are
 * allowed only when the recorded source/verification page is Wikimedia
 * Commons. Other domains remain discovery/provenance links until a human has
 * confirmed reuse rights.
 */
function yl_place_photo_source_is_auto_localizable( $post_id, $url ) {
    $url = trim( (string) $url );
    if ( ! preg_match( '#^https?://#i', $url ) ) {
        return false;
    }
    $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
    $source_page = strtolower( trim( (string) get_post_meta( $post_id, '_yl_image_source_url', true ) ) );
    $verify_page = strtolower( trim( (string) get_post_meta( $post_id, '_yl_image_verification_url', true ) ) );
    if ( 'upload.wikimedia.org' === $host && ( false !== strpos( $source_page, 'commons.wikimedia.org/' ) || false !== strpos( $verify_page, 'commons.wikimedia.org/' ) ) ) {
        return true;
    }

    $reuse = strtolower( trim( (string) get_post_meta( $post_id, '_yl_image_reuse_status', true ) ) );
    return in_array( $reuse, array( 'licensed', 'permission', 'public_domain', 'cc', 'cc-by', 'cc-by-sa' ), true );
}

/**
 * Curated exact-photo localizer (v7.23).
 *
 * Google Places photos stay live through the API because their photo resource
 * names are not a permanent media source. Curated exact photo URLs stored in
 * YangLocal seed/meta can be copied into the WordPress Media Library so cards
 * and galleries load from this host.
 */
function yl_place_photo_curated_source_urls( $post_id ) {
    if ( 'yl_place' !== get_post_type( $post_id ) || 'exact_venue' !== get_post_meta( $post_id, '_yl_image_match_level', true ) ) {
        return array();
    }

    // Only sources with an explicit reuse status may be copied into Media Library.
    // Official pages remain useful provenance, but are not treated as permission to re-host their images.
    $reuse = strtolower( trim( (string) get_post_meta( $post_id, '_yl_image_reuse_status', true ) ) );
    if ( ! in_array( $reuse, array( 'licensed', 'public_domain', 'permission', 'cc', 'cc-by', 'cc-by-sa' ), true ) ) {
        return array();
    }

    $urls = array();
    $demo = trim( (string) get_post_meta( $post_id, '_yl_demo_image_url', true ) );
    if ( yl_place_photo_source_is_auto_localizable( $post_id, $demo ) ) {
        $urls[] = esc_url_raw( $demo );
    }

    $external = trim( (string) get_post_meta( $post_id, '_yl_external_gallery_urls', true ) );
    if ( $external ) {
        foreach ( preg_split( '/\r\n|\r|\n/', $external ) as $url ) {
            $url = trim( $url );
            if ( yl_place_photo_source_is_auto_localizable( $post_id, $url ) ) {
                $urls[] = esc_url_raw( $url );
            }
        }
    }

    return array_slice( array_values( array_unique( array_filter( $urls ) ) ), 0, 6 );
}

/**
 * Return reusable exact-venue external gallery URLs that are safe to expose
 * before they are copied into Media Library. v7.23 intentionally restricts
 * direct galleries to Wikimedia Commons/upload URLs and a confirmed reuse
 * status; generic/official discovery pages are never treated as image files.
 */
function yl_place_photo_external_gallery_urls( $post_id ) {
    if ( 'yl_place' !== get_post_type( $post_id ) || 'exact_venue' !== get_post_meta( $post_id, '_yl_image_match_level', true ) ) {
        return array();
    }
    $post = get_post( $post_id );
    $primary_local_id = absint( get_post_meta( $post_id, '_yl_primary_local_attachment_id', true ) );
    if ( $post && in_array( $post->post_name, yl_place_photo_priority_slugs(), true ) && $primary_local_id && wp_attachment_is_image( $primary_local_id ) ) {
        // R15: priority cards/galleries stay fully local after their primary fix.
        return array();
    }
    $reuse = strtolower( trim( (string) get_post_meta( $post_id, '_yl_image_reuse_status', true ) ) );
    if ( ! in_array( $reuse, array( 'licensed', 'public_domain', 'permission', 'cc', 'cc-by', 'cc-by-sa' ), true ) ) {
        return array();
    }
    $raw = trim( (string) get_post_meta( $post_id, '_yl_external_gallery_urls', true ) );
    if ( ! $raw ) {
        return array();
    }
    $out = array();
    $localized = function_exists( 'yl_place_photo_localized_source_urls' ) ? yl_place_photo_localized_source_urls( $post_id ) : array();
    foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $url ) {
        $url = trim( $url );
        if ( ! preg_match( '#^https?://#i', $url ) ) {
            continue;
        }
        $parts = wp_parse_url( $url );
        $host  = isset( $parts['host'] ) ? strtolower( (string) $parts['host'] ) : '';
        $path  = isset( $parts['path'] ) ? (string) $parts['path'] : '';
        $is_commons_redirect = 'commons.wikimedia.org' === $host && 0 === strpos( $path, '/wiki/Special:Redirect/file/' );
        if ( 'upload.wikimedia.org' !== $host && ! $is_commons_redirect ) {
            continue;
        }
        if ( ! yl_place_photo_source_is_auto_localizable( $post_id, $url ) || in_array( $url, $localized, true ) ) {
            continue;
        }
        $out[] = esc_url_raw( $url );
        if ( count( $out ) >= 6 ) {
            break;
        }
    }
    return array_values( array_unique( array_filter( $out ) ) );
}

/** Resolve a Wikimedia image URL back to its file page for attribution/license. */
function yl_place_photo_source_page_for_url( $post_id, $url ) {
    $url   = trim( (string) $url );
    $parts = wp_parse_url( $url );
    $host  = isset( $parts['host'] ) ? strtolower( (string) $parts['host'] ) : '';
    $path  = isset( $parts['path'] ) ? rawurldecode( (string) $parts['path'] ) : '';
    $filename = '';

    if ( 'commons.wikimedia.org' === $host && 0 === strpos( $path, '/wiki/Special:Redirect/file/' ) ) {
        $filename = substr( $path, strlen( '/wiki/Special:Redirect/file/' ) );
    } elseif ( 'upload.wikimedia.org' === $host ) {
        $segments = array_values( array_filter( explode( '/', trim( $path, '/' ) ), 'strlen' ) );
        $thumb = array_search( 'thumb', $segments, true );
        if ( false !== $thumb && isset( $segments[ $thumb + 3 ] ) ) {
            $filename = $segments[ $thumb + 3 ];
        } elseif ( count( $segments ) >= 5 ) {
            $filename = $segments[ count( $segments ) - 1 ];
        }
    }

    if ( $filename ) {
        return esc_url_raw( 'https://commons.wikimedia.org/wiki/File:' . rawurlencode( str_replace( ' ', '_', $filename ) ) );
    }
    return esc_url_raw( get_post_meta( $post_id, '_yl_image_source_url', true ) );
}

function yl_place_photo_localized_source_urls( $post_id ) {
    $raw = trim( (string) get_post_meta( $post_id, '_yl_localized_source_urls', true ) );
    if ( ! $raw ) {
        return array();
    }
    return array_values( array_unique( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) ) ) );
}

function yl_place_has_local_exact_photo( $post_id ) {
    if ( 'exact_venue' !== get_post_meta( $post_id, '_yl_image_match_level', true ) ) {
        return false;
    }
    if ( has_post_thumbnail( $post_id ) ) {
        return true;
    }
    return (bool) trim( (string) get_post_meta( $post_id, '_yl_gallery_ids', true ) );
}

function yl_place_photo_localization_stats() {
    $ids = get_posts(
        array(
            'post_type'      => 'yl_place',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
        )
    );

    $stats = array(
        'total'       => count( $ids ),
        'exact'       => 0,
        'local_exact' => 0,
        'pending'     => array(),
        'maps_fallback' => 0,
        'rights_review' => 0,
    );

    foreach ( $ids as $id ) {
        if ( 'exact_venue' !== get_post_meta( $id, '_yl_image_match_level', true ) ) {
            $stats['maps_fallback']++;
            continue;
        }
        $stats['exact']++;
        if ( yl_place_has_local_exact_photo( $id ) ) {
            $stats['local_exact']++;
        }

        $sources   = yl_place_photo_curated_source_urls( $id );
        $localized = yl_place_photo_localized_source_urls( $id );
        $missing   = array_values( array_diff( $sources, $localized ) );
        if ( $missing ) {
            $stats['pending'][] = (int) $id;
        } elseif ( ! yl_place_has_local_exact_photo( $id ) ) {
            $remote_demo = trim( (string) get_post_meta( $id, '_yl_demo_image_url', true ) );
            if ( preg_match( '#^https?://#i', $remote_demo ) ) {
                $stats['rights_review']++;
            }
        }
    }
    return $stats;
}

function yl_place_photo_image_extension_from_mime( $mime ) {
    $map = array(
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        'image/avif' => 'avif',
    );
    return isset( $map[ $mime ] ) ? $map[ $mime ] : '';
}

function yl_place_photo_sideload_one( $post_id, $url, $index = 0 ) {
    if ( ! current_user_can( 'upload_files' ) ) {
        return new WP_Error( 'forbidden', 'Tài khoản hiện tại không có quyền upload ảnh.' );
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url( $url, 25 );
    if ( is_wp_error( $tmp ) ) {
        return $tmp;
    }

    $size = @filesize( $tmp );
    if ( ! $size || $size > 12 * MB_IN_BYTES ) {
        @unlink( $tmp );
        return new WP_Error( 'invalid_size', 'Ảnh rỗng hoặc vượt quá 12 MB.' );
    }

    $mime = function_exists( 'wp_get_image_mime' ) ? wp_get_image_mime( $tmp ) : '';
    $ext  = yl_place_photo_image_extension_from_mime( $mime );
    if ( ! $ext ) {
        @unlink( $tmp );
        return new WP_Error( 'invalid_image', 'URL không trả về file ảnh hợp lệ.' );
    }

    $file_array = array(
        'name'     => sanitize_file_name( sanitize_title( get_the_title( $post_id ) ) . '-yanglocal-' . ( absint( $index ) + 1 ) . '.' . $ext ),
        'tmp_name' => $tmp,
    );

    $attachment_id = media_handle_sideload(
        $file_array,
        $post_id,
        get_the_title( $post_id ) . ' — ảnh địa điểm'
    );

    if ( is_wp_error( $attachment_id ) ) {
        @unlink( $tmp );
        return $attachment_id;
    }

    $source_page = yl_place_photo_source_page_for_url( $post_id, $url );
    update_post_meta( $attachment_id, '_yl_original_source_url', esc_url_raw( $url ) );
    update_post_meta( $attachment_id, '_yl_original_source_page', $source_page );
    update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Ảnh địa điểm ' . get_the_title( $post_id ) );
    if ( $source_page && false !== strpos( strtolower( $source_page ), 'commons.wikimedia.org/' ) ) {
        wp_update_post(
            array(
                'ID'           => $attachment_id,
                'post_excerpt' => 'Nguồn: Wikimedia Commons — xem trang nguồn của ảnh để biết tác giả và giấy phép cụ thể.',
            )
        );
    }

    return (int) $attachment_id;
}

/**
 * Priority covers that must be stable on production even when Wikimedia
 * hotlinks/redirects are slow. Kept intentionally narrow for the R15 image fix.
 */
function yl_place_photo_priority_slugs() {
    // R16 P3: all high-priority reusable Commons exact-venue covers.
    // Each item is downloaded by the existing one-request-per-image admin workflow
    // to stay safe on HelioHost/shared-host execution limits.
    return array(
        'van-mieu-quoc-tu-giam',
        'cau-long-bien',
        'aeon-mall-long-bien',
        'vincom-mega-mall-royal-city',
        'trang-tien-plaza',
        'the-garden-shopping-center',
        'vincom-mega-mall-times-city',
        'vincom-center-nguyen-chi-thanh',
        'vincom-center-ba-trieu',
        'indochina-plaza-hanoi',
        'quang-truong-ba-dinh',
        'khu-di-tich-chu-tich-ho-chi-minh-phu-chu-tich',
        'bao-tang-ho-chi-minh',
        'chua-mot-cot',
        'hoang-thanh-thang-long',
        'di-tich-nha-tu-hoa-lo',
        'den-ngoc-son',
        'o-quan-chuong',
        'cot-co-ha-noi',
        'bao-tang-lich-su-quoc-gia',
        'bao-tang-lich-su-quan-su-viet-nam',
        'bao-tang-dan-toc-hoc-viet-nam',
        'bao-tang-phu-nu-viet-nam',
        'bao-tang-my-thuat-viet-nam',
        'bao-tang-ha-noi',
        'bao-tang-chien-thang-b52',
        'go-dong-da',
        'cho-dem-pho-co-ha-noi',
        'pho-sach-ha-noi-19-12',
        'nha-tho-lon-ha-noi',
        'ho-tay-ha-noi',
        'the-note-coffee-luong-van-can',
        'bun-cha-huong-lien',
    );
}

function yl_place_photo_priority_ids() {
    $ids = array();
    foreach ( yl_place_photo_priority_slugs() as $slug ) {
        $post = get_page_by_path( $slug, OBJECT, 'yl_place' );
        if ( $post && 'publish' === $post->post_status ) {
            $ids[] = (int) $post->ID;
        }
    }
    return array_values( array_unique( array_filter( $ids ) ) );
}

function yl_place_photo_find_attachment_for_source( $post_id, $url ) {
    $ids = get_posts(
        array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_parent'    => absint( $post_id ),
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_yl_original_source_url',
            'meta_value'     => esc_url_raw( $url ),
            'no_found_rows'  => true,
        )
    );
    return $ids ? absint( $ids[0] ) : 0;
}

function yl_place_photo_set_primary_local_attachment( $post_id, $attachment_id, $source_url, $force_cover = false ) {
    $post_id       = absint( $post_id );
    $attachment_id = absint( $attachment_id );
    if ( ! $post_id || ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
        return false;
    }

    update_post_meta( $post_id, '_yl_primary_local_attachment_id', $attachment_id );

    $gallery = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, '_yl_gallery_ids', true ) ) ) );
    $gallery = array_values( array_unique( array_merge( array( $attachment_id ), $gallery ) ) );
    update_post_meta( $post_id, '_yl_gallery_ids', implode( ',', $gallery ) );

    $localized = yl_place_photo_localized_source_urls( $post_id );
    if ( $source_url ) {
        $localized[] = esc_url_raw( $source_url );
    }
    if ( $localized ) {
        update_post_meta( $post_id, '_yl_localized_source_urls', implode( "\n", array_values( array_unique( array_filter( $localized ) ) ) ) );
    }

    $curated = 'curated_demo' === (string) get_post_meta( $post_id, '_yl_cover_preference', true );
    if ( $force_cover || $curated || ! has_post_thumbnail( $post_id ) ) {
        set_post_thumbnail( $post_id, $attachment_id );
    }
    update_post_meta( $post_id, '_yl_image_localized_at', current_time( 'mysql' ) );
    return true;
}

/**
 * Copy only the selected primary exact image to Media Library. One image per
 * request keeps this safe on HelioHost/free shared hosting time limits.
 */
function yl_place_photo_localize_primary( $post_id, $force_cover = false ) {
    $post_id = absint( $post_id );
    if ( ! $post_id || 'yl_place' !== get_post_type( $post_id ) ) {
        return new WP_Error( 'invalid_place', 'Địa điểm không hợp lệ.' );
    }
    if ( 'exact_venue' !== get_post_meta( $post_id, '_yl_image_match_level', true ) ) {
        return new WP_Error( 'not_exact', 'Địa điểm chưa được xác minh ảnh exact.' );
    }

    $source = trim( (string) get_post_meta( $post_id, '_yl_demo_image_url', true ) );
    if ( ! $source || ! preg_match( '#^https?://#i', $source ) ) {
        return new WP_Error( 'no_remote_primary', 'Ảnh chính không phải nguồn remote cần tải.' );
    }
    if ( ! yl_place_photo_source_is_auto_localizable( $post_id, $source ) ) {
        return new WP_Error( 'rights_review', 'Nguồn ảnh chính chưa đủ điều kiện để tự sao chép về Media Library.' );
    }

    $attachment_id = absint( get_post_meta( $post_id, '_yl_primary_local_attachment_id', true ) );
    if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
        $attachment_id = yl_place_photo_find_attachment_for_source( $post_id, $source );
    }

    if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
        yl_place_photo_set_primary_local_attachment( $post_id, $attachment_id, $source, $force_cover );
        return array(
            'post_id'       => $post_id,
            'title'         => get_the_title( $post_id ),
            'created'       => 0,
            'attachment_id' => $attachment_id,
            'local'         => true,
        );
    }

    $attachment_id = yl_place_photo_sideload_one( $post_id, $source, 0 );
    if ( is_wp_error( $attachment_id ) ) {
        return $attachment_id;
    }

    yl_place_photo_set_primary_local_attachment( $post_id, $attachment_id, $source, $force_cover );
    return array(
        'post_id'       => $post_id,
        'title'         => get_the_title( $post_id ),
        'created'       => 1,
        'attachment_id' => $attachment_id,
        'local'         => true,
    );
}

function yl_place_photo_localize_primary_ajax() {
    check_ajax_referer( 'yl_localize_priority_photo', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'upload_files' ) ) {
        wp_send_json_error( array( 'message' => 'Bạn không có quyền thực hiện thao tác này.' ), 403 );
    }

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( ! in_array( $post_id, yl_place_photo_priority_ids(), true ) ) {
        wp_send_json_error( array( 'message' => 'Địa điểm không nằm trong nhóm ảnh ưu tiên R16.' ), 400 );
    }

    $result = yl_place_photo_localize_primary( $post_id, true );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }
    wp_send_json_success( $result );
}
add_action( 'wp_ajax_yl_localize_priority_photo', 'yl_place_photo_localize_primary_ajax' );

function yl_place_photo_localize_post( $post_id ) {
    $post_id = absint( $post_id );
    if ( ! $post_id || 'yl_place' !== get_post_type( $post_id ) ) {
        return new WP_Error( 'invalid_place', 'Địa điểm không hợp lệ.' );
    }
    if ( 'exact_venue' !== get_post_meta( $post_id, '_yl_image_match_level', true ) ) {
        return new WP_Error( 'not_exact', 'Địa điểm chưa được xác minh ảnh exact.' );
    }

    $created_count = 0;
    $errors        = array();
    $primary       = trim( (string) get_post_meta( $post_id, '_yl_demo_image_url', true ) );
    if ( $primary && yl_place_photo_source_is_auto_localizable( $post_id, $primary ) ) {
        $force_primary = 'curated_demo' === (string) get_post_meta( $post_id, '_yl_cover_preference', true ) || in_array( $post_id, yl_place_photo_priority_ids(), true );
        $primary_result = yl_place_photo_localize_primary( $post_id, $force_primary );
        if ( is_wp_error( $primary_result ) ) {
            $errors[] = $primary_result->get_error_message();
        } else {
            $created_count += isset( $primary_result['created'] ) ? absint( $primary_result['created'] ) : 0;
        }
    }

    // Re-read state because the primary helper may have added one local image.
    $sources   = yl_place_photo_curated_source_urls( $post_id );
    $localized = yl_place_photo_localized_source_urls( $post_id );
    $gallery   = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, '_yl_gallery_ids', true ) ) ) );
    $created   = array();

    foreach ( $sources as $index => $url ) {
        if ( in_array( $url, $localized, true ) ) {
            continue;
        }
        $attachment_id = yl_place_photo_sideload_one( $post_id, $url, $index );
        if ( is_wp_error( $attachment_id ) ) {
            $errors[] = $attachment_id->get_error_message();
            continue;
        }
        $created[]   = $attachment_id;
        $gallery[]   = $attachment_id;
        $localized[] = $url;
        if ( ! has_post_thumbnail( $post_id ) ) {
            set_post_thumbnail( $post_id, $attachment_id );
        }
    }

    if ( $gallery ) {
        update_post_meta( $post_id, '_yl_gallery_ids', implode( ',', array_values( array_unique( array_map( 'absint', $gallery ) ) ) ) );
    }
    if ( $localized ) {
        update_post_meta( $post_id, '_yl_localized_source_urls', implode( "\n", array_values( array_unique( $localized ) ) ) );
    }
    if ( $created || $created_count ) {
        update_post_meta( $post_id, '_yl_image_localized_at', current_time( 'mysql' ) );
    }

    return array(
        'post_id' => $post_id,
        'title'   => get_the_title( $post_id ),
        'created' => $created_count + count( $created ),
        'errors'  => $errors,
        'local'   => yl_place_has_local_exact_photo( $post_id ),
    );
}

function yl_place_photo_localize_ajax() {
    check_ajax_referer( 'yl_localize_exact_photo', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'upload_files' ) ) {
        wp_send_json_error( array( 'message' => 'Bạn không có quyền thực hiện thao tác này.' ), 403 );
    }
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    $result  = yl_place_photo_localize_post( $post_id );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }
    wp_send_json_success( $result );
}
add_action( 'wp_ajax_yl_localize_exact_photo', 'yl_place_photo_localize_ajax' );


function yl_place_photos_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $key = yl_place_photos_api_key();
    $photo_stats = yl_place_photo_localization_stats();
    $locked_ids = get_posts( array(
        'post_type'      => 'yl_place',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array( array( 'key' => '_yl_google_place_id', 'compare' => 'EXISTS' ) ),
    ) );
    $locked_ids = array_values( array_filter( $locked_ids, static function( $id ) { return (bool) trim( (string) get_post_meta( $id, '_yl_google_place_id', true ) ); } ) );
    ?>
    <div class="wrap">
        <h1>YangLocal · Ảnh thật địa điểm v7.23</h1>
        <p><strong>Mặc định an toàn:</strong> nếu địa điểm chưa có ảnh đúng địa điểm, YangLocal không còn dùng ảnh café/món ăn minh họa để giả làm ảnh quán. Người dùng bấm vào khối ảnh để mở gallery ảnh thật, Google Maps và các nguồn tìm ảnh.</p>
        <p>Nếu bạn nhập Google Maps Platform API key có bật <strong>Places API (New)</strong>, cửa sổ ảnh sẽ tự lấy tối đa 6 ảnh thật từ Google Places khi người dùng bấm xem. API key chỉ dùng phía server và không được in ra HTML.</p>
        <p><strong><?php echo esc_html( count( $locked_ids ) ); ?></strong> địa điểm hiện đã có Google Place ID khóa sẵn trong dữ liệu; các địa điểm còn lại sẽ được Text Search đối chiếu theo <strong>tên + địa chỉ</strong> khi mở gallery, sau đó lưu Place ID nếu điểm khớp đủ cao.</p>
        <form method="post" action="options.php">
            <?php settings_fields( 'yl_place_photos_settings' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="yl_google_places_api_key">Google Places API key</label></th>
                    <td>
                        <input id="yl_google_places_api_key" name="yl_google_places_api_key" type="password" class="regular-text" value="<?php echo esc_attr( $key ); ?>" autocomplete="new-password">
                        <p class="description">Không bắt buộc. Google chỉ dùng để bổ sung album live; ảnh exact đã tuyển chọn có thể tải hẳn vào Media Library ở phần bên dưới.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Lưu cấu hình ảnh' ); ?>
        </form>

        <hr style="margin:28px 0;">
        <h2>Tải ảnh exact về chính host YangLocal</h2>
        <p>Đây là bước giúp card không còn phụ thuộc vào ảnh hotlink/Google Maps. YangLocal chỉ tự tải URL <strong>exact_venue</strong> khi nguồn có điều kiện tái sử dụng đủ rõ (mặc định: Wikimedia Commons hoặc meta quyền sử dụng đã xác nhận); ảnh sẽ vào <strong>Media Library</strong>, ảnh đầu tiên thành ảnh đại diện và các ảnh còn lại vào gallery.</p>
        <p>
            <strong><?php echo esc_html( $photo_stats['local_exact'] ); ?></strong> địa điểm đã có ảnh exact local ·
            <strong><?php echo esc_html( count( $photo_stats['pending'] ) ); ?></strong> địa điểm exact có nguồn tái sử dụng đang chờ tải ·
            <strong><?php echo esc_html( $photo_stats['rights_review'] ); ?></strong> nguồn exact cần rà quyền trước khi copy ·
            <strong><?php echo esc_html( $photo_stats['maps_fallback'] ); ?></strong> địa điểm chưa đủ bằng chứng exact nên vẫn dùng nút xem album thật.
        </p>
        <button type="button" class="button button-primary" id="yl-localize-exact-photos"<?php disabled( empty( $photo_stats['pending'] ) ); ?>>Tải ảnh exact về Media Library</button>
        <span id="yl-localize-photo-status" style="margin-left:10px;"></span>
        <div id="yl-localize-photo-log" style="margin-top:12px;max-width:860px;max-height:220px;overflow:auto;background:#fff;border:1px solid #dcdcde;padding:10px 12px;display:none;"></div>
        <script>
        (function(){
            var button = document.getElementById('yl-localize-exact-photos');
            if (!button) return;
            var ids = <?php echo wp_json_encode( array_values( $photo_stats['pending'] ) ); ?>;
            var nonce = <?php echo wp_json_encode( wp_create_nonce( 'yl_localize_exact_photo' ) ); ?>;
            var status = document.getElementById('yl-localize-photo-status');
            var log = document.getElementById('yl-localize-photo-log');

            function addLog(text, ok) {
                log.style.display = 'block';
                var row = document.createElement('div');
                row.textContent = text;
                row.style.padding = '3px 0';
                row.style.color = ok ? '#1d6b3c' : '#b32d2e';
                log.appendChild(row);
                log.scrollTop = log.scrollHeight;
            }

            button.addEventListener('click', async function(){
                if (!ids.length) return;
                button.disabled = true;
                var success = 0, failed = 0;
                for (var i = 0; i < ids.length; i++) {
                    status.textContent = 'Đang tải ' + (i + 1) + '/' + ids.length + '…';
                    var body = new URLSearchParams();
                    body.set('action','yl_localize_exact_photo');
                    body.set('nonce',nonce);
                    body.set('post_id',String(ids[i]));
                    try {
                        var response = await fetch(ajaxurl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString()});
                        var json = await response.json();
                        if (json.success) {
                            success++;
                            var created = json.data && json.data.created ? json.data.created : 0;
                            var errors = json.data && json.data.errors ? json.data.errors : [];
                            addLog('✓ ' + json.data.title + ' — tải ' + created + ' ảnh' + (errors.length ? ' · ' + errors.join('; ') : ''), true);
                        } else {
                            failed++;
                            addLog('✕ ID ' + ids[i] + ' — ' + ((json.data && json.data.message) || 'Không tải được'), false);
                        }
                    } catch (e) {
                        failed++;
                        addLog('✕ ID ' + ids[i] + ' — lỗi kết nối: ' + e.message, false);
                    }
                }
                status.textContent = 'Hoàn tất: ' + success + ' địa điểm OK' + (failed ? ', ' + failed + ' lỗi' : '') + '. Tải lại trang để xem thống kê mới.';
                button.textContent = 'Đã chạy tải ảnh exact';
            });
        })();
        </script>
    </div>
    <?php
}

function yl_place_photo_manual_urls( $post_id ) {
    $photos = array();
    $level  = get_post_meta( $post_id, '_yl_image_match_level', true );
    $preference = (string) get_post_meta( $post_id, '_yl_cover_preference', true );

    // R15: if the curated primary was localized, fullscreen must open that local
    // attachment first. Remote Wikimedia stays only as a pre-localization fallback.
    $primary_local_id = absint( get_post_meta( $post_id, '_yl_primary_local_attachment_id', true ) );
    $has_primary_local = 'exact_venue' === $level && $primary_local_id && wp_attachment_is_image( $primary_local_id );
    if ( 'exact_venue' === $level && 'curated_demo' === $preference ) {
        if ( $has_primary_local ) {
            $primary_url  = wp_get_attachment_image_url( $primary_local_id, 'large' );
            $primary_full = wp_get_attachment_image_url( $primary_local_id, 'full' );
            $source_page  = esc_url_raw( get_post_meta( $primary_local_id, '_yl_original_source_page', true ) );
            if ( $primary_url ) {
                $photos[] = array(
                    'url'         => $primary_url,
                    'full_url'    => $primary_full ?: $primary_url,
                    'caption'     => sanitize_text_field( get_post_meta( $post_id, '_yl_image_caption', true ) ),
                    'license'     => sanitize_text_field( get_post_meta( $post_id, '_yl_image_license', true ) ),
                    'open_url'    => $source_page ?: wp_get_attachment_url( $primary_local_id ),
                    'attribution' => trim( (string) get_post_meta( $post_id, '_yl_image_credit', true ) ),
                    'source'      => $source_page ? 'Wikimedia Commons · YangLocal Media Library' : 'YangLocal Media Library',
                );
            }
        } elseif ( function_exists( 'yl_place_safe_remote_photo_url' ) ) {
            $curated = yl_place_safe_remote_photo_url( $post_id );
            if ( $curated ) {
                $photos[] = array(
                    'url'         => $curated,
                    'full_url'    => $curated,
                    'caption'     => sanitize_text_field( get_post_meta( $post_id, '_yl_image_caption', true ) ),
                    'license'     => sanitize_text_field( get_post_meta( $post_id, '_yl_image_license', true ) ),
                    'open_url'    => esc_url_raw( get_post_meta( $post_id, '_yl_image_source_url', true ) ?: $curated ),
                    'attribution' => trim( (string) get_post_meta( $post_id, '_yl_image_credit', true ) ),
                    'source'      => 'Wikimedia Commons',
                );
            }
        }
    }

    // Even Media Library images are shown as venue photos only after the place is explicitly marked exact_venue.
    $gallery_ids = get_post_meta( $post_id, '_yl_gallery_ids', true );
    if ( 'exact_venue' === $level && $gallery_ids ) {
        foreach ( array_filter( array_map( 'absint', explode( ',', $gallery_ids ) ) ) as $attachment_id ) {
            if ( $has_primary_local && $attachment_id === $primary_local_id ) {
                continue;
            }
            $url = wp_get_attachment_image_url( $attachment_id, 'large' );
            $full_url = wp_get_attachment_image_url( $attachment_id, 'full' );
            if ( $url ) {
                $source_page = esc_url_raw( get_post_meta( $attachment_id, '_yl_original_source_page', true ) );
                $photos[] = array(
                    'url'         => $url,
                    'full_url'    => $full_url ?: $url,
                    'open_url'    => $source_page ?: wp_get_attachment_url( $attachment_id ),
                    'attribution' => $source_page ? 'Nguồn và giấy phép: Wikimedia Commons (xem trang nguồn).' : '',
                    'source'      => $source_page ? 'Wikimedia Commons · YangLocal Media Library' : 'YangLocal Media Library',
                );
            }
        }
    }

    $featured_id = get_post_thumbnail_id( $post_id );
    $featured = get_the_post_thumbnail_url( $post_id, 'large' );
    $featured_full = get_the_post_thumbnail_url( $post_id, 'full' );
    if ( $featured && 'exact_venue' === $level && ( ! $has_primary_local || absint( $featured_id ) !== $primary_local_id ) ) {
        $photos[] = array(
            'url'         => $featured,
            'full_url'    => $featured_full ?: $featured,
            'open_url'    => $featured,
            'attribution' => '',
            'source'      => 'YangLocal',
        );
    }

    $has_local_media = (bool) ( $gallery_ids || $featured );
    if ( 'exact_venue' === $level && ! $has_local_media ) {
        $demo = trim( (string) get_post_meta( $post_id, '_yl_demo_image_url', true ) );
        if ( $demo && ! preg_match( '#^https?://#i', $demo ) && function_exists( 'get_template_directory' ) ) {
            $relative = 'assets/images/demo/' . ltrim( $demo, '/' );
            if ( file_exists( trailingslashit( get_template_directory() ) . $relative ) ) {
                $photos[] = array(
                    'url'         => trailingslashit( get_template_directory_uri() ) . $relative,
                    'full_url'    => trailingslashit( get_template_directory_uri() ) . $relative,
                    'caption'     => sanitize_text_field( get_post_meta( $post_id, '_yl_image_caption', true ) ),
                    'license'     => sanitize_text_field( get_post_meta( $post_id, '_yl_image_license', true ) ),
                    'open_url'    => esc_url_raw( get_post_meta( $post_id, '_yl_image_source_url', true ) ?: '' ),
                    'attribution' => '',
                    'source'      => 'YangLocal local media',
                );
            }
        }
    }

    if ( 'exact_venue' === $level && ! $has_local_media && function_exists( 'yl_place_safe_remote_photo_url' ) ) {
        $safe_remote = yl_place_safe_remote_photo_url( $post_id );
        if ( $safe_remote ) {
            $credit = trim( (string) get_post_meta( $post_id, '_yl_image_credit', true ) );
            $photos[] = array(
                'url'         => $safe_remote,
                'full_url'    => $safe_remote,
                'caption'     => sanitize_text_field( get_post_meta( $post_id, '_yl_image_caption', true ) ),
                'license'     => sanitize_text_field( get_post_meta( $post_id, '_yl_image_license', true ) ),
                'open_url'    => esc_url_raw( get_post_meta( $post_id, '_yl_image_source_url', true ) ?: $safe_remote ),
                'attribution' => $credit,
                'source'      => 'Wikimedia Commons',
            );
        }
    }

    if ( 'exact_venue' === $level ) {
        foreach ( yl_place_photo_external_gallery_urls( $post_id ) as $external_url ) {
            $photos[] = array(
                'url'         => $external_url,
                'full_url'    => $external_url,
                'caption'     => sanitize_text_field( get_post_meta( $post_id, '_yl_image_caption', true ) ),
                'license'     => sanitize_text_field( get_post_meta( $post_id, '_yl_image_license', true ) ),
                'open_url'    => yl_place_photo_source_page_for_url( $post_id, $external_url ),
                'attribution' => 'Xem tác giả và giấy phép cụ thể tại trang nguồn.',
                'source'      => 'Wikimedia Commons',
            );
        }
    }

    $seen = array();
    $out  = array();
    foreach ( $photos as $photo ) {
        $url = isset( $photo['url'] ) ? $photo['url'] : '';
        if ( ! $url || isset( $seen[ $url ] ) ) {
            continue;
        }
        $seen[ $url ] = true;
        $out[] = $photo;
        if ( count( $out ) >= 6 ) {
            break;
        }
    }
    return $out;
}

/**
 * Give a Google Places candidate a conservative score before trusting it.
 * The critical rule is: if our record contains a street number, a candidate
 * with a different street number must not silently become the selected place.
 */
function yl_place_photo_google_candidate_score( $post_id, $place ) {
    if ( ! is_array( $place ) ) {
        return 0;
    }

    $raw_title = (string) get_the_title( $post_id );
    $title_parts = preg_split( '/\s+(?:-|–|—)\s+/u', $raw_title );
    $raw_title_core = ! empty( $title_parts[0] ) ? trim( $title_parts[0] ) : $raw_title;
    $title   = function_exists( 'yl_normalize_search_text' ) ? yl_normalize_search_text( $raw_title ) : strtolower( $raw_title );
    $address = function_exists( 'yl_normalize_search_text' ) ? yl_normalize_search_text( get_post_meta( $post_id, '_yl_address', true ) ) : strtolower( get_post_meta( $post_id, '_yl_address', true ) );

    $candidate_name = '';
    if ( ! empty( $place['displayName']['text'] ) ) {
        $candidate_name = $place['displayName']['text'];
    } elseif ( ! empty( $place['displayName'] ) && is_string( $place['displayName'] ) ) {
        $candidate_name = $place['displayName'];
    }
    $candidate_name = function_exists( 'yl_normalize_search_text' ) ? yl_normalize_search_text( $candidate_name ) : strtolower( $candidate_name );
    $candidate_addr = function_exists( 'yl_normalize_search_text' ) ? yl_normalize_search_text( isset( $place['formattedAddress'] ) ? $place['formattedAddress'] : '' ) : strtolower( isset( $place['formattedAddress'] ) ? $place['formattedAddress'] : '' );

    // Use the portion before the branch suffix as the most important name signal.
    $title_core = function_exists( 'yl_normalize_search_text' ) ? yl_normalize_search_text( $raw_title_core ) : strtolower( $raw_title_core );
    $tokens = array_values( array_filter( preg_split( '/\s+/', $title_core ) ) );
    $name_hits = 0;
    foreach ( $tokens as $token ) {
        if ( strlen( $token ) < 3 || in_array( $token, array( 'cafe', 'coffee', 'quan', 'nha', 'hang' ), true ) ) {
            continue;
        }
        if ( false !== strpos( ' ' . $candidate_name . ' ', ' ' . $token . ' ' ) ) {
            $name_hits++;
        }
    }

    $score = 0;
    if ( $title_core && false !== strpos( $candidate_name, $title_core ) ) {
        $score += 55;
    } elseif ( $name_hits >= 2 ) {
        $score += 42;
    } elseif ( 1 === $name_hits ) {
        $score += 24;
    }

    preg_match_all( '/\b\d{1,4}[a-z]?\b/i', $address, $source_numbers );
    preg_match_all( '/\b\d{1,4}[a-z]?\b/i', $candidate_addr, $candidate_numbers );
    $source_numbers    = isset( $source_numbers[0] ) ? array_unique( $source_numbers[0] ) : array();
    $candidate_numbers = isset( $candidate_numbers[0] ) ? array_unique( $candidate_numbers[0] ) : array();

    if ( ! empty( $source_numbers ) ) {
        $number_match = array_intersect( $source_numbers, $candidate_numbers );
        if ( empty( $number_match ) ) {
            // A mismatched street number is the strongest signal that Google
            // returned another branch with the same brand/name.
            return 0;
        }
        $score += 30;
    }

    $address_tokens = array_values( array_filter( preg_split( '/\s+/', $address ) ) );
    $address_hits   = 0;
    foreach ( $address_tokens as $token ) {
        if ( strlen( $token ) < 4 || preg_match( '/^\d/', $token ) || in_array( $token, array( 'phuong', 'quan', 'thanh', 'pho', 'ha', 'noi' ), true ) ) {
            continue;
        }
        if ( false !== strpos( ' ' . $candidate_addr . ' ', ' ' . $token . ' ' ) ) {
            $address_hits++;
        }
    }
    $score += min( 20, $address_hits * 5 );

    return min( 100, $score );
}

function yl_place_photo_google_search( $post_id ) {
    $api_key = yl_place_photos_api_key();
    if ( ! $api_key ) {
        return new WP_Error( 'no_api_key', 'Chưa cấu hình Google Places API key.' );
    }

    $place_id = trim( (string) get_post_meta( $post_id, '_yl_google_place_id', true ) );
    if ( ! $place_id ) {
        $stored_maps_url = (string) get_post_meta( $post_id, '_yl_google_maps_url', true );
        $query_parts     = wp_parse_url( $stored_maps_url, PHP_URL_QUERY );
        if ( $query_parts ) {
            parse_str( $query_parts, $maps_args );
            if ( ! empty( $maps_args['query_place_id'] ) ) {
                $place_id = sanitize_text_field( $maps_args['query_place_id'] );
            }
        }
    }
    if ( $place_id ) {
        $url = 'https://places.googleapis.com/v1/places/' . rawurlencode( $place_id );
        $response = wp_remote_get(
            add_query_arg( array( 'languageCode' => 'vi' ), $url ),
            array(
                'timeout' => 12,
                'headers' => array(
                    'X-Goog-Api-Key'   => $api_key,
                    'X-Goog-FieldMask' => 'id,displayName,formattedAddress,photos,googleMapsUri,googleMapsLinks.photosUri',
                ),
            )
        );
        if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( is_array( $body ) && yl_place_photo_google_candidate_score( $post_id, $body ) >= 55 ) {
                return $body;
            }
            // Do not keep a stale/wrong branch ID forever.
            delete_post_meta( $post_id, '_yl_google_place_id' );
        }
    }

    $address = trim( (string) get_post_meta( $post_id, '_yl_address', true ) );
    $query   = trim( get_the_title( $post_id ) . ' ' . $address . ' Hà Nội' );
    if ( ! $query ) {
        return new WP_Error( 'missing_query', 'Thiếu tên hoặc địa chỉ.' );
    }

    $response = wp_remote_post(
        'https://places.googleapis.com/v1/places:searchText',
        array(
            'timeout' => 12,
            'headers' => array(
                'Content-Type'     => 'application/json',
                'X-Goog-Api-Key'   => $api_key,
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.photos,places.googleMapsUri,places.googleMapsLinks.photosUri',
            ),
            'body'    => wp_json_encode(
                array(
                    'textQuery'    => $query,
                    'languageCode' => 'vi',
                    'regionCode'   => 'VN',
                    'pageSize'     => 3,
                )
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }
    if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return new WP_Error( 'places_http_error', 'Google Places trả về HTTP ' . (int) wp_remote_retrieve_response_code( $response ) );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['places'] ) || ! is_array( $body['places'] ) ) {
        return new WP_Error( 'place_not_found', 'Không tìm thấy địa điểm khớp trên Google Places.' );
    }

    $place      = null;
    $best_score = 0;
    foreach ( array_slice( $body['places'], 0, 3 ) as $candidate ) {
        $candidate_score = yl_place_photo_google_candidate_score( $post_id, $candidate );
        if ( $candidate_score > $best_score ) {
            $best_score = $candidate_score;
            $place      = $candidate;
        }
    }
    if ( ! is_array( $place ) || $best_score < 55 ) {
        return new WP_Error( 'place_match_uncertain', 'Google Places có kết quả nhưng chưa đủ chắc đúng chi nhánh.' );
    }
    if ( ! empty( $place['id'] ) ) {
        // Google Place IDs may be stored; photo resource names are intentionally not stored.
        update_post_meta( $post_id, '_yl_google_place_id', sanitize_text_field( $place['id'] ) );
    }
    return $place;
}

function yl_place_photo_token_encode( $photo_name, $post_id ) {
    $payload = wp_json_encode(
        array(
            'name'    => (string) $photo_name,
            'post_id' => (int) $post_id,
            'exp'     => time() + 600,
        )
    );
    $payload_b64 = rtrim( strtr( base64_encode( $payload ), '+/', '-_' ), '=' );
    $sig         = hash_hmac( 'sha256', $payload_b64, wp_salt( 'auth' ) );
    return $payload_b64 . '.' . $sig;
}

function yl_place_photo_token_decode( $token ) {
    $parts = explode( '.', (string) $token, 2 );
    if ( 2 !== count( $parts ) ) {
        return false;
    }
    list( $payload_b64, $sig ) = $parts;
    $expected = hash_hmac( 'sha256', $payload_b64, wp_salt( 'auth' ) );
    if ( ! hash_equals( $expected, $sig ) ) {
        return false;
    }
    $padded = strtr( $payload_b64, '-_', '+/' );
    $pad    = strlen( $padded ) % 4;
    if ( $pad ) {
        $padded .= str_repeat( '=', 4 - $pad );
    }
    $data = json_decode( base64_decode( $padded ), true );
    if ( ! is_array( $data ) || empty( $data['name'] ) || empty( $data['exp'] ) || time() > (int) $data['exp'] ) {
        return false;
    }
    return $data;
}

function yl_place_photos_ajax() {
    check_ajax_referer( 'yl_place_photos', 'nonce' );
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( ! $post_id || 'yl_place' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
        wp_send_json_error( array( 'message' => 'Địa điểm không hợp lệ.' ), 400 );
    }

    $photos      = yl_place_photo_manual_urls( $post_id );
    $google_used = false;
    $google_err  = '';
    $maps_url        = function_exists( 'yl_get_google_maps_url' ) ? yl_get_google_maps_url( $post_id ) : '';
    $maps_photos_url = '';

    $place = yl_place_photo_google_search( $post_id );
    if ( ! is_wp_error( $place ) ) {
        $google_used = true;
        if ( ! empty( $place['googleMapsUri'] ) ) {
            $maps_url = esc_url_raw( $place['googleMapsUri'] );
        }
        if ( ! empty( $place['googleMapsLinks']['photosUri'] ) ) {
            $maps_photos_url = esc_url_raw( $place['googleMapsLinks']['photosUri'] );
        }
        if ( ! empty( $place['photos'] ) && is_array( $place['photos'] ) ) {
            foreach ( array_slice( $place['photos'], 0, 6 ) as $photo ) {
                if ( empty( $photo['name'] ) ) {
                    continue;
                }
                $token = yl_place_photo_token_encode( $photo['name'], $post_id );
                $media = add_query_arg(
                    array(
                        'action'   => 'yl_place_photo_media',
                        'token'    => $token,
                        '_wpnonce' => wp_create_nonce( 'yl_place_photo_media_' . $post_id ),
                    ),
                    admin_url( 'admin-ajax.php' )
                );
                $attrib        = '';
                $attributions  = array();
                if ( ! empty( $photo['authorAttributions'] ) && is_array( $photo['authorAttributions'] ) ) {
                    foreach ( $photo['authorAttributions'] as $author ) {
                        if ( empty( $author['displayName'] ) ) {
                            continue;
                        }
                        $attributions[] = array(
                            'name' => sanitize_text_field( $author['displayName'] ),
                            'uri'  => ! empty( $author['uri'] ) ? esc_url_raw( $author['uri'] ) : '',
                        );
                    }
                    if ( ! empty( $attributions[0]['name'] ) ) {
                        $attrib = $attributions[0]['name'];
                    }
                }
                $photos[] = array(
                    'url'         => esc_url_raw( $media ),
                    'open_url'    => ! empty( $photo['googleMapsUri'] ) ? esc_url_raw( $photo['googleMapsUri'] ) : $maps_url,
                    'attribution' => $attrib,
                    'attributions'=> $attributions,
                    'source'      => 'Google Maps',
                );
            }
        }
    } else {
        $google_err = $place->get_error_code();
    }

    $seen = array();
    $clean = array();
    foreach ( $photos as $photo ) {
        if ( empty( $photo['url'] ) || isset( $seen[ $photo['url'] ] ) ) {
            continue;
        }
        $seen[ $photo['url'] ] = true;
        $clean[] = $photo;
        if ( count( $clean ) >= 6 ) {
            break;
        }
    }

    $address = trim( (string) get_post_meta( $post_id, '_yl_address', true ) );
    $source_url = trim( (string) get_post_meta( $post_id, '_yl_source_url', true ) );
    $verification_url = trim( (string) get_post_meta( $post_id, '_yl_image_verification_url', true ) );
    wp_send_json_success(
        array(
            'title'        => get_the_title( $post_id ),
            'address'      => $address,
            'photos'       => $clean,
            'maps_url'        => $maps_url,
            'maps_photos_url' => $maps_photos_url,
            'tiktok_url'      => ( preg_match( '#^https://(?:www\.)?tiktok\.com/#i', (string) get_post_meta( $post_id, '_yl_tiktok_url', true ) ) ? esc_url_raw( get_post_meta( $post_id, '_yl_tiktok_url', true ) ) : '' ),
            'source_url'   => esc_url_raw( $source_url ),
            'verification_url' => esc_url_raw( $verification_url ),
            'google_used'  => $google_used,
            'google_error' => $google_err,
            'api_ready'    => (bool) yl_place_photos_api_key(),
        )
    );
}
add_action( 'wp_ajax_yl_place_photos', 'yl_place_photos_ajax' );
add_action( 'wp_ajax_nopriv_yl_place_photos', 'yl_place_photos_ajax' );

function yl_place_photo_media_ajax() {
    $token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
    $data  = yl_place_photo_token_decode( $token );
    if ( ! $data ) {
        status_header( 403 );
        exit;
    }
    $post_id = absint( $data['post_id'] );
    if ( ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'yl_place_photo_media_' . $post_id ) ) {
        status_header( 403 );
        exit;
    }
    $api_key = yl_place_photos_api_key();
    if ( ! $api_key ) {
        status_header( 503 );
        exit;
    }

    $url = 'https://places.googleapis.com/v1/' . ltrim( $data['name'], '/' ) . '/media';
    $url = add_query_arg(
        array(
            'maxWidthPx'      => 1200,
            'maxHeightPx'     => 900,
            'skipHttpRedirect'=> 'true',
            'key'             => $api_key,
        ),
        $url
    );
    $response = wp_remote_get( $url, array( 'timeout' => 12 ) );
    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        status_header( 502 );
        exit;
    }
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['photoUri'] ) ) {
        status_header( 404 );
        exit;
    }
    nocache_headers();
    wp_redirect( esc_url_raw( $body['photoUri'] ), 302 );
    exit;
}
add_action( 'wp_ajax_yl_place_photo_media', 'yl_place_photo_media_ajax' );
add_action( 'wp_ajax_nopriv_yl_place_photo_media', 'yl_place_photo_media_ajax' );
