<?php

/**
 * Class HMN_Comment_Popularity
 */
class HMN_Comment_Popularity {

	/**
	 * Plugin version number.
	 */
	const VERSION = '1.0';

	/**
	 * @var the single class instance.
	 */
	private static $instance;

	/**
	 * Creates a new HMN_Comment_Popularity object, and registers with WP hooks.
	 */
	private function __construct() {

		add_action( 'show_user_profile', array( $this, 'render_user_karma_field' ) );
		add_action( 'edit_user_profile', array( $this, 'render_user_karma_field' ) );

		add_action( 'personal_options_update', array( $this, 'save_user_meta' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_meta' ) );

		add_action( 'wp_insert_comment', array( $this, 'set_comment_weight' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_comment_vote', array( $this, 'comment_vote' ) );

		add_filter( 'manage_edit-comments_columns', array( $this, 'add_comment_columns' ) );
		add_filter( 'manage_comments_custom_column', array( $this, 'populate_comment_column' ), 10, 2 );

		add_filter( 'manage_edit-comments_sortable_columns', array( $this, 'make_weight_column_sortable' ) );

	}

	/**
	 * Disallow object cloning
	 */
	private function __clone() {}

	/**
	 * Provides access to the class instance
	 *
	 * @return HMN_Comment_Popularity
	 */
	public static function get_instance() {

		if ( ! self::$instance instanceof HMN_Comment_Popularity ) {
			self::$instance = new HMN_Comment_Popularity();

		}

		return self::$instance;
	}

	/**
	 * Load the Javascripts
	 */
	public function enqueue_scripts() {

		wp_register_script( 'comment-popularity', plugins_url( '../assets/js/voting.js', __FILE__ ), array( 'jquery' ), self::VERSION );

		$args = array(
			'hmn_vote_nonce' => wp_create_nonce( 'hmn_vote_submit' ),
			'ajaxurl'        => admin_url( 'admin-ajax.php' ),
		);

		wp_localize_script( 'comment-popularity', 'comment_popularity', $args );

		wp_enqueue_script( 'comment-popularity' );

	}

	/**
	 * Renders the HTML for voting on comments
	 *
	 * @param $comment_id
	 */
	public function render_ui( $comment_id ) {

		$comment_weight = $this->get_comment_weight( $comment_id );

		$form = '<div class="comment-weight-container">';
		$form .= '<span><a data-comment-id="' . esc_attr( $comment_id ) . '" class="vote-up" href="#">▲</a></span>';
		$form .= '<span class="comment-weight">' . esc_html( $comment_weight ) . '</span>';
		$form .= '<span><a data-comment-id="' . esc_attr( $comment_id ) . '" class="vote-down" href="#">▼</a></span>';
		$form .= '</div>';

		echo $form;
	}

	/**
	 * Retrieves the value for the comment weight data.
	 *
	 * @param $comment_id
	 *
	 * @return int
	 */
	protected function get_comment_weight( $comment_id ) {

		$comment = get_comment( $comment_id );

		return $comment->comment_karma;

	}

	/**
	 * Updates the comment weight value in the database.
	 *
	 * @param $vote
	 * @param $comment_id
	 *
	 * @return int
	 */
	public function update_comment_weight( $vote, $comment_id ) {

		$comment_arr = get_comment( $comment_id, ARRAY_A );

		$weight_value = $comment_arr['comment_karma'] + $vote;

		if ( $weight_value <= 0 )
			$weight_value = 0;

		$comment_arr['comment_karma'] = $weight_value;

		$ret = wp_update_comment( $comment_arr );

		return $ret;
	}

	/**
	 * Fetches the karma for the current user from the database.
	 *
	 * @param $user_id
	 *
	 * @return int
	 */
	public function get_user_karma( $user_id ) {

		// get user meta for karma
		// if its > 0 then user is an expert
		$user_karma = (int) get_user_meta( $user_id, 'hmn_user_karma', true );

		return $user_karma;
	}

	/**
	 * Renders the HTML form element for setting the user karma value.
	 *
	 * @param $user
	 */
	public function render_user_karma_field( $user ) {
		?>

		<h3>User Karma points</h3>

		<table class="form-table">

			<tr>

				<th><label for="hmn_user_karma">Karma</label></th>

				<td>

					<input name="hmn_user_karma" type="number" step="1" min="0" id="hmn_user_karma" value="<?php echo esc_attr( get_the_author_meta( 'hmn_user_karma', $user->ID ) ); ?>" class="small-text">

				</td>

			</tr>

		</table>

	<?php
	}

	/**
	 * Saves the custom user meta data.
	 *
	 * @param $user_id
	 */
	public function save_user_meta( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$user_karma = absint( $_POST['hmn_user_karma'] );

		update_user_meta( $user_id, 'hmn_user_karma', $user_karma );

	}

	/**
	 * Sets the initial comment karma.
	 *
	 * @param $comment_id
	 * @param $comment
	 */
	public function set_comment_weight( $comment_id, $comment ) {

		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();

		$user_karma = (int) get_user_meta( $user_id, 'hmn_user_karma', true );

		if ( 0 < $user_karma ) {
			$this->update_comment_weight( $user_karma, $comment_id );
		}

	}

	/**
	 * Updates the comment author karma when a comment is upvoted.
	 *
	 * @param $comment_id
	 *
	 * @return bool|int
	 */
	public function update_user_karma( $comment_id ) {

		$email = get_comment_author_email( $comment_id );

		$user = get_user_by( 'email', $email );

		if ( false !== $user ) {

			//comment author is a registered user! Update karma
			$user_karma = (int) get_user_meta( $user->ID, 'hmn_user_karma', true );

			$user_karma += 1;

			update_user_meta( $user->ID, 'hmn_user_karma', $user_karma );

			return $user_karma;
		}

		return false;
	}

	public function get_comment_author_karma( $email ) {

		$author = get_user_by( 'email', $email );

		return get_user_meta( $author->ID, 'hmn_user_karma', true );
	}

	public function get_comments_sorted_by_weight( $args = array(), $comments = null ) {

		$defaults = array( 'echo' => false );

		$args = array_merge( $defaults, $args );

		if ( null == $comments ) {

			global $wp_query;

			$comments = $wp_query->comments;

		}

		uasort( $comments, function( $a, $b ){
			return $b->comment_karma > $a->comment_karma;
		});

		return wp_list_comments( $args, $comments );
	}

	public function add_comment_columns( $columns )
	{
		return array_merge( $columns, array(
			'comment_karma' => 'Weight',
		) );
	}

	public function populate_comment_column( $column, $comment_ID )
	{
		$comment = get_comment( $comment_ID );

		echo intval( $comment->comment_karma );
	}

	public function make_weight_column_sortable( $columns ) {

		$columns['comment_karma'] = 'comment_karma';

		return $columns;

	}

	/**
	 * Handles the voting ajax request.
	 */
	public function comment_vote() {

		check_ajax_referer( 'hmn_vote_submit', 'hmn_vote_nonce' );

		if ( ! in_array( (int)$_POST['vote'], array( -1, 1 ) ) ) {
			die;
		} // wp_send_json_error?

		$vote       = intval( $_POST['vote'] );
		$comment_id = absint( $_POST['comment_id'] );

		$this->update_comment_weight( $vote, $comment_id );

		// update comment author karma if it's an upvote.
		if ( 0 < $vote )
			$this->update_user_karma( $comment_id );

		$return = array(
			'weight'      => $this->get_comment_weight( $comment_id ),
			'comment_id' => $comment_id,
		);

		wp_send_json_success( $return );
	}

}
