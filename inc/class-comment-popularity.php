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

		add_action( 'wp_insert_comment', array( $this, 'set_comment_karma' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_comment_vote', array( $this, 'comment_vote' ) );
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

		wp_register_script( 'comment-popularity', plugins_url( '../assets/js/voting.js', __FILE__ ), array(), self::VERSION );


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

		$karma_count = $this->get_karma_count( $comment_id );

		$form = '<div class="karma">';
		$form .= '<span><a data-comment-id="' . esc_attr( $comment_id ) . '" class="add-karma" href="#">▲</a></span>';
		$form .= '<span class="comment-karma">' . esc_html( $karma_count ) . '</span>';
		$form .= '<span><a data-comment-id="' . esc_attr( $comment_id ) . '" class="remove-karma" href="#">▼</a></span>';
		$form .= '</div>';

		echo $form;
	}

	/**
	 * Retrieves the value for the comment karma data.
	 *
	 * @param $comment_id
	 *
	 * @return int
	 */
	protected function get_karma_count( $comment_id ) {

		// get_comment_meta will return an empty string if key is not set
		if ( 0 == strlen( $karma_count = get_comment_meta( $comment_id, 'hmn_karma_count', true ) ) ) {
			$karma_count = 0;
		}

		return (int) $karma_count;

	}

	/**
	 * Updates the karma value in the database.
	 *
	 * @param $vote
	 * @param $comment_id
	 *
	 * @return int
	 */
	public function update_karma_count( $vote, $comment_id ) {

		$comment_karma = $this->get_karma_count( $comment_id );

		$karma_value = $comment_karma + $vote;

		if ( $karma_value > 0 ) {
			update_comment_meta( $comment_id, 'hmn_karma_count', $karma_value );
		} else {
			update_comment_meta( $comment_id, 'hmn_karma_count', 0 );
		}

		return $karma_value;
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
	 * Sets the comment karma
	 *
	 * @param $comment_id
	 * @param $comment
	 */
	public function set_comment_karma( $comment_id, $comment ) {

		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();

		$user_karma = (int) get_user_meta( $user_id, 'hmn_user_karma', true );

		if ( 0 < $user_karma ) {
			$this->update_karma_count( $user_karma, $comment_id );
		}

	}

	/**
	 * Handles the voting ajax request.
	 */
	public function comment_vote() {

		check_ajax_referer( 'hmn_vote_submit', 'hmn_vote_nonce' );

		if ( ! in_array( $_POST['vote'], array( -1, 1 ) ) )
			die; // wp_send_json_error?

		$vote       = intval( $_POST['vote'] );
		$comment_id = absint( $_POST['comment_id'] );

		$karma_count = $this->update_karma_count( $vote, $comment_id );

		$return = array(
			'karma'      => $karma_count,
			'comment_id' => $comment_id,
		);

		wp_send_json_success( $return );
	}

}
