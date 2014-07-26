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
		add_action( 'wp_ajax_nopriv_comment_vote', array( $this, 'comment_vote' ) );

		add_filter( 'manage_edit-comments_columns', array( $this, 'add_comment_columns' ) );
		add_filter( 'manage_comments_custom_column', array( $this, 'populate_comment_column' ), 10, 2 );

		add_filter( 'manage_edit-comments_sortable_columns', array( $this, 'make_weight_column_sortable' ) );

		add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );

		$this->set_permissions();

	}

	public function set_permissions() {

		$admin_role = get_role( 'administrator' );

		if ( ! empty( $admin_role ) ) {
			$admin_role->add_cap( 'manage_user_karma_settings' );
		}

		$contributor_role = get_role( 'contributor' );

		if ( ! empty( $contributor_role ) ) {
			$contributor_role->add_cap( 'vote_on_comments' );
		}
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
	 * Adds a setting field on the Discusion admin page.
	 */
	public function register_plugin_settings() {

		register_setting( 'discussion', 'comment_popularity_prefs', array( $this, 'validate_settings' ) );

		add_settings_field( 'hmn_cp_expert_karma_field', __( 'Default karma value for expert users', 'comment-popularity' ), array( $this, 'render_expert_karma_input' ), 'discussion', 'default', array( 'label_for' => 'hmn_cp_expert_karma_field' ) );
	}

	/**
	 * Callback to render the option HTML input on the settings page.
	 */
	public function render_expert_karma_input() {

		$prefs = get_option( 'comment_popularity_prefs', array( 'default_expert_karma' => 0 ) );

		$default_expert_karma = array_key_exists( 'default_expert_karma', $prefs ) ? $prefs['default_expert_karma'] : 0;

		echo '<input class="small-text" id="default_expert_karma" name="comment_popularity_prefs[default_expert_karma]" placeholder="Enter value" type="number" min="0" max="" step="1" value="' . esc_attr( $default_expert_karma ) . '" />';

	}

	/**
	 * Sanitize the user input.
	 *
	 * @param $input
	 *
	 * @return mixed
	 */
	public function validate_settings( $input ) {

		$valid['default_expert_karma'] = absint( $input['default_expert_karma'] );

		return $valid;
	}

	/**
	 * Renders the HTML for voting on comments
	 *
	 * @param $comment_id
	 */
	public function render_ui( $comment_id ) {

		$comment_weight = $this->get_comment_weight( $comment_id );

		$container_classes = array( 'comment-weight-container' );
		if ( ! $this->user_can_vote( get_current_user_id(), $comment_id ) ) {
			$container_classes[] = 'voting-disabled';
		}

		$form = sprintf( '<div class="%s">', implode( ' ', $container_classes ) );
		$form .= '<span><a data-comment-id="' . esc_attr( $comment_id ) . '" class="vote-up" href="#">&#9650;</a></span>';
		$form .= '<span class="comment-weight">' . esc_html( $comment_weight ) . '</span>';
		$form .= '<span><a data-comment-id="' . esc_attr( $comment_id ) . '" class="vote-down" href="#">&#9660;</a></span>';
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
	 * Determine if a user has been granted expert status.
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function get_user_expert_status( $user_id ) {

		return (bool) get_user_meta( $user_id, 'hmn_user_expert_status', true );
	}

	/**
	 * Renders the HTML form element for setting the user karma value.
	 *
	 * @param $user
	 */
	public function render_user_karma_field( $user ) {

		if ( ! current_user_can( 'manage_user_karma_settings' ) ) {
			return;
		}

		$prefs = get_option( 'comment_popularity_prefs', array( 'default_expert_karma' => 0 ) );

		$default_karma = $prefs['default_expert_karma'];

		$current_karma = get_the_author_meta( 'hmn_user_karma', $user->ID );

		$user_karma = ( empty( $current_karma ) ) ? $default_karma : $current_karma;

		$user_expert_status = get_the_author_meta( 'hmn_user_expert_status', $user->ID );

		?>

		<h3><?php esc_html_e( 'Comment popularity settings', 'comment-popularity' ); ?></h3>

		<table class="form-table">

			<tr>

				<th>

					<label for="hmn_user_expert_status"><?php esc_html_e( 'Expert Commenter', 'comment-popularity' ); ?></label>

				</th>

				<td>

					<input id="hmn_user_expert_status" name="hmn_user_expert_status" type="hidden" value="0" />
					<input id="hmn_user_expert_status" name="hmn_user_expert_status" type="checkbox" value="1" <?php checked( $user_expert_status ); ?> />

				</td>

			</tr>

			<tr>

				<th>

					<label for="hmn_user_karma"><?php esc_html_e( 'Karma', 'comment-popularity' ); ?></label>

				</th>

				<td>

					<input name="hmn_user_karma" type="number" step="1" min="0" id="hmn_user_karma" value="<?php echo esc_attr( $user_karma ); ?>" class="small-text">

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

		if ( ! current_user_can( 'manage_user_karma_settings' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$user_karma = absint( $_POST['hmn_user_karma'] );

		$user_expert_status = (bool)$_POST['hmn_user_expert_status'];

		update_user_meta( $user_id, 'hmn_user_karma', $user_karma );

		update_user_meta( $user_id, 'hmn_user_expert_status', $user_expert_status );

	}

	/**
	 * Sets the initial comment karma.
	 *
	 * @param $comment_id
	 * @param $comment
	 */
	public function set_comment_weight( $comment_id, $comment ) {

		$user_id = get_current_user_id();

		if ( ! $this->user_can_vote( $user_id, $comment_id ) ) {
			return;
		}

		$is_expert = $this->get_user_expert_status( $user_id );

		$user_karma = $this->get_user_karma( $user_id );

		if ( $is_expert && ( 0 < $user_karma ) ) {
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

	/**
	 * Fetch the comment author karma from the options.
	 *
	 * @param $email
	 *
	 * @return mixed
	 */
	public function get_comment_author_karma( $email ) {

		$author = get_user_by( 'email', $email );

		return get_user_meta( $author->ID, 'hmn_user_karma', true );
	}

	/**
	 * Sorts the comments by weight and returns them.
	 *
	 * @param array $args
	 * @param null  $comments
	 *
	 * @return string
	 */
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

	/**
	 * Add comment karma column to the admin view.
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function add_comment_columns( $columns )
	{
		return array_merge( $columns, array(
			'comment_karma' => 'Weight',
		) );
	}

	/**
	 * Populate the custom comment list table view with karma.
	 *
	 * @param $column
	 * @param $comment_ID
	 */
	public function populate_comment_column( $column, $comment_ID )
	{
		$comment = get_comment( $comment_ID );

		echo intval( $comment->comment_karma );
	}

	/**
	 * Add ability to sort by comment karma on the edit comments admin view.
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function make_weight_column_sortable( $columns ) {

		$columns['comment_karma'] = 'comment_karma';

		return $columns;

	}

	/**
	 * Determine if the user can vote.
	 *
	 * @param $user_id
	 * @param $comment_id
	 *
	 * @return bool
	 */
	public function user_can_vote( $user_id, $comment_id ) {

		if ( ! current_user_can( 'vote_on_comments' ) ) {
			return new WP_Error( 'insufficient_permissions', __( 'You lack sufficient permissions to vote on comments', 'comment-popularity' ) );
		}

		$comments_voted_on = get_user_meta( $user_id, 'comments_voted_on', true );

		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'not_logged_in', __( 'You must be logged in to vote on comments', 'comment-popularity' ) );
		}

		if ( ! empty( $comments_voted_on[ 'comment_id_' . $comment_id ] ) ) {

			$last_voted = $comments_voted_on[ 'comment_id_' . $comment_id ];

			$current_time = current_time( 'timestamp' );

			if ( ( $current_time - $last_voted ) > ( 15 * MINUTE_IN_SECONDS ) ) {
				return true; // user can vote, has been over 15 minutes since last vote.
			} else {
				return new WP_Error( 'voting_flood', __( 'You cannot vote again so soon on this comment, please wait ' . human_time_diff( $current_time, $last_voted ) . ' minutes', 'comment-popularity' ) );
			}
		}

		return true;
	}

	/**
	 * Save the user's vote to user meta.
	 *
	 * @param $user_id
	 * @param $comment_id
	 */
	public function update_comments_voted_on_for_user( $user_id, $comment_id ) {

		$comments_voted_on = get_user_meta( $user_id, 'comments_voted_on', true );

		$comments_voted_on[ 'comment_id_' . $comment_id ] = time();

		update_user_meta( $user_id, 'comments_voted_on', $comments_voted_on );
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

		$user_id = get_current_user_id();

		$user_can_vote = $this->user_can_vote( $user_id, $comment_id );

		if ( is_wp_error( $user_can_vote ) ) {

			$error_code = $user_can_vote->get_error_code();
			$error_msg = $user_can_vote->get_error_message( $error_code );

			$return = array(
				'error_message' => $error_msg,
				'comment_id'    => $comment_id,
			);

			wp_send_json_error( $return );

		}

		$this->update_comment_weight( $vote, $comment_id );

		// update comment author karma if it's an upvote.
		if ( 0 < $vote )
			$this->update_user_karma( $comment_id );

		$this->update_comments_voted_on_for_user( $user_id, $comment_id );

		$return = array(
			'weight'      => $this->get_comment_weight( $comment_id ),
			'comment_id' => $comment_id,
		);

		wp_send_json_success( $return );
	}

}
