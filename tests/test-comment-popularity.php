<?php namespace CommentPopularity;

use CommentPopularity\HMN_Comment_Popularity;
use CommentPopularity\HMN_CP_Visitor;
use CommentPopularity\HMN_CP_Visitor_Member;

/**
 * Class Test_HMN_Comment_Popularity
 */
class Test_HMN_Comment_Popularity extends \WP_UnitTestCase {

	protected $test_voter_id;
	protected $test_commenter_id;
	protected $test_admin_id;

	protected $test_post_id;

	protected $test_comment_id;

	protected $plugin;

	public function setUp() {

		parent::setUp();

		$this->plugin = HMN_Comment_Popularity::get_instance();

		$this->plugin::activate();

		$this->test_voter_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'test_voter',
				'email'      => 'voter@kgb.ru',
			)
		);
		wp_set_current_user( $this->test_voter_id );

		$visitor = new HMN_CP_Visitor_Member( $this->test_voter_id );

		$this->plugin->set_visitor( $visitor );

		$this->test_commenter_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'test_commenter',
				'email'      => 'commenter@kgb.ru',
			)
		);

		$this->test_admin_id = $this->factory->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'test_admin',
				'email'      => 'admin@kgb.ru',
			)
		);

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
			'comment_author_email' => 'commenter@kgb.ru',
		) );

	}

	public function tearDown() {

		parent::tearDown();

		$this->plugin = null;

		wp_delete_comment( $this->test_comment_id );

		wp_delete_post( $this->test_post_id );

		delete_user_option( $this->test_voter_id, 'hmn_comments_voted_on' );

		wp_delete_user( $this->test_voter_id );
		wp_delete_user( $this->test_commenter_id );
		wp_delete_user( $this->test_admin_id );
	}

	protected function add_cap() {

		$role = get_role( 'author' );

		if ( ! $role->has_cap( 'vote_on_comments' ) ) {

			$role->add_cap( 'vote_on_comments' );

		}
	}

	protected function remove_cap() {

		$role = get_role( 'author' );

		if ( ! empty( $role ) ) {

			$role->remove_cap( 'vote_on_comments' );

		}

	}

	public function test_too_soon_to_vote_again() {

		$this->plugin->comment_vote( $this->test_voter_id,  $this->test_comment_id, 'upvote' );

		$ret = $this->plugin->comment_vote( $this->test_voter_id, $this->test_comment_id, 'upvote' );

		$this->assertArrayHasKey( 'error_code', $ret );

		$this->assertEquals( 'voting_flood', $ret['error_code'] );

	}

	public function test_upvote_comment_saves_action_to_user_meta() {

		$vote_time = current_time( 'timestamp' );

		$action = 'upvote';

		$result = $this->plugin->get_visitor()->log_vote( $this->test_comment_id, $action );

		$expected = array(
			'vote_time' => $vote_time,
			'last_action' => $action,
		);

		$this->assertEquals( $expected['vote_time'], $result['vote_time'], 30 );
		$this->assertEquals( $expected['last_action'], $result['last_action'] );

	}

	public function test_comment_author_karma_increases_on_upvote() {

		$vote = 'upvote';

		// Current comment author karma value
		$current_value = $this->plugin->get_comment_author_karma( $this->test_commenter_id );

		$new_value = $this->plugin->update_comment_author_karma( $this->test_commenter_id, $this->plugin->get_vote_value( $vote ) );

		$this->assertGreaterThan( $current_value, $new_value );
	}

	public function test_comment_author_karma_decreases_on_downvote() {

		$vote = 'downvote';

		update_user_option( $this->test_commenter_id, 'hmn_user_karma', 2 );

		// Current comment author karma value
		$current_value = $this->plugin->get_comment_author_karma( $this->test_commenter_id );

		// Downvote twice so we check negative values
		$this->plugin->update_comment_author_karma( $this->test_commenter_id, $this->plugin->get_vote_value( $vote ) );
		$new_value = $this->plugin->update_comment_author_karma( $this->test_commenter_id, $this->plugin->get_vote_value( $vote ) );

		$this->assertLessThan( $current_value, $new_value );
	}

	public function test_comment_author_karma_not_negative() {

		$vote = 'downvote';

		update_user_option( $this->test_commenter_id, 'hmn_user_karma', 0 );

		// Current comment author karma value
		$current_value = $this->plugin->get_comment_author_karma( $this->test_commenter_id );

		// Downvote twice so we check negative values
		$this->plugin->update_comment_author_karma( $this->test_commenter_id, $vote );

		$new_value = $this->plugin->update_comment_author_karma( $this->test_commenter_id, $vote );

		$this->assertEquals( $current_value, $new_value );
		$this->assertEquals( 0, $new_value );
	}

	public function test_upvoting_comment_changes_comment_weight() {

		$vote = 'upvote';

		// Set initial comment weight
		$this->plugin->update_comment_weight( $this->test_comment_id, $this->plugin->get_vote_value( $vote ) );

		$current_value = $this->plugin->get_comment_weight( $this->test_comment_id );

		$new_value = $this->plugin->update_comment_weight( $this->test_comment_id, $this->plugin->get_vote_value( $vote ) );

		$this->assertGreaterThan( $current_value, $new_value );
	}

	public function test_downvoting_comment_changes_comment_weight() {

		$vote = 'downvote';

		$comment_arr = get_comment( $this->test_comment_id, ARRAY_A );

		$comment_arr['comment_karma'] = 2;

		$ret = wp_update_comment( $comment_arr );

		$current_value = $this->plugin->get_comment_weight( $this->test_comment_id );

		$new_value = $this->plugin->update_comment_weight( $this->test_comment_id, $this->plugin->get_vote_value( $vote ) );

		$this->assertLessThan( $current_value, $new_value );
		$this->assertEquals( 1, $new_value );
	}

	public function test_downvoting_comment_not_negative() {

		$vote = 'downvote';
		$comment_arr = get_comment( $this->test_comment_id, ARRAY_A );


		$comment_arr['comment_karma'] = 0;

		$ret = wp_update_comment( $comment_arr );

		$current_value = $this->plugin->get_comment_weight( $this->test_comment_id );

		$new_value = $this->plugin->update_comment_weight( $this->test_comment_id, $this->plugin->get_vote_value( $vote ) );

		$this->assertEquals( $current_value, $new_value );
		$this->assertEquals( 0, $new_value );
	}

	public function test_undo_vote() {
		 // do an upvote
		// do a second upvote
		// check that comment weight is same as it was before
		// check user karma is same as before
		// ex
   }

}
