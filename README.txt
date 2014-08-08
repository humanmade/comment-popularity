=== Comment Popularity ===
Contributors: pauldewouters, humanmade
Tags: comments, voting, karma
Requires at least: 3.9.1
Tested up to: 4.0-beta2
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Comment Popularity adds the ability for logged in users to vote on comments.

== Description ==

With this plugin, members of your site will be able to vote comments up or down. Think Reddit comments.

After activating the plugin, there will be up/down arrows next to each comment and the total weight of the comment.
Comments are sorted by weight in a descending order.

Each commenter is allocated karma each time that one of their comments are upvoted.

Admin users can give users the "expert" label which will attribute more weight to their comments.

You'll find an option under discussion for the default expert karma and it can also be changed on the user's profile.

== Installation ==

This section describes how to install the plugin and get it working.

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

= Where do I find the plugin settings? =

Under Settings > Discussion, and Users > Profile

## Advanced Usage

**I want to use this with a custom comment template.**

First thing - you need to remove the default comments template added by the plugin.

````
add_action( 'plugins_loaded', function() {
	remove_filter( 'comments_template', array( 'HMN_Comment_Popularity', 'custom_comments_template' ) );
}, 100 );
````

Secondly, you need to add replace the `wp_list_comments` call with the following code:

````
if ( function_exists( 'hmn_cp_the_sorted_comments' ) ) {
	hmn_cp_the_sorted_comments( $args );
} else {
	wp_list_comments();
}
````

Finally, you need to add the following function to your custom comment template where you would like to output the voting icons.

````
hmn_cp_the_comment_upvote_form();
````

To display the comment author karma:

````
hmn_cp_the_comment_author_karma();
````

== Screenshots ==

1. Set the default karma value for expert users.
2. Set the user's karma and expert label.
3. The comment voting controls, and the user's karma on the public facing site.

== Changelog ==

= 1.0.1 =

* Fix contributors
* Remove unneeded files

= 1.0 =
* First version.
