jQuery(document).ready(function($){
	$('.be-events-calendar-date').datepicker();
	$('#be-events-calendar-allday').change(function(event) {
		beAllDay();
	});
	beAllDay();
	function beAllDay() {
		if ( $('#be-events-calendar-allday').is(":checked") ) {
			$('#be-events-calendar-start-time').val('12:01 AM').hide();
			$('#be-events-calendar-end-time').val('11:59 PM').hide();
		} else {
			$('#be-events-calendar-start-time').val('').show();
			$('#be-events-calendar-end-time').val('').show();
		}
	}
});