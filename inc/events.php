<?php
/**
 * Event Calendar Base
 *
 * @package    BE-Events-Calendar
 * @since      1.0.0
 * @link       https://github.com/billerickson/BE-Events-Calendar
 * @author     Bill Erickson <bill@billerickson.net>
 * @copyright  Copyright (c) 2014, Bill Erickson
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
 
class BE_Events_Calendar {

	/**
	 * Primary class constructor
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// Fire on activation
		register_activation_hook( BE_EVENTS_CALENDAR_FILE, array( $this, 'activation' ) );

		// Load the plugin base
		add_action( 'plugins_loaded', array( $this, 'init' ) );	
	}
	
	/**
	 * Flush the WordPress permalink rewrite rules on activation
	 *
	 * @since 1.0.0
	 */
	function activation() {

		$this->post_type();
		flush_rewrite_rules();
	}

	/**
	 * Loads the plugin base into WordPress
	 *
	 * @since 1.0.0
	 */
	function init() {
	
		// Create Post Type
		add_action( 'init', array( $this, 'post_type' ) );
		
		// Post Type columns
		add_filter( 'manage_edit-events_columns', array( $this, 'edit_event_columns' ) ) ;
		add_action( 'manage_events_posts_custom_column', array( $this, 'manage_event_columns' ), 10, 2 );

		// Post Type sorting
		add_filter( 'manage_edit-events_sortable_columns', array( $this, 'event_sortable_columns' ) );
		add_action( 'load-edit.php', array( $this, 'edit_event_load' ) );

		// Post Type title placeholder
		add_action( 'gettext',  array( $this, 'title_placeholder' ) );
		
		// Create Taxonomy
		add_action( 'init', array( $this, 'taxonomies' ) );
		
		// Create Metabox
		$metabox = apply_filters( 'be_events_manager_metabox_override', false );
		if ( false === $metabox ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'metabox_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'metabox_scripts' ) );
			add_action( 'add_meta_boxes', array( $this, 'metabox_register' ) );
			add_action( 'save_post', array( $this, 'metabox_save' ),  1, 2  );
		}
		
		// Modify Event Listings query
		add_action( 'pre_get_posts', array( $this, 'event_query' ) );
	}
	
	/** 
	 * Register Post Type
	 *
	 * @since 1.0.0
	 */
	function post_type() {

		$labels = array(
			'name'               => 'Events',
			'singular_name'      => 'Event',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Event',
			'edit_item'          => 'Edit Event',
			'new_item'           => 'New Event',
			'view_item'          => 'View Event',
			'search_items'       => 'Search Events',
			'not_found'          =>  'No events found',
			'not_found_in_trash' => 'No events found in trash',
			'parent_item_colon'  => '',
			'menu_name'          => 'Events'
		);
		
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true, 
			'show_in_menu'       => true, 
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'events', 'with_front' => false ),
			'capability_type'    => 'post',
			'has_archive'        => true, 
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor' ),
			'menu_icon'          => 'dashicons-calendar',
		); 
	
		register_post_type( 'events', $args );	
	}
	
	/**
	 * Edit Column Titles
	 *
	 * @since 1.0.0
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 * @param array $columns
	 * @return array
	 */
	function edit_event_columns( $columns ) {
	
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'title'       => 'Event',
			'event_start' => 'Starts',
			'event_end'   => 'Ends',
			'date'        => 'Published Date',
		);
	
		return $columns;
	}
	
	/**
	 * Edit Column Content
	 *
	 * @since 1.0.0
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 * @param string $column
	 * @param int $post_id
	 */
	function manage_event_columns( $column, $post_id ) {

		global $post;
	
		switch( $column ) {
	
			/* If displaying the 'duration' column. */
			case 'event_start' :
	
				/* Get the post meta. */
				$allday = get_post_meta( $post_id, 'be_event_allday', true );
				$date_format = $allday ? 'M j, Y' : 'M j, Y g:i A';
				$start = esc_attr( date( $date_format, get_post_meta( $post_id, 'be_event_start', true ) ) );
	
				/* If no duration is found, output a default message. */
				if ( empty( $start ) )
					echo __( 'Unknown' );
	
				/* If there is a duration, append 'minutes' to the text string. */
				else
					echo $start;
	
				break;
	
			/* If displaying the 'genre' column. */
			case 'event_end' :
	
				/* Get the post meta. */
				$allday = get_post_meta( $post_id, 'be_event_allday', true );
				$date_format = $allday ? 'M j, Y' : 'M j, Y g:i A';
				$end = esc_attr( date( $date_format, get_post_meta( $post_id, 'be_event_end', true ) ) );
	
				/* If no duration is found, output a default message. */
				if ( empty( $end ) )
					echo __( 'Unknown' );
	
				/* If there is a duration, append 'minutes' to the text string. */
				else
					echo $end;
	
				break;
	
			/* Just break out of the switch statement for everything else. */
			default :
				break;
		}
	}	 
	
	/**
	 * Make Columns Sortable
	 *
	 * @since 1.0.0
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 * @param array $columns
	 * @return array
	 */
	function event_sortable_columns( $columns ) {
	
		$columns['event_start'] = 'event_start';
		$columns['event_end']   = 'event_end';
	
		return $columns;
	}	 
	
	/**
	 * Check for load request
	 *
	 * @since 1.0.0
	 */
	function edit_event_load() {

		add_filter( 'request', array( $this, 'sort_events' ) );
	}
	
	/**
	 * Sort events on load request
	 *
	 * @since 1.0.0
	 * @param array $vars
	 * @return array
	 */
	function sort_events( $vars ) {

		/* Check if we're viewing the 'event' post type. */
		if ( isset( $vars['post_type'] ) && 'events' == $vars['post_type'] ) {
	
			/* Check if 'orderby' is set to 'start_date'. */
			if ( isset( $vars['orderby'] ) && 'event_start' == $vars['orderby'] ) {
	
				/* Merge the query vars with our custom variables. */
				$vars = array_merge(
					$vars,
					array(
						'meta_key' => 'be_event_start',
						'orderby' => 'meta_value_num'
					)
				);
			}
			
			/* Check if 'orderby' is set to 'end_date'. */
			if ( isset( $vars['orderby'] ) && 'event_end' == $vars['orderby'] ) {
	
				/* Merge the query vars with our custom variables. */
				$vars = array_merge(
					$vars,
					array(
						'meta_key' => 'be_event_end',
						'orderby' => 'meta_value_num'
					)
				);
			}
			
		}
	
		return $vars;
	}

	/**
	 * Change the default title placeholder text
	 *
	 * @since 1.0.0
	 * @global array $post
	 * @param string $translation
	 * @return string Customized translation for title
	 */
	function title_placeholder( $translation ) {

		global $post;
		if ( isset( $post ) && 'events' == $post->post_type && 'Enter title here' == $translation ) {
			$translation = 'Enter Event Name Here';
		}
		return $translation;
	}

	/**
	 * Create Taxonomies
	 *
	 * @since 1.0.0
	 */
	function taxonomies() {
	
		$supports = get_theme_support( 'be-events-calendar' );
		if ( !is_array( $supports ) || !in_array( 'event-category', $supports[0] ) )
			return;
			
		$post_types = in_array( 'recurring-events', $supports[0] ) ? array( 'events', 'recurring-events' ) : array( 'events' );
			
		$labels = array(
			'name'              => 'Categories',
			'singular_name'     => 'Category',
			'search_items'      => 'Search Categories',
			'all_items'         => 'All Categories',
			'parent_item'       => 'Parent Category',
			'parent_item_colon' => 'Parent Category:',
			'edit_item'         => 'Edit Category',
			'update_item'       => 'Update Category',
			'add_new_item'      => 'Add New Category',
			'new_item_name'     => 'New Category Name',
			'menu_name'         => 'Category'
		); 	
	
		register_taxonomy( 'event-category', $post_types, array(
			'hierarchical' => true,
			'labels'       => $labels,
			'show_ui'      => true,
			'query_var'    => true,
			'rewrite'      => array( 'slug' => 'event-category' ),
		));
	}

	/**
	 * Loads styles for metaboxes
	 *
	 * @since 1.0.0
	 */
	function metabox_styles() {

		if ( isset( get_current_screen()->base ) && 'post' !== get_current_screen()->base ) {
			return;
		}

		if ( isset( get_current_screen()->post_type ) && 'events' != get_current_screen()->post_type ) {
			return;
		}

		// Load styles
		wp_register_style( 'be-events-calendar', BE_EVENTS_CALENDAR_URL . 'css/events-admin.css', array(), BE_EVENTS_CALENDAR_VERSION );
		wp_enqueue_style( 'be-events-calendar' );
	}

	/**
	 * Loads scripts for metaboxes.
	 *
	 * @since 1.0.0
	 */
	function metabox_scripts() {

		if ( isset( get_current_screen()->base ) && 'post' !== get_current_screen()->base ) {
			return;
		}

		if ( isset( get_current_screen()->post_type ) && 'events' != get_current_screen()->post_type ) {
			return;
		}

		// Load scripts.
		wp_register_script( 'be-events-calendar', BE_EVENTS_CALENDAR_URL . 'js/events-admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ) , BE_EVENTS_CALENDAR_VERSION, true );
		wp_enqueue_script( 'be-events-calendar' );
	}

	/**
	 * Initialize the metabox
	 *
	 * @since 1.0.0
	 */
	function metabox_register() {

		add_meta_box( 'be-events-calendar-date-time', 'Date and Time Details', array( $this, 'render_metabox' ), 'events', 'normal', 'high' );
	}

	/**
	 * Render the metabox
	 *
	 * @since 1.0.0
	 */
	function render_metabox() {

		$start  = get_post_meta( get_the_ID() , 'be_event_start', true );
		$end    = get_post_meta( get_the_ID() , 'be_event_end',   true );
		$allday = get_post_meta( get_the_ID(), 'be_event_allday', true );

		if ( !empty( $start ) && !empty( $end ) ) {
			$start_date = date( 'm/d/Y', $start );
			$start_time = date( 'g:ia',  $start );
			$end_date   = date( 'm/d/Y', $end   );
			$end_time   = date( 'g:ia',  $end   );
		}

		wp_nonce_field( 'be_events_calendar_date_time', 'be_events_calendar_date_time_nonce' );
		?>

		<div class="section" style="min-height:0;">
			<label for="be-events-calendar-allday">All Day event?</label>
			<input name="be-events-calendar-allday" type="checkbox" id="be-events-calendar-allday" value="1" <?php checked( '1', $allday ); ?>>
		</div>
		<div class="section">
			<label for="be-events-calendar-start">Start date and time:</label> 
			<input name="be-events-calendar-start" type="text"  id="be-events-calendar-start" class="be-events-calendar-date" value="<?php echo !empty( $start ) ? $start_date : ''; ?>" placeholder="Date">
			<input name="be-events-calendar-start-time" type="text"  id="be-events-calendar-start-time" class="be-events-calendar-time" value="<?php echo !empty( $start ) ? $start_time : ''; ?>" placeholder="Time">
		</div>
		<div class="section">
			<label for="be-events-calendar-end">End date and time:</label> 
			<input name="be-events-calendar-end" type="text"  id="be-events-calendar-end" class="be-events-calendar-date" value="<?php echo !empty( $end ) ? $end_date : ''; ?>" placeholder="Date">
			<input name="be-events-calendar-end-time" type="text"  id="be-events-calendar-end-time" class="be-events-calendar-time" value="<?php echo !empty( $end ) ? $end_time : ''; ?>" placeholder="Time">
		</div>
		<p class="desc">Date format should be <strong>MM/DD/YYYY</strong>. Time format should be <strong>H:MM am/pm</strong>.<br>Example: 05/12/2015 6:00pm</p>
		<?php
	}
	
	/**
	 * Save metabox contents
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param array $post
	 */
	function metabox_save( $post_id, $post ) {
		
		// Security check
		if ( ! isset( $_POST['be_events_calendar_date_time_nonce'] ) || ! wp_verify_nonce( $_POST['be_events_calendar_date_time_nonce'], 'be_events_calendar_date_time' ) ) {
			return;
		}

		// Bail out if running an autosave, ajax, cron, or revision.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		 // Bail out if the user doesn't have the correct permissions to update the slider.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Make sure the event start/end dates were not left blank before we run the save
		if ( isset( $_POST['be-events-calendar-start'] ) && isset( $_POST['be-events-calendar-end'] ) && !empty( $_POST['be-events-calendar-start'] ) && !empty( $_POST['be-events-calendar-end'] ) ) {
			$start      = $_POST['be-events-calendar-start'] . ' ' . $_POST['be-events-calendar-start-time'];
			$start_unix = strtotime( $start );
			$end        = $_POST['be-events-calendar-end'] . ' ' . $_POST['be-events-calendar-end-time'];
			$end_unix   = strtotime( $end );
			$allday     = ( isset( $_POST['be-events-calendar-allday'] ) ? '1' : '0' );

			update_post_meta( $post_id, 'be_event_start',  $start_unix );
			update_post_meta( $post_id, 'be_event_end',    $end_unix   );
			update_post_meta( $post_id, 'be_event_allday', $allday     );
		}
	}
	
	/**
	 * Modify WordPress query where needed for event listings
	 *
	 * @since 1.0.0
	 * @param object $query
	 */
	function event_query( $query ) {

		// If you don't want the plugin to mess with the query, use this filter to override it
		$override = apply_filters( 'be_events_manager_query_override', false );
		if ( $override )
			return;
		
		if ( $query->is_main_query() && !is_admin() && ( is_post_type_archive( 'events' ) || is_tax( 'event-category' ) ) ) {	
			$meta_query = array(
				array(
					'key' => 'be_event_end',
					'value' => (int) current_time( 'timestamp' ),
					'compare' => '>'
				)
			);
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'ASC' );
			$query->set( 'meta_query', $meta_query );
			$query->set( 'meta_key', 'be_event_start' );
		}
	}
	
}

new BE_Events_Calendar;