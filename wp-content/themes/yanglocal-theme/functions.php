<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'YL_THEME_VERSION', '7.24.1' );
define( 'YL_THEME_ASSET_VERSION', '7.24.1-r17-guide' );

function yl_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', array( 'height' => 64, 'width' => 220, 'flex-height' => true, 'flex-width' => true ) );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/editor.css' );

    register_nav_menus(
        array(
            'primary' => 'Menu chính',
            'footer'  => 'Menu chân trang',
        )
    );

    add_image_size( 'yl-card', 720, 540, true );
    add_image_size( 'yl-editorial', 1200, 800, true );
}
add_action( 'after_setup_theme', 'yl_theme_setup' );

/**
 * R6 map bootstrap: Leaflet is loaded by a local deterministic loader with
 * multiple CDN fallbacks. Place/campus map code waits for the loader callback
 * instead of assuming a single remote <script> has already executed.
 */
function yl_enqueue_leaflet_assets() {
    wp_enqueue_script(
        'yanglocal-leaflet-loader',
        get_template_directory_uri() . '/assets/js/leaflet-loader.js',
        array(),
        YL_THEME_ASSET_VERSION,
        true
    );
    wp_enqueue_script(
        'yanglocal-map-common',
        get_template_directory_uri() . '/assets/js/map-common.js',
        array( 'yanglocal-leaflet-loader' ),
        YL_THEME_ASSET_VERSION,
        true
    );
}

function yl_theme_assets() {
    wp_enqueue_style( 'yanglocal-fonts', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Lora:wght@600;700&display=swap', array(), null );
    wp_enqueue_style( 'yanglocal-style', get_stylesheet_uri(), array( 'yanglocal-fonts' ), YL_THEME_ASSET_VERSION );
    wp_enqueue_script( 'yanglocal-main', get_template_directory_uri() . '/assets/js/main.js', array(), YL_THEME_ASSET_VERSION, true );
    if ( function_exists( 'yl_place_photo_manual_urls' ) ) {
        wp_enqueue_script( 'yanglocal-place-photos', get_template_directory_uri() . '/assets/js/place-photos.js', array(), YL_THEME_ASSET_VERSION, true );
        wp_localize_script( 'yanglocal-place-photos', 'YangLocalPlacePhotos', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'yl_place_photos' ),
        ) );
    }
    if ( function_exists( 'yl_get_school_picker_data' ) ) {
        wp_enqueue_script( 'yanglocal-school-picker', get_template_directory_uri() . '/assets/js/school-picker.js', array(), YL_THEME_ASSET_VERSION, true );
        wp_localize_script( 'yanglocal-school-picker', 'YangLocalSchools', array( 'items' => yl_get_school_picker_data() ) );
    }

    if ( function_exists( 'yl_get_user_favorites' ) ) {
        wp_enqueue_script( 'yanglocal-favorites', get_template_directory_uri() . '/assets/js/favorites.js', array(), YL_THEME_ASSET_VERSION, true );
        wp_localize_script(
            'yanglocal-favorites',
            'YangLocalFavorites',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'yl_favorite_nonce' ),
                'loggedIn'  => is_user_logged_in(),
                'savedIds'  => is_user_logged_in() ? yl_get_user_favorites() : array(),
                'storageKey'=> 'yanglocal_guest_favorites',
                'labels'    => array( 'save' => 'Lưu lại', 'saved' => 'Đã lưu', 'error' => 'Chưa thể cập nhật. Thử lại sau.' ),
            )
        );
    }

    if ( is_tax( 'near_school' ) ) {
        $school = get_queried_object();
        $slat = $school && ! is_wp_error( $school ) && function_exists( 'yl_get_school_meta' ) ? yl_get_school_meta( $school, 'latitude' ) : '';
        $slng = $school && ! is_wp_error( $school ) && function_exists( 'yl_get_school_meta' ) ? yl_get_school_meta( $school, 'longitude' ) : '';
        $sverified = $school && ! is_wp_error( $school ) && function_exists( 'yl_get_school_meta' ) && '1' === (string) yl_get_school_meta( $school, 'coord_verified', '0' );
        if ( $sverified && is_numeric( $slat ) && is_numeric( $slng ) ) {
            yl_enqueue_leaflet_assets();
            wp_enqueue_script( 'yanglocal-campus-map', get_template_directory_uri() . '/assets/js/campus-map.js', array( 'yanglocal-map-common' ), YL_THEME_ASSET_VERSION, true );
        }
    }

    if ( is_singular( 'yl_place' ) ) {
        $lat = get_post_meta( get_queried_object_id(), '_yl_latitude', true );
        $lng = get_post_meta( get_queried_object_id(), '_yl_longitude', true );
        $verified = '1' === (string) get_post_meta( get_queried_object_id(), '_yl_coord_verified', true );
        if ( $verified && is_numeric( $lat ) && is_numeric( $lng ) ) {
            yl_enqueue_leaflet_assets();
            wp_enqueue_script( 'yanglocal-map', get_template_directory_uri() . '/assets/js/place-map.js', array( 'yanglocal-map-common' ), YL_THEME_ASSET_VERSION, true );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'yl_theme_assets' );


/** R3: every yl_place resolves through the current shared v7.24.1 shell. */
function yl_force_current_place_template( $template ) {
    if ( is_singular( 'yl_place' ) ) {
        $current = trailingslashit( get_template_directory() ) . 'single-yl_place.php';
        if ( is_readable( $current ) ) {
            return $current;
        }
    }
    return $template;
}
add_filter( 'single_template', 'yl_force_current_place_template', 99 );

/** R3: WordPress default comment URL field must never leak into review UI. */
function yl_remove_comment_website_field( $fields ) {
    if ( is_array( $fields ) && isset( $fields['url'] ) ) {
        unset( $fields['url'] );
    }
    return $fields;
}
add_filter( 'comment_form_default_fields', 'yl_remove_comment_website_field', 999 );

function yl_icon( $name, $label = '' ) {
    $icons = array(
        'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'heart'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.7a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.5 1-1a5.5 5.5 0 0 0 0-7.8Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
        'menu'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'star'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"/></svg>',
        'map'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Z" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="10" r="2" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>',
        'wifi'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 10a10 10 0 0 1 14 0M8 13a6 6 0 0 1 8 0M11 16a2 2 0 0 1 2 0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="19" r="1"/></svg>',
        'bolt'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m13 2-7 11h6l-1 9 7-12h-6l1-8Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
        'users'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.75" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
        'clock'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
        'phone'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.2 3.5 4.8 5.2c-.8.6-.9 1.8-.4 2.9 2 4.4 5.5 7.9 9.9 9.9 1.1.5 2.3.4 2.9-.4l1.7-2.4-4.1-2-1.4 1.5c-2.1-1.1-4-3-5.1-5.1l1.5-1.4-2.6-4.7Z" fill="none" stroke="currentColor" stroke-width="1.55" stroke-linejoin="round"/></svg>',
        'wrench' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.7 6.3a4.5 4.5 0 0 0-5.8 5.8L3.5 17.5a2.1 2.1 0 0 0 3 3l5.4-5.4a4.5 4.5 0 0 0 5.8-5.8l-2.8 2.8-3-3 2.8-2.8Z" fill="none" stroke="currentColor" stroke-width="1.55" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'printer'=> '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8V3h10v5M7 17H4V9h16v8h-3M7 14h10v7H7z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="17" cy="11" r="1"/></svg>',
        'laundry'=> '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="2.5" width="16" height="19" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="13.5" r="5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 6h.01M11 6h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
        'shop'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10v10h16V10M3 10l2-6h14l2 6M8 20v-6h8v6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3 10c1.2 1.4 2.5 1.4 3.7 0 1.2 1.4 2.5 1.4 3.7 0 1.2 1.4 2.5 1.4 3.7 0 1.2 1.4 2.5 1.4 3.7 0 1.2 1.4 2.5 1.4 3.7 0" fill="none" stroke="currentColor" stroke-width="1.3"/></svg>',
    );
    $svg = isset( $icons[ $name ] ) ? $icons[ $name ] : '';
    if ( ! $svg ) {
        return '';
    }
    if ( $label ) {
        return '<span class="screen-reader-text">' . esc_html( $label ) . '</span>' . $svg;
    }
    return $svg;
}


/**
 * Compact, deterministic venue monogram for truthful no-image cards.
 * Uses remove_accents() so Vietnamese place names remain stable without
 * requiring mbstring on shared hosting.
 */
function yl_place_monogram( $title, $limit = 3 ) {
    $plain = strtoupper( remove_accents( wp_strip_all_tags( (string) $title ) ) );
    $words = preg_split( '/[^A-Z0-9]+/', $plain, -1, PREG_SPLIT_NO_EMPTY );
    $mark  = '';
    foreach ( (array) $words as $word ) {
        if ( '' === $word ) { continue; }
        $mark .= substr( $word, 0, 1 );
        if ( strlen( $mark ) >= max( 1, absint( $limit ) ) ) { break; }
    }
    return $mark ? $mark : 'YL';
}

function yl_neutral_fallback_image_url( $key = 'cafe' ) {
    $safe_aliases = array( 'snack' => 'food', 'night-market' => 'city' );
    $safe_key = isset( $safe_aliases[ $key ] ) ? $safe_aliases[ $key ] : ( in_array( $key, array( 'cafe', 'study', 'food', 'city', 'event', 'gym', 'service', 'books', 'shopping', 'museum', 'history' ), true ) ? $key : 'cafe' );
    return add_query_arg( 'v', YL_THEME_ASSET_VERSION, get_template_directory_uri() . '/assets/images/fallback-' . $safe_key . '.svg' );
}

function yl_place_local_demo_photo_url( $post_id ) {
    $source = trim( (string) get_post_meta( $post_id, '_yl_demo_image_url', true ) );
    if ( ! $source || preg_match( '#^https?://#i', $source ) ) {
        return '';
    }
    $relative = 'assets/images/demo/' . ltrim( $source, '/' );
    $file     = trailingslashit( get_template_directory() ) . $relative;
    if ( ! file_exists( $file ) ) {
        return '';
    }
    return esc_url( add_query_arg( 'v', YL_THEME_ASSET_VERSION, trailingslashit( get_template_directory_uri() ) . $relative ) );
}

/**
 * Build a responsive srcset for controlled local demo assets that use the
 * `-720/-1200/-1600.webp` naming convention. Missing variants are ignored.
 */
function yl_place_local_demo_srcset( $post_id ) {
    $source = trim( (string) get_post_meta( $post_id, '_yl_demo_image_url', true ) );
    if ( ! $source || preg_match( '#^https?://#i', $source ) ) {
        return '';
    }
    if ( ! preg_match( '/-(720|1200|1600)\.webp$/i', $source ) ) {
        return '';
    }

    $base    = preg_replace( '/-(720|1200|1600)\.webp$/i', '', $source );
    $srcset  = array();
    $dir     = trailingslashit( get_template_directory() ) . 'assets/images/demo/';
    $baseurl = trailingslashit( get_template_directory_uri() ) . 'assets/images/demo/';
    foreach ( array( 720, 1200, 1600 ) as $width ) {
        $filename = $base . '-' . $width . '.webp';
        if ( file_exists( $dir . $filename ) ) {
            $url      = add_query_arg( 'v', YL_THEME_ASSET_VERSION, $baseurl . $filename );
            $srcset[] = esc_url( $url ) . ' ' . $width . 'w';
        }
    }
    return implode( ', ', $srcset );
}

function yl_place_first_local_gallery_photo_url( $post_id ) {
    $gallery_ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, '_yl_gallery_ids', true ) ) ) );
    foreach ( $gallery_ids as $attachment_id ) {
        $url = wp_get_attachment_image_url( $attachment_id, 'yl-card' );
        if ( $url ) {
            return $url;
        }
    }
    return '';
}

function yl_place_safe_remote_photo_url( $post_id ) {
    if ( 'yl_place' !== get_post_type( $post_id ) ) {
        return '';
    }
    $match_level = (string) get_post_meta( $post_id, '_yl_image_match_level', true );
    if ( ! in_array( $match_level, array( 'exact_venue', 'brand_context' ), true ) ) {
        return '';
    }

    $reuse = strtolower( trim( (string) get_post_meta( $post_id, '_yl_image_reuse_status', true ) ) );
    if ( ! in_array( $reuse, array( 'licensed', 'public_domain' ), true ) ) {
        return '';
    }

    $url = trim( (string) get_post_meta( $post_id, '_yl_demo_image_url', true ) );
    if ( ! preg_match( '#^https?://#i', $url ) ) {
        return '';
    }

    $parts = wp_parse_url( $url );
    $host  = isset( $parts['host'] ) ? strtolower( $parts['host'] ) : '';
    $path  = isset( $parts['path'] ) ? (string) $parts['path'] : '';
    if ( 'upload.wikimedia.org' === $host ) {
        return esc_url_raw( $url );
    }
    if ( 'commons.wikimedia.org' === $host && 0 === strpos( $path, '/wiki/Special:Redirect/file/' ) ) {
        return esc_url_raw( $url );
    }
    return '';
}


function yl_commons_responsive_srcset( $url ) {
    if ( ! preg_match( '#^https://commons\.wikimedia\.org/wiki/Special:Redirect/file/#i', (string) $url ) ) { return ''; }
    $base = remove_query_arg( 'width', $url );
    return esc_url( add_query_arg( 'width', 720, $base ) ) . ' 720w, ' . esc_url( add_query_arg( 'width', 1200, $base ) ) . ' 1200w, ' . esc_url( add_query_arg( 'width', 1600, $base ) ) . ' 1600w';
}

function yl_place_reference_image_url( $post_id, $key = 'cafe' ) {
    // v7.24.1: TRUTH > COVERAGE. For non-exact places never rotate unrelated
    // venue-like photography. Use a neutral category illustration instead.
    return yl_neutral_fallback_image_url( $key );
}

function yl_place_image_state( $post_id ) {
    $level = (string) get_post_meta( $post_id, '_yl_image_match_level', true );
    if ( 'exact_branch' === $level ) {
        return 'EXACT_BRANCH';
    }
    if ( 'exact_venue' === $level ) {
        return 'EXACT_PLACE';
    }
    if ( 'brand_context' === $level ) {
        return 'EXACT_BRAND';
    }
    if ( in_array( $level, array( 'exact_dish', 'close_match', 'contextual', 'category' ), true ) ) {
        return 'CONTEXTUAL';
    }
    return 'MISSING';
}

function yl_place_cover_info( $post_id, $fallback = '' ) {
    if ( ! $fallback ) {
        $fallback = yl_fallback_key_for_post( $post_id, 'cafe' );
    }
    $title      = get_the_title( $post_id );
    $level      = (string) get_post_meta( $post_id, '_yl_image_match_level', true );
    $state      = yl_place_image_state( $post_id );
    $preference = (string) get_post_meta( $post_id, '_yl_cover_preference', true );
    $fallback_url = yl_neutral_fallback_image_url( $fallback );
    $priority_local_only = false;
    if ( function_exists( 'yl_place_photo_priority_slugs' ) ) {
        $place_post = get_post( $post_id );
        $priority_local_only = $place_post && in_array( $place_post->post_name, yl_place_photo_priority_slugs(), true );
    }

    // R15: a curated cover still overrides stale historical Media Library images,
    // but once the selected source has been copied into YangLocal Media Library
    // the explicit primary attachment must win over the remote hotlink.
    if ( in_array( $level, array( 'exact_venue', 'exact_branch' ), true ) && 'curated_demo' === $preference ) {
        $primary_local_id = absint( get_post_meta( $post_id, '_yl_primary_local_attachment_id', true ) );
        if ( $primary_local_id && wp_attachment_is_image( $primary_local_id ) ) {
            $primary_local = wp_get_attachment_image_url( $primary_local_id, 'yl-card' );
            if ( $primary_local ) {
                return array( 'url'=>$primary_local, 'status'=>'EXACT', 'state'=>$state, 'label'=>'', 'is_exact'=>true, 'alt'=>$title, 'fallback_url'=>$fallback_url );
            }
        }
        $local_demo = yl_place_local_demo_photo_url( $post_id );
        if ( $local_demo ) {
            return array( 'url'=>$local_demo, 'srcset'=>yl_place_local_demo_srcset($post_id), 'sizes'=>'(max-width: 760px) 100vw, 720px', 'detail_sizes'=>'(max-width: 760px) 100vw, (max-width: 1400px) 70vw, 1200px', 'status'=>'EXACT', 'state'=>$state, 'label'=>'', 'is_exact'=>true, 'alt'=>$title, 'fallback_url'=>$fallback_url );
        }
        $remote = yl_place_safe_remote_photo_url( $post_id );
        if ( $remote && ! $priority_local_only ) {
            return array( 'url'=>$remote, 'srcset'=>yl_commons_responsive_srcset($remote), 'sizes'=>'(max-width: 760px) 100vw, 720px', 'status'=>'EXACT', 'state'=>$state, 'label'=>'', 'is_exact'=>true, 'alt'=>$title, 'fallback_url'=>$fallback_url );
        }
    }

    if ( in_array( $level, array( 'exact_venue', 'exact_branch' ), true ) ) {
        $featured = get_the_post_thumbnail_url( $post_id, 'yl-card' );
        if ( $featured ) {
            return array( 'url'=>$featured, 'status'=>'EXACT', 'state'=>$state, 'label'=>'', 'is_exact'=>true, 'alt'=>$title, 'fallback_url'=>$fallback_url );
        }
        $gallery = yl_place_first_local_gallery_photo_url( $post_id );
        if ( $gallery ) {
            return array( 'url'=>$gallery, 'status'=>'EXACT', 'state'=>$state, 'label'=>'', 'is_exact'=>true, 'alt'=>$title, 'fallback_url'=>$fallback_url );
        }
        $local_demo = yl_place_local_demo_photo_url( $post_id );
        if ( $local_demo ) {
            return array( 'url'=>$local_demo, 'srcset'=>yl_place_local_demo_srcset($post_id), 'sizes'=>'(max-width: 760px) 100vw, 720px', 'detail_sizes'=>'(max-width: 760px) 100vw, (max-width: 1400px) 70vw, 1200px', 'status'=>'EXACT', 'state'=>$state, 'label'=>'', 'is_exact'=>true, 'alt'=>$title, 'fallback_url'=>$fallback_url );
        }
        $remote = yl_place_safe_remote_photo_url( $post_id );
        if ( $remote && ! $priority_local_only ) {
            return array( 'url'=>$remote, 'srcset'=>yl_commons_responsive_srcset($remote), 'sizes'=>'(max-width: 760px) 100vw, 720px', 'status'=>'EXACT', 'state'=>$state, 'label'=>'', 'is_exact'=>true, 'alt'=>$title, 'fallback_url'=>$fallback_url );
        }
    }

    if ( 'brand_context' === $level ) {
        // R16 P4: a brand/context image is not an exact branch photo. Keep the
        // verified no-image state instead of hotlinking a different branch/venue.
    }

    // A missing/unverified venue image is deliberately represented as data state,
    // not as a photo. Templates render this state as a text-first card/panel.
    $label = 'Chưa có ảnh địa điểm đã xác minh';
    if ( 'exact_dish' === $level && in_array( $fallback, array( 'food', 'snack' ), true ) ) {
        $label = 'Ảnh món tham khảo';
    }
    return array(
        'url'          => '',
        'status'       => 'FALLBACK',
        'state'        => $state,
        'label'        => $label,
        'is_exact'     => false,
        'alt'          => $label . ' cho ' . $title,
        'fallback_url' => $fallback_url,
    );
}

function yl_place_has_trusted_photo( $post_id ) {
    if ( 'yl_place' !== get_post_type( $post_id ) ) {
        return true;
    }
    $cover = yl_place_cover_info( $post_id );
    return ! empty( $cover['is_exact'] );
}

/**
 * Return cover-ready Place IDs, preferring exact covers before honest reference covers.
 */
function yl_get_cover_ready_place_ids( $args = array(), $limit = 4 ) {
    $limit = max( 1, absint( $limit ) );
    $args  = wp_parse_args(
        $args,
        array(
            'post_type'      => 'yl_place',
            'post_status'    => 'publish',
            'posts_per_page' => max( 32, $limit * 10 ),
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
        )
    );
    $args['posts_per_page'] = max( absint( $args['posts_per_page'] ), $limit );
    $args['fields']         = 'ids';
    $args['no_found_rows']  = true;

    $candidate_ids = get_posts( $args );
    $exact = array();
    $other = array();
    foreach ( $candidate_ids as $candidate_id ) {
        $cover = yl_place_cover_info( $candidate_id );
        if ( empty( $cover['url'] ) ) {
            continue;
        }
        if ( ! empty( $cover['is_exact'] ) ) {
            $exact[] = (int) $candidate_id;
        } else {
            $other[] = (int) $candidate_id;
        }
    }
    return array_slice( array_merge( $exact, $other ), 0, $limit );
}

/** Backward-compatible alias used by older templates. */
function yl_get_trusted_photo_place_ids( $args = array(), $limit = 4 ) {
    return yl_get_cover_ready_place_ids( $args, $limit );
}

function yl_service_icon_name( $post_id ) {
    $title = remove_accents( strtolower( get_the_title( $post_id ) ) );
    if ( false !== strpos( $title, 'giat' ) || false !== strpos( $title, 'laundry' ) ) {
        return 'laundry';
    }
    if ( false !== strpos( $title, 'sua xe' ) || false !== strpos( $title, 'motor' ) || false !== strpos( $title, 'cuu ho' ) ) {
        return 'wrench';
    }
    if ( false !== strpos( $title, 'photo' ) || false !== strpos( $title, 'in nhanh' ) || false !== strpos( $title, 'in an' ) ) {
        return 'printer';
    }
    if ( false !== strpos( $title, 'winmart' ) || false !== strpos( $title, 'sieu thi' ) || false !== strpos( $title, 'tap hoa' ) ) {
        return 'shop';
    }
    return 'map';
}


function yl_real_fallback_image_url( $post_id, $key = 'cafe' ) {
    $demo_base = trailingslashit( get_template_directory_uri() ) . 'assets/images/demo/';
    $demo_dir  = trailingslashit( get_template_directory() ) . 'assets/images/demo/';

    if ( 'yl_place' === get_post_type( $post_id ) ) {
        if ( 'gym' === $key && file_exists( $demo_dir . 'yl-gym-close-match.webp' ) ) {
            return add_query_arg( 'v', YL_THEME_ASSET_VERSION, $demo_base . 'yl-gym-close-match.webp' );
        }
        if ( 'service' === $key ) {
            $icon = function_exists( 'yl_service_icon_name' ) ? yl_service_icon_name( $post_id ) : 'map';
            $service_map = array(
                'laundry' => 'yl-fast-clean-laundry.webp',
                'shop'    => 'yl-winmart-timescity.webp',
                'wrench'  => 'yl-sua-xe-anh-hung.webp',
            );
            if ( isset( $service_map[ $icon ] ) && file_exists( $demo_dir . $service_map[ $icon ] ) ) {
                return add_query_arg( 'v', YL_THEME_ASSET_VERSION, $demo_base . $service_map[ $icon ] );
            }
        }
        if ( 'books' === $key && file_exists( $demo_dir . 'yl-library-modern.webp' ) ) {
            return add_query_arg( 'v', YL_THEME_ASSET_VERSION, $demo_base . 'yl-library-modern.webp' );
        }
    }

    return yl_fallback_image_url( $key );
}

function yl_phone_href( $phone ) {
    $phone = trim( (string) $phone );
    if ( ! $phone ) {
        return '';
    }
    $phone = preg_replace( '/[^0-9+]/', '', $phone );
    return $phone ? 'tel:' . $phone : '';
}

function yl_fallback_image_url( $key = 'cafe' ) {
    $photo_map = array(
        'cafe'    => 'hanoi-sidewalk-cafe.webp',
        'study'   => 'hanoi-library.webp',
        'food'         => 'hanoi-street-food.webp',
        'snack'        => 'hanoi-street-food.webp',
        'night-market' => 'hanoi-hoankiem-night.webp',
        'city'         => 'hanoi-walking-street.webp',
        'event'   => 'hanoi-study-group.webp',
        'books'   => 'hanoi-library.webp',
    );

    if ( isset( $photo_map[ $key ] ) ) {
        $relative = 'assets/images/demo/' . $photo_map[ $key ];
        if ( file_exists( trailingslashit( get_template_directory() ) . $relative ) ) {
            return add_query_arg( 'v', YL_THEME_ASSET_VERSION, trailingslashit( get_template_directory_uri() ) . $relative );
        }
    }

    $safe_aliases = array( 'snack' => 'food', 'night-market' => 'city' );
    $safe_key = isset( $safe_aliases[ $key ] ) ? $safe_aliases[ $key ] : ( in_array( $key, array( 'cafe', 'study', 'food', 'city', 'event', 'gym', 'service', 'books', 'shopping', 'museum', 'history' ), true ) ? $key : 'cafe' );
    return add_query_arg( 'v', YL_THEME_ASSET_VERSION, get_template_directory_uri() . '/assets/images/fallback-' . $safe_key . '.svg' );
}

function yl_fallback_key_for_post( $post_id, $default = 'cafe' ) {
    $post_type = get_post_type( $post_id );

    if ( 'yl_place' === $post_type ) {
        $terms = get_the_terms( $post_id, 'place_category' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            $slug = $terms[0]->slug;
            $map  = array(
                'cafe'      => 'cafe',
                'hoc-tap'   => 'study',
                'quan-an'   => 'food',
                'an-vat'          => 'snack',
                'cho-dia-phuong'   => 'food',
                'cho-dem'         => 'night-market',
                'dich-vu'   => 'service',
                'nha-sach'  => 'books',
                'gym'       => 'gym',
                'vui-choi'        => 'city',
                'mua-sam'         => 'shopping',
                'bao-tang'        => 'museum',
                'lich-su-di-san'  => 'history',
            );
            if ( isset( $map[ $slug ] ) ) {
                return $map[ $slug ];
            }
        }
    }

    if ( 'yl_event' === $post_type ) {
        return 'event';
    }

    if ( 'post' === $post_type ) {
        $categories = get_the_category( $post_id );
        if ( $categories ) {
            $slug = $categories[0]->slug;
            if ( 'an-uong' === $slug ) {
                return 'food';
            }
            if ( 'cafe' === $slug ) {
                return 'cafe';
            }
            if ( 'hoc-tap' === $slug ) {
                return 'study';
            }
        }
        return 'city';
    }

    return $default;
}

function yl_demo_image_url( $post_id, $fallback = '' ) {
    $source = get_post_meta( $post_id, '_yl_demo_image_url', true );
    if ( ! $fallback ) {
        $fallback = yl_fallback_key_for_post( $post_id, 'cafe' );
    }

    if ( 'yl_place' === get_post_type( $post_id ) ) {
        $cover = yl_place_cover_info( $post_id, $fallback );
        return ! empty( $cover['url'] ) ? $cover['url'] : yl_neutral_fallback_image_url( $fallback );
    }

    $featured = get_the_post_thumbnail_url( $post_id, 'yl-card' );
    if ( $featured ) {
        return $featured;
    }
    if ( $source ) {
        if ( preg_match( '#^https?://#i', $source ) ) {
            return esc_url( $source );
        }
        $relative   = 'assets/images/demo/' . ltrim( $source, '/' );
        $local_file = trailingslashit( get_template_directory() ) . $relative;
        if ( file_exists( $local_file ) ) {
            return esc_url( add_query_arg( 'v', YL_THEME_ASSET_VERSION, trailingslashit( get_template_directory_uri() ) . $relative ) );
        }
    }
    return yl_neutral_fallback_image_url( $fallback );
}


/**
 * R17 Cẩm nang: lightweight editorial helpers. These intentionally use the
 * built-in WordPress post/category model so the guide stays Gutenberg-friendly
 * and compatible with shared PHP hosting.
 */
function yl_guide_topics() {
    return array(
        ''                    => array( 'label' => 'Tất cả', 'category' => '' ),
        'sinh-vien-moi'       => array( 'label' => 'Sinh viên mới', 'category' => 'sinh-vien-moi' ),
        'an-uong'             => array( 'label' => 'Ăn uống', 'category' => 'an-uong' ),
        'cafe'                => array( 'label' => 'Café & học bài', 'category' => 'cafe' ),
        'di-choi'             => array( 'label' => 'Đi chơi', 'category' => 'di-choi' ),
        'tiet-kiem'           => array( 'label' => 'Tiết kiệm', 'category' => 'tiet-kiem' ),
        'hoc-tap'             => array( 'label' => 'Học tập', 'category' => 'hoc-tap' ),
        'kinh-nghiem-ha-noi' => array( 'label' => 'Kinh nghiệm Hà Nội', 'category' => 'kinh-nghiem-ha-noi' ),
    );
}

function yl_guide_current_topic() {
    $topic  = isset( $_GET['guide_topic'] ) ? sanitize_key( wp_unslash( $_GET['guide_topic'] ) ) : '';
    $topics = yl_guide_topics();
    return array_key_exists( $topic, $topics ) ? $topic : '';
}

function yl_guide_archive_url( $topic = '' ) {
    $posts_page_id = (int) get_option( 'page_for_posts' );
    $url           = $posts_page_id ? get_permalink( $posts_page_id ) : home_url( '/cam-nang/' );
    $topics        = yl_guide_topics();
    if ( $topic && isset( $topics[ $topic ] ) ) {
        $url = add_query_arg( 'guide_topic', $topic, $url );
    }
    return $url;
}

function yl_guide_reading_time( $post_id = 0 ) {
    $post_id = $post_id ? absint( $post_id ) : get_the_ID();
    $content = (string) get_post_field( 'post_content', $post_id );
    $content = trim( preg_replace( '/\\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $content ) ) ) );
    if ( '' === $content ) {
        return '1 phút đọc';
    }
    $words   = preg_split( '/\\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY );
    $minutes = max( 1, (int) ceil( count( $words ) / 220 ) );
    return sprintf( '%d phút đọc', $minutes );
}

function yl_guide_primary_category( $post_id = 0 ) {
    $post_id    = $post_id ? absint( $post_id ) : get_the_ID();
    $categories = get_the_category( $post_id );
    if ( ! $categories ) {
        return null;
    }
    $priority = array_keys( array_filter( yl_guide_topics(), static function ( $item ) { return ! empty( $item['category'] ); } ) );
    foreach ( $priority as $slug ) {
        foreach ( $categories as $category ) {
            if ( $slug === $category->slug ) {
                return $category;
            }
        }
    }
    return $categories[0];
}

function yl_get_guide_featured_id() {
    static $featured_id = null;
    if ( null !== $featured_id ) {
        return $featured_id;
    }
    $ids = get_posts(
        array(
            'post_type'              => 'post',
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'meta_key'               => '_yl_guide_featured',
            'meta_value'             => '1',
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );
    if ( ! $ids ) {
        $ids = get_posts(
            array(
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'posts_per_page'      => 1,
                'fields'              => 'ids',
                'orderby'             => 'modified',
                'order'               => 'DESC',
                'no_found_rows'       => true,
                'ignore_sticky_posts' => true,
            )
        );
    }
    $featured_id = $ids ? absint( $ids[0] ) : 0;
    return $featured_id;
}

function yl_filter_guide_main_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() ) {
        return;
    }

    $query->set( 'posts_per_page', 9 );
    $query->set( 'ignore_sticky_posts', true );

    $topic  = yl_guide_current_topic();
    $topics = yl_guide_topics();
    if ( $topic && ! empty( $topics[ $topic ]['category'] ) ) {
        $query->set( 'category_name', $topics[ $topic ]['category'] );
    } else {
        $featured_id = yl_get_guide_featured_id();
        if ( $featured_id ) {
            $excluded   = array_map( 'absint', (array) $query->get( 'post__not_in' ) );
            $excluded[] = $featured_id;
            $query->set( 'post__not_in', array_values( array_unique( $excluded ) ) );
        }
    }
}
add_action( 'pre_get_posts', 'yl_filter_guide_main_query', 20 );

function yl_guide_related_place_ids( $post_id = 0, $limit = 4 ) {
    $post_id = $post_id ? absint( $post_id ) : get_the_ID();
    $raw     = (string) get_post_meta( $post_id, '_yl_related_place_slugs', true );
    $slugs   = preg_split( '/[\\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
    $slugs   = array_values( array_unique( array_filter( array_map( 'sanitize_title', $slugs ) ) ) );
    if ( ! $slugs ) {
        return array();
    }
    $slugs = array_slice( $slugs, 0, max( 1, absint( $limit ) ) );
    return get_posts(
        array(
            'post_type'           => 'yl_place',
            'post_status'         => 'publish',
            'posts_per_page'      => count( $slugs ),
            'post_name__in'       => $slugs,
            'orderby'             => 'post_name__in',
            'fields'              => 'ids',
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        )
    );
}

function yl_guide_related_posts( $post_id = 0, $limit = 3 ) {
    $post_id    = $post_id ? absint( $post_id ) : get_the_ID();
    $categories = wp_get_post_categories( $post_id );
    if ( ! $categories ) {
        return array();
    }
    return get_posts(
        array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => max( 1, absint( $limit ) ),
            'post__not_in'        => array( $post_id ),
            'category__in'        => array_map( 'absint', $categories ),
            'orderby'             => 'modified',
            'order'               => 'DESC',
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        )
    );
}

function yl_place_primary_category( $post_id ) {
    $terms = get_the_terms( $post_id, 'place_category' );
    return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

function yl_place_primary_district( $post_id ) {
    $terms = get_the_terms( $post_id, 'district' );
    return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

function yl_theme_fallback_menu() {
    $posts_page_id = (int) get_option( 'page_for_posts' );
    $guide_url     = $posts_page_id ? get_permalink( $posts_page_id ) : home_url( '/?post_type=post' );
    $archive_url = get_post_type_archive_link( 'yl_place' );
    $items = array(
        array( 'label' => 'Khám phá', 'url' => $archive_url ),
        array( 'label' => 'Ăn uống', 'url' => add_query_arg( 'category', 'quan-an', $archive_url ) ),
        array( 'label' => 'Café & Học', 'url' => add_query_arg( 'category', 'cafe', $archive_url ) ),
        array( 'label' => 'Hà Nội', 'url' => home_url( '/#yanglocal-goi-y' ) ),
        array( 'label' => 'Cẩm nang', 'url' => $guide_url ),
    );
    echo '<ul>';
    foreach ( $items as $item ) {
        echo '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
    }
    echo '</ul>';
}

function yl_register_patterns() {
    if ( ! function_exists( 'register_block_pattern' ) ) {
        return;
    }
    register_block_pattern_category( 'yanglocal', array( 'label' => 'YangLocal' ) );
    register_block_pattern(
        'yanglocal/editorial-intro',
        array(
            'title'      => 'YangLocal · Editorial intro',
            'categories' => array( 'yanglocal' ),
            'content'    => '<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide"><!-- wp:heading {"level":2} --><h2>Hôm nay đi đâu?</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Một đoạn giới thiệu ngắn, cụ thể và hữu ích cho sinh viên.</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
        )
    );
    register_block_pattern(
        'yanglocal/about-note',
        array(
            'title'      => 'YangLocal · About note',
            'categories' => array( 'yanglocal' ),
            'content'    => '<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"32px","bottom":"32px"}}}} --><div class="wp-block-group alignwide" style="padding-top:32px;padding-bottom:32px"><!-- wp:heading {"level":2} --><h2>YangLocal ghi lại những gì có ích</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Ưu tiên mức giá, khu vực, tiện ích và trải nghiệm thực tế; tránh những con số hoặc đánh giá chưa được xác minh.</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
        )
    );
    register_block_pattern(
        'yanglocal/hero-note',
        array(
            'title'      => 'YangLocal · Hero',
            'categories' => array( 'yanglocal' ),
            'content'    => '<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide"><!-- wp:paragraph --><p><strong>YANGLOCAL · HÀ NỘI CHO SINH VIÊN</strong></p><!-- /wp:paragraph --><!-- wp:heading {"level":1} --><h1>Hôm nay ăn gì, học ở đâu?</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Tìm theo quận, ngân sách và nhu cầu.</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
        )
    );
    register_block_pattern(
        'yanglocal/cta',
        array(
            'title'      => 'YangLocal · CTA',
            'categories' => array( 'yanglocal' ),
            'content'    => '<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide"><!-- wp:heading {"level":2} --><h2>Có địa điểm muốn góp ý?</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Gửi thông tin để YangLocal cập nhật danh sách.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/lien-he/">Liên hệ</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->',
        )
    );
    register_block_pattern(
        'yanglocal/featured-note',
        array(
            'title'      => 'YangLocal · Featured intro',
            'categories' => array( 'yanglocal' ),
            'content'    => '<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide"><!-- wp:heading {"level":2} --><h2>Địa điểm đáng lưu</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Một đoạn mở đầu ngắn cho nhóm địa điểm bạn đang biên tập.</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
        )
    );
}
add_action( 'init', 'yl_register_patterns' );

function yl_body_classes( $classes ) {
    $classes[] = 'yanglocal';
    return $classes;
}
add_filter( 'body_class', 'yl_body_classes' );

function yl_meta_description() {
    if ( is_admin() ) {
        return;
    }
    $description = '';
    if ( is_singular() ) {
        $description = get_the_excerpt( get_queried_object_id() );
    } elseif ( is_front_page() ) {
        $description = 'YangLocal — địa điểm ăn uống, café, chỗ học và sự kiện dành cho sinh viên tại Hà Nội.';
    }
    if ( $description ) {
        echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( wp_trim_words( $description, 28, '' ) ) ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'yl_meta_description', 2 );

function yl_structured_data() {
    if ( ! is_singular( array( 'post', 'yl_event', 'yl_place' ) ) ) {
        return;
    }
    $post_id = get_queried_object_id();
    $type = get_post_type( $post_id );
    $data = array(
        '@context' => 'https://schema.org',
        'name'     => get_the_title( $post_id ),
        'url'      => get_permalink( $post_id ),
    );
    if ( 'post' === $type ) {
        $data['@type'] = 'Article';
        $data['datePublished'] = get_the_date( DATE_W3C, $post_id );
        $data['dateModified'] = get_the_modified_date( DATE_W3C, $post_id );
    } elseif ( 'yl_event' === $type ) {
        $data['@type'] = 'Event';
        $date = get_post_meta( $post_id, '_yl_event_date', true );
        $start = get_post_meta( $post_id, '_yl_event_start_time', true );
        $end = get_post_meta( $post_id, '_yl_event_end_time', true );
        if ( $date ) {
            $data['startDate'] = $date . ( $start ? 'T' . $start . ':00' : '' );
            if ( $end ) {
                $data['endDate'] = $date . 'T' . $end . ':00';
            }
        }
        $venue = get_post_meta( $post_id, '_yl_event_venue', true );
        $address = get_post_meta( $post_id, '_yl_event_address', true );
        if ( $venue || $address ) {
            $data['location'] = array( '@type' => 'Place', 'name' => $venue, 'address' => $address );
        }
    } else {
        $data['@type'] = 'Place';
        $address = get_post_meta( $post_id, '_yl_address', true );
        if ( $address ) {
            $data['address'] = $address;
        }
        $rating = function_exists( 'yl_get_rating_summary' ) ? yl_get_rating_summary( $post_id ) : array( 'average' => 0, 'count' => 0 );
        if ( $rating['count'] > 0 ) {
            $data['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => $rating['average'], 'reviewCount' => $rating['count'] );
        }
    }
    echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'yl_structured_data', 20 );

function yl_comment_callback( $comment, $args, $depth ) {
    $rating  = (int) get_comment_meta( $comment->comment_ID, 'yl_rating', true );
    $author  = get_comment_author( $comment );
    $initial = function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( trim( $author ), 0, 1 ) ) : strtoupper( substr( trim( $author ), 0, 1 ) );
    echo '<li id="comment-' . esc_attr( $comment->comment_ID ) . '" class="yl-review-item"><article class="yl-review-card">';
    echo '<div class="yl-review-card__avatar" aria-hidden="true">' . esc_html( $initial ? $initial : 'Y' ) . '</div>';
    echo '<div class="yl-review-card__body"><div class="yl-review-card__meta"><strong>' . esc_html( $author ) . '</strong><time datetime="' . esc_attr( get_comment_date( 'c', $comment ) ) . '">' . esc_html( get_comment_date( '', $comment ) ) . '</time></div>';
    if ( $rating ) { echo '<div class="yl-review-stars" aria-label="' . esc_attr( $rating . ' trên 5 sao' ) . '">' . esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) ) . '</div>'; }
    echo '<div class="yl-review-card__text">' . wp_kses_post( wpautop( get_comment_text( $comment ) ) ) . '</div>';
    echo '</div></article></li>';
}

function yl_theme_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'yl_site_options', array( 'title' => 'YangLocal · Thông tin website', 'priority' => 35 ) );
    $wp_customize->add_setting( 'yl_footer_description', array( 'default' => 'Đi đâu, ăn gì, học ở đâu — có YangLocal.', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'yl_footer_description', array( 'label' => 'Mô tả footer', 'section' => 'yl_site_options', 'type' => 'text' ) );
    $wp_customize->add_setting( 'yl_contact_email', array( 'default' => get_option( 'admin_email' ), 'sanitize_callback' => 'sanitize_email' ) );
    $wp_customize->add_control( 'yl_contact_email', array( 'label' => 'Email liên hệ', 'section' => 'yl_site_options', 'type' => 'email' ) );
    foreach ( array( 'facebook' => 'Facebook URL', 'instagram' => 'Instagram URL' ) as $key => $label ) {
        $wp_customize->add_setting( 'yl_' . $key . '_url', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $wp_customize->add_control( 'yl_' . $key . '_url', array( 'label' => $label . ' (để trống nếu chưa có)', 'section' => 'yl_site_options', 'type' => 'url' ) );
    }
}
add_action( 'customize_register', 'yl_theme_customize_register' );

function yl_place_gallery_urls( $post_id ) {
    $urls       = array();
    $level      = get_post_meta( $post_id, '_yl_image_match_level', true );
    $preference = (string) get_post_meta( $post_id, '_yl_cover_preference', true );
    $priority_local_only = false;
    if ( function_exists( 'yl_place_photo_priority_slugs' ) ) {
        $place_post = get_post( $post_id );
        $priority_local_only = $place_post && in_array( $place_post->post_name, yl_place_photo_priority_slugs(), true );
    }

    // R3 curated primary must be first in the actual rendered gallery. Older local
    // venue photos can remain as secondary gallery items but may not reclaim cover.
    if ( in_array( $level, array( 'exact_venue', 'exact_branch' ), true ) && 'curated_demo' === $preference ) {
        $local_demo = yl_place_local_demo_photo_url( $post_id );
        $remote     = yl_place_safe_remote_photo_url( $post_id );
        if ( $local_demo ) { $urls[] = $local_demo; }
        elseif ( $remote && ! $priority_local_only ) { $urls[] = $remote; }
    }

    $gallery_ids = get_post_meta( $post_id, '_yl_gallery_ids', true );
    if ( $gallery_ids ) {
        foreach ( array_filter( array_map( 'absint', explode( ',', $gallery_ids ) ) ) as $attachment_id ) {
            $url = wp_get_attachment_image_url( $attachment_id, 'yl-editorial' );
            if ( $url ) { $urls[] = $url; }
        }
    }

    if ( in_array( $level, array( 'exact_venue', 'exact_branch' ), true ) ) {
        $featured = get_the_post_thumbnail_url( $post_id, 'yl-editorial' );
        if ( $featured ) { $urls[] = $featured; }
        $local_demo = yl_place_local_demo_photo_url( $post_id );
        if ( $local_demo ) { $urls[] = $local_demo; }
        $safe_remote = yl_place_safe_remote_photo_url( $post_id );
        if ( $safe_remote && ! $priority_local_only ) { $urls[] = $safe_remote; }
        // R16 P4: do not hotlink secondary remote gallery images on production.
        // Curated external URLs remain in metadata and are displayed only after the
        // exact-photo localizer has copied them into Media Library (_yl_gallery_ids).
    }

    return array_slice( array_values( array_unique( array_filter( $urls ) ) ), 0, 6 );
}

function yl_get_direct_tiktok_url( $post_id ) {
    $url = trim( (string) get_post_meta( $post_id, '_yl_tiktok_url', true ) );
    if ( ! $url || ! preg_match( '#^https://(?:www\.)?tiktok\.com/#i', $url ) ) {
        return '';
    }
    return esc_url_raw( $url );
}

function yl_render_external_place_links( $post_id, $class = 'yl-external-actions', $include_maps = true ) {
    $maps    = function_exists( 'yl_get_google_maps_url' ) ? yl_get_google_maps_url( $post_id ) : '';
    $tiktok  = yl_get_direct_tiktok_url( $post_id );
    $website = trim( (string) get_post_meta( $post_id, '_yl_website', true ) );
    $items   = array();
    if ( $include_maps && $maps ) {
        $items[] = '<a class="yl-button yl-button--ghost" href="' . esc_url( $maps ) . '" target="_blank" rel="noopener noreferrer">' . yl_icon( 'map' ) . ' Xem trên Google Maps</a>';
    }
    if ( $tiktok ) {
        $items[] = '<a class="yl-button yl-button--ghost" href="' . esc_url( $tiktok ) . '" target="_blank" rel="noopener noreferrer">♪ Xem trên TikTok</a>';
    }
    if ( $website && preg_match( '#^https?://#i', $website ) ) {
        $items[] = '<a class="yl-button yl-button--ghost" href="' . esc_url( $website ) . '" target="_blank" rel="noopener noreferrer">Trang chính thức</a>';
    }
    if ( ! $items ) {
        return '';
    }
    return '<div class="' . esc_attr( $class ) . '">' . implode( '', $items ) . '</div>';
}

function yl_open_graph_tags() {
    if ( is_admin() ) {
        return;
    }
    $title = wp_get_document_title();
    $description = is_singular() ? get_the_excerpt( get_queried_object_id() ) : 'Đi đâu, ăn gì, học ở đâu — có YangLocal.';
    $url = is_singular() ? get_permalink( get_queried_object_id() ) : home_url( '/' );
    $type = is_singular( 'post' ) ? 'article' : 'website';
    echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( wp_strip_all_tags( wp_trim_words( $description, 28, '' ) ) ) . '">' . "\n";
    echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
    if ( is_singular() ) {
        $object_id = get_queried_object_id();
        if ( 'yl_place' !== get_post_type( $object_id ) || ! function_exists( 'yl_place_has_trusted_photo' ) || yl_place_has_trusted_photo( $object_id ) ) {
            $image = yl_demo_image_url( $object_id, 'city' );
            if ( $image ) {
                echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "
";
            }
        }
    }
}
add_action( 'wp_head', 'yl_open_graph_tags', 12 );
