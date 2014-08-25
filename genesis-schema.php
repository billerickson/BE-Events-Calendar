<?php
/**
 * Schema for Genesis
 *
 * @package    BE-Events-Calendar
 * @since      1.0.3
 * @link       https://github.com/billerickson/BE-Events-Calendar
 * @author     Bill Erickson <bill@billerickson.net>
 * @copyright  Copyright (c) 2014, Bill Erickson
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
 
class BE_Event_Schema {


	public function __construct() {

		add_action( 'plugins_loaded', array( $this, 'init' ) );	

	}
	
	public function init() {

		add_filter( 'genesis_attr_content', array( $this, 'empty_schema' ), 20 );
		add_filter( 'genesis_attr_entry', array( $this, 'event_schema' ), 20 );
		add_filter( 'genesis_attr_entry-title', array( $this, 'event_name_itemprop' ), 20 );
		add_filter( 'genesis_attr_entry-content', array( $this, 'event_description_itemprop' ), 20 );
		add_filter( 'genesis_post_title_output', array( $this, 'title_link' ), 20 );
		add_action( 'genesis_entry_header', array( $this, 'event_date' ) );
	}
	
	/**
	 * Empty Schema
	 *
	 */
	function empty_schema( $attr ) {
	
		// Only run on events archive
		if( !is_post_type_archive( 'events' ) )
			return $attr;
			
		$attr['itemtype'] = '';
		$attr['itemprop'] = '';
		$attr['itemscope'] = '';
		return $attr;	
	}
	
	/**
	 * Event Schema
	 *
	 */
	function event_schema( $attr ) {

		// Only run on event
		if( ! 'events' == get_post_type() )
			return $attr;
			
		$attr['itemtype'] = 'http://schema.org/Event';
		$attr['itemprop'] = '';
		$attr['itemscope'] = 'itemscope';
		return $attr;
	}

	/**
	 * Event Name Itemprop
	 *
	 */
	function event_name_itemprop( $attr ) {
		if( 'events' == get_post_type() )
			$attr['itemprop'] = 'name';
		return $attr;
	}
	
	/**
	 * Event Description Itemprop
	 *
	 */
	function event_description_itemprop( $attr ) {
		if( 'events' == get_post_type() )
			$attr['itemprop'] = 'description';
		return $attr;
	}
	
	/**
	 * Title Link
	 *
	 */
	function title_link( $output ) {
		if( 'events' == get_post_type() )
			$output = str_replace( 'rel="bookmark"', 'rel="bookmark" itemprop="url"', $output );
		return $output;
	}
	
	/**
	 * Event Date
	 *
	 */
	function event_date() {
		if( 'events' !== get_post_type() )
			return;
			
		$start = get_post_meta( get_the_ID(), 'be_event_start', true );
		if( $start )
			 echo '<meta itemprop="startDate" content="' . date('c', $start ).'">';
		
		$end = get_post_meta( get_the_ID(), 'be_event_end', true );
		if( $end ) 
			echo '<meta itemprop="endDate" content="' . date( 'c', $end ).'">';
		
	}
}

new BE_Event_Schema;
