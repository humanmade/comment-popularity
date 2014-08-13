=== Comment Popularity ===
Contributors: pauldewouters, mattheu,humanmade,cfo-publishing
Tags: comments,vote,upvote,karma
Requires at least: 3.8.4
Tested up to: 4.0-beta2
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Comment Popularity adds the ability for logged in users to vote on comments.

== Description ==
With this plugin, members of your site will be able to vote comments up or down. Think Reddit comments.

After activating the plugin, there will be up/down arrows next to each comment and the total weight of the comment.
Comments are sorted by weight in a descending order.

Each commenter is allocated karma each time that one of their comments are upvoted.

Admin users can give users the \"expert\" label which will attribute more weight to their comments.

You\'ll find an option under discussion for the default expert karma and it can also be changed on the user\'s profile.

Follow development of this plugin on [Github](https://github.com/humanmade/comment-popularity/)

== Installation ==
= Using The WordPress Dashboard =

1. Navigate to the \'Add New\' in the plugins dashboard
2. Search for \'plugin-name\'
3. Click \'Install Now\'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the \'Add New\' in the plugins dashboard
2. Navigate to the \'Upload\' area
3. Select `plugin-name.zip` from your computer
4. Click \'Install Now\'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `plugin-name.zip`
2. Extract the `plugin-name` directory to your computer
3. Upload the `plugin-name` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

== Frequently Asked Questions ==
= Can anonymous visitors vote on comments? =

No, they can\'t. Currently, the only way to be able to vote is to be a registered member of the site where the plugin is
installed. There are plans to integrate social network authentication such as Twitter/Facebook... in the future.

= Where do I find the plugin settings? =

Under Settings > Discussion, and Users > Profile

## Advanced Usage

**I want to use this with a custom comment template.**

First thing - you need to remove the default comments template added by the plugin.

`add_action( \'plugins_loaded\', function() {
	remove_filter( \'comments_template\', array( \'HMN_Comment_Popularity\', \'custom_comments_template\' ) );
}, 100 );`

Secondly, you need to add replace the `wp_list_comments` call with the following code:

`if ( function_exists( \'hmn_cp_the_sorted_comments\' ) ) {
	hmn_cp_the_sorted_comments( $args );
} else {
	wp_list_comments();
}`

Finally, you need to add the following function to your custom comment template where you would like to output the voting icons.

`hmn_cp_the_comment_upvote_form();`

To display the comment author karma:

`hmn_cp_the_comment_author_karma();`

== Screenshots ==
1. Set the default karma value for expert users.
2. Set the user\'s karma and expert label.
3. The comment voting controls, and the user\'s karma on the public facing site.

== Changelog ==

== 1.1.1 / 2014-08-13 ==

 * Bump to version 1.1.1
 * Fix a PHP notice

== 1.1.0 / 2014-08-13 ==

 * Bump to version 1.1.0
 * Make the paths filterable
 * Fix how we call comments template
 * Add a comment template file
 * Move comments to own file
 * Fix a PHP notice
 * Regenerate readme
 * Add link to github
 * Fix some spacing and formatting
 * Update CONTRIBUTING.md
 * Fix composer.json
 * Regenerate readme.md
 * Fix Readme file and add contributors
 * Prevent users from upvoting their comments
 * No need to check if user can vote
 * Register widget
 * Add a most voted comments widget
 * Update min WordPress version in readme
 * Add Matt as contributor
 * Add sortable karma column to users view
 * Return just the values
 * Add task to update class plugin version
 * Update version
 * Add some actions
 * Update function desc
 * Use prefixed constant VERSION
 * Improve activation checks
 * Change required WP Version
 * Prefix filters
 * Rename constants
 * Add Travis badge
 * Update tests to account for downvoting a comment with no karma
 * Rename function
 * Return comment weight
 * Rename callback
 * Return as integer
 * Bump version
 * First version of instructions
 * Add contributing.md file

== 1.0.2 / 2014-08-08 ==

 * Add tests for comment weight update
 * disallow negative karma
 * Fix code after refactor method
 * Return the database value
 * Fix issue where we were adding downvotes
 * Subtract karma if comment is downvoted
 * Add tests for commenter karma update
 * Update tests
 * Allow overriding vote value
 * Use the literal vote values
 * Minified CSS
 * remove build
 * Merge pull request #44 from humanmade/build-tasks
 * Add minified scripts
 * JSHint config
 * Ignore build dir
 * Merge branch \'master\' into build-tasks
 * add comments
 * Changelog file
 * Add build tasks
 * Add task plugins
 * Use minified scripts and styles
 * add comments
 * Merge pull request #45 from humanmade/docs
 * Merge branch \'master\' into docs
 * Update readme
 * Add readme to markdown task
 * Documentation for how to remove the built in comment template
 
==  1.0.1 ==

* Fix contributors
* Remove unneeded files

== 1.0.0 ==

* Initial release