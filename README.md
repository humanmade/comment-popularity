comment-popularity
==================

Allow visitors to vote on comments.

## Advanced Usage

**I want to use this with a custom comment template.**

First thing - you need to remove the default comments template added by the plugin.

````
add_action( 'plugins_loaded', function() {
	remove_filter( 'comments_template', array( HMN_Comment_Popularity::get_instance(), 'custom_comments_template' ) );
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