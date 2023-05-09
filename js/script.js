jQuery(document).ready(function($) {
	
	// nav login link click event handler
	$('.nav-login').on('click', navLoginClick);
	$('.register').on('click', navRegisterClick);
	
	function navLoginClick(e) {
		e.preventDefault();
		// open login modal
		$('.lwa-modal-trigger-el').trigger('click');
		$('.lwa-form').show();
		$('.lwa-register').hide();
		$('.lwa-links-register-inline').remove();
		$('.lwa-links-register-inline-cancel').remove();
	}

	
	function navRegisterClick(e){ 
		e.preventDefault(); 
		// console.log('register link clicked');
		// open login modal and register form
		$('.lwa-modal-trigger-el').trigger('click');
		$('.lwa-form').hide();
		$('.lwa-register').show();
		$('.lwa-links-register-inline-cancel').remove();
	}

	// add html validation for register form - give better hints on error too.
	$('.lwa-modal-overlay .lwa-register .lwa-username input').attr({
		pattern: '[a-z0-9.]{4,}',
		title: 'Only lowercase letters and numbers. Minimum 4 characters.',
		required: true
	});

	$('.lwa-modal-overlay .lwa-register .lwa-email input').attr({
		type: 'email',
		required: true
	})
	
});