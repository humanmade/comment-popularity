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

	abstract function is_vote_valid( $comment_id, $action = '' );

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
	 * Stores the IP address.
	 *
	 * @var string
	 */
	protected $cookie;

	protected $logged_votes;

	/**
	 * @param $visitor_id
	 */
	public function __construct( $visitor_id ) {

		parent::__construct( $visitor_id );

		$this->set_cookie();

		$this->retrieve_logged_votes();
	}

	/**
	 *
	 */
	public function set_cookie() {

		// Set a cookie with the visitor IP address that expires in a week.
		$expiry = apply_filters( 'hmn_cp_cookie_expiry', time() + ( 7 * DAY_IN_SECONDS ) );

		//Set a cookie now to see if they are supported by the browser.
		$secure = ( 'https' === parse_url( site_url(), PHP_URL_SCHEME ) && 'https' === parse_url( home_url(), PHP_URL_SCHEME ) );

		setcookie( 'hmn_cp_visitor', $this->visitor_id, $expiry, COOKIEPATH, COOKIE_DOMAIN, $secure );
		if ( SITECOOKIEPATH != COOKIEPATH ) {
			setcookie( 'hmn_cp_visitor', $this->visitor_id, $expiry, SITECOOKIEPATH, COOKIE_DOMAIN, $secure );
		}

		// Make cookie available immediately by setting value manually.
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
	 * Save the user's vote to an option.
	 *
	 * @param $comment_id
	 * @param $action
	 *
	 * @return mixed
	 */
	public function log_vote( $comment_id, $action ) {

		$logged_votes = $this->retrieve_logged_votes();

		$logged_votes[ 'comment_id_' . $comment_id ]['vote_time'] = current_time( 'timestamp' );
		$logged_votes[ 'comment_id_' . $comment_id ]['last_action'] = $action;

		$this->save_logged_votes( $logged_votes );

		$logged_votes = $this->retrieve_logged_votes();

		$updated = $logged_votes[ 'comment_id_' . $comment_id ];

		/**
		 * Fires once the user meta has been updated.
		 *
		 * @param int   $visitor_id
		 * @param int   $comment_id
		 * @param array $updated
		 */
		do_action( 'hmn_cp_logged_guest_vote', $this->visitor_id, $comment_id, $updated );

		return $updated;

	}

	/**
	 * Delete vote.
	 *
	 * @param $comment_id
	 */
	public function unlog_vote( $comment_id ) {
		$logged_votes = $this->retrieve_logged_votes();
		unset( $logged_votes[ 'comment_id_' . $comment_id ] );
		$this->save_logged_votes( $logged_votes );
	}

	/**
	 * Retrieves the logged votes from the DB option and returns those belonging to
	 * the IP address in the cookie.
	 *
	 * @return mixed
	 */
	public function retrieve_logged_votes() {

		if ( is_multisite() ) {

			$blog_id = get_current_blog_id();
			$hmn_cp_guests_logged_votes = get_blog_option( $blog_id, 'hmn_cp_guests_logged_votes' );

		} else {

			$hmn_cp_guests_logged_votes = get_option( 'hmn_cp_guests_logged_votes' );

		}

		return $hmn_cp_guests_logged_votes[ $this->cookie ];
	}

	/**
	 * Save the votes for the current guest to the DB option.
	 *
	 * @param $votes
	 */
	protected function save_logged_votes( $votes ) {

		$logged_votes = array();

		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			$logged_votes = get_blog_option( $blog_id, 'hmn_cp_guests_logged_votes' );
			$logged_votes[ $this->visitor_id ] = $votes;
			update_blog_option( $blog_id, 'hmn_cp_guests_logged_votes', $logged_votes );
		} else {
			$logged_votes[ $this->visitor_id ] = $votes;
			update_option( 'hmn_cp_guests_logged_votes', $logged_votes );
		}
	}

	/**
	 * Determine if the guest visitor can vote.
	 *
	 * @param        $comment_id
	 * @param string $action
	 *
	 * @return bool|WP_Error
	 */
	public function is_vote_valid( $comment_id, $action = '' ) {

		// For now, all votes are valid.
		return true;

	}

}

/**
 * Class HMN_CP_Visitor_Member
 * @package CommentPopularity
 */
class HMN_CP_Visitor_Member extends HMN_CP_Visitor {

	/**
	 * Determine if the user can vote.
	 *
	 * @param        $comment_id
	 * @param string $action
	 *
	 * @return bool|WP_Error
	 */
	public function is_vote_valid( $comment_id, $action = '' ) {

		$comment = get_comment( $comment_id );

		if ( ! current_user_can( 'vote_on_comments' ) ) {
			return new \WP_Error( 'insufficient_permissions', __( 'You lack sufficient permissions to vote on comments', 'comment-popularity' ) );
		}

		if ( $comment->user_id && ( $this->get_id() === (int) $comment->user_id ) ) {
			return new \WP_Error( 'upvote_own_comment', sprintf( __( 'You cannot %s your own comments.', 'comment-popularity' ), $action ) );
		}

		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'not_logged_in', __( 'You must be logged in to vote on comments', 'comment-popularity' ) );
		}

		// Vote is valid.
		return true;

	}

	/**
	 * Save the user's vote to user meta.
	 *
	 * @param $comment_id
	 * @param $action
	 *
	 * @return mixed
	 */
	public function log_vote( $comment_id, $action ) {

		$comments_voted_on = $this->retrieve_logged_votes();

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
		do_action( 'hmn_cp_update_comments_voted_on_for_user', $this->get_id(), $comment_id, $updated );

		return $updated;
	}

	/**
	 * Delete vote.
	 *
	 * @param $comment_id
	 */
	public function unlog_vote( $comment_id ) {

		$comments_voted_on = $this->retrieve_logged_votes();
		unset( $comments_voted_on[ 'comment_id_' . $comment_id ] );

		if ( !empty( $comments_voted_on ) ) {
			update_user_option( $this->get_id(), 'hmn_comments_voted_on', $comments_voted_on );
		} else {
			delete_user_option( $this->get_id(), 'hmn_comments_voted_on' );
		}

	}

	/**
	 * Retrieves the list of comments the user has voted on.
	 *
	 * @return array Votes.
	 */
	public function retrieve_logged_votes() {

		$votes = get_user_option( 'hmn_comments_voted_on', $this->get_id() );

		return ! empty( $votes ) ? $votes : array();
	}

}
