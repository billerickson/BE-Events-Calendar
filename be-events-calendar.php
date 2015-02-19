<?php
/**
 * Plugin Name: BE Events Calendar
 * Description: Allows you to manage events.
 * Version:     1.1.0
 * Author:      Bill Erickson
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.  You may NOT assume that you can use any other
 * version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package    BE-Events-Calendar
 * @since      1.0.0
 * @link       https://github.com/billerickson/BE-Events-Calendar
 * @author     Bill Erickson <bill@billerickson.net>
 * @copyright  Copyright (c) 2014, Bill Erickson
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants
define( 'BE_EVENTS_CALENDAR_VERSION', '1.1.0' );
define( 'BE_EVENTS_CALENDAR_DIR', plugin_dir_path( __FILE__ ) );
define( 'BE_EVENTS_CALENDAR_URL', plugin_dir_url( __FILE__ ) );

// Include files
require_once BE_EVENTS_CALENDAR_DIR . 'inc/events.php';
require_once BE_EVENTS_CALENDAR_DIR . 'inc/events-recurring.php';
require_once BE_EVENTS_CALENDAR_DIR . 'inc/widget-upcoming-events.php';
require_once BE_EVENTS_CALENDAR_DIR . 'inc/genesis-schema.php';
require_once BE_EVENTS_CALENDAR_DIR . 'inc/view-calendar.php';