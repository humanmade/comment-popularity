<?php namespace CommentPopularity;

/**
 * Class HMN_CP_Widget_Most_voted
 */
class HMN_CP_Widget_Most_Voted extends \WP_Widget {

	/**
	 *
	 */
	public function __construct() {

		$widget_options = array(
			'classname'   => 'widget-most-voted widget_most_voted',
			'description' => esc_html__( 'Most voted comments', 'comment-popularity' )
		);

		parent::__construct( 'hmn-cp-most-voted', __( 'Most voted comments', 'comment-popularity' ), $widget_options );
		$this->alt_option_name = 'widget_hmn_cp_most_voted';
	}

	/**
	 * @param array $instance
	 */
	public function form( $instance ) {

		$title  = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'comment-popularity' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of comments to show:', 'comment-popularity' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" />
		</p>
	<?php

	}

	/**
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {

		$instance           = $old_instance;
		$instance['title']  = strip_tags( $new_instance['title'] );
		$instance['number'] = absint( $new_instance['number'] );
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions['widget_hmn_cp_most_voted'] ) ) {
			delete_option( 'widget_hmn_cp_most_voted' );
		}

		return $instance;

	}

	/**
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'widget_hmn_cp_most_voted', 'widget' );
		}
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];

			return;
		}

		$output = '';

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Most voted Comments', 'comment-popularity' );

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number ) {
			$number = 5;
		}
		
		$hmn_cp_plugin = HMN_Comment_Popularity::get_instance();
		$comments = $hmn_cp_plugin->get_comments_sorted_by_weight( false, array( 'number' => $number, 'echo' => false ) );

		$output .= $args['before_widget'];
		if ( $title ) {
			$output .= $args['before_title'] . $title . $args['after_title'];
		}

		$output .= '<ul id="recentcomments">';
		if ( $comments ) {
			// Prime cache for associated posts. (Prime post term cache if we need it for permalinks.)
			$post_ids = array_unique( wp_list_pluck( $comments, 'comment_post_ID' ) );
			_prime_post_caches( $post_ids, strpos( get_option( 'permalink_structure' ), '%category%' ), false );

			foreach ( (array) $comments as $comment ) {
				$output .= '<li class="recentcomments">';
				/* translators: comments widget: 1: comment author, 2: post link */
				$output .= sprintf( _x( '%1$s on %2$s, ( Weight: %3$s )', '1: Author 2: Post title 3: Weight', 'comment-popularity' ),
					'<span class="comment-author-link">' . get_comment_author_link( $comment->comment_ID ) . '</span>',
					'<a href="' . esc_url( get_comment_link( $comment->comment_ID ) ) . '">' . get_the_title( $comment->comment_post_ID ) . '</a>',
					'<span class="comment-weight">' . $comment->comment_karma . '</span>'
				);
				$output .= '</li>';
			}
		}
		$output .= '</ul>';
		$output .= $args['after_widget'];

		echo $output;

		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ] = $output;
			wp_cache_set( 'widget_hmn_cp_most_voted', $cache, 'widget' );
		}

	}

	/**
	 *
	 */
	public function flush_widget_cache() {
		wp_cache_delete( 'widget_hmn_cp_most_voted', 'widget' );
	}

}