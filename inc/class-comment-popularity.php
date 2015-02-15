<?php namespace CommentPopularity;

/**
 * Class HMN_Comment_Popularity
 * @package CommentPopularity
 */
class HMN_Comment_Popularity {

	/**
	 * Plugin version number.
	 */
	const HMN_CP_PLUGIN_VERSION = '1.3.4';

	/**
	 * The minimum PHP version compatibility.
	 */
	const HMN_CP_REQUIRED_PHP_VERSION = '5.3.2';

	/**
	 *
	 */
	const HMN_CP_REQUIRED_WP_VERSION = '3.8.4';

	/**
	 * The instance of HMN_Comment_Popularity.
	 *
	 * @var HMN_Comment_Popularity the single class instance.
	 */
	private static $instance;

	/**
	 * The instance of Twig_Environment
	 *
	 * @var null
	 */
	protected $twig;

	/**
	 * @var bool
	 */
	protected $sort_comments_by_weight = true;

	/**
	 * @var bool
	 */
	protected $allow_guest_voting = false;

	/**
	 * @var bool
	 */
	protected $allow_negative_comment_weight = false;

	/**
	 * @var HMN_CP_Visitor
	 */
	protected $visitor;

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
	 * Creates a new HMN_Comment_Popularity object, and registers with WP hooks.
	 */
	private function __construct() {

		$this->includes();

		add_action( 'wp_insert_comment', array( $this, 'insert_comment_callback' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_comment_vote_callback', array( $this, 'comment_vote_callback' ) );
		add_action( 'wp_ajax_nopriv_comment_vote_callback', array( $this, 'comment_vote_callback' ) );

		add_action( 'init', array( $this, 'load_textdomain' ) );

		add_filter( 'comments_template', array( $this, 'custom_comments_template' ) );

		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		$this->init_twig();

	}

	/**
	 * Initialize the visitor object.
	 *
	 * @param HMN_CP_Visitor $visitor
	 */
	public function set_visitor( $visitor ) {
		$this->visitor = $visitor;
	}

	/**
	 * @return HMN_CP_Visitor
	 */
	public function get_visitor() {
		return $this->visitor;
	}

	/*
	 * Include required files.
	 */
	/**
	 *
	 */
	protected function includes() {

		// Load our dependencies
		require_once plugin_dir_path( __FILE__ ) . 'lib/autoload.php';

		// Widgets
		require_once plugin_dir_path( __FILE__ ) . 'widgets/class-widget-most-voted.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/experts/class-widget-experts.php';

		// Visitor
		require_once plugin_dir_path( __FILE__ ) . 'class-visitor.php';
	}

	/**
	 * Register the plugin widgets.
	 */
	public function register_widgets() {

		register_widget( 'CommentPopularity\HMN_CP_Widget_Most_Voted' );
		register_widget( 'CommentPopularity\HMN_CP_Widget_Experts' );

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
				$value = new \WP_Error( 'invalid_vote_type', __( 'Sorry, invalid vote type', 'comment-popularity' ) );
				break;

		}

		return $value;
	}

	/**
	 * @return array
	 */
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

			if ( current_user_can( 'activate_plugins' ) ) {

				deactivate_plugins( plugin_basename( __FILE__ ) );
				wp_die( sprintf( __( 'This plugin requires WordPress version %s. Sorry about that.', 'comment-popularity' ), self::HMN_CP_REQUIRED_WP_VERSION ), 'Comment Popularity', array( 'back_link' => true ) );

			}

		}

		self::set_permissions();
	}

	/**
	 * Tasks to perform when plugin is deactivated.
	 */
	public static function deactivate() {

		foreach ( get_editable_roles() as $role ) {

			$role_obj = get_role( strtolower( $role['name'] ) );

			if ( ! empty( $role_obj ) ) {

				if ( in_array( 'manage_user_karma_settings', $role_obj->capabilities) ) {
					$role_obj->remove_cap( 'manage_user_karma_settings' );
				}

				if ( in_array( 'vote_on_comments', $role_obj->capabilities ) ) {
					$role_obj->remove_cap( 'vote_on_comments' );
				}

			}

		}

	}

	/**
	 * Instantiates the Twig objects.
	 */
	public function init_twig() {

		$template_path = apply_filters( 'hmn_cp_template_path', plugin_dir_path( __FILE__ ) . '/templates' );

		$loader = new \Twig_Loader_Filesystem( $template_path );
		$this->twig = new \Twig_Environment( $loader );

	}

	/**
	 * Add custom capabilities to allowed roles.
	 */
	public static function set_permissions() {

		$admin_roles = apply_filters( 'hmn_cp_roles', array( 'administrator', 'editor' ) );

		foreach ( $admin_roles as $role ) {

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

		$vars = array(
			'container_classes' => $container_classes,
			'comment_id'        => $comment_id,
			'comment_weight'    => $this->get_comment_weight( $comment_id ),
			'enable_voting'    => $this->visitor_can_vote()
		);

		echo $this->twig->render( 'voting-system.html', $vars );
	}

	/**
	 * @return bool
	 */
	protected function visitor_can_vote() {

		// Visitor can vote if guest voting is enabled, if user is logged in and has correct permission
		return ( ! is_null( $this->visitor ) ) && ( $this->is_guest_voting_allowed() || ( is_user_logged_in() && current_user_can( 'vote_on_comments' ) ) );
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

		// Prevent negative weight if not allowed.
		if ( ( ! $this->is_negative_comment_weight_allowed() ) && 0 >= $comment_arr['comment_karma'] ) {
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
	 * Sets the initial comment karma.
	 *
	 * @param $comment_id
	 * @param $comment
	 */
	public function insert_comment_callback( $comment_id, $comment ) {

		if ( ! $comment->user_id )
			return;

		if ( ! $user = get_userdata( $comment->user_id ) )
			return;

		$is_expert = $this->get_comment_author_expert_status( $user->ID );

		$user_karma = $this->get_comment_author_karma( $user->ID );

		if ( $is_expert && ( 0 < $user_karma ) ) {
			$this->update_comment_weight( $comment_id, $user_karma );
		}

	}

	/**
	 * Determine if a comment author has been granted expert status.
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function get_comment_author_expert_status( $user_id ) {

		return (bool) get_user_option( 'hmn_user_expert_status', $user_id );
	}

	/**
	 * Sorts the comments by weight and returns them.
	 *
	 * @param array $args
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
		$comment_query = new \WP_Comment_Query;
		$comments = $comment_query->query( $get_comments_args );

		if ( $html )
			return wp_list_comments( $args, $comments );

		return $comments;
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

		$result = $this->comment_vote( $this->visitor->get_id(), $comment_id, $vote );

		if ( array_key_exists( 'error_message', $result ) ) {

			wp_send_json_error( $result );

		} else {

			wp_send_json_success( $result );

		}

	}

	/**
	 * Fetches the karma for the current user from the database.
	 *
	 * @param $user_id
	 *
	 * @return int
	 */
	public function get_comment_author_karma( $user_id ) {

		// get user meta for karma
		$user_karma = get_user_option( 'hmn_user_karma', $user_id );

		return ( '' !== $user_karma ) ? (int)$user_karma : 0;
	}

	/**
	 * Updates the comment author karma when a comment is voted on.
	 *
	 * @param $commenter_id
	 * @param $value
	 *
	 * @return int|mixed|void
	 */
	public function update_comment_author_karma( $commenter_id, $value ) {

		$user_karma = $this->get_comment_author_karma( $commenter_id );

		$user_karma += $value;

		update_user_option( $commenter_id, 'hmn_user_karma', $user_karma );

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
	 * Processes the comment vote logic.
	 *
	 * @param $vote
	 * @param $comment_id
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	public function comment_vote( $user_id, $comment_id, $vote ) {

		$labels = $this->get_vote_labels();

		$vote_is_valid = $this->get_visitor()->is_vote_valid( $comment_id, $labels[ $vote ] );

		if ( is_wp_error( $vote_is_valid ) ) {

			$error_code = $vote_is_valid->get_error_code();
			$error_msg = $vote_is_valid->get_error_message( $error_code );

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
			$this->update_comment_author_karma( $author->ID, $this->get_vote_value( $vote ) );
		}

		$this->get_visitor()->log_vote( $comment_id, $vote );

		do_action( 'hmn_cp_comment_vote', $user_id, $comment_id, $labels[ $vote ] );

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

	/**
	 * Determine if comments are sorted by weight.
	 *
	 * @return mixed|void
	 */
	public function are_comments_sorted_by_weight() {
		return apply_filters( 'hmn_cp_sort_comments_by_weight', $this->sort_comments_by_weight );
	}

	/**
	 * Determine if guest voting is allowed.
	 *
	 * @return mixed|void
	 */
	public function is_guest_voting_allowed() {
		return apply_filters( 'hmn_cp_allow_guest_voting', $this->allow_guest_voting );
	}

	/**
	 * Determine if negative comment weight is allowed
	 *
	 * @return mixed|void
	 */
	public function is_negative_comment_weight_allowed() {
		return apply_filters( 'hmn_cp_allow_negative_comment_weight', $this->allow_negative_comment_weight );
	}

}
