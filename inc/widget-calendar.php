<?php
/**
 * Calendar widget.
 *
 * @package    BE-Events-Calendar
 * @since      1.1.0
 * @link       https://github.com/billerickson/BE-Events-Calendar
 * @author     Bill Erickson <bill@billerickson.net>
 * @copyright  Copyright (c) 2015, Bill Erickson
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

class BE_Events_Calendar_Widget extends WP_Widget {

	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @since 1.1.0
	 * @var array
	 */
	protected $defaults;

	/**
	 * Constructor
	 *
	 * @since 1.1.0
	 */
	function __construct() {

		// widget defaults
		$this->defaults = array(
			'title'  => '',
		);

		// widget basics
		$widget_ops = array(
			'classname'   => 'be-events-calendar-widget',
			'description' => '',
		);

		// widget controls
		$control_ops = array(
			'id_base' => 'be-events-calendar-widget',
		);

		// load widget
		parent::__construct( 'be-events-calendar-widget', 'Events Calendar Widget', $widget_ops, $control_ops );

		// Ajax
		add_action( 'wp_ajax_be_events_calendar_widget',        array( $this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_be_events_calendar_widget', array( $this, 'ajax' ) );
	}

	/**
	 * Render the calendar
	 *
	 * @since 1.1.0
	 * @param string $month
	 */
	function calendar( $month = '' ) {

			if ( !empty( $month ) ) {
				$timestamp  = strtotime( str_replace( '-', ' ', $month ) );
			} else {
				$timestamp = current_time( 'timestamp' );
			}

			$month         = date( 'n', $timestamp );
			$month_text    = date( 'F', $timestamp );
			$year          = date( 'Y', $timestamp );
			$days_in_month = date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
			$month_start   = strtotime( "$month/1/$year" );
			$month_end     = strtotime( "$month/$days_in_month/$year" );

			//------------------------------------------------------------------
			// Loop
			//------------------------------------------------------------------
			$calendar    = array();
			$events_args = array(
				'post_type' => 'events',
				'meta_query' => array(
					array(
						'key'     => 'be_event_start',
						'value'   => array( $month_start, $month_end ),
						'type'    => 'NUMERIC',
						'compare' => 'BETWEEN',
					)
				),
			);									
			$events = new WP_Query( $events_args ); while( $events->have_posts() ) : $events->the_post();
				$all_day         = false;
				$start_timestamp = get_post_meta( get_the_ID(), 'be_event_start', true );
				$start_time      = date( 'h:iA', $start_timestamp );
				$end_timestamp   = get_post_meta( get_the_ID(), 'be_event_end', true );
				$end_time        = date( 'h:iA', $end_timestamp );

				// Determine if the event is "all day". If the start and end time are both
				// set to 12:00am then the user did not specify a time, thus the event will
				// be considered all day.
				if ( $start_time == '12:01AM' && $end_time == '11:59PM' ) {
					$all_day = true;
				}

				$calendar[] = array(
					'title'  => get_the_title(),
					'start'  => $start_timestamp,
					'end'    => $end_timestamp,
					'url'    => get_permalink(),
					'allDay' => $all_day,
				);
			endwhile;
			wp_reset_postdata();

			//------------------------------------------------------------------
			// Calendar
			//------------------------------------------------------------------

			// Open calendar table
			echo '<table class="be-event-calendar-widget">';

			// Day headings
			$headings = array( 'Sun','Mon','Tues','Wed','Thu','Fri','Sat' );
			$headings = apply_filters( 'be_events_calendar_widget_headings', $headings );
			echo '<thead><tr class="calendar-row"><th class="calendar-day-head">' . implode( '</th><th class="calendar-day-head">', $headings ) . '</th></tr></thead>';

			// Vars
			$running_day       = date( 'w', mktime( 0, 0, 0, $month, 1, $year ) );
			$days_in_this_week = 1;
			$day_counter       = 0;	

			echo '<div class="calendar-month" id="' . date( 'F-Y', $timestamp ) . '">';
				echo '<a href="#" class="prev be-event-calendar-widget-previous">&laquo;</a>';
				echo '<strong>' . $month_text. ' ' . $year . '</strong>';
				echo '<a href="" class="next be-event-calendar-widget-next">&raquo;</a>';	
			echo '</div>';

			// Begin calendar body
			echo '<tbody><tr class="calendar-row">';

			// Before month blanks
			for( $x = 0; $x < $running_day; $x++ ) :
				echo '<td class="calendar-day-np">&nbsp;</td>';
				$days_in_this_week++;
			endfor;

			// Generate the days
			for( $list_day = 1; $list_day <= $days_in_month; $list_day++ ) :

				$now          = "$month/$list_day/$year";
				$class        = ( $now === date( 'n/d/Y', current_time( 'timestamp' ) ) ? 'current' : '' );
				$today        = strtotime( $now, current_time( 'timestamp' ) );
				$tomorrow     = $today+86400;
				$today_events = array();

				foreach( $calendar as $event ) {
					if( $event['start'] <= $tomorrow && $event['end'] >= $today ) {
						$today_events[] = $event;
					}
				}

				echo '<td class="calendar-day ' . $class . '">';
					if ( !empty( $today_events ) ) {
						echo '<a class="day-number has-events" href="#">' . $list_day . '</a>';
						echo '<div class="events-pop" style="display:none;">';
							foreach ( $today_events as $event ) {
								echo '<a href="' . esc_html( $event['url'] ) . '">';
									if ( $event['allDay'] == false && date( 'd', $event['start'] ) == date( 'd', $event['end'] ) ) {
										echo '<span class="event-time">';
										if ( date( 'A', $event['start'] ) == date( 'A', $event['end'] ) ) {
											// Same AM/PM
											echo date( 'g:i', $event['start'] ) . '-' . date( 'g:ia', $event['end'] );
										} else {
											echo date( 'g:ia', $event['start'] ) . '-' . date( 'g:ia', $event['end'] );
										}
										echo '</span> ';
									} else {
										// All day or multi-day event
									}
									echo esc_html( $event['title'] );
									echo '<span class="arrow"></span>';
								 echo '</a>';
							}
						echo '</div>';
					} else {
						echo '<div class="day-number">' . $list_day . '</div>';
					}
				echo '</td>';

				if ( $running_day == 6 ):
					echo '</tr>';
					if( ( $day_counter+1 ) != $days_in_month ):
						echo '<tr class="calendar-row">';
					endif;
					$running_day = -1;
					$days_in_this_week = 0;
				endif;
				$days_in_this_week++; $running_day++; $day_counter++;
			endfor;

			// After month blanks
			if ( $days_in_this_week < 8 && $days_in_this_week!=1 ):
				for ( $x = 1; $x <= ( 8 - $days_in_this_week ); $x++ ):
					echo '<td class="calendar-day-np">&nbsp;</td>';
				endfor;
			endif;

			// Finish calendar table
			echo '</tr></tbody></table>';

	}

	/**
	 * Ajax pass-through function
	 *
	 * @since 1.1.0
	 */
	function ajax() {

		check_ajax_referer( 'be_events_calendar_widget', 'nonce' );

		$timestamp = strtotime( str_replace( '-', ' ', $_POST['month'] ) );
		if ( $_POST['next'] === 'true' ) {
			$month = strtotime('+1 month', $timestamp );
		} else {
			$month = strtotime('-1 month', $timestamp );
		}
	
		ob_start();
		$this->calendar( date( 'F-Y', $month ) );
		$output = ob_get_clean();
		echo json_encode( array( 'calendar' => $output ) );
		die();
	}

	/**
	 * Outputs the HTML for this widget.
	 *
	 * @since 1.1.0
	 * @param array $args An array of standard parameters for widgets in this theme 
	 * @param array $instance An array of settings for this widget instance 
	 */
	function widget( $args, $instance ) {

		extract( $args );

		// Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		// Load assets
		$css = apply_filters( 'be_events_calendar_widget_css', true );
		if ( true === $css ) {
			wp_enqueue_style( 'be-events-calendar-widget', BE_EVENTS_CALENDAR_URL . 'css/events-calendar-widget.css', array(), BE_EVENTS_CALENDAR_VERSION );
		}

		echo $before_widget;

			echo '<div class="be-events-calendar-widget-wrap">';
				$this->calendar( date( 'F-Y' ) );
			echo '</div>';

			?>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					$(document).on('click', '.be-event-calendar-widget-previous, .be-event-calendar-widget-next', function(event) {
						event.preventDefault();
						var $wrap = $(this).parent().parent();
						var next = $(this).hasClass('next');
						$wrap.css('cursor', 'progress');
						$(this).css('cursor', 'progress');
						var opts = {
							url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
							type: 'post',
							dataType: 'json',
							data: {
								action: 'be_events_calendar_widget',
								nonce: '<?php echo wp_create_nonce( 'be_events_calendar_widget' );  ?>',
								month: $wrap.find('.calendar-month').attr('id'),
								next: next
							},
							success: function(res){
								$wrap.empty().html(res.calendar);
								$wrap.css('cursor', 'auto');
								$(this).css('cursor', 'auto');
							},
							error: function(xhr, textStatus ,e) {
								if ( xhr.responseText ) {
									console.log(xhr.responseText);
								}
							}
						}
						$.ajax(opts);
					});
					$(document).on('click', '.be-event-calendar-widget a.day-number', function(event) {
						event.preventDefault();
						var day = $(this).parent();
						var dayHeight = day.outerHeight();
						var dayWidth = day.width();
						var dayOffset = day.offset();
						var cal = $(this).parent().parent();
						var calWidth = cal.width();
						var calOffset = cal.offset();
						var pop = $(this).next('.events-pop').addClass('current');
						$(cal).find('.events-pop.visible').each(function(){
							if( ! $(this).hasClass('current' ) )
								$(this).removeClass('visible').fadeToggle();							
						});
						pop.removeClass('current').addClass('visible').find( '.arrow').css({
							left: dayOffset.left - calOffset.left + 3
						});
						pop.css({
							left: calOffset.left - dayOffset.left,
							top: dayHeight+15,
							width: calWidth
						}).fadeToggle();
					});
				});
			</script>
			<?php

		echo $after_widget;
	}
 
	/**
	 * Deals with the settings when they are saved by the admin. Here is
	 * where any validation should be dealt with.
	 *
	 * @since 1.1.0
	 * @param array $new_instance An array of new settings as submitted by the admin
	 * @param array $old_instance An array of the previous settings 
	 * @return array The validated and (if necessary) amended settings
	 */
	function update( $new_instance, $old_instance ) {

		$new_instance['title'] = strip_tags( $new_instance['title'] );
		return $new_instance;
	}

	/**
	 * Displays the form for this widget on the Widgets page of the WP Admin area.
	 *
	 * @since 1.1.0
	 * @param array $instance An array of the current settings for this widget
	 */
	function form( $instance ) {

		// Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>
		<?php
	}
}
add_action( 'widgets_init', create_function( '', "register_widget('BE_Events_Calendar_Widget');" ) );