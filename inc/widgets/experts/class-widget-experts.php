<?php namespace CommentPopularity;

/**
 * Class HMN_CP_Widget_Experts
 */
class HMN_CP_Widget_Experts extends \WP_Widget {

	/**
	 *
	 * Unique identifier for your widget.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * widget file.
	 *
	 * @since    1.2.0
	 *
	 * @var      string
	 */
	protected $widget_slug = 'comment-popularity-experts-widget';

	protected $defaults = array();

	protected $twig;

	/*--------------------------------------------------*/
	/* Constructor
	/*--------------------------------------------------*/

	/**
	 * Specifies the classname and description, instantiates the widget,
	 * loads localization files, and includes necessary stylesheets and JavaScript.
	 */
	public function __construct() {

		parent::__construct(
			$this->get_widget_slug(),
			__( 'Expert commenters', 'comment-popularity' ),
			array(
				'classname'   => $this->get_widget_slug() . '-class',
				'description' => __( 'Display expert commenters with their karma.', 'comment-popularity' )
			)
		);

		$this->defaults = array(
			'title'  => '',
			'number' => 5
		);

		// Refreshing the widget's cached output with each new post
		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );

	} // end constructor

	/**
	 * Instantiates the Twig objects.
	 */
	public function init_twig() {

		$template_path = apply_filters( 'hmn_cp_experts_widget_template_path', plugin_dir_path( __FILE__ ) . '/views' );

		$loader = new \Twig_Loader_Filesystem( $template_path );
		$this->twig = new \Twig_Environment( $loader );

	}


	/**
	 * Return the widget slug.
	 *
	 * @since    1.2.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_widget_slug() {
		return $this->widget_slug;
	}

	/*--------------------------------------------------*/
	/* Widget API Functions
	/*--------------------------------------------------*/

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array $args  The array of form elements
	 * @param array $instance The current instance of the widget
	 */
	public function widget( $args, $instance ) {

		$args = wp_parse_args( $args, $this->defaults );

		// Check if there is a cached output
		$cache = wp_cache_get( $this->get_widget_slug(), 'widget' );

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset ( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset ( $cache[ $args['widget_id'] ] ) ) {
			return print $cache[ $args['widget_id'] ];
		}

		extract( $args, EXTR_SKIP );

		$widget_string = $before_widget;

		/* If a title was input by the user, display it. */
		if ( ! empty( $instance['title'] ) )
			$widget_string .= $args['before_title'] . apply_filters( 'widget_title',  $instance['title'], $instance, $this->id_base ) . $args['after_title'];

		$experts = $this->get_experts();
		$plugin = HMN_Comment_Popularity::get_instance();
		$this->init_twig();
		$vars = array(
			'experts' => $experts,
		);

		ob_start();

		echo $this->twig->render( 'experts-widget.html', $vars );

		$widget_string .= ob_get_clean();
		$widget_string .= $after_widget;

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		$cache[ $args['widget_id'] ] = $widget_string;

		wp_cache_set( $this->get_widget_slug(), $cache, 'widget' );

		print $widget_string;

	} // end widget


	public function flush_widget_cache() {
		wp_cache_delete( $this->get_widget_slug(), 'widget' );
	}

	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param array $new_instance The new instance of values to be generated via the update.
	 * @param array $old_instance The previous instance of values before the update.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = absint( $new_instance['number'] );

		return $instance;

	} // end widget

	/**
	 * Generates the administration form for the widget.
	 *
	 * @param array $instance The array of keys and values for the widget.
	 */
	public function form( $instance ) {

		$instance = wp_parse_args(
			(array) $instance,
			$this->defaults
		);

		// Display the admin form
		include( plugin_dir_path( __FILE__ ) . 'views/admin.php' );

	} // end form

	protected function get_experts( $args = array() ) {

		/* Get the experts list. */
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
			'orderby'  => 'meta_value',
			'order'    => 'DESC',
			'meta_key' => 'hmn_user_karma'
		);

		$experts = get_users( $args );

		foreach ( $experts as $key => $expert ) {

			$name = $expert->user_login;

			if ( ! empty( $expert->first_name ) && ! empty( $expert->last_name ) ) {
				$name = $expert->first_name . ' ' . $expert->last_name;
			}
			$return[$key]['name'] = $name;
			$return[$key]['karma'] = get_user_option( 'hmn_user_karma', $expert->ID );
			$return[$key]['avatar'] = $this->get_gravatar_url( $expert->user_email );

		}

		return $return;

	}

	public function get_gravatar_url( $email ) {

		$hash = md5( strtolower( trim ( $email ) ) );
		return 'http://gravatar.com/avatar/' . $hash;
	}

} // end class
