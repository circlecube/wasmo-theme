jQuery(document).ready(function($) {
	
	// nav login link click event handler
	$('.nav-login').on('click', navLoginClick);
	// open modal on nav link click
	function navLoginClick(e) {
		e.preventDefault();
		$('.lwa-links-modal').trigger('click');
	}
	
});