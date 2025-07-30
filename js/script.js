/**
 * wasmormon.org JavaScript functionality
 * 
 * Handles login/registration modals, directory lazy loading,
 * and form validation for the wasmormon.org theme.
 * 
 * @since 1.0.0
 * @author WasMormon.org Team
 */
document.addEventListener('DOMContentLoaded', function() {
	
	/**
	 * Initialize event listeners for navigation and directory functionality
	 * 
	 * Sets up click handlers for login/register links, directory load more button,
	 * and scroll/resize listeners for automatic directory loading.
	 */
	const navLoginLinks = document.querySelectorAll('.nav-login');
	const registerLinks = document.querySelectorAll('.register');
	const loadMoreButton = document.querySelector('.directory-load-more .load-more-button');
	
	// Attach event listeners
	navLoginLinks.forEach(link => link.addEventListener('click', navLoginClick));
	registerLinks.forEach(link => link.addEventListener('click', navRegisterClick));
	if (loadMoreButton) {
		loadMoreButton.addEventListener('click', directoryLoadMore);
	}
	window.addEventListener('scroll', directoryAutoLoad);
	window.addEventListener('resize', directoryAutoLoad);
	
	/**
	 * Handle navigation login link clicks
	 * 
	 * Opens the login modal and displays the login form while hiding
	 * the registration form. Removes any existing registration links
	 * to prevent UI conflicts.
	 * 
	 * @param {Event} e - The click event object
	 * @returns {void}
	 */
	function navLoginClick(e) {
		e.preventDefault();
		
		// Get modal elements
		const modalTrigger = document.querySelector('.lwa-modal-trigger-el');
		const lwaForm = document.querySelector('.lwa-form');
		const lwaRegister = document.querySelector('.lwa-register');
		const registerInlineLinks = document.querySelectorAll('.lwa-links-register-inline');
		const registerInlineCancelLinks = document.querySelectorAll('.lwa-links-register-inline-cancel');
		
		// Open modal and show login form
		if (modalTrigger) modalTrigger.click();
		if (lwaForm) lwaForm.style.display = 'block';
		if (lwaRegister) lwaRegister.style.display = 'none';
		
		// Clean up registration-related links
		registerInlineLinks.forEach(link => link.remove());
		registerInlineCancelLinks.forEach(link => link.remove());
	}

	/**
	 * Handle navigation register link clicks
	 * 
	 * Opens the login modal and displays the registration form while hiding
	 * the login form. Removes any existing registration cancel links
	 * to prevent UI conflicts.
	 * 
	 * @param {Event} e - The click event object
	 * @returns {void}
	 */
	function navRegisterClick(e) { 
		e.preventDefault(); 
		
		// Get modal elements
		const modalTrigger = document.querySelector('.lwa-modal-trigger-el');
		const lwaForm = document.querySelector('.lwa-form');
		const lwaRegister = document.querySelector('.lwa-register');
		const registerInlineCancelLinks = document.querySelectorAll('.lwa-links-register-inline-cancel');
		
		// Open modal and show registration form
		if (modalTrigger) modalTrigger.click();
		if (lwaForm) lwaForm.style.display = 'none';
		if (lwaRegister) lwaRegister.style.display = 'block';
		
		// Clean up registration cancel links
		registerInlineCancelLinks.forEach(link => link.remove());
	}

	/**
	 * Handle directory "Load More" button clicks
	 * 
	 * Loads the next batch of profile cards by removing the 'lazy-load-profile'
	 * class from hidden profiles. Supports both regular loading (based on offset)
	 * and Command+Click to load all remaining profiles at once.
	 * 
	 * When all profiles are loaded, disables and hides the load more button.
	 * 
	 * @param {Event} e - The click event object
	 * @returns {void}
	 */
	function directoryLoadMore(e) {
		e.preventDefault();
		const lazyLoadProfiles = document.querySelectorAll('.directory .person.lazy-load-profile');
		
		if (e.metaKey) {
			// Command+Click detected - load all remaining profiles
			lazyLoadProfiles.forEach(profile => profile.classList.remove('lazy-load-profile'));
		} else {
			// Regular click - load next batch based on offset
			const loadMoreContainer = document.querySelector('.directory-load-more');
			const offset = parseInt(loadMoreContainer.getAttribute('data-offset')) || 12;
			const profilesToLoad = Array.from(lazyLoadProfiles).slice(0, offset);
			profilesToLoad.forEach(profile => profile.classList.remove('lazy-load-profile'));
		}
		
		// Check if all profiles are now loaded
		const remainingProfiles = document.querySelectorAll('.directory .person.lazy-load-profile');
		if (remainingProfiles.length === 0) {
			const loadMoreButton = document.querySelector('.load-more-button');
			const loadMoreContainer = document.querySelector('.directory-load-more');
			if (loadMoreButton) loadMoreButton.setAttribute('disabled', 'disabled');
			if (loadMoreContainer) loadMoreContainer.style.display = 'none';
		}
	}

	/**
	 * Handle automatic directory loading on scroll/resize
	 * 
	 * Automatically triggers the "Load More" functionality when the user
	 * scrolls near the load more button, providing infinite scroll behavior.
	 * Also triggered on window resize to handle viewport changes.
	 * 
	 * @param {Event} e - The scroll or resize event object
	 * @returns {void}
	 */
	function directoryAutoLoad(e) {
		const loadMore = document.querySelector('.directory-load-more');
		if (loadMore && isOnScreen(loadMore, 100)) {
			const loadMoreButton = document.querySelector('.directory-load-more .load-more-button');
			if (loadMoreButton) loadMoreButton.click();
		}
	}

	/**
	 * Check if an element is visible on screen
	 * 
	 * Determines whether an element is currently visible within the viewport,
	 * with optional offset padding. Used for triggering automatic loading
	 * when users scroll near interactive elements.
	 * 
	 * @param {HTMLElement} el - The element to check visibility for
	 * @param {number} [offset=0] - Additional offset padding in pixels
	 * @returns {boolean} True if element is visible on screen, false otherwise
	 */
	function isOnScreen(el, offset = 0) {
		const rect = el.getBoundingClientRect();
		const elementTop = rect.top + window.pageYOffset;
		const elementBottom = elementTop + el.offsetHeight;
		const viewportTop = window.pageYOffset;
		const viewportBottom = viewportTop + window.innerHeight;
		
		// Check if element is within viewport bounds (with padding)
		// return elementBottom > viewportTop && elementTop < viewportBottom; // exact calculation
		return elementTop > viewportTop && elementBottom < viewportBottom; // adds visual padding
	}

	/**
	 * Initialize form validation for user registration
	 * 
	 * Sets up HTML5 validation attributes for username and email fields
	 * in the login modal registration form. Provides user-friendly
	 * validation messages and requirements.
	 */
	
	// Username field validation
	// const usernameModalInput = document.querySelector('.lwa-modal-overlay .lwa-register .lwa-username input');
	// if (usernameModalInput) {
	// 	usernameModalInput.setAttribute('pattern', '[a-zA-Z0-9._-]{4,50}');
	// 	usernameModalInput.setAttribute('title', 'Letters, numbers, dash, period, or underscore. Minimum 4 characters.');
	// 	usernameModalInput.setAttribute('required', 'true');
	// }

	// Email field validation
	const emailModalInput = document.querySelector('.lwa-modal-overlay .lwa-register .lwa-email input');
	if (emailModalInput) {
		emailModalInput.setAttribute('type', 'email');
		emailModalInput.setAttribute('required', 'true');
	}
	
	/**
	 * Handle username preview and update for all user login inputs
	 * 
	 * Finds all username inputs on the page and adds individual preview elements
	 * to show how the username will be displayed on the site. Each input gets
	 * its own preview that appears directly beneath it.
	 * 
	 * @returns {void}
	 */
	function initializeUsernamePreview() {
		const userLoginInputs = document.querySelectorAll('input[name="user_login"]');
		
		userLoginInputs.forEach(function(usernameInput, index) {
			// username field validation
			usernameInput.setAttribute('pattern', '[a-zA-Z0-9._\\-]{4,50}');
			usernameInput.setAttribute('title', 'Letters, numbers, dash, period, or underscore. Minimum 4 characters.');
			usernameInput.setAttribute('required', 'true');

			// Create unique preview element for this input
			const previewElement = document.createElement('span');
			previewElement.className = 'user-login-note';
			previewElement.id = 'user-login-preview-' + index;
			
			// Insert preview element after the username input
			usernameInput.parentNode.insertBefore(previewElement, usernameInput.nextSibling);
			
			/**
			 * Format username according to site rules
			 * 
			 * Converts spaces and periods to dashes, removes invalid characters,
			 * and ensures compatibility with the site's username requirements.
			 * 
			 * @param {string} username - Raw username input
			 * @returns {string} Formatted username
			 */
			function formatUsername(username) {
				return username
					.replace(/[\s\.]+/g, '-')
					.replace(/[^-a-zA-Z0-9._]/g, '');
			}
			
			/**
			 * Update the preview text for this specific input
			 * 
			 * Shows the formatted username and the URL where their story
			 * will be published. Also triggers greeting update if applicable.
			 * 
			 * @returns {void}
			 */
			function updatePreview() {
				const username = formatUsername(usernameInput.value.toLowerCase());
				if (username) {
					previewElement.innerHTML = '✅ Your story will be published at <u>wasmormon.org/@' + username + '</u>';
					previewElement.style.display = 'block';
				} else {
					previewElement.style.display = 'none';
				}
				updateGreeting(username);
			}
			
			/**
			 * Update greeting field with formatted username
			 * 
			 * Automatically populates the greeting field with a friendly
			 * "Hello, I'm [username]!" message when username is entered.
			 * 
			 * @param {string} username - Formatted username to use in greeting
			 * @returns {void}
			 */
			function updateGreeting(username) {
				const greeting = document.querySelector('input[id="acf-field_66311d94efe37"]');
				if (greeting && username) {
					greeting.value = 'Hello, I\'m ' + username + '!';
				}
			}
			
			// Add event listeners for real-time updates
			usernameInput.addEventListener('input', updatePreview);
			usernameInput.addEventListener('keyup', updatePreview);
			usernameInput.addEventListener('change', updatePreview);
			
			// Initial update
			updatePreview();
		});
	}
	
	// Initialize username previews
	initializeUsernamePreview();
});
