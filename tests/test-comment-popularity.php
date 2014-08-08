<?php
require_once 'testcase.php';
/**
 * Class Test_HMN_Comment_Popularity
 */
class Test_HMN_Comment_Popularity extends HMN_Comment_PopularityTestCase {

	protected $test_user_id;

	protected $test_post_id;

	protected $test_comment_id;

	public function setUp() {

		parent::setUp();

		$this->test_user_id = $this->factory->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'test_admin',
				'email'      => 'test@land.com',
			)
		);
		wp_set_current_user( $this->test_user_id );

		// set interval to 5 seconds
		add_filter( 'hmn_cp_interval', function(){
			return 5;
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

		$this->plugin = null;

		wp_delete_comment( $this->test_comment_id );

		wp_delete_post( $this->test_post_id );

		delete_user_meta( $this->test_user_id, 'comments_voted_on' );

		wp_delete_user( $this->test_user_id );
	}

	protected function add_cap() {

		$role = get_role( 'administrator' );

		if ( ! $role->has_cap( 'vote_on_comments' ) ) {

			$role->add_cap( 'vote_on_comments' );

		}
	}

	protected function remove_cap() {

		$role = get_role( 'administrator' );

		if ( ! empty( $role ) ) {

			$role->remove_cap( 'vote_on_comments' );

		}

	}

	public function test_too_soon_to_vote_again() {

		$this->plugin->comment_vote( 'upvote', $this->test_comment_id, $this->test_user_id );

		$ret = $this->plugin->comment_vote( 'downvote', $this->test_comment_id, $this->test_user_id );

		$this->assertEquals( 'voting_flood', $ret['error_code'] );

	}

	public function test_prevent_same_vote_twice() {

		$this->plugin->comment_vote( 'upvote', $this->test_comment_id, $this->test_user_id );

		$ret = $this->plugin->comment_vote( 'upvote', $this->test_comment_id, $this->test_user_id );

		sleep( 7 );

		$this->assertEquals( 'same_action', $ret['error_code'] );

	}

	public function test_upvote_comment() {

		$vote_time = current_time( 'timestamp' );

		$action = 'upvote';

		$result = $this->plugin->update_comments_voted_on_for_user( $this->test_user_id, $this->test_comment_id, $action );

		$expected = array(
			'vote_time' => $vote_time,
			'last_action' => $action
		);

		$this->assertEquals( $expected, $result );

	}

}
