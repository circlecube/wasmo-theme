jQuery(document).ready(function($) {
	
	// nav login link click event handler
	$('.nav-login').on('click', navLoginClick);
	// open modal on nav link click
	function navLoginClick(e) {
		e.preventDefault();
		// open login modal
		$('.lwa-links-modal').trigger('click');
	}

	$('.register').on('click', function(e){ 
		e.preventDefault(); 
		// open register modal
		$('.lwa-links-register').trigger('click'); 
	} );
	
});