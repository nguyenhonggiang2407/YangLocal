<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function yl_review_rating_field() {
    if ( ! is_singular( 'yl_place' ) ) {
        return;
    }
    echo '<fieldset class="comment-form-rating yl-star-rating"><legend>Đánh giá <span class="required" aria-hidden="true">*</span></legend><div class="yl-star-rating__choices">';
    for ( $i = 5; $i >= 1; $i-- ) {
        printf(
            '<input type="radio" id="yl_rating_%1$d" name="yl_rating" value="%1$d" required><label for="yl_rating_%1$d" title="%1$d sao"><span aria-hidden="true">★</span><span class="screen-reader-text">%1$d sao</span></label>',
            (int) $i
        );
    }
    echo '</div></fieldset>';
}
add_action( 'comment_form_after_fields', 'yl_review_rating_field' );
add_action( 'comment_form_logged_in_after', 'yl_review_rating_field' );

function yl_validate_review_rating( $commentdata ) {
    if ( isset( $commentdata['comment_post_ID'] ) && 'yl_place' === get_post_type( (int) $commentdata['comment_post_ID'] ) ) {
        $rating = isset( $_POST['yl_rating'] ) ? absint( $_POST['yl_rating'] ) : 0;
        if ( $rating < 1 || $rating > 5 ) {
            wp_die( esc_html__( 'Vui lòng chọn mức đánh giá từ 1 đến 5 sao.', 'yanglocal' ) );
        }
    }
    return $commentdata;
}
add_filter( 'preprocess_comment', 'yl_validate_review_rating' );

function yl_save_review_rating( $comment_id ) {
    $rating = isset( $_POST['yl_rating'] ) ? absint( $_POST['yl_rating'] ) : 0;
    if ( $rating >= 1 && $rating <= 5 ) {
        add_comment_meta( $comment_id, 'yl_rating', $rating, true );
    }
    $comment = get_comment( $comment_id );
    if ( $comment && 'yl_place' === get_post_type( (int) $comment->comment_post_ID ) ) {
        yl_refresh_rating_cache( (int) $comment->comment_post_ID );
    }
}
add_action( 'comment_post', 'yl_save_review_rating' );

function yl_refresh_rating_cache( $post_id ) {
    $comments = get_comments(
        array(
            'post_id' => $post_id,
            'status'  => 'approve',
            'type'    => 'comment',
            'number'  => 0,
        )
    );
    $sum = 0;
    $count = 0;
    foreach ( $comments as $comment ) {
        $rating = (int) get_comment_meta( $comment->comment_ID, 'yl_rating', true );
        if ( $rating >= 1 && $rating <= 5 ) {
            $sum += $rating;
            $count++;
        }
    }
    $average = $count ? round( $sum / $count, 1 ) : 0;
    update_post_meta( $post_id, '_yl_rating_average', $average );
    update_post_meta( $post_id, '_yl_rating_count', $count );
}

function yl_recalculate_rating_on_status_change( $new_status, $old_status, $comment ) {
    if ( $new_status === $old_status || ! $comment || 'yl_place' !== get_post_type( (int) $comment->comment_post_ID ) ) {
        return;
    }
    yl_refresh_rating_cache( (int) $comment->comment_post_ID );
}
add_action( 'transition_comment_status', 'yl_recalculate_rating_on_status_change', 10, 3 );

function yl_get_rating_summary( $post_id ) {
    return array(
        'average' => (float) get_post_meta( $post_id, '_yl_rating_average', true ),
        'count'   => (int) get_post_meta( $post_id, '_yl_rating_count', true ),
    );
}

function yl_recalculate_rating_after_comment_change( $comment_id ) {
    $comment = get_comment( $comment_id );
    if ( $comment && 'yl_place' === get_post_type( (int) $comment->comment_post_ID ) ) {
        yl_refresh_rating_cache( (int) $comment->comment_post_ID );
    }
}
add_action( 'edit_comment', 'yl_recalculate_rating_after_comment_change' );
// Recalculate before deletion while the comment still exists and its post ID is available.
add_action( 'delete_comment', 'yl_recalculate_rating_after_comment_change' );
