<?php namespace CommentPopularity;

/**
 * Class HMN_CP_Visitor
 * @package CommentPopularity
 */
/**
 * Class HMN_CP_Visitor
 * @package CommentPopularity
 */
abstract class HMN_CP_Visitor {

	/**
	 * @var string
	 */
	protected $visitor_id;

	/**
	 * Time needed between 2 votes by user on same comment.
	 *
	 * @var mixed|void
	 */
	protected $interval;

	/**
	 * Creates a new HMN_CP_Visitor object.
	 */
	public function __construct( $visitor_id ) {

		$this->visitor_id = $visitor_id;


		$this->interval = apply_filters( 'hmn_cp_interval', 15 * MINUTE_IN_SECONDS );

	}

	/**
	 * @return mixed
	 */
	abstract function log_vote( $comment_id, $action );

	abstract function can_vote( $comment_id, $action = '' );

	/**
	 * @return string
	 */
	public function get_id() {
		return $this->visitor_id;
	}

}

/**
 * Class HMN_CP_Visitor_Guest
 * @package CommentPopularity
 */
class HMN_CP_Visitor_Guest extends HMN_CP_Visitor {

	/**
	 * @var string
	 */
	protected $cookie;

	/**
	 * @param $visitor_id
	 */
	public function __construct( $visitor_id ) {

		parent::__construct( $visitor_id );

		$this->set_cookie();
	}

	/**
	 *
	 */
	public function set_cookie() {

		// Set a cookie with the visitor IP address that expires in a week.
		$expiry = apply_filters( 'hmn_cp_cookie_expiry', time() + ( 7 * DAY_IN_SECONDS ) );

		setcookie( 'hmn_cp_visitor', $this->visitor_id, $expiry );

		// Make cookie available immediately by settng value manually.
		$_COOKIE['hmn_cp_visitor'] = $this->visitor_id;

		$this->cookie = $_COOKIE['hmn_cp_visitor'];
	}

	/**
	 *
	 * @return mixed
	 */
	public function get_cookie() {
		return $this->cookie;
	}

	/**
	 * Save the user's vote to user meta.
	 *
	 * @param $visitor_id
	 * @param $comment_id
	 * @param $action
	 *
	 * @return mixed
	 */
	public function log_vote( $comment_id, $action ) {


	}

	/**
	 * Determine if the user can vote.
	 *
	 * @param        $visitor_id
	 * @param        $comment_id
	 * @param string $action
	 *
	 * @return bool|WP_Error
	 */
	public function can_vote( $comment_id, $action = '' ) {

	}

}

/**
 * Class HMN_CP_Visitor_Member
 * @package CommentPopularity
 */
class HMN_CP_Visitor_Member extends HMN_CP_Visitor {

	/**
	 * @param $visitor_id WP User ID.
	 */
	public function __construct( $visitor_id ) {

		parent::__construct( $visitor_id );
	}

	/**
	 * Determine if a user has been granted expert status.
	 *
	 * @param $visitor_id
	 *
	 * @return bool
	 */
	public function get_expert_status() {

		return (bool) get_user_option( 'hmn_user_expert_status', $this->visitor_id );
	}

	/**
	 * Determine if the user can vote.
	 *
	 * @param        $visitor_id
	 * @param        $comment_id
	 * @param string $action
	 *
	 * @return bool|WP_Error
	 */
	public function can_vote( $comment_id, $action = '' ) {

		$comment = get_comment( $comment_id );

		if ( ! current_user_can( 'vote_on_comments' ) ) {
			return new \WP_Error( 'insufficient_permissions', __( 'You lack sufficient permissions to vote on comments', 'comment-popularity' ) );
		}

		if ( $comment->user_id && ( $this->visitor_id === (int)$comment->user_id ) ) {
			return new \WP_Error( 'upvote_own_comment', sprintf( __( 'You cannot %s your own comments.', 'comment-popularity' ), $action ) );
		}

		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'not_logged_in', __( 'You must be logged in to vote on comments', 'comment-popularity' ) );
		}

		$comments_voted_on = get_user_option( 'hmn_comments_voted_on', $this->visitor_id );

		// User has not yet voted on this comment
		if ( empty( $comments_voted_on[ 'comment_id_' . $comment_id ] ) ) {
			return array();
		}

		// Is user trying to vote twice on same comment?
		$last_action = $comments_voted_on[ 'comment_id_' . $comment_id ]['last_action'];

		if ( $last_action === $action ) {
			return new \WP_Error( 'same_action', sprintf( __( 'You cannot %s this comment again.', 'comment-popularity' ), $action ) );
		}

		// Is user trying to vote too fast?
		$last_voted = $comments_voted_on[ 'comment_id_' . $comment_id ]['vote_time'];

		$current_time = current_time( 'timestamp' );

		$elapsed_time = $current_time - $last_voted;

		if ( $elapsed_time > $this->interval ) {
			return true; // user can vote, has been over 15 minutes since last vote.
		} else {
			return new \WP_Error( 'voting_flood', __( 'You cannot vote again so soon on this comment, please wait ' . human_time_diff( $last_voted + $this->interval, $current_time ), 'comment-popularity' ) );
		}

	}

	/**
	 * Save the user's vote to user meta.
	 *
	 * @param $visitor_id
	 * @param $comment_id
	 * @param $action
	 *
	 * @return mixed
	 */
	public function log_vote( $comment_id, $action ) {

		$comments_voted_on = get_user_option( 'hmn_comments_voted_on', $this->visitor_id );

		$comments_voted_on[ 'comment_id_' . $comment_id ]['vote_time'] = current_time( 'timestamp' );
		$comments_voted_on[ 'comment_id_' . $comment_id ]['last_action'] = $action;

		update_user_option( $this->visitor_id, 'hmn_comments_voted_on', $comments_voted_on );

		$comments_voted_on = get_user_option( 'hmn_comments_voted_on', $this->visitor_id );

		$updated = $comments_voted_on[ 'comment_id_' . $comment_id ];

		/**
		 * Fires once the user meta has been updated.
		 *
		 * @param int   $visitor_id
		 * @param int   $comment_id
		 * @param array $updated
		 */
		do_action( 'hmn_cp_update_comments_voted_on_for_user', $this->visitor_id, $comment_id, $updated );

		return $updated;
	}

}
