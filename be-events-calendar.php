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

	public function __construct() {

		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ) );	
	}
	
	public function activation() {
		flush_rewrite_rules();
	}

	public function init() {
	
		// Create Post Type
		add_action( 'init', array( $this, 'post_type' ) );
		add_filter( 'manage_edit-events_columns', array( $this, 'edit_event_columns' ) ) ;
		add_action( 'manage_events_posts_custom_column', array( $this, 'manage_event_columns' ), 10, 2 );
		add_filter( 'manage_edit-events_sortable_columns', array( $this, 'event_sortable_columns' ) );
		add_action( 'load-edit.php', array( $this, 'edit_event_load' ) );
		
		// Create Taxonomy
		add_action( 'init', array( $this, 'taxonomies' ) );
		
		// Create Metabox
		add_filter( 'cmb_meta_boxes', array( $this, 'metaboxes' ) );
		add_action( 'init', array( $this, 'initialize_meta_boxes' ), 9999 );
		
		// Modify Event Listings query
		add_action( 'pre_get_posts', array( $this, 'event_query' ) );
		
	}
	
	/** 
	 * Register Post Type
	 * @link http://codex.wordpress.org/Function_Reference/register_post_type
	 *
	 */

	public function post_type() {
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
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 *
	 */
	
	function edit_event_columns( $columns ) {
	
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'title'       => __( 'Event' ),
			'event_start' => __( 'Starts' ),
			'event_end'   => __( 'Ends' ),
			'date'        => __( 'Published Date' )
		);
	
		return $columns;
	}
	
	/**
	 * Edit Column Content
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 *
	 */

	function manage_event_columns( $column, $post_id ) {
		global $post;
	
		switch( $column ) {
	
			/* If displaying the 'duration' column. */
			case 'event_start' :
	
				/* Get the post meta. */
				$start = esc_attr( date( 'M j, Y g:i A', get_post_meta( $post_id, 'be_event_start', true ) ) );
	
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
				$end = esc_attr( date( 'M j, Y g:i A', get_post_meta( $post_id, 'be_event_end', true ) ) );
	
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
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 *
	 */

	function event_sortable_columns( $columns ) {
	
		$columns['event_start'] = 'event_start';
		$columns['event_end']   = 'event_end';
	
		return $columns;
	}	 
	
	function edit_event_load() {
		add_filter( 'request', array( $this, 'sort_events' ) );
	}
	
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
	 * Create Taxonomies
	 * @link http://codex.wordpress.org/Function_Reference/register_taxonomy
	 *
	 */
	
	function taxonomies() {
	
		$supports = get_theme_support( 'be-events-calendar' );
		if( !is_array( $supports ) || !in_array( 'event-category', $supports[0] ) )
			return;
			
		$post_types = in_array( 'recurring-events', $supports[0] ) ? array( 'events', 'recurring-events' ) : array( 'events' );
			
		$labels = array(
			'name'              => 'Categories',
			'singular_name'     => 'Category',
			'search_items'      =>  'Search Categories',
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
	 * Create Metaboxes
	 * @link http://www.billerickson.net/wordpress-metaboxes/
	 *
	 */
	
	function metaboxes( $meta_boxes ) {
		
		$events_metabox = array(
		    'id'         => 'event-details',
		    'title'      => 'Event Details',
		    'pages'      => array('events'), 
			'context'    => 'normal',
			'priority'   => 'high',
			'show_names' => true, 
		    'fields'     => array(
		    	array(
		    		'name' => 'Start Date and Time',
		    		'id'   => 'be_event_start',
		    		'desc' => '',
		    		'type' => 'text_datetime_timestamp',
		    	),
		    	array(
		    		'name' => 'End Date and Time',
		    		'id'   => 'be_event_end',
		    		'desc' => '',
		    		'type' => 'text_datetime_timestamp',
		    	),
		    )
		
		);
		
		// Use this to override the metabox and create your own
		$override = apply_filters( 'be_events_manager_metabox_override', false );
		if ( false === $override ) $meta_boxes[] = $events_metabox;
		
		return $meta_boxes;
	}

	function initialize_meta_boxes() {
	    if (!class_exists('cmb_Meta_Box')) {
	        require_once( 'lib/metabox/init.php' );
	    }
	}
	
	function event_query( $query ) {

		// If you don't want the plugin to mess with the query, use this filter to override it
		$override = apply_filters( 'be_events_manager_query_override', false );
		if( $override )
			return;
		
		if( $query->is_main_query() && !is_admin() && ( is_post_type_archive( 'events' ) || is_tax( 'event-category' ) ) ) {	
			$meta_query = array(
				array(
					'key' => 'be_event_end',
					'value' => time(),
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
