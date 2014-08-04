<?php

Class Test_HMN_Comment_Popularity extends HMN_Comment_PopularityTestCase {

	const CLASS_NAME = 'HMN_Comment_Popularity';

	protected $test_user_id;

	protected $test_post_id;

	protected $test_comment_id;

	protected $instance;

	public function setUp() {

		parent::setUp();

		$this->instance = HMN_Comment_Popularity::get_instance();

		$this->test_user_id = $this->factory->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'test_admin',
				'email'      => 'test@land.com',
			)
		);
		wp_set_current_user( $this->test_user_id );

		// set interval to 30 seconds
		add_filter( 'hmn_cp_interval', function(){
			return 30;
		});


		// insert a post
		$this->test_post_id = $this->factory->post->create();

		// insert a comment on our test post
		$comment_date = current_time( 'mysql' );

		$this->test_comment_id = $this->factory->comment->create( array(
			'comment_date'    => $comment_date,
			'comment_post_ID' => $this->test_post_id,
		) );

	}

	public function tearDown() {

		parent::tearDown();

		$this->instance = null;

		wp_delete_comment( $this->test_comment_id );

		wp_delete_post( $this->test_post_id );

		wp_delete_user( $this->test_user_id );
	}

	/**
	 * Check if get instance function return a valid instance of the strem class
	 *
	 * @return void
	 */
	public function test_get_instance() {

		$this->instance = HMN_Comment_Popularity::get_instance();

		$this->assertInstanceOf( 'HMN_Comment_Popularity', $this->instance );

	}

	public function test_too_soon_to_vote_again() {

		// User votes on comment
		$this->instance->update_comments_voted_on_for_user( $this->test_user_id, $this->test_comment_id, 'upvote' );

		// Vote again immediately
		$this->instance->update_comments_voted_on_for_user( $this->test_user_id, $this->test_comment_id, 'downvote' );

		$ret = $this->instance->user_can_vote( $this->test_user_id, $this->test_comment_id, 'upvote' );

		// Make sure we have a WP_Error
		$this->assertInstanceOf( 'WP_Error', $ret );

		// Make sure it's the correct error code
		$this->assertEquals( 'voting_flood', $ret->get_error_code() );
	}

}