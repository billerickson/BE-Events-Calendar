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
	 * @since 1.0.0
	 */
	function __construct() {

		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Loads the class into WordPress
	 *
	 * @since 1.0.0
	 */
	function init() {

		// Create Post Type
		add_action( 'init', array( $this, 'post_type' ) );

		// Post Type columns
		add_filter( 'manage_edit-event_columns', array( $this, 'edit_event_columns' ), 20 );
		add_action( 'manage_event_posts_custom_column', array( $this, 'manage_event_columns' ), 20, 2 );

		// Post Type sorting
		add_filter( 'manage_edit-event_sortable_columns', array( $this, 'event_sortable_columns' ), 20 );
		//add_action( 'load-edit.php', array( $this, 'edit_event_load' ), 20 );

		// Post Type title placeholder
		add_filter( 'enter_title_here', array( $this, 'title_placeholder' ) );

		// Create Metabox
		$metabox = apply_filters( 'be_events_manager_metabox_override', false );
		if ( false === $metabox ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'metabox_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'metabox_scripts' ) );
			add_action( 'add_meta_boxes', array( $this, 'metabox_register' ) );
			add_action( 'save_post', array( $this, 'metabox_save' ), 1, 2 );
		}

		// Generate Events
		add_action( 'wp_insert_post', array( $this, 'generate_events' ) );
		add_action( 'wp_insert_post', array( $this, 'regenerate_events' ) );
	}

	/**
	 * Register Post Type
	 *
	 * @since 1.0.0
	 */
	function post_type() {

		// Only run if recurring event support has been added
		$supports = get_theme_support( 'be-events-calendar' );
		if ( ! is_array( $supports ) || ! in_array( 'recurring-events', $supports[0] ) ) {
			return;
		}

		$labels = array(
			'name'               => _x( 'Recurring Events', 'post type general name', 'be-events-calendar' ),
			'singular_name'      => _x( 'Recurring Event', 'post type singular name', 'be-events-calendar' ),
			'add_new'            => __( 'Add Recurring Event', 'be-events-calendar' ),
			'add_new_item'       => __( 'Add New Recurring Event', 'be-events-calendar' ),
			'edit_item'          => __( 'Edit Recurring Event', 'be-events-calendar' ),
			'new_item'           => __( 'New Recurring Event', 'be-events-calendar' ),
			'view_item'          => __( 'View Recurring Event', 'be-events-calendar' ),
			'view_items'         => __( 'View Recurring Events', 'be-events-calendar' ),
			'search_items'       => __( 'Search Recurring Events', 'be-events-calendar' ),
			'not_found'          => __( 'No recurring events found', 'be-events-calendar' ),
			'not_found_in_trash' => __( 'No recurring events found in trash', 'be-events-calendar' ),
			'parent_item_colon'  => __( 'Parent Recurring Event:', 'be-events-calendar' ),
			'all_items'          => __( 'All Recurring Events', 'be-events-calendar' ),
			'archives'           => __( 'Recurring Event Archives', 'be-events-calendar' ),
			'attributes'         => __( 'Recurring Event Attributes', 'be-events-calendar' ),
			'menu_name'          => _x( 'Recurring Events', 'admin menu', 'be-events-calendar' ),
			'filter_items_list'        => __( 'Filter recurring events list', 'be-events-calendar' ),
			'items_list_navigation'    => __( 'Recurring Events list navigation', 'be-events-calendar' ),
			'items_list'               => __( 'Recurring Events list', 'be-events-calendar' ),
			'item_published'           => __( 'Recurring Event published.', 'be-events-calendar' ),
			'item_published_privately' => __( 'Recurring Event published privately.', 'be-events-calendar' ),
			'item_reverted_to_draft'   => __( 'Recurring Event reverted to draft.', 'be-events-calendar' ),
			'item_scheduled'           => __( 'Recurring Event scheduled.', 'be-events-calendar' ),
			'item_updated'             => __( 'Recurring Event updated.', 'be-events-calendar' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=event',
			'query_var'          => true,
			'rewrite'            => true,
			'capability_type'    => 'post',
			'taxonomies'         => BE_Events_Calendar::get_theme_supported_taxonomies(),
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'       => true,
			'rest_base'          => 'recurring-events',
		);

		$args = apply_filters( 'be_events_manager_recurring_post_type_args', $args );
		register_post_type( 'recurring_event', $args );
	}

	/**
	 * Edit Column Titles
	 *
	 * @since 1.0.0
	 *
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 *
	 * @param array $columns
	 * @return array
	 */
	function edit_event_columns( $columns ) {

		$supports = get_theme_support( 'be-events-calendar' );
		if ( ! is_array( $supports ) || ! in_array( 'recurring-events', $supports[0] ) ) {
			return $columns;
		}

		$new_columns = array();
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'title' == $key ) {
				$new_columns['recurring'] = esc_html__( 'Part of Series', 'be-events-calendar' );
			}
		}

		return $new_columns;
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

		if ( 'recurring' == $column ) {
			$parent = get_post_meta( $post_id, 'be_recurring_event', true );
			if ( ! empty( $parent ) ) {
				echo '<a href="' . get_edit_post_link( $parent ) . '">' . get_the_title( $parent ) . '</a>';
			}
		}
	}

	/**
	 * Make Columns Sortable
	 *
	 * @since 1.0.0
	 *
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 *
	 * @param array $columns
	 * @return array
	 */
	function event_sortable_columns( $columns ) {

		$columns['recurring'] = esc_html__( 'recurring', 'be-events-calendar' );

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
	 *
	 * @param array $vars
	 * @return array
	 */
	function sort_events( $vars ) {

		// Check if we're viewing the 'event' post type.
		if ( isset( $vars['post_type'] ) && 'event' == $vars['post_type'] ) {

			// Check if 'orderby' is set to 'recurring'.
			if ( isset( $vars['orderby'] ) && 'recurring' == $vars['orderby'] ) {

				// Merge the query vars with our custom variables.
				$vars = array_merge(
					$vars,
					array(
						'meta_key' => 'be_recurring_event',
						'orderby'  => 'meta_value_num',
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
	 *
	 * @param string $title
	 * @return string Customized translation for title
	 */
	function title_placeholder( $title ) {

		$screen = get_current_screen();
		if ( 'event' === $screen->post_type ) {
			$title = __( 'Enter Event Name Here', 'be-events-calendar' );
		}

		return $title;
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

		if ( isset( get_current_screen()->post_type ) && 'recurring_event' != get_current_screen()->post_type ) {
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

		if ( isset( get_current_screen()->post_type ) && 'recurring_event' != get_current_screen()->post_type ) {
			return;
		}

		// Load scripts.
		wp_register_script( 'be-events-calendar', BE_EVENTS_CALENDAR_URL . 'js/events-admin.js', array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-datepicker',
		), BE_EVENTS_CALENDAR_VERSION, true );
		wp_enqueue_script( 'be-events-calendar' );

		$l18n_data['dateFormat'] = apply_filters( 'be_event_set_dmy_format', false ) ? 'dd/mm/yy' : 'mm/dd/yy';

		wp_localize_script( 'be-events-calendar', 'beEventsCalendar', $l18n_data );
	}

	/**
	 * Initialize the metabox
	 *
	 * @since 1.0.0
	 */
	function metabox_register() {

		add_meta_box( 'be-events-calendar-date-time', esc_html__( 'Date and Time Details', 'be-events-calendar' ), array( $this, 'render_metabox' ), 'recurring_event', 'normal', 'high' );
	}

	/**
	 * Render the metabox
	 *
	 * @since 1.0.0
	 */
	function render_metabox() {

		$set_dmy_format = apply_filters( 'be_event_set_dmy_format', false );
		$date_format    = $set_dmy_format ? 'd/m/Y' : 'm/d/Y';

		$set_24_hour_clock = apply_filters( 'be_event_set_24_hour_clock', false );
		$time_format       = $set_24_hour_clock ? 'G:i' : 'g:ia';

		$start         = get_post_meta( get_the_ID(), 'be_event_start', true );
		$end           = get_post_meta( get_the_ID(), 'be_event_end', true );
		$recurring     = get_post_meta( get_the_ID(), 'be_recurring_period', true );
		$recurring_end = get_post_meta( get_the_ID(), 'be_recurring_end', true );
		$regenerate    = get_post_meta( get_the_ID(), 'be_regenerate_events', true );

		if ( ! empty( $start ) && ! empty( $end ) ) {
			$start_date = date( $date_format, $start );
			$start_time = date( $time_format, $start );
			$end_date   = date( $date_format, $end );
			$end_time   = date( $time_format, $end );
		}
		if ( ! empty( $recurring_end ) ) {
			$recurring_end = date( $date_format, $recurring_end );
		}

		wp_nonce_field( 'be_events_calendar_date_time', 'be_events_calendar_date_time_nonce' );
		?>
		<div class="section">
			<p class="title"><?php esc_html_e( 'First Event', 'be-events-calendar' ); ?></p>
			<p class="subtitle"><?php esc_html_e( 'Serves as a base for all events', 'be-events-calendar' ); ?></p>
		</div>
		<div class="section">
			<label
				for="be-events-calendar-start"><?php esc_html_e( 'Start date and time:', 'be-events-calendar' ); ?></label>
			<input name="be-events-calendar-start" type="text" id="be-events-calendar-start"
			       class="be-events-calendar-date" value="<?php echo ! empty( $start_date ) ? $start_date : ''; ?>"
			       placeholder="<?php esc_html_e( 'Date', 'be-events-calendar' ); ?>">
			<input name="be-events-calendar-start-time" type="text" id="be-events-calendar-start-time"
			       class="be-events-calendar-time" value="<?php echo ! empty( $start_time ) ? $start_time : ''; ?>"
			       placeholder="<?php esc_html_e( 'Time', 'be-events-calendar' ); ?>">
		</div>
		<div class="section">
			<label for="be-events-calendar-end"><?php esc_html_e( 'End date and time:', 'be-events-calendar' ); ?></label>
			<input name="be-events-calendar-end" type="text" id="be-events-calendar-end" class="be-events-calendar-date"
			       value="<?php echo ! empty( $end_date ) ? $end_date : ''; ?>"
			       placeholder="<?php esc_html_e( 'Date', 'be-events-calendar' ); ?>">
			<input name="be-events-calendar-end-time" type="text" id="be-events-calendar-end-time"
			       class="be-events-calendar-time" value="<?php echo ! empty( $end_time ) ? $end_time : ''; ?>"
			       placeholder="<?php esc_html_e( 'Time', 'be-events-calendar' ); ?>">
		</div>
		<p class="desc">
			<?php printf( esc_html__( 'Date format should be %s.', 'be-events-calendar'), '<strong>' . ( $set_dmy_format ? 'DD/MM/YYYY' : 'MM/DD/YYYY' ) . '</strong>' ); ?>
			<?php printf( esc_html__( 'Time format should be %s.', 'be-events-calendar' ), '<strong>' . ( $set_24_hour_clock ? 'H:MM' : 'H:MM' ) . '</strong>' ); ?>
			<br><?php printf( esc_html__( 'Example: %s.', 'be-events-calendar' ), ( $set_dmy_format ? '21/05/2015' : '05/21/2015' ) . ' ' . ( $set_24_hour_clock ? '18:00' : '6:00pm' ) ); ?>
		</p>
		<hr>
		<div class="section">
			<p class="title"><?php esc_html_e( 'Recurring Options', 'be-events-calendar' ); ?></p>
		</div>
		<div class="section">
			<label for="be-events-calendar-repeat"><?php esc_html_e( 'Repeat period:', 'be-events-calendar' ); ?></label>
			<select name="be-events-calendar-repeat" id="be-events-calendar-repeat">
				<option value="daily" <?php selected( 'daily', $recurring ); ?>><?php esc_html_e( 'Daily', 'be-events-calendar' ); ?></option>
				<option value="weekly" <?php selected( 'weekly', $recurring ); ?>><?php esc_html_e( 'Weekly', 'be-events-calendar' ); ?></option>
				<option value="monthly" <?php selected( 'montly', $recurring ); ?>><?php esc_html_e( 'Monthly', 'be-events-calendar' ); ?></option>
			</select>
		</div>
		<div class="section">
			<label for="be-events-calendar-repeat-end"><?php esc_html_e( 'Repeat ends:', 'be-events-calendar' ); ?></label>
			<input name="be-events-calendar-repeat-end" type="text" id="be-events-calendar-repeat-end"
			       class="be-events-calendar-date"
			       value="<?php echo ! empty( $recurring_end ) ? $recurring_end : ''; ?>" placeholder="Date">
		</div>
		<div class="section">
			<label for="be-events-calendar-regenerate"><?php esc_html_e( 'Regenerate events:', 'be-events-calendar' ); ?></label>
			<input type="checkbox" name="be-events-calendar-regenerate" id="be-events-calendar-regenerate"
			       value="1" <?php checked( '1', $regenerate ); ?>>
			<span class="check-desc">
				<strong><?php esc_html_e( 'This will update all events of this serie!', 'be-events-calendar' ); ?></strong>
				<?php esc_html_e( 'Past events will be unchanged.', 'be-events-calendar' ); ?>
			</span>
		</div>
		<?php
	}

	/**
	 * Save metabox contents
	 *
	 * @since 1.0.0
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
		     && ! empty( $_POST['be-events-calendar-start'] )
		     && ! empty( $_POST['be-events-calendar-end'] )
		     && ! empty( $_POST['be-events-calendar-repeat-end'] )
		) {
			$start           = $_POST['be-events-calendar-start'] . ' ' . $_POST['be-events-calendar-start-time'];
			$start_unix      = strtotime( $set_dmy_format ? str_replace('/', '-', $start ) : $start );
			$end             = $_POST['be-events-calendar-end'] . ' ' . $_POST['be-events-calendar-end-time'];
			$end_unix        = strtotime( $set_dmy_format ? str_replace('/', '-', $end ) : $end );
			$repeat_end      = $_POST['be-events-calendar-repeat-end'];
			$repeat_end_unix = strtotime( $set_dmy_format ? str_replace('/', '-', $repeat_end ) : $repeat_end );

			update_post_meta( $post_id, 'be_event_start', $start_unix );
			update_post_meta( $post_id, 'be_event_end', $end_unix );
			update_post_meta( $post_id, 'be_recurring_period', $_POST['be-events-calendar-repeat'] );
			update_post_meta( $post_id, 'be_recurring_end', $repeat_end_unix );

			if ( isset( $_POST['be-events-calendar-regenerate'] ) ) {
				update_post_meta( $post_id, 'be_regenerate_events', '1' );
			}
		}
	}

	/**
	 * Generate Events
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 * @param boolean $regenerating
	 */
	function generate_events( $post_id, $regenerating = false ) {

		if ( 'recurring_event' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return;
		}

		// Only generate once
		$generated = get_post_meta( $post_id, 'be_generated_events', true );
		if ( $generated ) {
			return;
		}

		$event_title   = get_post( $post_id )->post_title;
		$event_content = get_post( $post_id )->post_content;
		$event_start   = get_post_meta( $post_id, 'be_event_start', true );
		$event_end     = get_post_meta( $post_id, 'be_event_end', true );

		$first = false;
		$stop  = get_post_meta( $post_id, 'be_recurring_end', true );
		if ( empty( $stop ) && ! empty( $event_start ) ) {
			$stop = strtotime( '+1 Years', $event_start );
		}
		$period = get_post_meta( $post_id, 'be_recurring_period', true );
		while ( $event_start < $stop ) {

			// For regenerating, only create future events
			if ( ! $regenerating || ( $regenerating && $event_start > time() ) ):

				// Create the Event
				$args     = array(
					'post_title'   => $event_title,
					'post_content' => $event_content,
					'post_status'  => 'publish',
					'post_type'    => 'event',
				);
				$event_id = wp_insert_post( $args );
				if ( $event_id ) {
					update_post_meta( $event_id, 'be_recurring_event', $post_id );
					update_post_meta( $event_id, 'be_event_start', $event_start );
					update_post_meta( $event_id, 'be_event_end', $event_end );

					// Add any additional metadata
					$metas = apply_filters( 'be_events_manager_recurring_meta', array( '_thumbnail_id' ) );
					if ( ! empty( $metas ) ) {
						foreach ( $metas as $meta ) {
							update_post_meta( $event_id, $meta, get_post_meta( $post_id, $meta, true ) );
						}
					}

					// Event Category
					$supports = get_theme_support( 'be-events-calendar' );
					if ( is_array( $supports ) && in_array( 'event-category', $supports[0] ) ) {
						$terms = get_the_terms( $post_id, 'event_category' );
						if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
							$terms = wp_list_pluck( $terms, 'slug' );
							wp_set_object_terms( $event_id, $terms, 'event_category' );
						}
					}

				}
			endif;

			// Increment the date
			switch ( $period ) {

				case 'daily':
					$event_start = strtotime( '+1 Days', $event_start );
					$event_end   = strtotime( '+1 Days', $event_end );
					break;

				case 'weekly':
					$event_start = strtotime( '+1 Weeks', $event_start );
					$event_end   = strtotime( '+1 Weeks', $event_end );
					break;

				case 'monthly':
					$event_start = strtotime( '+1 Months', $event_start );
					$event_end   = strtotime( '+1 Months', $event_end );
					break;
			}
		}

		// Dont generate again
		update_post_meta( $post_id, 'be_generated_events', true );
	}

	/**
	 * Regenerate Events
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 */
	function regenerate_events( $post_id ) {
		if ( 'recurring_event' !== get_post_type( $post_id ) ) {
			return;
		}

		// Make sure they want to regenerate them
		$regenerate = get_post_meta( $post_id, 'be_regenerate_events', true );
		if ( ! $regenerate ) {
			return;
		}

		// Delete all future events
		$args = array(
			'post_type'      => 'event',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => 'be_event_start',
					'value'   => time(),
					'compare' => '>',
				),
				array(
					'key'   => 'be_recurring_event',
					'value' => $post_id,
				),
			),
		);
		$loop = new WP_Query( $args );
		if ( $loop->have_posts() ): while ( $loop->have_posts() ): $loop->the_post();
			wp_delete_post( get_the_ID(), false );
		endwhile; endif;
		wp_reset_postdata();

		// Turn off regenerate and on generate
		delete_post_meta( $post_id, 'be_regenerate_events' );
		delete_post_meta( $post_id, 'be_generated_events' );

		// Generate new events
		$this->generate_events( $post_id, true );
	}

}

new BE_Recurring_Events;
