jQuery(document).ready(function($) {
	
	// nav login link click event handler
	$( '.nav-login' ).on( 'click', navLoginClick );
	$( '.register' ).on( 'click', navRegisterClick );
	$( '.directory-load-more .load-more-button' ).on( 'click', directoryLoadMore );
	$( window ).on( 'scroll resize', directoryAutoLoad );
	
	function navLoginClick( e ) {
		e.preventDefault();
		// open login modal
		$('.lwa-modal-trigger-el').trigger('click');
		$('.lwa-form').show();
		$('.lwa-register').hide();
		$('.lwa-links-register-inline').remove();
		$('.lwa-links-register-inline-cancel').remove();
	}

	
	function navRegisterClick( e ){ 
		e.preventDefault(); 
		// open login modal and register form
		$('.lwa-modal-trigger-el').trigger('click');
		$('.lwa-form').hide();
		$('.lwa-register').show();
		$('.lwa-links-register-inline-cancel').remove();
	}

	// get next group of people in directory and remove the lazy load class
	function directoryLoadMore( e ){
		e.preventDefault();
		// command click detected - load all remaining people
		if ( e.metaKey ) {
			$('.directory .person.lazy-load-profile').removeClass('lazy-load-profile');
		} else {
			// get offset value
			var offset = parseInt( $('.directory-load-more').attr('data-offset') );
			// load next people
			$('.directory .person.lazy-load-profile').slice(0,offset).removeClass('lazy-load-profile');
		}
		// if no more people to load, disable and hide the load more button
		if( $('.directory .person.lazy-load-profile').length == 0 ){
			$('.load-more-button').attr('disabled','disabled');
			$('.directory-load-more').hide();
		}
	}

	// Automatically load more when scrolling to bottom
	function directoryAutoLoad( e ) {
		const loadMore = $( '.directory-load-more' );
		if ( loadMore.length && isOnScreen( loadMore, 100 ) ) {
			$('.directory-load-more .load-more-button').trigger('click');
		}
	}

	// auto load more people when user scrolls to bottom of directory
	function isOnScreen( el, offset = 0 ) {
		const elementTop = $(el).offset().top;
		const elementBottom = elementTop + $(el).outerHeight();
		const viewportTop = $(window).scrollTop();
		const viewportBottom = viewportTop + $(window).height();
		// return elementBottom > viewportTop && elementTop < viewportBottom; // exact
		return elementTop > viewportTop && elementBottom < viewportBottom; // adds some padding
	}

	// Add html validation for user registration username field - also give better hints on error.
	$('.lwa-modal-overlay .lwa-register .lwa-username input').attr({
		pattern: '[a-z0-9.]{4,}',
		title: 'Only lowercase letters and numbers. Minimum 4 characters.',
		required: true
	});

	// Add html validation for user registration email field - sets type to email and required.
	$('.lwa-modal-overlay .lwa-register .lwa-email input').attr({
		type: 'email',
		required: true
	});
	
});