<?php
/**
 * Helper functions for use in themes.
 */

/**
 * Displays the voting arrows and karma.
 */
function hmn_cp_the_comment_upvote_form() {

	if ( class_exists( 'HMN_Comment_Popularity' ) ) {

		$cfo_cp = HMN_Comment_Popularity::get_instance();

		$cfo_cp->render_ui( get_comment_ID() );

	}

}

/**
 * Displays the author karma.
 */
function hmn_cp_the_comment_author_karma() {

	if ( class_exists( 'HMN_Comment_Popularity' ) ) {

		$cfo_cp = HMN_Comment_Popularity::get_instance();

		$author_karma = $cfo_cp->get_comment_author_karma( get_comment_author_email( get_comment_ID() ) );

		if ( isset( $author_karma ) ) {
			echo '<small class="user-karma">(User Karma: ' . esc_html( $author_karma ) . ')</small>';
		}

	}

}

/**
 * Displays the post comments sorted by weight/karma DESC.
 *
 * @param array $args
 * @param null  $comments
 */
function hmn_cp_the_sorted_comments( $args = array(), $comments = null ) {

	if ( class_exists( 'HMN_Comment_Popularity' ) ) {

		$cfo_cp = HMN_Comment_Popularity::get_instance();

		echo $cfo_cp->get_comments_sorted_by_weight( $args, $comments );

	}

}