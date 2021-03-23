jQuery(document).ready(function($) {
	
	// nav login link click event handler
	$('.nav-login').on('click', navLoginClick);
	// open modal on nav link click
	function navLoginClick(e) {
		e.preventDefault();
		// open login modal
		$('.lwa-links-modal').trigger('click');
		$('.lwa-form').show();
		$('.lwa-register').hide();
		$('.lwa-links-register-inline-cancel').show();
	}

	$('.register').on('click', function(e){ 
		e.preventDefault(); 
		console.log('register link clicked');
		// open register modal
		$('.lwa-links-modal').trigger('click');
		$('.lwa-form').hide();
		$('.lwa-register').show();
		$('.lwa-links-register-inline-cancel').hide();
		// $('.lwa-links-register').trigger('click'); 
	} );

	// html validation for register form - give better hints on error too.
	$('.lwa-modal #user_login').attr({
		pattern: '[a-z0-9.]{4,}',
		title: 'Only lowercase letters and numbers. Minimum 4 characters.',
		required: true
	});

	$('.lwa-modal #user_email').attr({
		type: 'email',
		required: true
	})
	
});