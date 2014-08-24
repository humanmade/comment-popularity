<?php

/**
 * Class HMN_Comment_Popularity
 */
class HMN_Comment_Popularity {

	/**
	 * Plugin version number.
	 */
	const HMN_CP_PLUGIN_VERSION = '1.2.0';

	/**
	 * The minimum PHP version compatibility.
	 */
	const HMN_CP_REQUIRED_PHP_VERSION = '5.3.2';

	const HMN_CP_REQUIRED_WP_VERSION = '3.8.4';

	/**
	 * The instance of HMN_Comment_Popularity.
	 *
	 * @var the single class instance.
	 */
	private static $instance;

	/**
	 * The instance of Twig_Environment
	 *
	 * @var null
	 */
	protected $twig = null;

	/**
	 * Time needed between 2 votes by user on same comment.
	 *
	 * @var mixed|void
	 */
	protected $interval;

	/**
	 * User roles allowed to manage karma settings.
	 *
	 * @var mixed|void
	 */
	protected $admin_roles;

	/**
	 * Creates a new HMN_Comment_Popularity object, and registers with WP hooks.
	 */
	private function __construct() {

		$this->includes();

		$this->interval = apply_filters( 'hmn_cp_interval', 15 * MINUTE_IN_SECONDS );

		$this->admin_roles = apply_filters( 'hmn_cp_roles', array( 'administrator', 'editor' ) );

		add_action( 'wp_insert_comment', array( $this, 'insert_comment_callback' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_comment_vote_callback', array( $this, 'comment_vote_callback' ) );
		add_action( 'wp_ajax_nopriv_comment_vote_callback', array( $this, 'comment_vote_callback' ) );

		add_action( 'init', array( $this, 'load_textdomain' ) );

		add_filter( 'comments_template', array( $this, 'custom_comments_template' ) );

		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		$this->init_twig();
		$this->set_permissions();
	}

	/*
	 * Include required files.
	 */
	protected function includes() {

		// Load our dependencies
		require_once plugin_dir_path( __FILE__ ) . 'lib/autoload.php';

		// Widgets
		require_once plugin_dir_path( __FILE__ ) . 'widgets/class-widget-most-voted.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/experts/class-widget-experts.php';

	}

	/**
	 * Register the plugin widgets.
	 */
	public function register_widgets() {

		register_widget( 'HMN_CP_Widget_Most_Voted' );
		register_widget( 'HMN_CP_Widget_Experts' );

	}

	/**
	 * Returns the value of an upvote or downvote.
	 *
	 * @param $type ( 'upvote' or 'downvote' )
	 *
	 * @return int|mixed|void
	 */
	public function get_vote_value( $type ) {

		switch ( $type ) {

			case 'upvote':
				$value = apply_filters( 'hmn_cp_upvote_value', 1 );
				break;

			case 'downvote':
				$value = apply_filters( 'hmn_cp_downvote_value', -1 );
				break;

			default:
				$value = new WP_Error( 'invalid_vote_type', __( 'Sorry, invalid vote type', 'comment-popularity' ) );
				break;

		}

		return $value;
	}

	public function get_vote_labels() {
		return array(
			'upvote'   => _x( 'upvote', 'verb', 'comment-popularity' ),
			'downvote' => _x( 'downvote', 'verb', 'comment-popularity' ),
		);
	}

	/**
	 * Run checks on plugin activation.
	 */
	public static function activate() {

		global $wp_version;

		if ( version_compare( $wp_version, self::HMN_CP_REQUIRED_WP_VERSION, '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( sprintf( __( 'This plugin requires WordPress version %s. Sorry about that.', 'comment-popularity' ), self::HMN_CP_REQUIRED_WP_VERSION ), 'Comment Popularity', array( 'back_link' => true ) );
		}
	}

	/**
	 * Instantiates the Twig objects.
	 */
	public function init_twig() {

		$template_path = apply_filters( 'hmn_cp_template_path', plugin_dir_path( __FILE__ ) . '/templates' );

		$loader = new Twig_Loader_Filesystem( $template_path );
		$this->twig = new Twig_Environment( $loader );

	}

	/**
	 * Returns the plugin roles array.
	 *
	 * @return mixed|void
	 */
	public function get_roles() {
		return $this->admin_roles;
	}

	/**
	 * Add custom capabilities to allowed roles.
	 */
	public function set_permissions() {

		foreach ( $this->admin_roles as $role ) {

			$role = get_role( $role );

			if ( ! empty( $role ) ) {

				$role->add_cap( 'manage_user_karma_settings' );

			}

		}

		// Allow all user roles to vote.
		global $wp_roles;

		foreach ( $wp_roles->role_objects as $role ) {

			if ( ! empty( $role ) ) {
				$role->add_cap( 'vote_on_comments' );
			}
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

		wp_enqueue_style( 'growl', plugins_url( '../assets/js/modules/growl/stylesheets/jquery.growl.min.css', __FILE__ ), array(), self::HMN_CP_PLUGIN_VERSION );

		wp_enqueue_script( 'growl', plugins_url( '../assets/js/modules/growl/javascripts/jquery.growl.min.js', __FILE__ ), array( 'jquery' ), self::HMN_CP_PLUGIN_VERSION, true );

		$js_file = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '../assets/js/voting.js' : '../assets/js/voting.min.js';
		wp_register_script( 'comment-popularity', plugins_url( $js_file, __FILE__ ), array( 'jquery', 'growl' ), self::HMN_CP_PLUGIN_VERSION );

		$args = array(
			'hmn_vote_nonce' => wp_create_nonce( 'hmn_vote_submit' ),
			'ajaxurl'        => admin_url( 'admin-ajax.php' ),
		);

		wp_localize_script( 'comment-popularity', 'comment_popularity', $args );

		wp_enqueue_script( 'comment-popularity' );

	}

	/**
	 * Override comments template with custom one.
	 *
	 * @return string
	 */
	public function custom_comments_template() {

		global $post;

		if ( ! ( is_singular() && ( have_comments() || 'open' == $post->comment_status ) ) ) {

			return;

		}

		return apply_filters( 'hmn_cp_comments_template_path', plugin_dir_path( __FILE__ ) . 'templates/comments.php' );

	}

	/**
	 * Template for comments and pingbacks.
	 * Used as a callback by wp_list_comments() for displaying the comments.
	 *
	 * @param $comment
	 * @param $args
	 * @param $depth
	 */
	function comment_callback( $comment, $args, $depth ) {

		include apply_filters( 'hmn_cp_single_comment_template_path', plugin_dir_path( __FILE__ ) . 'templates/comment.php' );

	}

	/**
	 * Renders the HTML for voting on comments
	 *
	 * @param $comment_id
	 */
	public function render_ui( $comment_id ) {

		$container_classes = array( 'comment-weight-container' );

		if ( ! current_user_can( 'vote_on_comments' ) ) {
			$container_classes[] = 'voting-disabled';
		}

		$vars = array(
			'container_classes' => $container_classes,
			'comment_id'        => $comment_id,
			'comment_weight'    => $this->get_comment_weight( $comment_id )
		);

		echo $this->twig->render( 'voting-system.html', $vars );
	}

	/**
	 * Retrieves the value for the comment weight data.
	 *
	 * @param $comment_id
	 *
	 * @return int
	 */
	public function get_comment_weight( $comment_id ) {

		$comment = get_comment( $comment_id );

		return (int)$comment->comment_karma;

	}

	/**
	 * Updates the comment weight value in the database.
	 *
	 * @param $vote
	 * @param $comment_id
	 *
	 * @return int
	 */
	public function update_comment_weight( $comment_id, $weight_value ) {

		$comment_arr = get_comment( $comment_id, ARRAY_A );


		$comment_arr['comment_karma'] += $weight_value;

		if ( 0 >= $comment_arr['comment_karma'] ) {
			$comment_arr['comment_karma'] = 0;
		}

		wp_update_comment( $comment_arr );

		$comment_arr = get_comment( $comment_id, ARRAY_A );

		/**
		 * Fires once a comment has been updated.
		 *
		 * @param array $comment_arr The comment data array.
		 */
		do_action( 'hmn_cp_update_comment_weight', $comment_arr );

		return $comment_arr['comment_karma'];
	}

	/**
	 * Determine if a user has been granted expert status.
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function get_user_expert_status( $user_id ) {

		return (bool) get_user_option( 'hmn_user_expert_status',$user_id );
	}

	/**
	 * Sets the initial comment karma.
	 *
	 * @param $comment_id
	 * @param $comment
	 */
	public function insert_comment_callback( $comment_id, $comment ) {

		$user_id = get_current_user_id();

		$is_expert = $this->get_user_expert_status( $user_id );

		$user_karma = $this->get_user_karma( $user_id );

		if ( $is_expert && ( 0 < $user_karma ) ) {
			$this->update_comment_weight( $comment_id, $user_karma );
		}

	}

	/**
	 * Updates the comment author karma when a comment is voted on.
	 *
	 * @param $commenter_id
	 * @param $vote
	 *
	 * @return int|mixed|void
	 */
	public function update_user_karma( $commenter_id, $value ) {

		$user_karma = $this->get_user_karma( $commenter_id );

		$user_karma += $value;

		update_user_meta( $commenter_id, 'hmn_user_karma', $user_karma );

		$user_karma = get_user_option( 'hmn_user_karma', $commenter_id );

		/**
		 * Fires once the user meta has been updated for the karma.
		 *
		 * @param int $commenter_id
		 * @param int $user_karma
		 */
		do_action( 'hmn_cp_update_user_karma', $commenter_id, $user_karma );

		return $user_karma;
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
		$user_karma = get_user_option( 'hmn_user_karma', $user_id );

		return ( '' !== $user_karma ) ? (int)$user_karma : 0;
	}


	/**
	 * Sorts the comments by weight and returns them.
	 *
	 * @param array $args
	 * @param null  $comments
	 *
	 * @return string
	 */
	public function get_comments_sorted_by_weight( $html = false, $args = array() ) {

		// WP_Comment_Query arguments
		$defaults = array (
			'status'         => 'approve',
			'type'           => 'comment',
			'order'          => 'DESC',
			'orderby'        => 'comment_karma',
		);

		$get_comments_args = wp_parse_args( $args, $defaults );

		// The Comment Query
		$comment_query = new WP_Comment_Query;
		$comments = $comment_query->query( $get_comments_args );

		if ( $html )
			return wp_list_comments( $args, $comments );

		return $comments;
	}

	/**
	 * Determine if the user can vote.
	 *
	 * @param        $user_id
	 * @param        $comment_id
	 * @param string $action
	 *
	 * @return bool|WP_Error
	 */
	public function user_can_vote( $user_id, $comment_id, $action = '' ) {

		$labels = $this->get_vote_labels();

		$comment = get_comment( $comment_id );

		if ( ! current_user_can( 'vote_on_comments' ) ) {
			return new WP_Error( 'insufficient_permissions', __( 'You lack sufficient permissions to vote on comments', 'comment-popularity' ) );
		}

		if ( $comment->user_id && ( $user_id === (int)$comment->user_id ) ) {
			return new WP_Error( 'upvote_own_comment', sprintf( __( 'You cannot %s your own comments.', 'comment-popularity' ), $labels[ $action ] ) );
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'not_logged_in', __( 'You must be logged in to vote on comments', 'comment-popularity' ) );
		}

		$comments_voted_on = get_user_option( 'comments_voted_on', $user_id );

		// User has not yet voted on this comment
		if ( empty( $comments_voted_on[ 'comment_id_' . $comment_id ] ) ) {
			return array();
		}

		// Is user trying to vote twice on same comment?
		$last_action = $comments_voted_on[ 'comment_id_' . $comment_id ]['last_action'];

		if ( $last_action === $action ) {
			return new WP_Error( 'same_action', sprintf( __( 'You cannot %s this comment again.', 'comment-popularity' ), $labels[ $action ] ) );
		}

		// Is user trying to vote too fast?
		$last_voted = $comments_voted_on[ 'comment_id_' . $comment_id ]['vote_time'];

		$current_time = current_time( 'timestamp' );

		$elapsed_time = $current_time - $last_voted;

		if ( $elapsed_time > $this->interval ) {
			return true; // user can vote, has been over 15 minutes since last vote.
		} else {
			return new WP_Error( 'voting_flood', __( 'You cannot vote again so soon on this comment, please wait ' . human_time_diff( $last_voted + $this->interval, $current_time ), 'comment-popularity' ) );
		}

	}

	/**
	 * Save the user's vote to user meta.
	 *
	 * @param $user_id
	 * @param $comment_id
	 * @param $action
	 *
	 * @return mixed
	 */
	public function update_comments_voted_on_for_user( $user_id, $comment_id, $action ) {

		$comments_voted_on = get_user_option( 'comments_voted_on', $user_id );

		$comments_voted_on[ 'comment_id_' . $comment_id ]['vote_time'] = current_time( 'timestamp' );
		$comments_voted_on[ 'comment_id_' . $comment_id ]['last_action'] = $action;

		update_user_meta( $user_id, 'comments_voted_on', $comments_voted_on );

		$comments_voted_on = get_user_option( 'comments_voted_on', $user_id );

		$updated = $comments_voted_on[ 'comment_id_' . $comment_id ];

		/**
		 * Fires once the user meta has been updated.
		 *
		 * @param int   $user_id
		 * @param int   $comment_id
		 * @param array $updated
		 */
		do_action( 'hmn_cp_update_comments_voted_on_for_user', $user_id, $comment_id, $updated );

		return $updated;
	}

	/**
	 * Ajax handler for the vote action.
	 */
	public function comment_vote_callback() {

		check_ajax_referer( 'hmn_vote_submit', 'hmn_vote_nonce' );

		$comment_id = absint( $_POST['comment_id'] );

		$vote = $_POST['vote'];

		if ( ! in_array( $vote, array( 'upvote', 'downvote' ) ) ) {

			$return = array(
				'error_code'    => 'invalid_action',
				'error_message' => __( 'Invalid action', 'comment-popularity' ),
				'comment_id'    => $comment_id,
			);

			wp_send_json_error( $return );
		}

		$user_id = get_current_user_id();

		$result = $this->comment_vote( $vote, $comment_id, $user_id );

		if ( array_key_exists( 'error_message', $result ) ) {

			wp_send_json_error( $result );

		} else {

			wp_send_json_success( $result );

		}

	}

	/**
	 * Processes the comment vote logic.
	 *
	 * @param $vote
	 * @param $comment_id
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	public function comment_vote( $vote, $comment_id, $user_id ) {

		$user_can_vote = $this->user_can_vote( $user_id, $comment_id, $vote );

		if ( is_wp_error( $user_can_vote ) ) {

			$error_code = $user_can_vote->get_error_code();
			$error_msg = $user_can_vote->get_error_message( $error_code );

			$return = array(
				'error_code'    => $error_code,
				'error_message' => $error_msg,
				'comment_id'    => $comment_id,
			);

			return $return;

		}

		$this->update_comment_weight( $comment_id, $this->get_vote_value( $vote ) );

		// Get the comment author object.
		$email = get_comment_author_email( $comment_id );
		$author = get_user_by( 'email', $email );

		// update comment author karma if registered user.
		if ( false !== $author ) {
			$this->update_user_karma( $author->ID, $this->get_vote_value( $vote ) );
		}

		$this->update_comments_voted_on_for_user( $user_id, $comment_id, $vote );

		do_action( 'hmn_cp_comment_vote', $user_id, $comment_id, $vote );

		$return = array(
			'success_message'    => __( 'Thanks for voting!', 'comment-popularity' ),
			'weight'     => $this->get_comment_weight( $comment_id ),
			'comment_id' => $comment_id,
		);

		return $return;
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @return void
	 */
	public function load_textdomain() {

		// Set filter for plugin's languages directory
		$hmn_cp_lang_dir = dirname( plugin_basename( __DIR__ ) ) . '/languages/';
		$hmn_cp_lang_dir = apply_filters( 'hmn_cp_languages_directory', $hmn_cp_lang_dir );

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), 'comment-popularity' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'comment-popularity', $locale );

		// Setup paths to current locale file
		$mofile_local  = $hmn_cp_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/comment-popularity/' . $mofile;

		if ( file_exists( $mofile_global ) ) {

			// Look in global /wp-content/languages/comment-popularity folder
			load_textdomain( 'comment-popularity', $mofile_global );

		} elseif ( file_exists( $mofile_local ) ) {

			// Look in local /wp-content/plugins/comment-popularity/languages/ folder
			load_textdomain( 'comment-popularity', $mofile_local );

		} else {

			// Load the default language files
			load_plugin_textdomain( 'comment-popularity', false, $hmn_cp_lang_dir );

		}
	}

}
