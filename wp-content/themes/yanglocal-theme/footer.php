</main>
<footer class="yl-footer">
    <div class="yl-container">
        <div class="yl-footer__grid yl-footer__grid--v724">
            <div class="yl-footer__intro">
                <a class="yl-footer-brand yl-brand--image" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="YangLocal — Trang chủ"><img src="<?php echo esc_url( trailingslashit( get_template_directory_uri() ) . 'assets/images/brand/yanglocal-mark-v7241-r12.svg' ); ?>" alt="" width="38" height="38" aria-hidden="true"><strong>YangLocal</strong></a>
                <p><?php echo esc_html( get_theme_mod( 'yl_footer_description', 'Đi đâu, ăn gì, học ở đâu — có YangLocal.' ) ); ?></p>
                <p class="yl-footer-credit">YangLocal là project sinh viên được phát triển bởi Nguyen Hong Giang.</p>
            </div>
            <div><h3>Khám phá</h3><ul>
                <li><a href="<?php echo esc_url( add_query_arg( 'category', 'quan-an', get_post_type_archive_link( 'yl_place' ) ) ); ?>">Ăn uống</a></li>
                <li><a href="<?php echo esc_url( add_query_arg( 'category', 'cafe', get_post_type_archive_link( 'yl_place' ) ) ); ?>">Café & Học</a></li>
                <li><a href="<?php echo esc_url( home_url( '/gan-truong/' ) ); ?>">Gần trường</a></li>
                <li><a href="<?php echo esc_url( add_query_arg( 'category', 'lich-su-di-san', get_post_type_archive_link( 'yl_place' ) ) ); ?>">Lịch sử</a></li>
                <li><a href="<?php echo esc_url( add_query_arg( 'category', 'bao-tang', get_post_type_archive_link( 'yl_place' ) ) ); ?>">Bảo tàng</a></li>
            </ul></div>
            <div><h3>Hỗ trợ</h3><ul>
                <?php $yl_posts_page_id=(int)get_option('page_for_posts'); $yl_guide_url=$yl_posts_page_id?get_permalink($yl_posts_page_id):home_url('/?post_type=post'); ?>
                <li><a href="<?php echo esc_url( $yl_guide_url ); ?>">Cẩm nang</a></li>
                <li><a href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">Liên hệ</a></li>
                <li><a href="<?php echo esc_url( home_url( '/ve-yanglocal/' ) ); ?>">Về YangLocal</a></li>
                <li><a href="<?php echo esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/chinh-sach-rieng-tu/' ) ); ?>">Riêng tư</a></li>
            </ul></div>
            <div><h3>Tài khoản</h3><ul>
                <li><a href="<?php echo esc_url( home_url( '/dang-nhap/' ) ); ?>"><?php echo is_user_logged_in() ? 'Tài khoản' : 'Đăng nhập'; ?></a></li>
                <li><a href="<?php echo esc_url( home_url( '/da-luu/' ) ); ?>">Địa điểm đã lưu</a></li>
                <li><a href="<?php echo esc_url( get_post_type_archive_link( 'yl_event' ) ); ?>">Sự kiện</a></li>
            </ul></div>
        </div>
        <div class="yl-footer__bottom"><span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> YangLocal.</span><span>Một local guide Hà Nội cho sinh viên.</span></div>
    </div>
</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
