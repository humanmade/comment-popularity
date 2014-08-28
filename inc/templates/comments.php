<?php

use CommentPopularity\HMN_Comment_Popularity;

if ( post_password_required() ) :
	return;
endif;
?>

<div id="comments" class="comments-area">
	<?php if ( have_comments() ) : ?>
		<h3 class="comments-title">
			<?php
			printf(
				_nx( 'One comment', '%1$s comments', get_comments_number(), 'comments title', 'comment-popularity' ),
				number_format_i18n( get_comments_number() )
			);
			?>
		</h3>

		<?php if ( get_comment_pages_count() > 1 ) : ?>
			<nav id="comment-nav-above" class="comment-navigation" role="navigation">
				<span class="screen-reader-text"><?php _e( 'Comment navigation', 'comment-popularity' ); ?></span>
				<?php paginate_comments_links(); ?>
			</nav>
		<?php endif; ?>

		<ol class="comment-list">

			<?php

			$hmn_cp_obj = HMN_Comment_Popularity::get_instance();

			global $comment;
			global $post;

			$args = array(
				'post_id'  => $post->ID,
				'echo'     => true,
				'callback' => array( $hmn_cp_obj, 'comment_callback' ),
				'style'    => 'ol'
			);

			if ( $hmn_cp_obj->are_comments_sorted_by_weight() ) {
				$hmn_cp_obj->get_comments_sorted_by_weight( true, $args );
			} else {
				wp_list_comments( $args );
			}

			?>

		</ol>

		<?php if ( get_comment_pages_count() > 1 ) : ?>
			<nav id="comment-nav-below" class="comment-navigation" role="navigation">
				<span class="screen-reader-text"><?php _e( 'Comment navigation', 'comment-popularity' ); ?></span>
				<?php paginate_comments_links(); ?>
			</nav>
		<?php endif; ?>

	<?php endif; ?>

	<?php if ( ! comments_open() && '0' != get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="no-comments">
			<?php _e( 'Comments are closed.', 'comment-popularity' ); ?>
		</p>
	<?php endif; ?>

	<?php comment_form(); ?>
</div>
