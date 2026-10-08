<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function yl_school_seed_data() {
    $path = YL_CORE_PATH . 'data/schools.json';
    if ( ! file_exists( $path ) ) { return array(); }
    $decoded = json_decode( file_get_contents( $path ), true );
    return is_array( $decoded ) ? $decoded : array();
}

function yl_school_meta_keys() {
    return array( 'code','short_name','aliases','school_type','official_website','campus_name','address','ward','district','city','latitude','longitude','source_url','coord_source_url','source_checked','verified','coord_verified','priority','nearby_districts','cluster','image_source','logo_source' );
}

function yl_sync_school_terms() {
    if ( ! taxonomy_exists( 'near_school' ) ) { return; }
    foreach ( yl_school_seed_data() as $data ) {
        if ( empty( $data['slug'] ) || empty( $data['name'] ) ) { continue; }
        $slug = sanitize_title( $data['slug'] );
        $term = get_term_by( 'slug', $slug, 'near_school' );
        $args = array( 'slug' => $slug, 'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '' );
        if ( ! $term ) {
            $inserted = wp_insert_term( sanitize_text_field( $data['name'] ), 'near_school', $args );
            if ( is_wp_error( $inserted ) ) { continue; }
            $term = get_term( $inserted['term_id'], 'near_school' );
        } else {
            $args['name'] = sanitize_text_field( $data['name'] );
            wp_update_term( $term->term_id, 'near_school', $args );
            $term = get_term( $term->term_id, 'near_school' );
        }
        if ( ! $term || is_wp_error( $term ) ) { continue; }
        foreach ( yl_school_meta_keys() as $key ) {
            if ( ! array_key_exists( $key, $data ) ) { continue; }
            $value = $data[ $key ];
            if ( is_array( $value ) ) { $value = implode( ',', array_map( 'sanitize_text_field', $value ) ); }
            if ( in_array( $key, array( 'verified','coord_verified' ), true ) ) { $value = ! empty( $value ) ? '1' : '0'; }
            update_term_meta( $term->term_id, 'yl_school_' . $key, sanitize_text_field( (string) $value ) );
        }
    }
}

function yl_get_school_meta( $term, $key, $default = '' ) {
    if ( is_string( $term ) ) { $term = get_term_by( 'slug', sanitize_title( $term ), 'near_school' ); }
    if ( ! $term || is_wp_error( $term ) ) { return $default; }
    $value = get_term_meta( $term->term_id, 'yl_school_' . $key, true );
    return '' !== $value ? $value : $default;
}

function yl_get_school_freshness( $term ) {
    $checked = yl_get_school_meta( $term, 'source_checked' );
    if ( ! $checked || ! strtotime( $checked ) ) { return array( 'status'=>'unknown','label'=>'Chưa xác minh ngày','days'=>null ); }
    $days = max( 0, (int) floor( ( current_time( 'timestamp' ) - strtotime( $checked . ' 00:00:00' ) ) / DAY_IN_SECONDS ) );
    if ( $days < 60 ) { return array( 'status'=>'fresh','label'=>'Mới kiểm tra','days'=>$days ); }
    if ( $days <= 120 ) { return array( 'status'=>'review','label'=>'Cần kiểm tra','days'=>$days ); }
    return array( 'status'=>'stale','label'=>'Đã cũ','days'=>$days );
}


/**
 * District slugs that can be used as a transparent regional fallback when a
 * school has not accumulated explicit place relationships yet. This is never
 * presented as a measured walking distance.
 */
function yl_get_school_nearby_district_slugs( $term ) {
    $raw = yl_get_school_meta( $term, 'nearby_districts', '' );
    if ( ! $raw ) { return array(); }
    $slugs = array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $raw ) ) ) );
    return array_values( array_unique( $slugs ) );
}

function yl_school_place_coverage_label( $term ) {
    if ( is_string( $term ) ) { $term = get_term_by( 'slug', sanitize_title( $term ), 'near_school' ); }
    if ( ! $term || is_wp_error( $term ) ) { return 'Đang bổ sung dữ liệu'; }
    if ( (int) $term->count > 0 ) {
        return number_format_i18n( (int) $term->count ) . ' địa điểm đã gắn';
    }
    $verified = '1' === yl_get_school_meta( $term, 'verified', '0' );
    if ( $verified && yl_get_school_nearby_district_slugs( $term ) ) {
        return 'Có gợi ý theo khu vực';
    }
    if ( ! $verified ) {
        return 'Đang xác minh khu vực';
    }
    return 'Đang bổ sung dữ liệu khu vực';
}

function yl_school_maps_url( $term ) {
    $address = yl_get_school_meta( $term, 'address', '' );
    $query = $address ? $address : ( is_object( $term ) ? $term->name . ', Hà Nội' : 'Hà Nội' );
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
}

function yl_get_school_picker_data() {
    $terms = get_terms( array( 'taxonomy'=>'near_school', 'hide_empty'=>false ) );
    if ( is_wp_error( $terms ) ) { return array(); }
    $items = array();
    foreach ( $terms as $term ) {
        $aliases = array_filter( array_map( 'trim', explode( ',', yl_get_school_meta( $term, 'aliases' ) ) ) );
        $items[] = array(
            'slug'=>$term->slug, 'name'=>$term->name,
            'short'=>yl_get_school_meta( $term, 'short_name', $term->name ),
            'aliases'=>$aliases, 'district'=>yl_get_school_meta( $term, 'district' ),
            'priority'=>(int) yl_get_school_meta( $term, 'priority', 0 ),
            'verified'=>'1' === yl_get_school_meta( $term, 'verified', '0' ),
            'url'=>get_term_link( $term ),
        );
    }
    usort( $items, function( $a, $b ) {
        if ( 'hubt' === $a['slug'] ) { return -1; }
        if ( 'hubt' === $b['slug'] ) { return 1; }
        if ( $a['priority'] === $b['priority'] ) { return strcasecmp( $a['name'], $b['name'] ); }
        return $b['priority'] <=> $a['priority'];
    } );
    return $items;
}

function yl_render_school_picker( $name, $selected = '', $id = '' ) {
    $id = $id ? sanitize_html_class( $id ) : sanitize_html_class( $name );
    $selected_term = $selected ? get_term_by( 'slug', sanitize_title( $selected ), 'near_school' ) : false;
    $label = $selected_term ? $selected_term->name : '';
    ob_start(); ?>
    <div class="yl-school-picker" data-yl-school-picker>
        <input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $selected ); ?>" data-yl-school-value>
        <input id="<?php echo esc_attr( $id ); ?>" type="search" value="<?php echo esc_attr( $label ); ?>" placeholder="Tìm HUBT, Bách Khoa, NEU…" autocomplete="off" data-yl-school-search aria-label="Tìm trường đại học" aria-autocomplete="list" aria-haspopup="listbox" aria-expanded="false">
        <div class="yl-school-picker__results" data-yl-school-results role="listbox" hidden></div>
        <small class="yl-field-hint yl-school-picker__error" data-yl-school-error hidden>Hãy chọn một trường trong danh sách gợi ý.</small>
    </div><?php
    return ob_get_clean();
}

function yl_get_nearby_schools( $term, $limit = 8, $max_km = 10 ) {
    if ( is_string( $term ) ) { $term = get_term_by( 'slug', sanitize_title( $term ), 'near_school' ); }
    if ( ! $term || is_wp_error( $term ) ) { return array(); }
    $lat = yl_get_school_meta( $term, 'latitude' ); $lng = yl_get_school_meta( $term, 'longitude' );
    if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) || '1' !== yl_get_school_meta( $term, 'coord_verified', '0' ) ) { return array(); }
    $all = get_terms( array( 'taxonomy'=>'near_school', 'hide_empty'=>false, 'exclude'=>array( $term->term_id ) ) );
    if ( is_wp_error( $all ) ) { return array(); }
    $rows = array();
    foreach ( $all as $other ) {
        $olat=yl_get_school_meta( $other, 'latitude' ); $olng=yl_get_school_meta( $other, 'longitude' );
        if ( ! is_numeric( $olat ) || ! is_numeric( $olng ) || '1' !== yl_get_school_meta( $other, 'coord_verified', '0' ) ) { continue; }
        $km = function_exists( 'yl_haversine_distance_km' ) ? yl_haversine_distance_km( $lat, $lng, $olat, $olng ) : null;
        if ( null !== $km && $km <= (float) $max_km ) { $rows[] = array( 'term'=>$other, 'distance'=>$km ); }
    }
    usort( $rows, function( $a,$b ){ return $a['distance'] <=> $b['distance']; } );
    return array_slice( $rows, 0, max( 1, absint( $limit ) ) );
}

function yl_school_term_add_fields() { ?>
    <div class="form-field"><label for="yl_school_short_name">Tên ngắn</label><input name="yl_school_short_name" id="yl_school_short_name" type="text"><p>Ví dụ HUBT, HUST, NEU.</p></div>
    <div class="form-field"><label for="yl_school_aliases">Aliases</label><input name="yl_school_aliases" id="yl_school_aliases" type="text"><p>Phân tách bằng dấu phẩy; dùng cho tìm kiếm campus.</p></div>
    <div class="form-field"><label for="yl_school_school_type">Loại cơ sở</label><select name="yl_school_school_type" id="yl_school_school_type"><option value="university">Trường đại học</option><option value="academy">Học viện</option><option value="university_system">Đại học / hệ thống</option><option value="military">Quân đội / công an</option></select></div>
    <div class="form-field"><label for="yl_school_campus_name">Tên campus</label><input name="yl_school_campus_name" id="yl_school_campus_name" type="text"></div>
    <div class="form-field"><label for="yl_school_address">Địa chỉ campus</label><input name="yl_school_address" id="yl_school_address" type="text"></div>
    <div class="form-field"><label for="yl_school_ward">Phường / xã</label><input name="yl_school_ward" id="yl_school_ward" type="text"></div>
    <div class="form-field"><label for="yl_school_district">Khu vực</label><input name="yl_school_district" id="yl_school_district" type="text"></div>
    <div class="form-field"><label>Tọa độ</label><input name="yl_school_latitude" type="text" placeholder="Latitude"><input name="yl_school_longitude" type="text" placeholder="Longitude"><p>Chỉ nhập khi đã xác minh nguồn tọa độ.</p></div>
    <div class="form-field"><label for="yl_school_official_website">Website chính thức</label><input name="yl_school_official_website" id="yl_school_official_website" type="url"></div>
    <div class="form-field"><label for="yl_school_source_url">Nguồn dữ liệu</label><input name="yl_school_source_url" id="yl_school_source_url" type="url"></div>
    <div class="form-field"><label for="yl_school_coord_source_url">Nguồn tọa độ</label><input name="yl_school_coord_source_url" id="yl_school_coord_source_url" type="url"></div>
    <div class="form-field"><label for="yl_school_source_checked">Ngày kiểm tra</label><input name="yl_school_source_checked" id="yl_school_source_checked" type="date"></div>
    <div class="form-field"><label for="yl_school_nearby_districts">Quận/khu vực gợi ý</label><input name="yl_school_nearby_districts" id="yl_school_nearby_districts" type="text"><p>Slug phân tách bằng dấu phẩy, ví dụ: dong-da,cau-giay. Dùng làm fallback khu vực, không phải khoảng cách mét.</p></div>
    <div class="form-field"><label for="yl_school_cluster">Cụm campus</label><input name="yl_school_cluster" id="yl_school_cluster" type="text"></div>
    <div class="form-field"><label for="yl_school_priority">Priority</label><input name="yl_school_priority" id="yl_school_priority" type="number" min="0" max="100" value="0"><p>HUBT = 100; dùng để sắp xếp School Picker.</p></div>
    <div class="form-field"><label><input name="yl_school_verified" type="checkbox" value="1"> Đã xác minh thông tin campus</label><br><label><input name="yl_school_coord_verified" type="checkbox" value="1"> Tọa độ đã xác minh</label></div>
<?php }
add_action( 'near_school_add_form_fields', 'yl_school_term_add_fields' );

function yl_school_term_edit_fields( $term ) {
    $fields = array(
        'short_name'=>'Tên ngắn', 'aliases'=>'Aliases', 'campus_name'=>'Tên campus', 'address'=>'Địa chỉ campus', 'ward'=>'Phường / xã',
        'district'=>'Khu vực', 'latitude'=>'Latitude', 'longitude'=>'Longitude', 'official_website'=>'Website chính thức',
        'source_url'=>'Nguồn dữ liệu', 'coord_source_url'=>'Nguồn tọa độ', 'source_checked'=>'Ngày kiểm tra', 'nearby_districts'=>'Quận/khu vực gợi ý', 'cluster'=>'Cụm campus', 'priority'=>'Priority',
    ); ?>
    <tr class="form-field"><th><label for="yl_school_school_type">Loại cơ sở</label></th><td><select name="yl_school_school_type" id="yl_school_school_type"><?php foreach ( array( 'university'=>'Trường đại học','academy'=>'Học viện','university_system'=>'Đại học / hệ thống','military'=>'Quân đội / công an' ) as $value=>$label ) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected( yl_get_school_meta($term,'school_type','university'), $value ); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></td></tr>
    <?php foreach ( $fields as $key=>$label ) : ?><tr class="form-field"><th><label for="yl_school_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th><td><input name="yl_school_<?php echo esc_attr($key); ?>" id="yl_school_<?php echo esc_attr($key); ?>" type="<?php echo in_array($key,array('official_website','source_url','coord_source_url'),true)?'url':('priority'===$key?'number':'text'); ?>" value="<?php echo esc_attr( yl_get_school_meta( $term, $key ) ); ?>"<?php echo 'priority'===$key?' min="0" max="100"':''; ?>></td></tr><?php endforeach; ?>
    <tr class="form-field"><th>Xác minh</th><td><label><input name="yl_school_verified" type="checkbox" value="1" <?php checked( yl_get_school_meta($term,'verified','0'),'1' ); ?>> Đã xác minh thông tin campus</label><br><label><input name="yl_school_coord_verified" type="checkbox" value="1" <?php checked( yl_get_school_meta($term,'coord_verified','0'),'1' ); ?>> Tọa độ đã xác minh</label></td></tr>
<?php }
add_action( 'near_school_edit_form_fields', 'yl_school_term_edit_fields' );

function yl_school_term_save_fields( $term_id ) {
    if ( ! current_user_can( 'manage_categories' ) ) { return; }
    $text_keys = array( 'short_name','aliases','school_type','campus_name','address','ward','district','source_checked','nearby_districts','cluster' );
    foreach ( $text_keys as $key ) {
        if ( isset( $_POST['yl_school_'.$key] ) ) { update_term_meta( $term_id, 'yl_school_'.$key, sanitize_text_field( wp_unslash( $_POST['yl_school_'.$key] ) ) ); }
    }
    foreach ( array( 'official_website','source_url','coord_source_url' ) as $key ) {
        if ( isset( $_POST['yl_school_'.$key] ) ) { update_term_meta( $term_id, 'yl_school_'.$key, esc_url_raw( wp_unslash( $_POST['yl_school_'.$key] ) ) ); }
    }
    foreach ( array( 'latitude','longitude' ) as $key ) {
        if ( isset( $_POST['yl_school_'.$key] ) ) {
            $value = trim( (string) wp_unslash( $_POST['yl_school_'.$key] ) );
            update_term_meta( $term_id, 'yl_school_'.$key, is_numeric( $value ) ? (string) (float) $value : '' );
        }
    }
    if ( isset( $_POST['yl_school_priority'] ) ) { update_term_meta( $term_id, 'yl_school_priority', (string) min( 100, absint( $_POST['yl_school_priority'] ) ) ); }
    update_term_meta( $term_id, 'yl_school_verified', isset($_POST['yl_school_verified']) ? '1':'0' );
    update_term_meta( $term_id, 'yl_school_coord_verified', isset($_POST['yl_school_coord_verified']) ? '1':'0' );
}
add_action( 'created_near_school', 'yl_school_term_save_fields' );
add_action( 'edited_near_school', 'yl_school_term_save_fields' );

function yl_school_admin_columns( $columns ) {
    $columns['yl_school_short'] = 'Tên ngắn';
    $columns['yl_school_verify'] = 'Xác minh';
    $columns['yl_school_freshness'] = 'Độ mới';
    return $columns;
}
add_filter( 'manage_edit-near_school_columns', 'yl_school_admin_columns' );

function yl_school_admin_column_content( $content, $column, $term_id ) {
    $term = get_term( $term_id, 'near_school' );
    if ( ! $term || is_wp_error( $term ) ) { return $content; }
    if ( 'yl_school_short' === $column ) { return esc_html( yl_get_school_meta( $term, 'short_name', '—' ) ); }
    if ( 'yl_school_verify' === $column ) {
        $verified = '1' === yl_get_school_meta( $term, 'verified', '0' );
        $coord = '1' === yl_get_school_meta( $term, 'coord_verified', '0' );
        return esc_html( ($verified?'Dữ liệu ✓':'Đang bổ sung') . ($coord?' · GPS ✓':'') );
    }
    if ( 'yl_school_freshness' === $column ) {
        $fresh = yl_get_school_freshness( $term );
        $date = yl_get_school_meta( $term, 'source_checked' );
        return esc_html( $fresh['label'] . ($date?' · '.$date:'') );
    }
    return $content;
}
add_filter( 'manage_near_school_custom_column', 'yl_school_admin_column_content', 10, 3 );

function yl_schools_shortcode() {
    $terms = get_terms( array( 'taxonomy'=>'near_school','hide_empty'=>false ) );
    if ( is_wp_error( $terms ) || ! $terms ) { return '<div class="yl-empty-state"><p>Chưa có dữ liệu trường đại học.</p></div>'; }
    $hubt = get_term_by( 'slug','hubt','near_school' );
    $near = $hubt ? yl_get_nearby_schools( $hubt, 6, 10 ) : array();
    $popular=array(); $verified_count=0; $pending_count=0; $clusters=array();
    foreach ( $terms as $term ) {
        if ('1'===yl_get_school_meta($term,'verified','0')) $verified_count++; else $pending_count++;
        if ((int)yl_get_school_meta($term,'priority',0)>=75 && 'hubt'!==$term->slug) $popular[]=$term;
        $cluster=yl_get_school_meta($term,'cluster'); if($cluster){ if(!isset($clusters[$cluster]))$clusters[$cluster]=array(); $clusters[$cluster][]=$term; }
    }
    foreach($clusters as $cluster_key=>$cluster_terms){ if(count($cluster_terms)<2)unset($clusters[$cluster_key]); }
    usort($popular,function($a,$b){return (int)yl_get_school_meta($b,'priority',0)<=>(int)yl_get_school_meta($a,'priority',0);});
    ob_start(); ?>
    <section class="yl-campus-directory" data-yl-school-directory>
        <header class="yl-page-header yl-campus-directory__hero"><p class="yl-eyebrow">Quanh trường cùng YangLocal</p><h1>Bạn học trường nào?</h1><p>Tìm trường bằng tên hoặc viết tắt để xem chỗ ăn, học, in tài liệu và đi chơi quanh khu bạn học.</p>
        <form class="yl-campus-search" data-yl-campus-jump data-base="<?php echo esc_url( home_url('/gan-truong/') ); ?>"><?php echo yl_render_school_picker('school','','yl-campus-school'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><button class="yl-button" type="submit">Xem quanh trường</button></form>
        <p class="yl-campus-coverage">Danh sách trường được cập nhật dần; khoảng cách chỉ hiện khi có tọa độ đủ tin cậy.</p></header>
        <?php if ( $hubt ) : ?><section class="yl-campus-feature"><div><p class="yl-eyebrow">Ưu tiên cho bạn</p><h2>Quanh HUBT có gì?</h2><p>Ăn trưa, café, photocopy và vài chỗ đi sau giờ học quanh Vĩnh Tuy.</p><a class="yl-button" href="<?php echo esc_url(get_term_link($hubt)); ?>">Mở HUBT Campus</a></div><div class="yl-campus-feature__facts"><span>HUBT</span><strong><?php echo esc_html( number_format_i18n($hubt->count) ); ?></strong><small>địa điểm đã gắn</small></div></section><?php endif; ?>
        <?php if ( $near ) : ?><section class="yl-campus-section"><div class="yl-section-heading yl-section-heading--compact"><div><p class="yl-eyebrow">Quanh HUBT</p><h2>Các trường ở gần</h2></div></div><div class="yl-school-index-grid"><?php foreach($near as $row): $t=$row['term']; ?><a class="yl-school-index-card" href="<?php echo esc_url(get_term_link($t)); ?>"><span class="yl-eyebrow"><?php echo esc_html(yl_get_school_meta($t,'short_name',$t->name)); ?></span><strong><?php echo esc_html($t->name); ?></strong><p><?php echo esc_html(yl_format_distance($row['distance']).' từ HUBT'); ?></p><small><?php echo esc_html(yl_get_school_meta($t,'district','Hà Nội')); ?></small></a><?php endforeach; ?></div></section><?php endif; ?>
        <?php if($clusters): $cluster_labels=array('bach-kinh-xay'=>'Bách – Kinh – Xây','cau-giay'=>'Cầu Giấy','chua-lang-nguyen-chi-thanh'=>'Chùa Láng – Nguyễn Chí Thanh','dong-da'=>'Đống Đa','tu-liem'=>'Từ Liêm','hoang-mai-thanh-xuan'=>'Hoàng Mai – Thanh Xuân','ha-dong'=>'Hà Đông','nguyen-trai-thanh-xuan'=>'Nguyễn Trãi – Thanh Xuân','gia-lam'=>'Gia Lâm','kim-ma-ba-dinh'=>'Kim Mã – Ba Đình','hoa-lac'=>'Hòa Lạc'); ?><section class="yl-campus-section"><div class="yl-section-heading yl-section-heading--compact"><div><p class="yl-eyebrow">Cụm campus</p><h2>Tìm theo khu học tập</h2><p>Chọn theo cụm trường để xem nhanh các địa điểm quanh khu học tập.</p></div></div><div class="yl-campus-cluster-grid"><?php foreach($clusters as $cluster_key=>$cluster_terms): ?><div class="yl-campus-cluster"><h3><?php echo esc_html(isset($cluster_labels[$cluster_key])?$cluster_labels[$cluster_key]:ucwords(str_replace('-',' ',$cluster_key))); ?></h3><div><?php foreach($cluster_terms as $cluster_term): ?><a href="<?php echo esc_url(get_term_link($cluster_term)); ?>"><?php echo esc_html(yl_get_school_meta($cluster_term,'short_name',$cluster_term->name)); ?></a><?php endforeach; ?></div></div><?php endforeach; ?></div></section><?php endif; ?>
        <section class="yl-campus-section"><div class="yl-section-heading yl-section-heading--compact"><div><p class="yl-eyebrow">Campus phổ biến</p><h2>Tìm nhanh theo trường</h2></div></div><div class="yl-school-index-grid"><?php foreach(array_slice($popular,0,12) as $term): ?><a class="yl-school-index-card" href="<?php echo esc_url(get_term_link($term)); ?>"><span class="yl-eyebrow"><?php echo esc_html(yl_get_school_meta($term,'short_name',$term->name)); ?></span><strong><?php echo esc_html($term->name); ?></strong><p><?php echo esc_html($term->description); ?></p><small><?php echo esc_html( yl_school_place_coverage_label( $term ) ); ?></small></a><?php endforeach; ?></div></section>
        <section class="yl-campus-section"><details class="yl-campus-all"><summary>Tất cả trường (<?php echo esc_html(count($terms)); ?>)</summary><div class="yl-school-index-grid"><?php foreach($terms as $term): ?><a class="yl-school-index-card" data-school-card data-school-search="<?php echo esc_attr(strtolower($term->name.' '.yl_get_school_meta($term,'short_name').' '.yl_get_school_meta($term,'aliases'))); ?>" href="<?php echo esc_url(get_term_link($term)); ?>"><span class="yl-eyebrow"><?php echo esc_html(yl_get_school_meta($term,'short_name',$term->name)); ?></span><strong><?php echo esc_html($term->name); ?></strong><small><?php echo esc_html(yl_get_school_meta($term,'district','Hà Nội')); ?> · <?php echo esc_html( yl_school_place_coverage_label( $term ) ); ?></small></a><?php endforeach; ?></div></details></section>
    </section><?php return ob_get_clean();
}
add_shortcode( 'yanglocal_schools', 'yl_schools_shortcode' );
