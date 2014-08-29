<?php namespace CommentPopularity;

/**
 * Class HMN_CP_Visitor
 */
final class HMN_CP_Visitor {

	/**
	 * The instance of HMN_CP_Visitor.
	 *
	 * @var the single class instance.
	 */
	private static $instance;

	protected $visitor_id = '';

	protected $cookie;

	/**
	 * Creates a new HMN_CP_Visitor object, and registers with WP hooks.
	 */
	private function __construct( $visitor_id ) {

		$this->visitor_id = $visitor_id;

		// Set a cookie with the visitor IP address that expires in a week.
		$expiry = apply_filters( 'hmn_cp_cookie_expiry', time() + ( 7 * DAY_IN_SECONDS ) );

		setcookie( 'hmn_cp_visitor', $visitor_id, $expiry );

		// Make cookie available immediately by settng value manually.
		$_COOKIE['hmn_cp_visitor'] = $visitor_id;

		$this->cookie = $_COOKIE['hmn_cp_visitor'];
	}

	/**
	 * Disallow object cloning
	 */
	private function __clone() {}
	private function __wakeup() {}
	private function __sleep() {}

	/**
	 * Provides access to the class instance
	 *
	 * @return HMN_CP_Visitor
	 */
	public static function get_instance( $visitor_id ) {

		if ( ! self::$instance instanceof HMN_CP_Visitor ) {
			self::$instance = new HMN_CP_Visitor( $visitor_id );

		}

		return self::$instance;
	}

	public function get_cookie() {
		return $this->cookie;
	}

}
