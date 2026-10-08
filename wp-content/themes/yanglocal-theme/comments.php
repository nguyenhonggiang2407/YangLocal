<?php
if ( post_password_required() ) { return; }
$commenter = wp_get_current_commenter();
$req       = get_option( 'require_name_email' );
$required  = $req ? ' required' : '';
?>
<div id="comments" class="yl-review-block">
    <header class="yl-review-block__head"><p class="yl-eyebrow">Trải nghiệm thật</p><h2>Bạn đã đến đây chưa?</h2><p>Chia sẻ trải nghiệm để người đi sau dễ quyết định hơn.</p></header>
    <?php if ( have_comments() ) : ?><ol class="yl-comments-list"><?php wp_list_comments( array( 'style'=>'ol','short_ping'=>true,'callback'=>'yl_comment_callback' ) ); ?></ol><?php the_comments_pagination(); ?>
    <?php else : ?><div class="yl-review-empty"><strong>Chưa có đánh giá</strong><span>Nếu bạn đã ghé, bạn có thể là người đầu tiên chia sẻ.</span></div><?php endif; ?>
    <?php
    $fields = array(
        'author' => '<p class="comment-form-author"><label for="author">Tên' . ( $req ? ' <span class="required">*</span>' : '' ) . '</label><input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" maxlength="245" autocomplete="name"' . $required . '></p>',
        'email'  => '<p class="comment-form-email"><label for="email">Email' . ( $req ? ' <span class="required">*</span>' : '' ) . '</label><input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" maxlength="100" autocomplete="email"' . $required . '><small>Email không hiển thị công khai.</small></p>',
    );
    comment_form( array(
        'title_reply'=>'Viết đánh giá','label_submit'=>'Gửi đánh giá','fields'=>$fields,
        'comment_field'=>'<p class="comment-form-comment"><label for="comment">Trải nghiệm <span class="required">*</span></label><textarea id="comment" name="comment" rows="5" maxlength="4000" required placeholder="Có gì đáng thử? Giá có đúng không? Không gian thế nào?"></textarea></p>',
        'comment_notes_before'=>'','comment_notes_after'=>'','logged_in_as'=>'<p class="logged-in-as">Bạn đang dùng tài khoản YangLocal để gửi đánh giá.</p>',
    ) );
    ?>
</div>
