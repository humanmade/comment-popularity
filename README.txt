=== Comment Popularity ===

Contributors: pauldewouters,mattheu,humanmade,cfo-publishing
Tags: comments,vote,upvote,karma,widget
Requires at least: 3.9
Tested up to: 4.2-alpha
Stable tag: 1.3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Comment Popularity adds the ability for visitors to vote on comments.

== Description ==

With this plugin, members of your site will be able to vote comments up or down. Think Reddit comments.

After activating the plugin, there will be up/down arrows next to each comment and the total weight of the comment.
Comments are sorted by weight in a descending order.

Each commenter is allocated karma each time that one of their comments are upvoted.

Admin users can give users the "expert" label which will attribute more weight to their comments.

You'll find an option under discussion for the default expert karma and it can also be changed on the user's profile.

Follow development of this plugin on [Github](https://github.com/humanmade/comment-popularity/)

Requires PHP `5.3.2` or newer.

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'plugin-name'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `plugin-name.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `plugin-name.zip`
2. Extract the `plugin-name` directory to your computer
3. Upload the `plugin-name` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

== Frequently Asked Questions ==

= Can anonymous visitors vote on comments? =

Yes, you can enable guest voting by adding this snippet to a mu-plugin:
`add_filter( 'hmn_cp_allow_guest_voting', '__return_true' );`

Please note that it uses cookies and IP addresses to identify a visitor. This is not as reliable as
requiring a user to create an account. Cookies can be deleted, and IP addresses can be shared.

= Can comment weight be negative? =

Yes, you can enable negative weight by adding this snippet to a mu-plugin:
`add_filter( 'hmn_cp_allow_negative_comment_weight', '__return_true' );`

= Can I disable the sorting by comment weight? =

Yes, add this snippet to your functions.php file or mu plugin:
`add_filter( 'hmn_cp_sort_comments_by_weight', '__return_false' );`

= Where do I find the plugin settings? =

Under Settings > Discussion, and Users > Profile

= Advanced Usage =

**I want to use this with a custom comment template.**

First thing - you need to remove the default comments template added by the plugin.

`add_action( 'plugins_loaded', function() {
	remove_filter( 'comments_template', array( 'HMN_Comment_Popularity', 'custom_comments_template' ) );
}, 100 );`

Secondly, you need to add replace the `wp_list_comments` call with the following code:

`if ( function_exists( 'hmn_cp_the_sorted_comments' ) ) {
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
2. Set the user's karma and expert label.
3. The comment voting controls, and the user's karma on the public facing site.

== Upgrade Notice ==

= 1.3.0 =

* Guest visitors can now vote ( requires setting a flag )
* Negative comment weight is now possible ( requires setting a flag )
* Fixes a few bugs ( user karma settings )

= 1.2.1 =

* Fixes a fatal error on uninstall

= 1.2.0 =

* Adds a new Experts widget.
* Fixes misc bugs.

= 1.1.2 =

* Fixes a bug which prevented the user karma to appear in the single comment template.

== Changelog ==

= 1.3.4 / 2015-02-12 =

* Only add capabilities on activation
* Display user first and last names in widget if available

= 1.3.3 / 2014-10-25 =

* Fix fatal error on uninstall

= 1.3.2 / 2014-10-10 =

* Init plugin earlier

= 1.3.1 / 2014-09-11 =

* Load on init and fix namespacing issue
* Fix function prefix
* Adjustments to readme
* Fix readme markdown

= 1.3.0 / 2014-09-01 =

* namespaces
* Fix insert comment hook
* add expert status method
* remove non visitor specific function
* Delete option on uninstall
* child class can define type
* add filter for negative comment weight
* only set visitor object if it doesn't exist
* check permissions before deactivating plugin
* Delete version DB option on uninstall
* Use get_user_option instead of get_author_meta
* Fix a bug in class name
* only proceed if user is logged in
* Guest voting works
* Refactor: introduce visitor classes
* Begin allowing guest voting
* Add namespace
* Add instructions for disabling custom sorting to the readme FAQ
* Allow user to disable sorting by weight
* Change tested up to version to 4.0
* Add database prefix to meta key in user query.

= 1.2.1 / 2014-08-27 =

* Load main plugin class to fix uninstall fatal error.
* Use the *user_option functions
* Add some assertions
* Rename user meta
* Update uninstall routine
* We're deleting a global option
* Update the option to match current plugin version after upgrade
* Add an upgrade routine
* More exclude rules for build process
* Version 1.2.0
* Update CONTRIBUTING.md
* Delete user voting history
* Update user meta key
* Prefix user meta key

= 1.2.0 / 2014-08-24 =

* the user_can_vote function is triggered by a vote action. therefore we cannot use it to determines if a comment can be voted on on page load. we can just check if current user has sufficient permissions. unless we persist a user/comment relationship.
* Refactor get comments by weight function
* Use exising function instead of another comment query
* USe the refactored function parameters
* Just add a newline
* clean up
* display gravatar
* adds an experts widget
* update translation files
* Limit number of comments by user widget setting
* Add context info for translators
* Make more strings translatable
* Recreate tests from WP CLI
* Update tested up to field

= 1.1.5 / 2014-08-19 =

* Update tests to reflect refactoring
* Change how comment weight is calculated
* Use a multisite compatible function for user meta
* Fix up tests config
* Use appropriate WordPress functions
* Remove debugging function
* Make debugging easier

= 1.1.4 / 2014-08-18 =

* Check PHP version before evaluating any code
* Include and register widgets from main plugin class
* Add a tag
* Use function instead of constant
* Composer udpates
* Use 5.2 compatible code here
* Update minified script
* Throttle clicking events
* Add a tag
* Use function instead of constant
* Composer udpates
* Use 5.2 compatible code here
* Update CONTRIBUTING.md

= 1.1.3 / 2014-08-16 =

* Add dotorg banners
* Bump version to 1.1.3
* Fix PHP error
* Switch statement order
* Add min PHP version
* Use html entities
* Ignore non build files
* Igonre contributing.md from build
* Remove some unused code
* Fix a PHP notice
* Update readme and changelog

= 1.1.2 / 2014-08-15 =

* Bump version
* Pass user ID as param instead of email
* Add RTD config
* fix to readme

= 1.1.1 / 2014-08-13 =

* Bump to version 1.1.1
* Fix a PHP notice

= 1.1.0 / 2014-08-13 =

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

= 1.0.2 / 2014-08-08 =

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
* Merge branch 'master' into build-tasks
* add comments
* Changelog file
* Add build tasks
* Add task plugins
* Use minified scripts and styles
* add comments
* Merge pull request #45 from humanmade/docs
* Merge branch 'master' into docs
* Update readme
* Add readme to markdown task
* Documentation for how to remove the built in comment template
 
= 1.0.1 =

* Fix contributors
* Remove unneeded files

= 1.0.0 =

* Initial release
