<?php
/*
Plugin Name: BE Events Calendar
Plugin URI:  http://www.github.com/billerickson/BE-Events-Calendar
Description: Allows you to manage events
Version:     1.0.3
Author:      Bill Erickson
Author URI:  http://www.billerickson.net
License:     GPLv2
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define version number for asset enqueueing
define( 'BE_EVENTS_CALENDAR_VERSION', '1.0.3' );

// Include files
require_once plugin_dir_path( __FILE__ ) . 'be-events-calendar.php';
require_once plugin_dir_path( __FILE__ ) . 'recurring-events.php';
require_once plugin_dir_path( __FILE__ ) . 'upcoming-events-widget.php';
require_once plugin_dir_path( __FILE__ ) . 'genesis-schema.php';