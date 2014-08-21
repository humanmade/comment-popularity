<?php

/* If a title was input by the user, display it. */
if ( ! empty( $args['title'] ) )
	echo $before_title . apply_filters( 'widget_title',  $args['title'], $instance, $this->id_base ) . $after_title;

/* Get the authors list. */
$experts = get_users();

// WP_User_Query arguments
$args = array (
	'number'         => '5',
	'meta_query'     => array(
		array(
			'key'       => 'hmn_user_expert_status',
			'value'     => '1',
			'compare'   => '=',
			'type'      => 'NUMERIC',
		),
	),
	'orderby' => 'meta_value',
	'order' => 'DESC',
	'meta_key' => 'hmn_user_karma'
);

/* Display the authors list. */
foreach( $experts as $expert ) {
	echo $expert->user_nicename;
}
