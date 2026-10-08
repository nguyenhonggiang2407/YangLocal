<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function yl_register_post_types() {
    register_post_type(
        'yl_place',
        array(
            'labels' => array(
                'name'               => 'Địa điểm',
                'singular_name'      => 'Địa điểm',
                'add_new'            => 'Thêm địa điểm',
                'add_new_item'       => 'Thêm địa điểm mới',
                'edit_item'          => 'Sửa địa điểm',
                'new_item'           => 'Địa điểm mới',
                'view_item'          => 'Xem địa điểm',
                'search_items'       => 'Tìm địa điểm',
                'not_found'          => 'Không tìm thấy địa điểm',
                'not_found_in_trash' => 'Thùng rác không có địa điểm',
                'menu_name'          => 'YangLocal · Địa điểm',
            ),
            'public'             => true,
            'show_in_rest'       => true,
            'menu_icon'          => 'dashicons-location-alt',
            'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'author' ),
            'has_archive'        => 'kham-pha',
            'rewrite'            => array( 'slug' => 'dia-diem', 'with_front' => false ),
            'show_in_nav_menus'  => true,
            'publicly_queryable' => true,
        )
    );

    register_post_type(
        'yl_event',
        array(
            'labels' => array(
                'name'          => 'Sự kiện',
                'singular_name' => 'Sự kiện',
                'add_new_item'  => 'Thêm sự kiện',
                'edit_item'     => 'Sửa sự kiện',
                'menu_name'     => 'YangLocal · Sự kiện',
            ),
            'public'            => true,
            'show_in_rest'      => true,
            'menu_icon'         => 'dashicons-calendar-alt',
            'supports'          => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
            'has_archive'       => 'su-kien',
            'rewrite'           => array( 'slug' => 'su-kien', 'with_front' => false ),
            'show_in_nav_menus' => true,
        )
    );

    register_post_type(
        'yl_message',
        array(
            'labels' => array(
                'name'          => 'Tin nhắn liên hệ',
                'singular_name' => 'Tin nhắn liên hệ',
                'menu_name'     => 'YangLocal · Liên hệ',
            ),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => true,
            'menu_icon'       => 'dashicons-email-alt',
            'supports'        => array( 'title', 'editor' ),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        )
    );

    register_post_type(
        'yl_report',
        array(
            'labels' => array(
                'name'          => 'Báo thông tin sai',
                'singular_name' => 'Báo thông tin sai',
                'menu_name'     => 'YangLocal · Góp ý dữ liệu',
            ),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => true,
            'menu_icon'       => 'dashicons-warning',
            'supports'        => array( 'title', 'editor' ),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        )
    );

    register_post_type(
        'yl_submission',
        array(
            'labels' => array(
                'name'          => 'Đề xuất địa điểm',
                'singular_name' => 'Đề xuất địa điểm',
                'menu_name'     => 'YangLocal · Đề xuất',
            ),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => true,
            'menu_icon'       => 'dashicons-plus-alt2',
            'supports'        => array( 'title' ),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        )
    );
}

function yl_register_taxonomies() {
    register_taxonomy(
        'place_category',
        array( 'yl_place' ),
        array(
            'labels' => array(
                'name'          => 'Danh mục địa điểm',
                'singular_name' => 'Danh mục địa điểm',
                'menu_name'     => 'Danh mục',
            ),
            'public'            => true,
            'show_in_rest'      => true,
            'hierarchical'      => true,
            'rewrite'           => array( 'slug' => 'loai-dia-diem' ),
        )
    );

    register_taxonomy(
        'district',
        array( 'yl_place' ),
        array(
            'labels' => array(
                'name'          => 'Khu vực',
                'singular_name' => 'Khu vực',
                'menu_name'     => 'Khu vực',
            ),
            'public'            => true,
            'show_in_rest'      => true,
            'hierarchical'      => true,
            'rewrite'           => array( 'slug' => 'khu-vuc' ),
        )
    );



    register_taxonomy(
        'near_school',
        array( 'yl_place' ),
        array(
            'labels' => array(
                'name'          => 'Gần trường',
                'singular_name' => 'Trường đại học',
                'menu_name'     => 'Gần trường',
            ),
            'public'            => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'hierarchical'      => false,
            'rewrite'           => array( 'slug' => 'gan-truong', 'with_front' => false ),
        )
    );

    register_taxonomy(
        'event_category',
        array( 'yl_event' ),
        array(
            'labels' => array(
                'name'          => 'Loại sự kiện',
                'singular_name' => 'Loại sự kiện',
            ),
            'public'       => true,
            'show_in_rest' => true,
            'hierarchical' => true,
            'rewrite'      => array( 'slug' => 'loai-su-kien' ),
        )
    );
}
