<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function yl_expand_search_post_types( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) { return; }
    $type=isset($_GET['type'])?sanitize_key(wp_unslash($_GET['type'])):'all';
    if('place'===$type)$query->set('post_type','yl_place'); elseif('article'===$type)$query->set('post_type','post'); elseif('event'===$type)$query->set('post_type','yl_event'); elseif('school'===$type)$query->set('post__in',array(0)); else $query->set('post_type',array('yl_place','post','yl_event'));
    $query->set('posts_per_page',12);
}
add_action('pre_get_posts','yl_expand_search_post_types');

function yl_discover_shortcode() {
    $query=yl_get_filtered_places(); $districts=get_terms(array('taxonomy'=>'district','hide_empty'=>false)); $categories=get_terms(array('taxonomy'=>'place_category','hide_empty'=>false)); if(is_wp_error($districts))$districts=array(); if(is_wp_error($categories))$categories=array();
    $selected_school=isset($_GET['school'])?sanitize_title(wp_unslash($_GET['school'])):'';
    ob_start(); ?>
    <section class="yl-discover-page"><header class="yl-page-header"><p class="yl-eyebrow">Khám phá địa điểm</p><h1>Tìm đúng chỗ cho việc bạn đang cần.</h1><p>Lọc theo quận, trường, ngân sách và tiện ích. Thông tin chưa đủ rõ sẽ không được dùng để ép thành kết quả.</p></header>
    <button class="yl-filter-toggle" type="button" data-yl-filter-toggle aria-expanded="false" aria-controls="yl-discover-filters">Bộ lọc</button>
    <form id="yl-discover-filters" class="yl-filter-bar" method="get" action="" data-yl-filter-panel>
      <label><span>Từ khóa</span><input type="search" name="q" value="<?php echo isset($_GET['q'])?esc_attr(sanitize_text_field(wp_unslash($_GET['q']))):''; ?>" placeholder="Tìm quán ăn, café, trường, bảo tàng, địa điểm..."></label>
      <label><span>Khu vực</span><select name="district"><option value="">Tất cả khu vực</option><?php foreach($districts as $district): ?><option value="<?php echo esc_attr($district->slug); ?>" <?php selected(isset($_GET['district'])?sanitize_title(wp_unslash($_GET['district'])):'',$district->slug); ?>><?php echo esc_html($district->name); ?></option><?php endforeach; ?></select></label>
      <label><span>Danh mục</span><select name="category"><option value="">Tất cả danh mục</option><?php foreach($categories as $category): ?><option value="<?php echo esc_attr($category->slug); ?>" <?php selected(isset($_GET['category'])?sanitize_title(wp_unslash($_GET['category'])):'',$category->slug); ?>><?php echo esc_html($category->name); ?></option><?php endforeach; ?></select></label>
      <label class="yl-filter-school"><span>Quanh trường</span><?php echo yl_render_school_picker('school',$selected_school,'yl-discover-school'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
      <label><span>Ngân sách</span><select name="price"><option value="">Không giới hạn</option><option value="30000" <?php selected(isset($_GET['price'])?absint($_GET['price']):0,30000); ?>>Tối đa 30K</option><option value="50000" <?php selected(isset($_GET['price'])?absint($_GET['price']):0,50000); ?>>Tối đa 50K</option><option value="100000" <?php selected(isset($_GET['price'])?absint($_GET['price']):0,100000); ?>>Tối đa 100K</option></select></label>
      <div class="yl-filter-amenities"><span>Tiện ích</span><?php foreach(yl_quick_find_amenity_labels() as $key=>$label): ?><label class="yl-chip"><input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked(isset($_GET[$key])&&'1'===(string)$_GET[$key]); ?>><?php echo esc_html(str_replace(array('Có ','Không gian '),'',$label)); ?></label><?php endforeach; ?></div>
      <div class="yl-filter-recommended"><span>YangLocal chọn</span><label class="yl-chip yl-chip--recommend"><input type="checkbox" name="recommended" value="1" <?php checked(isset($_GET['recommended'])&&'1'===(string)$_GET['recommended']); ?>>Nên đi / đáng trải nghiệm</label></div>
      <label><span>Sắp xếp</span><select name="sort"><option value="relevant">Phù hợp</option><option value="newest" <?php selected(isset($_GET['sort'])?sanitize_key(wp_unslash($_GET['sort'])):'','newest'); ?>>Mới nhất</option><option value="rating" <?php selected(isset($_GET['sort'])?sanitize_key(wp_unslash($_GET['sort'])):'','rating'); ?>>Đánh giá cao</option></select></label>
      <div class="yl-filter-actions"><button class="yl-button" type="submit">Lọc địa điểm</button><a class="yl-button yl-button--ghost" href="<?php echo esc_url(get_post_type_archive_link('yl_place')); ?>">Xóa lọc</a></div>
    </form>
    <div class="yl-results-meta"><strong><?php echo esc_html(number_format_i18n($query->found_posts)); ?></strong> địa điểm phù hợp</div>
    <?php if($query->have_posts()): ?><div class="yl-place-grid yl-place-grid--three"><?php while($query->have_posts()):$query->the_post(); get_template_part('template-parts/place-card'); endwhile; ?></div><?php else: ?><div class="yl-empty-state"><h2>Chưa tìm thấy chỗ phù hợp.</h2><p>Thử bỏ bớt một tiện ích hoặc mở rộng khu vực.</p><a class="yl-button yl-button--ghost" href="<?php echo esc_url(get_post_type_archive_link('yl_place')); ?>">Xóa bộ lọc</a></div><?php endif; wp_reset_postdata(); ?>
    <?php if($query->max_num_pages>1): $allowed_args=array('q','district','category','school','price','wifi','power_outlet','quiet','group_study','air_conditioning','parking','late_open','recommended','sort');$page_args=array();foreach($allowed_args as $arg_key){if(!isset($_GET[$arg_key]))continue;$raw=wp_unslash($_GET[$arg_key]);if(in_array($arg_key,array('price'),true))$page_args[$arg_key]=absint($raw);elseif(in_array($arg_key,array('wifi','power_outlet','quiet','group_study','air_conditioning','parking','late_open','recommended'),true))$page_args[$arg_key]='1'===(string)$raw?'1':'';elseif('q'===$arg_key)$page_args[$arg_key]=sanitize_text_field($raw);else$page_args[$arg_key]=sanitize_title($raw);} $page_args=array_filter($page_args,static function($value){return ''!==$value&&null!==$value;}); ?><nav class="yl-pagination"><?php echo paginate_links(array('total'=>$query->max_num_pages,'current'=>max(1,isset($_GET['pg'])?absint($_GET['pg']):1),'base'=>esc_url_raw(add_query_arg('pg','%#%',remove_query_arg('pg'))),'format'=>'','add_args'=>$page_args)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></nav><?php endif; ?>
    </section><?php return ob_get_clean();
}
add_shortcode('yanglocal_discover','yl_discover_shortcode');

function yl_quick_find_shortcode() {
    $districts=get_terms(array('taxonomy'=>'district','hide_empty'=>false)); $categories=get_terms(array('taxonomy'=>'place_category','hide_empty'=>false)); if(is_wp_error($districts))$districts=array(); if(is_wp_error($categories))$categories=array(); $input=array();
    if(isset($_GET['yl_quick_find'])&&'1'===(string)$_GET['yl_quick_find']){
      $map=array('district'=>'qf_district','category'=>'qf_category','school'=>'qf_school','price'=>'qf_price','radius'=>'qf_radius','wifi'=>'qf_wifi','power_outlet'=>'qf_power_outlet','quiet'=>'qf_quiet','group_study'=>'qf_group_study','air_conditioning'=>'qf_air_conditioning','parking'=>'qf_parking','late_open'=>'qf_late_open');
      foreach($map as $key=>$qk){if(isset($_GET[$qk])){if('price'===$key)$input[$key]=absint($_GET[$qk]);elseif('radius'===$key)$input[$key]=(float)$_GET[$qk];elseif(in_array($key,array_keys(yl_quick_find_amenity_labels()),true))$input[$key]='1'===(string)$_GET[$qk]?'1':'';else $input[$key]=sanitize_title(wp_unslash($_GET[$qk]));}}
    }
    if ( empty($input['school']) ) { $input['radius']=0; }
    $results=$input?yl_get_quick_find_results($input,6):array(); ob_start(); ?>
    <div class="yl-quick-find" id="quick-find"><div class="yl-quick-find__intro"><p class="yl-eyebrow">Tìm nhanh theo nhu cầu</p><h2>Chọn khu vực, trường và thứ bạn đang cần.</h2><p>Kết quả ưu tiên những nơi phù hợp với lựa chọn của bạn, có ảnh dễ nhận diện và có thể mở Maps khi cần kiểm tra thêm.</p></div>
    <form class="yl-quick-find__form" data-yl-qf-form method="get" action="<?php echo esc_url(home_url('/')); ?>#quick-find"><input type="hidden" name="yl_quick_find" value="1">
      <label><span>Khu vực</span><select name="qf_district"><option value="">Bất kỳ khu vực</option><?php foreach($districts as $d): ?><option value="<?php echo esc_attr($d->slug); ?>" <?php selected(isset($input['district'])?$input['district']:'',$d->slug); ?>><?php echo esc_html($d->name); ?></option><?php endforeach; ?></select></label>
      <label><span>Tôi cần</span><select name="qf_category"><option value="">Bất kỳ nhu cầu</option><?php foreach($categories as $c): ?><option value="<?php echo esc_attr($c->slug); ?>" <?php selected(isset($input['category'])?$input['category']:'',$c->slug); ?>><?php echo esc_html($c->name); ?></option><?php endforeach; ?></select></label>
      <label class="yl-filter-school"><span>Trường</span><?php echo yl_render_school_picker('qf_school',isset($input['school'])?$input['school']:'','yl-qf-school'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
      <label><span>Khoảng cách</span><select name="qf_radius" aria-describedby="yl-qf-radius-help"><option value="">Không giới hạn</option><?php foreach(array('0.5'=>'≤ 500 m','1'=>'≤ 1 km','2'=>'≤ 2 km','5'=>'≤ 5 km') as $v=>$label): ?><option value="<?php echo esc_attr($v); ?>" <?php selected(isset($input['radius'])?(float)$input['radius']:0,(float)$v); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select><small id="yl-qf-radius-help" class="yl-field-hint">Bật bán kính khi bạn muốn ưu tiên các địa điểm có vị trí đã được đối chiếu.</small></label>
      <label><span>Ngân sách</span><select name="qf_price"><option value="">Không giới hạn</option><option value="30000" <?php selected(isset($input['price'])?(int)$input['price']:0,30000); ?>>≤ 30K</option><option value="50000" <?php selected(isset($input['price'])?(int)$input['price']:0,50000); ?>>≤ 50K</option><option value="100000" <?php selected(isset($input['price'])?(int)$input['price']:0,100000); ?>>≤ 100K</option></select></label>
      <div class="yl-quick-find__checks"><?php $qn=array('wifi'=>'qf_wifi','power_outlet'=>'qf_power_outlet','quiet'=>'qf_quiet','group_study'=>'qf_group_study','air_conditioning'=>'qf_air_conditioning','parking'=>'qf_parking','late_open'=>'qf_late_open'); foreach(yl_quick_find_amenity_labels() as $key=>$label): ?><label class="yl-chip"><input type="checkbox" name="<?php echo esc_attr($qn[$key]); ?>" value="1" <?php checked(!empty($input[$key])); ?>><?php echo esc_html(str_replace(array('Có ','Không gian '),'',$label)); ?></label><?php endforeach; ?></div>
      <button class="yl-button" type="submit">Tìm chỗ phù hợp</button>
    </form>
    <?php if ( $input ) : ?>
      <div class="yl-quick-find__results">
        <div class="yl-section-heading yl-section-heading--compact"><div><p class="yl-eyebrow">Kết quả</p><h3><?php echo $results ? 'Những chỗ khớp nhất' : 'Chưa có chỗ khớp đủ tiêu chí'; ?></h3></div></div>
        <?php if ( $results ) : ?><div class="yl-qf-grid">
          <?php foreach ( $results as $result ) :
            $place = $result['post'];
            $fallback_key = function_exists( 'yl_fallback_key_for_post' ) ? yl_fallback_key_for_post( $place->ID, 'cafe' ) : 'cafe';
            $cover = function_exists( 'yl_place_cover_info' ) ? yl_place_cover_info( $place->ID, $fallback_key ) : array();
            $image = ! empty( $cover['url'] ) ? $cover['url'] : ( function_exists( 'yl_neutral_fallback_image_url' ) ? yl_neutral_fallback_image_url( $fallback_key ) : '' );
            $fallback = ! empty( $cover['fallback_url'] ) ? $cover['fallback_url'] : ( function_exists( 'yl_neutral_fallback_image_url' ) ? yl_neutral_fallback_image_url( $fallback_key ) : '' );
            $image_label = ! empty( $cover['label'] ) ? $cover['label'] : '';
            $image_alt = ! empty( $cover['alt'] ) ? $cover['alt'] : 'Ảnh tham khảo cho ' . get_the_title( $place );
            $has_photo = (bool) $image;
            $district_terms = wp_get_post_terms( $place->ID, 'district' );
            $category_terms = wp_get_post_terms( $place->ID, 'place_category' );
            $district_label = ( ! is_wp_error( $district_terms ) && $district_terms ) ? $district_terms[0]->name : 'Hà Nội';
            $category_label = ( ! is_wp_error( $category_terms ) && $category_terms ) ? $category_terms[0]->name : 'Địa điểm';
            $price_from = absint( get_post_meta( $place->ID, '_yl_price_from', true ) );
            $maps_url = function_exists( 'yl_get_google_maps_url' ) ? yl_get_google_maps_url( $place->ID ) : '';
          ?>
          <article class="yl-qf-result<?php echo $has_photo ? '' : ' yl-qf-result--neutral'; ?>">
            <?php if ( $has_photo ) : ?>
            <button type="button" class="yl-qf-result__image yl-place-photo-trigger" data-yl-place-photos data-post-id="<?php echo esc_attr( $place->ID ); ?>" data-title="<?php echo esc_attr( get_the_title( $place ) ); ?>" data-maps-url="<?php echo esc_url( $maps_url ); ?>" aria-label="Xem ảnh của <?php echo esc_attr( get_the_title( $place ) ); ?>">
              <img src="<?php echo esc_url( $image ); ?>" data-fallback-src="<?php echo esc_url( $fallback ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy" decoding="async">
              <?php if ( $image_label ) : ?><span class="yl-image-badge"><?php echo esc_html( $image_label ); ?></span><?php endif; ?>
            </button>
            <?php endif; ?>
            <div class="yl-qf-result__body">
              <div class="yl-qf-result__score">Khớp <strong><?php echo esc_html( count( $result['reasons'] ) ); ?></strong> tiêu chí</div>
              <h4><a href="<?php echo esc_url( get_permalink( $place ) ); ?>"><?php echo esc_html( get_the_title( $place ) ); ?></a></h4>
              <p class="yl-qf-result__meta"><?php echo esc_html( $category_label . ' · ' . $district_label ); ?><?php if ( $price_from ) : ?> · <?php echo esc_html( function_exists( 'yl_get_price_label' ) ? yl_get_price_label( $price_from ) : number_format_i18n( $price_from ) . 'đ' ); ?><?php endif; ?></p>
              <?php if ( $result['reasons'] ) : ?><ul class="yl-qf-reasons"><?php foreach ( array_slice( $result['reasons'], 0, 4 ) as $reason ) : ?><li><?php echo esc_html( $reason ); ?></li><?php endforeach; ?></ul><?php endif; ?>
              <div class="yl-qf-result__actions"><a class="yl-text-link" href="<?php echo esc_url( get_permalink( $place ) ); ?>">Xem chi tiết →</a><?php if ( $maps_url ) : ?><a class="yl-text-link" href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer">Chỉ đường ↗</a><?php endif; ?></div>
            </div>
          </article>
          <?php endforeach; ?>
        </div><?php else : ?><div class="yl-empty-state"><p>Không có địa điểm nào khớp đủ điều kiện. Bạn có thể nới một tiêu chí:</p><ul><?php foreach ( yl_get_quick_find_relaxation_hints( $input ) as $hint ) : ?><li><?php echo esc_html( $hint ); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      </div>
    <?php endif; ?>
    </div><?php return ob_get_clean();
}
add_shortcode('yanglocal_quick_find','yl_quick_find_shortcode');
