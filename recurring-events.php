<?php
/**
 * Recurring Events
 *
 * @package      CoreFunctionality
 * @since        1.0.0
 * @link         https://github.com/billerickson/Core-Functionality
 * @author       Bill Erickson <bill@billerickson.net>
 * @copyright    Copyright (c) 2011, Bill Erickson
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
 
class BE_Recurring_Events {
	var $instance;

	public function __construct() {
		$this->instance =& $this;
		add_action( 'plugins_loaded', array( $this, 'init' ) );	

	}
	
	public function init() {

		// Create Post Type
		add_action( 'init', array( $this, 'post_type' ) );
		
		// Create Metabox
		add_filter( 'cmb_meta_boxes', array( $this, 'metaboxes' ) );
		add_action( 'init', array( $this, 'initialize_meta_boxes' ), 9999 );
		
		// Generate Events 
		add_action( 'wp_insert_post', array( $this, 'generate_events' ) );
				
	}
	
	/** 
	 * Register Post Type
	 * @link http://codex.wordpress.org/Function_Reference/register_post_type
	 *
	 */

	public function post_type() {
	
		$supports = get_theme_support( 'be-events-calendar' );
		if( !in_array( 'recurring-events', $supports[0] ) )
			return;
	
		$labels = array(
			'name' => 'Recurring Events',
			'singular_name' => 'Recurring Event',
			'add_new' => 'Add Recurring Event',
			'add_new_item' => 'Add New Recurring Event',
			'edit_item' => 'Edit Recurring Event',
			'new_item' => 'New Recurring Event',
			'view_item' => 'View Recurring Event',
			'search_items' => 'Search Recurring Events',
			'not_found' =>  'No recurring events found',
			'not_found_in_trash' => 'No recurring events found in trash',
			'parent_item_colon' => '',
			'menu_name' => 'Recurring Events'
		);
		
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => true, 
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => null,
			'show_in_menu' => 'edit.php?post_type=events',
			'supports' => array('title','editor')
		); 
	
		register_post_type( 'recurring-events', $args );	
	}
	
	/**
	 * Create Metaboxes
	 * @link http://www.billerickson.net/wordpress-metaboxes/
	 *
	 */
	
	function metaboxes( $meta_boxes ) {
		
		$events_metabox = array(
		    'id' => 'event-details',
		    'title' => 'Event Details',
		    'pages' => array('recurring-events'), 
			'context' => 'normal',
			'priority' => 'high',
			'show_names' => true, 
		    'fields' => array(
		    	array(
		    		'name' => 'First Event',
		    		'id' => 'be_event_details_label',
					'desc' => 'Serves as as base for all events',
					'type' => 'title',
		    	),
		    	array(
		    		'name' => 'Start Date and Time',
		    		'id' => 'be_event_start',
		    		'desc' => '',
		    		'type' => 'text_datetime_timestamp',
		    	),
		    	array(
		    		'name' => 'End Date and Time',
		    		'id' => 'be_event_end',
		    		'desc' => '',
		    		'type' => 'text_datetime_timestamp',
		    	),
		    	array(
		    		'name' => 'Recurring Options',
		    		'id' => 'be_recurring_label',
		    		'type' => 'title',
		    	),
		    	array(
		    		'name' => 'Repeat period',
		    		'id' => 'be_recurring_period',
		    		'type' => 'select',
		    		'options' => array(
		    			array( 'name' => 'Daily', 'value' => 'daily' ),
		    			array( 'name' => 'Weekly', 'value' => 'weekly' ),
		    			array( 'name' => 'Monthly', 'value' => 'monthly' ),
		    		)
		    	),
		    	array(
		    		'name' => 'Repeating Ends',
		    		'id' => 'be_recurring_end',
		    		'type' => 'text_date_timestamp',
		    		'desc' => '',
		    	)
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
	
	/**
	 * Generate Events
	 *
	 */
	function generate_events( $post_id ) {
		if( 'recurring-events' !== get_post_type( $post_id ) )
			return;
			
		$event_title = get_post( $post_id )->post_title;
		$event_content = get_post( $post_id )->post_content;
		$event_start = get_post_meta( $post_id, 'be_event_start', true );
		$event_end = get_post_meta( $post_id, 'be_event_end', true );
		
		$first = false;
		$stop = get_post_meta( $post_id, 'be_recurring_end', true );
		$period = get_post_meta( $post_id, 'be_recurring_period', true );
		while( $event_start < $stop ) {
		
			// Create the Event
			$args = array(
				'post_title' => $event_title,
				'post_content' => $event_content,
				'post_status' => 'publish',
				'post_type' => 'events',
				'post_parent' => $post_id,
			);
			$event_id = wp_insert_post( $args );
			if( $event_id ) {
				update_post_meta( $event_id, 'be_event_start', $event_start );
				update_post_meta( $event_id, 'be_event_end', $event_end );
			}
			
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
	}
		
}

new BE_Recurring_Events;