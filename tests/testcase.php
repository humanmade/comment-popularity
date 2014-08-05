<?php

/**
 * Class HMN_Comment_PopularityTestCase
 */
class HMN_Comment_PopularityTestCase extends WP_UnitTestCase {

	protected $plugin;

	public function setUp() {
		parent::setUp();
		$this->plugin = HMN_Comment_Popularity::get_instance();
	}

	/**
	 * Helper function to check validity of action
	 *
	 * @param array  $tests
	 * @param string $function_call
	 */
	protected function do_action_validation( array $tests = array(), $function_call = 'has_action' ){
		foreach ( $tests as $test ) {
			list( $action, $class, $function ) = $test;

			//Default WP priority
			$priority = isset( $test[3] ) ? $test[3] : 10;

			//Default function call
			$function_call = ( in_array( $function_call, array( 'has_action', 'has_filter' ) ) ) ? $function_call : 'has_action';

			//Run assertion here
			$this->assertEquals(
				$priority,
				$function_call( $action, array( $class, $function ) ),
				"$action $function_call is not attached to $class::$function. It might also have the wrong priority (validated priority: $priority)"
			);
			$this->assertTrue(
				method_exists( $class, $function ),
				"Class '$class' doesn't implement the '$function' function"
			);
		}
	}

	/**
	 * Helper function to check validity of filters
	 * @param array $tests
	 */
	protected function do_filter_validation( array $tests = array() ){
		$this->do_action_validation( $tests, 'has_filter' );
	}

	/**
	 * Make sure the plugin is initialized with it's global variable
	 *
	 * @return void
	 */
	public function test_plugin_initialized() {
		$this->assertFalse( null == $this->plugin );
	}

	/**
	 * Check if get instance function return a valid instance of the comment_popularity class
	 *
	 * @return void
	 */
	public function test_get_instance() {

		$this->instance = HMN_Comment_Popularity::get_instance();

		$this->assertInstanceOf( 'HMN_Comment_Popularity', $this->instance );

	}

}