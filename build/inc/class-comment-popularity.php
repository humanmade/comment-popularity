<?php
// Load our dependencies
require_once plugin_dir_path( __FILE__ ) . '/lib/autoload.php';

/**
 * Class HMN_Comment_Popularity
 */
class HMN_Comment_Popularity {

	/**
	 * Plugin version number.
	 */
	const VERSION = '1.0.0';

	/**
	 * The minimum PHP version compatibility.
	 */
	const MINIMUM_PHP_VERSION = '5.3.2';

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

		$this->interval = apply_filters( 'hmn_cp_interval', 15 * MINUTE_IN_SECONDS );

		$this->admin_roles = apply_filters( 'hmn_cp_roles', array( 'administrator', 'editor' ) );

		add_action( 'wp_insert_comment', array( $this, 'set_comment_weight' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_comment_vote_callback', array( $this, 'comment_vote_callback' ) );
		add_action( 'wp_ajax_nopriv_comment_vote_callback', array( $this, 'comment_vote_callback' ) );

		add_action( 'init', array( $this, 'load_textdomain' ) );

		add_filter( 'comments_template', array( $this, 'custom_comments_template' ) );

		$this->init_twig();
		$this->set_permissions();
	}

	/**
	 * Run checks on plugin activation.
	 */
	public function activate() {

		// Check PHP version. We need at least 5.3.2 for Composer.
		if ( version_compare( PHP_VERSION, '5.3.2', '<' ) ) {
			deactivate_plugins( basename( __FILE__ ) );
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

		wp_enqueue_style( 'growl', plugins_url( '../assets/js/modules/growl/stylesheets/jquery.growl.css', __FILE__ ), array(), self::VERSION );

		wp_enqueue_script( 'growl', plugins_url( '../assets/js/modules/growl/javascripts/jquery.growl.js', __FILE__ ), array( 'jquery' ), self::VERSION, true );

		wp_register_script( 'comment-popularity', plugins_url( '../assets/js/voting.js', __FILE__ ), array( 'jquery', 'growl' ), self::VERSION );

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

		return plugin_dir_path( __FILE__ ) . 'templates/comments.php';

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

		$GLOBALS['comment'] = $comment;

		if ( 'pingback' == $comment->comment_type || 'trackback' == $comment->comment_type ) : ?>

			<li id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
			<div class="comment-body">
				<?php _e( 'Pingback:', 'comment-popularity' ); ?> <?php comment_author_link(); ?> <?php edit_comment_link( __( 'Edit', 'comment-popularity' ), '<span class="edit-link">', '</span>' ); ?>
			</div>

		<?php else : ?>

		<li id="comment-<?php comment_ID(); ?>" <?php comment_class( empty( $args['has_children'] ) ? '' : 'comment-parent' ); ?>>
			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
				<header class="comment-header">
					<?php $this->render_ui( get_comment_ID() ); ?>
					<?php // Avatar
					if ( 0 != $args['avatar_size'] ) :
						echo get_avatar( $comment, $args['avatar_size'] );
					endif;

					?>
					<div class="comment-date">
						<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
							<time datetime="<?php comment_time( 'c' ); ?>">
								<?php
								printf(
									_x( '%1$s at %2$s', '1: date, 2: time', 'comment-popularity' ),
									get_comment_date(),
									get_comment_time()
								);
								?>
							</time>
						</a>
					</div>
					<div class="comment-author vcard">
						<?php
						printf(
							'%1$s <span class="says">%2$s</span>',
							sprintf(
								'<cite class="fn">%s</cite>',
								get_comment_author_link()
							),
							_x( 'says:', 'e.g. Bob says hello.', 'comment-popularity' )
						);

						$comment_author_email = get_comment_author_email( $comment->comment_ID );

						if ( ! empty( $comment_author_email ) ) {

							$author_karma = $this->get_comment_author_karma( $comment_author_email );

							if ( false !== $author_karma ) {
								printf( __( '%1$s( User Karma: %2$s )%3$s', 'comment-popularity' ), '<small class="user-karma">', esc_html( $author_karma ), '</small>' );
							}

						}


						?>
					</div>

					<?php if ( '0' == $comment->comment_approved ) : ?>
						<p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'comment-popularity' ); ?></p>
					<?php endif; ?>
				</header>

				<div class="comment-content">
					<?php comment_text(); ?>
				</div>

				<?php
				comment_reply_link( array_merge( $args, array(
					'add_below' => 'div-comment',
					'depth'     => $depth,
					'max_depth' => $args['max_depth'],
					'before'    => '<footer class="comment-reply">',
					'after'     => '</footer>',
				) ) );
				?>
			</article>

		<?php endif;
	}

	/**
	 * Renders the HTML for voting on comments
	 *
	 * @param $comment_id
	 */
	public function render_ui( $comment_id ) {

		$container_classes = array( 'comment-weight-container' );

		if ( ! $this->user_can_vote( get_current_user_id(), $comment_id ) ) {
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
	 * Determine if the user can vote.
	 *
	 * @param        $user_id
	 * @param        $comment_id
	 * @param string $action
	 *
	 * @return bool|WP_Error
	 */
	public function user_can_vote( $user_id, $comment_id, $action = '' ) {

		if ( ! current_user_can( 'vote_on_comments' ) ) {
			return new WP_Error( 'insufficient_permissions', __( 'You lack sufficient permissions to vote on comments', 'comment-popularity' ) );
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'not_logged_in', __( 'You must be logged in to vote on comments', 'comment-popularity' ) );
		}

		$comments_voted_on = get_user_meta( $user_id, 'comments_voted_on', true );

		// User has not yet voted on this comment
		if ( empty( $comments_voted_on[ 'comment_id_' . $comment_id ] ) ) {
			return array();
		}

		// Is user trying to vote twice on same comment?
		$last_action = $comments_voted_on[ 'comment_id_' . $comment_id ]['last_action'];

		if ( $last_action === $action ) {
			return new WP_Error( 'same_action', sprintf( __( 'You already %sd this comment', 'comment-popularity' ), $action ) );
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
	 */
	public function update_comments_voted_on_for_user( $user_id, $comment_id, $action ) {

		$comments_voted_on = get_user_meta( $user_id, 'comments_voted_on', true );

		$comments_voted_on[ 'comment_id_' . $comment_id ]['vote_time'] = current_time( 'timestamp' );
		$comments_voted_on[ 'comment_id_' . $comment_id ]['last_action'] = $action;

		update_user_meta( $user_id, 'comments_voted_on', $comments_voted_on );

		return $comments_voted_on[ 'comment_id_' . $comment_id ];
	}

	/**
	 * Ajax handler for the vote action.
	 */
	public function comment_vote_callback() {

		check_ajax_referer( 'hmn_vote_submit', 'hmn_vote_nonce' );

		$vote       = intval( $_POST['vote'] );
		$comment_id = absint( $_POST['comment_id'] );

		if ( ! in_array( (int)$_POST['vote'], array( -1, 1 ) ) ) {

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
	 * Handles the voting ajax request.
	 *
	 * @param $vote
	 * @param $comment_id
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	public function comment_vote( $vote, $comment_id, $user_id ) {

		$action = ( $vote > 0 ) ? 'upvote' : 'downvote';

		$user_can_vote = $this->user_can_vote( $user_id, $comment_id, $action );

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

		$this->update_comment_weight( $vote, $comment_id );

		// update comment author karma if it's an upvote.
		if ( 0 < $vote )
			$this->update_user_karma( $comment_id );

		$this->update_comments_voted_on_for_user( $user_id, $comment_id, $action );

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
		$hmn_cp_lang_dir = basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/';
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
