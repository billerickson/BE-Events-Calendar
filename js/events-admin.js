jQuery(document).ready(function($){
	$('.be-events-calendar-date').datepicker();
	$('#be-events-calendar-allday').change(function(event) {
		if ( $(this).is(":checked") ) {
			$('#be-events-calendar-start-time').val('12:01AM').hide();
			$('#be-events-calendar-end-time').val('11:59PM').hide();
		} else {
			$('#be-events-calendar-start-time').val('').show();
			$('#be-events-calendar-end-time').val('').show();
		}
	});
	if ( $('#be-events-calendar-allday').is(":checked") ) {
		$('#be-events-calendar-start-time').hide();
		$('#be-events-calendar-end-time').hide();
	}
});