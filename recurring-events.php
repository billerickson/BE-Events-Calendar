<?php
/**
 * Recurring Events
 *
 * @package    BE-Events-Calendar
 * @since      1.0.0
 * @link       https://github.com/billerickson/BE-Events-Calendar
 * @author     Bill Erickson <bill@billerickson.net>
 * @copyright  Copyright (c) 2014, Bill Erickson
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
 
class BE_Recurring_Events {

	/**
	 * Primary class constructor
	 * 
	 */
	function __construct() {

		add_action( 'plugins_loaded', array( $this, 'init' ) );	
	}
	
	/**
	 * Loads the class into WordPress
	 *
	 */
	function init() {

		// Create Post Type
		add_action( 'init', array( $this, 'post_type' ) );
		
		// Post Type columns
		add_filter( 'manage_edit-recurring-events_columns', array( $this, 'edit_event_columns' ), 20 ) ;
		add_action( 'manage_recurring-events_posts_custom_column', array( $this, 'manage_event_columns' ), 20, 2 );
		
		// Post Type sorting
		add_filter( 'manage_edit-recurring-events_sortable_columns', array( $this, 'event_sortable_columns' ), 20 );
		//add_action( 'load-edit.php', array( $this, 'edit_event_load' ), 20 );

		// Post Type title placeholder
		add_action( 'gettext',  array( $this, 'title_placeholder' ) );

		// Create Metabox
		$metabox = apply_filters( 'be_events_manager_metabox_override', false );
		if ( false === $metabox ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'metabox_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'metabox_scripts' ) );
			add_action( 'add_meta_boxes', array( $this, 'metabox_register' ) );
			add_action( 'save_post', array( $this, 'metabox_save' ),  1, 2  );
		}
		
		// Generate Events 
		add_action( 'wp_insert_post', array( $this, 'generate_events' ) );
		add_action( 'wp_insert_post', array( $this, 'regenerate_events' ) );		
	}
	
	/** 
	 * Register Post Type
	 * 
	 * @link http://codex.wordpress.org/Function_Reference/register_post_type
	 */
	function post_type() {

		// Only run if recurring event support has been added
		$supports = get_theme_support( 'be-events-calendar' );
		if ( !is_array( $supports ) || !in_array( 'recurring-events', $supports[0] ) )
			return;
	
		$labels = array(
			'name'               => 'Recurring Events',
			'singular_name'      => 'Recurring Event',
			'add_new'            => 'Add Recurring Event',
			'add_new_item'       => 'Add New Recurring Event',
			'edit_item'          => 'Edit Recurring Event',
			'new_item'           => 'New Recurring Event',
			'view_item'          => 'View Recurring Event',
			'search_items'       => 'Search Recurring Events',
			'not_found'          =>  'No recurring events found',
			'not_found_in_trash' => 'No recurring events found in trash',
			'parent_item_colon'  => '',
			'menu_name'          => 'Recurring Events'
		);
		
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true, 
			'show_in_menu'       => true, 
			'query_var'          => true,
			'rewrite'            => true,
			'capability_type'    => 'post',
			'has_archive'        => true, 
			'hierarchical'       => false,
			'menu_position'      => null,
			'show_in_menu'       => 'edit.php?post_type=events',
			'supports'           => array( 'title','editor') 
		); 
	
		register_post_type( 'recurring-events', $args );	
	}
	
	/**
	 * Edit Column Titles
	 * 
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 * @param array $columns
	 * @return array
	 */
	function edit_event_columns( $columns ) {
		
		$supports = get_theme_support( 'be-events-calendar' );
		if( !is_array( $supports ) || !in_array( 'recurring-events', $supports[0] ) )
			return $columns;
	
		$new_columns = array();
		foreach( $columns as $key => $label ) {
			$new_columns[$key] = $label;
			if( 'title' == $key )
				$new_columns['recurring'] = 'Part of Series';
		}	
		return $new_columns;
	}
	
	/**
	 * Edit Column Content
	 * 
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 * @param string $column
	 * @param int $post_id
	 */
	function manage_event_columns( $column, $post_id ) {
		
		if ( 'recurring' == $column ) {
			$parent = get_post_meta( get_the_ID(), 'be_recurring_event', true );
			if ( !empty( $parent ) )
				echo '<a href="' . get_edit_post_link( $parent ) . '">' . get_the_title( $parent ) . '</a>';		
		}
	}	 
	
	/**
	 * Make Columns Sortable
	 * 
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 * @param array $columns
	 * @return array
	 */
	function event_sortable_columns( $columns ) {
	
		$columns['recurring'] = 'recurring';	
		return $columns;
	}	 
	
	/**
	 * Check for load request
	 *
	 */
	function edit_event_load() {

		add_filter( 'request', array( $this, 'sort_events' ) );
	}
	
	/**
	 * Sort events on load request
	 *
	 * @param array $vars
	 * @return array
	 */
	function sort_events( $vars ) {

		/* Check if we're viewing the 'event' post type. */
		if ( isset( $vars['post_type'] ) && 'events' == $vars['post_type'] ) {
	
			/* Check if 'orderby' is set to 'recurring'. */
			if ( isset( $vars['orderby'] ) && 'recurring' == $vars['orderby'] ) {
	
				/* Merge the query vars with our custom variables. */
				$vars = array_merge(
					$vars,
					array(
						'meta_key' => 'be_recurring_event',
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
	 * @global array $post
	 * @param string $translation
	 * @return string Customized translation for title
	 */
	function title_placeholder( $translation ) {

		global $post;
		if ( isset( $post ) && 'recurring-events' == $post->post_type && 'Enter title here' == $translation ) {
			$translation = 'Enter Event Name Here';
		}
		return $translation;
	}

	/**
	 * Loads styles for metaboxes
	 *
	 */
	function metabox_styles() {

		if ( isset( get_current_screen()->base ) && 'post' !== get_current_screen()->base ) {
			return;
		}

		if ( isset( get_current_screen()->post_type ) && 'recurring-events' != get_current_screen()->post_type ) {
			return;
		}

		// Load styles
		wp_register_style( 'be-events-calendar', plugins_url( 'css/events-admin.css', __FILE__ ), array(), BE_EVENTS_CALENDAR_VERSION );
		wp_enqueue_style( 'be-events-calendar' );
	}

	/**
	 * Loads scripts for metaboxes.
	 *
	 */
	function metabox_scripts( $hook ) {

		if ( isset( get_current_screen()->base ) && 'post' !== get_current_screen()->base ) {
			return;
		}

		if ( isset( get_current_screen()->post_type ) && 'recurring-events' != get_current_screen()->post_type ) {
			return;
		}

		// Load scripts.
		wp_register_script( 'be-events-calendar', plugins_url( 'js/events-admin.js', __FILE__ ), array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ) , BE_EVENTS_CALENDAR_VERSION, true );
		wp_enqueue_script( 'be-events-calendar' );
	}

	/**
	 * Initialize the metabox
	 *
	 */
	function metabox_register() {

		add_meta_box( 'be-events-calendar-date-time', 'Date and Time Details', array( $this, 'render_metabox' ), 'recurring-events', 'normal', 'high' );
	}

	/**
	 * Render the metabox
	 *
	 * @since 1.0.0
	 */
	function render_metabox() {

		$start         = get_post_meta( get_the_ID() , 'be_event_start',       true );
		$end           = get_post_meta( get_the_ID() , 'be_event_end',         true );
		$recurring     = get_post_meta( get_the_ID() , 'be_recurring_period',  true );
		$recurring_end = get_post_meta( get_the_ID() , 'be_recurring_end',     true );
		$regenerate    = get_post_meta( get_the_ID() , 'be_regenerate_events', true );

		if ( !empty( $start ) && !empty( $end ) ) {
			$start_date = date( 'm/d/Y', $start );
			$start_time = date( 'g:ia',  $start );
			$end_date   = date( 'm/d/Y', $end   );
			$end_time   = date( 'g:ia',  $end   );
		}
		if ( !empty( $recurring_end ) ) {
			$recurring_end = date( 'm/d/Y', $recurring_end );
		}

		wp_nonce_field( 'be_events_calendar_date_time', 'be_events_calendar_date_time_nonce' );
		?>
		<div class="section">
			<p class="title">First Event</p>
			<p class="subtitle">Serves as a base for all events</p>
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
		<hr>
		<div class="section">
			<p class="title">Recurring Options</p>
		</div>
		<div class="section">
			<label for="be-events-calendar-repeat">Repeat period:</label> 
			<select name="be-events-calendar-repeat" id="be-events-calendar-repeat">
				<option value="daily" <?php selected( 'daily', $recurring ); ?>>Daily</option>
				<option value="weekly" <?php selected( 'weekly', $recurring ); ?>>Weekly</option>
				<option value="monthly" <?php selected( 'montly', $recurring ); ?>>Monthly</option>
			</select>
		</div>
		<div class="section">
			<label for="be-events-calendar-repeat-end">Repeat ends:</label> 
			<input name="be-events-calendar-repeat-end" type="text"  id="be-events-calendar-repeat-end" class="be-events-calendar-date" value="<?php echo !empty( $recurring_end ) ? $recurring_end : ''; ?>" placeholder="Date">
		</div>
		<div class="section">
			<label for="be-events-calendar-regenerate">Repeat events:</label> 
			<input type="checkbox" name="be-events-calendar-regenerate" id="be-events-calendar-regenerate" value="1" <?php checked( '1', $regenerate ); ?>>
			<span class="check-desc"><strong>This will delete all scheduled events!</strong> Past events will be unchanged.</span>
		</div>
		<?php
	}
	
	/**
	 * Save metabox contents
	 *
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
		if ( isset( $_POST['be-events-calendar-start'] ) 
			&& isset( $_POST['be-events-calendar-end'] ) 
			&& isset( $_POST['be-events-calendar-repeat-end'] ) 
			&& !empty( $_POST['be-events-calendar-start'] ) 
			&& !empty( $_POST['be-events-calendar-end'] ) 
			&& !empty( $_POST['be-events-calendar-repeat-end'] ) )
		{
			$start      = $_POST['be-events-calendar-start'] . ' ' . $_POST['be-events-calendar-start-time'];
			$start_unix = strtotime( $start );
			$end        = $_POST['be-events-calendar-end'] . ' ' . $_POST['be-events-calendar-end-time'];
			$end_unix   = strtotime( $end );

			update_post_meta( $post_id, 'be_event_start', $start_unix );
			update_post_meta( $post_id, 'be_event_end',   $end_unix   );
			update_post_meta( $post_id, 'be_recurring_period', $_POST['be-events-calendar-repeat'] );
			update_post_meta( $post_id, 'be_recurring_end',  strtotime( $_POST['be-events-calendar-repeat-end'] )  );

			if ( isset( $_POST['be-events-calendar-regenerate'] ) ) {
				update_post_meta( $post_id, 'be_regenerate_events', '1' );
			}
		}
	}
	
	/**
	 * Generate Events
	 *
	 */
	function generate_events( $post_id, $regenerating = false ) {

		if( 'recurring-events' !== get_post_type( $post_id ) )
			return;
			
		if( 'publish' !== get_post_status( $post_id ) )
			return;
			
		// Only generate once
		$generated = get_post_meta( $post_id, 'be_generated_events', true );
		if( $generated )
			return;
			
		$event_title = get_post( $post_id )->post_title;
		$event_content = get_post( $post_id )->post_content;
		$event_start = get_post_meta( $post_id, 'be_event_start', true );
		$event_end = get_post_meta( $post_id, 'be_event_end', true );
		
		$first = false;
		$stop = get_post_meta( $post_id, 'be_recurring_end', true );
		if( empty( $stop ) && !empty( $event_start ) )
			$stop = strtotime( '+1 Years', $event_start );
		$period = get_post_meta( $post_id, 'be_recurring_period', true );
		while( $event_start < $stop ) {
		
			// For regenerating, only create future events
			if( !$regenerating || ( $regenerating && $event_start > time() ) ):

				// Create the Event
				$args = array(
					'post_title' => $event_title,
					'post_content' => $event_content,
					'post_status' => 'publish',
					'post_type' => 'events',
				);
				$event_id = wp_insert_post( $args );
				if( $event_id ) {
					update_post_meta( $event_id, 'be_recurring_event', $post_id );
					update_post_meta( $event_id, 'be_event_start', $event_start );
					update_post_meta( $event_id, 'be_event_end', $event_end );

					// Add any additional metadata
					$metas = apply_filters( 'be_events_manager_recurring_meta', array() );
					if( !empty( $metas ) ) {
						foreach( $metas as $meta ) {
							update_post_meta( $event_id, $meta, get_post_meta( $post_id, $meta, true ) );
						}
					}
					
					// Event Category
					$supports = get_theme_support( 'be-events-calendar' );
					if( is_array( $supports ) && in_array( 'event-category', $supports[0] ) ) {
						$terms = get_the_terms( $post_id, 'event-category' );
						if( !empty( $terms ) && !is_wp_error( $terms ) ) {
							$terms = wp_list_pluck( $terms, 'slug' );
							wp_set_object_terms( $event_id, $terms, 'event-category' );
						}
					}


				}
			endif;
			
			// Increment the date
			switch( $period ) {
				
				case 'daily':
					$event_start = strtotime( '+1 Days', $event_start );
					$event_end = strtotime( '+1 Days', $event_end );
					break;
					
				case 'weekly':
					$event_start = strtotime( '+1 Weeks', $event_start );
					$event_end = strtotime( '+1 Weeks', $event_end );
					break;
					
				case 'monthly':
					$event_start = strtotime( '+1 Months', $event_start );
					$event_end = strtotime( '+1 Months', $event_end );
					break;
			}
		}
		
		// Dont generate again
		update_post_meta( $post_id, 'be_generated_events', true );
	}
	
	/**
	 * Regenerate Events
	 *
	 */
	function regenerate_events( $post_id ) {
		if( 'recurring-events' !== get_post_type( $post_id ) )
			return;
			
		// Make sure they want to regenerate them
		$regenerate = get_post_meta( $post_id, 'be_regenerate_events', true );
		if( ! $regenerate )	
			return;
			
		// Delete all future events
		$args = array(
			'post_type' => 'events',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'be_event_start',
					'value' => time(),
					'compare' => '>'
				),
				array(
					'key' => 'be_recurring_event',
					'value' => $post_id,
				)
			)
		);
		$loop = new WP_Query( $args );
		if( $loop->have_posts() ): while( $loop->have_posts() ): $loop->the_post();
			wp_delete_post( get_the_ID(), false );
		endwhile; endif; wp_reset_postdata();
		
		// Turn off regenerate and on generate
		delete_post_meta( $post_id, 'be_regenerate_events' );
		delete_post_meta( $post_id, 'be_generated_events' );
		
		// Generate new events
		$this->generate_events( $post_id, true );
	}
		
}

new BE_Recurring_Events;
