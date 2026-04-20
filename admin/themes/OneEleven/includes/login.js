$(function() {
	$('.info-wrapper').removeClass('open');
	// shake it all on error
	$('#error').effect('shake', {
		times: 6,
		distance: 3
	}, 15);
	// reveal any message
	$('.message').hide().fadeIn(2600);
	// focus input with class focus
	$('input.focus').first().trigger('focus');
	// toggle info window
	$('.toggle-info').on('click', function(ev) {
		ev.preventDefault();
		$('.info').toggle();
		$('.info-wrapper').toggleClass('open');
		return false;
	});
});
