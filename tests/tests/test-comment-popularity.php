<?php

Class Test_HMN_Comment_Popularity extends HMN_Comment_PopularityTestCase {

	/**
	 * Check if get instance function return a valid instance of the strem class
	 *
	 * @return void
	 */
	public function test_get_instance() {
		$instance = HMN_Comment_Popularity::get_instance();
		$this->assertInstanceOf( 'HMN_Comment_Popularity', $instance );
	}

}