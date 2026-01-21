/**
 * wasmormon.org JavaScript functionality
 * 
 * Handles login/registration modals, directory lazy loading,
 * and form validation for the wasmormon.org theme.
 * 
 * @since 1.0.0
 * @author WasMormon.org Team
 */
document.addEventListener('DOMContentLoaded', function () {

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

		userLoginInputs.forEach(function (usernameInput, index) {
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

	// Initialize sortable tables
	initSortableTables();

	/**
	 * Initialize sortable tables site-wide
	 * 
	 * Finds all tables with the 'sortable-table' class and sets up click handlers
	 * on headers with 'data-sort' attributes. Supports multiple sort types:
	 * - 'int': Integer sorting
	 * - 'float': Decimal number sorting  
	 * - 'string': Alphabetical sorting
	 * - 'date': Date sorting (uses data attributes or parses text)
	 * 
	 * @returns {void}
	 */
	function initSortableTables() {
		const tables = document.querySelectorAll('.sortable-table');

		tables.forEach(function (table) {
			const headers = table.querySelectorAll('th[data-sort]');

			headers.forEach(function (header, index) {
				header.addEventListener('click', function () {
					sortTable(table, index, header.dataset.sort, header);
				});
			});
		});
	}

	/**
	 * Sort a table by a specific column
	 * 
	 * Sorts table rows based on the specified column and data type.
	 * Toggles between ascending and descending order on subsequent clicks.
	 * Updates visual sort indicators on headers.
	 * 
	 * @param {HTMLTableElement} table - The table element to sort
	 * @param {number} column - The column index to sort by
	 * @param {string} type - The sort type ('int', 'float', 'string', 'date')
	 * @param {HTMLTableCellElement} clickedHeader - The header that was clicked
	 * @returns {void}
	 */
	function sortTable(table, column, type, clickedHeader) {
		const tbody = table.querySelector('tbody');
		if (!tbody) return;

		const rows = Array.from(tbody.querySelectorAll('tr'));
		const headers = table.querySelectorAll('th');
		const isDesc = clickedHeader.classList.contains('sorted-desc');

		// Get the header text to determine which data attribute to use for dates
		const headerText = clickedHeader.textContent.trim().toLowerCase();

		rows.sort(function (a, b) {
			let aVal = getSortValue(a, column, type, headerText);
			let bVal = getSortValue(b, column, type, headerText);

			// Compare based on type
			if (type === 'int' || type === 'float') {
				aVal = parseFloat(aVal) || 0;
				bVal = parseFloat(bVal) || 0;
			} else if (type === 'string') {
				aVal = String(aVal).toLowerCase();
				bVal = String(bVal).toLowerCase();
			}
			// For 'date', values are already in comparable format (YYYY-MM-DD strings)

			if (isDesc) {
				return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
			}
			return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
		});

		// Re-append rows in sorted order
		rows.forEach(row => tbody.appendChild(row));

		// Update sort indicators
		headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc', 'sorted-desc'));
		clickedHeader.classList.add(isDesc ? 'sort-asc' : 'sort-desc');
		if (!isDesc) {
			clickedHeader.classList.add('sorted-desc');
		}
	}

	/**
	 * Get the sortable value from a table cell
	 * 
	 * Extracts the value to use for sorting from a cell. Checks multiple sources
	 * in order of priority:
	 * 1. Cell's data-sort-value attribute
	 * 2. Row's data-* attribute (for date columns)
	 * 3. Cell's text content
	 * 
	 * @param {HTMLTableRowElement} row - The table row
	 * @param {number} column - The column index
	 * @param {string} type - The sort type
	 * @param {string} headerText - The header text (for date attribute mapping)
	 * @returns {string|number} The value to use for sorting
	 */
	function getSortValue(row, column, type, headerText) {
		const cell = row.cells[column];
		if (!cell) return type === 'string' ? '' : 0;

		// Check for explicit sort value on cell
		if (cell.dataset.sortValue) {
			return cell.dataset.sortValue;
		}

		// For date type, check row data attributes based on header text
		if (type === 'date') {
			// Map header text to data attribute names
			const dateAttrMap = {
				'born': 'birth',
				'spouse born': 'birth',
				'died': 'death',
				'spouse died': 'death',
				'married': 'married',
				'marriage date': 'married',
				'year': 'year'
			};

			// Find matching data attribute
			for (const [text, attr] of Object.entries(dateAttrMap)) {
				if (headerText.includes(text) && row.dataset[attr]) {
					return row.dataset[attr];
				}
			}

			// Fallback: try to parse date from cell text
			const cellText = cell.textContent.trim();
			if (cellText && cellText !== '—' && cellText !== '●') {
				const parsed = Date.parse(cellText);
				if (!isNaN(parsed)) {
					return new Date(parsed).toISOString().split('T')[0];
				}
			}
			return '9999-12-31'; // Sort unknown dates last
		}

		// Get text content and clean it up
		let value = cell.textContent.trim();

		// Clean up common patterns for numeric sorting
		if (type === 'int' || type === 'float') {
			value = value.replace(/[^\d.-]/g, ''); // Remove non-numeric chars except . and -
			value = value.replace('—', '0');
		}

		return value;
	}
});
