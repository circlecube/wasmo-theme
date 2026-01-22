/**
 * Profile Interactions - Reactions AJAX handling
 * 
 * @package Wasmo_Theme
 */

(function ($) {
    'use strict';

    // Store reaction type info for UI updates
    var reactionTypes = {};

    /**
     * Initialize reaction buttons
     */
    function initReactionButtons() {
        // Build reaction types map from the DOM
        $('.reaction-picker .reaction-btn').each(function () {
            var $btn = $(this);
            var type = $btn.data('type');
            var title = $btn.attr('title') || '';
            var label = title.split(' - ')[0] || type;
            reactionTypes[type] = {
                label: label,
                iconHtml: $btn.find('.reaction-icon').html()
            };
        });

        // Handle reaction button clicks
        $(document).on('click', '.reaction-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);
            var $container = $btn.closest('.profile-reactions');
            var profileUserId = $container.data('profile');
            var section = $container.data('section');
            var nonce = $container.data('nonce');
            var reactionType = $btn.data('type');

            // Prevent double-clicks
            if ($btn.hasClass('loading')) {
                return;
            }

            // Add loading state
            $btn.addClass('loading');
            $container.addClass('loading');

            // Send AJAX request
            $.ajax({
                url: wasmoReactions.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wasmo_toggle_reaction',
                    nonce: nonce,
                    profile_user_id: profileUserId,
                    section: section,
                    reaction_type: reactionType
                },
                success: function (response) {
                    if (response.success) {
                        updateReactionUI($container, response.data);
                    } else {
                        showError(response.data.message || 'An error occurred');
                    }
                },
                error: function () {
                    showError('Failed to save reaction. Please try again.');
                },
                complete: function () {
                    $btn.removeClass('loading');
                    $container.removeClass('loading');
                }
            });
        });

        // Handle touch devices - toggle picker on trigger click
        $(document).on('click', '.reaction-trigger:not(:disabled)', function (e) {
            var $wrapper = $(this).closest('.reaction-picker-wrapper');

            // On touch devices, toggle the picker
            if ('ontouchstart' in window) {
                e.preventDefault();
                $wrapper.toggleClass('picker-open');
            }
        });

        // Close picker when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.reaction-picker-wrapper').length) {
                $('.reaction-picker-wrapper').removeClass('picker-open');
            }
        });

        // Handle "See who reacted" toggle
        $(document).on('click', '.reaction-details-toggle', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var $container = $btn.closest('.profile-reactions');
            var $details = $container.find('.reaction-details');

            $details.slideToggle(200);
            $btn.toggleClass('expanded');
        });
    }

    /**
     * Update reaction UI after AJAX response
     * 
     * @param {jQuery} $container The reaction container
     * @param {Object} data Response data
     */
    function updateReactionUI($container, data) {
        // Update active state on reaction buttons
        $container.find('.reaction-btn').each(function () {
            var $btn = $(this);
            var type = $btn.data('type');
            var isActive = (data.user_reaction === type);
            $btn.toggleClass('active', isActive);
        });

        // Update trigger button
        var $trigger = $container.find('.reaction-trigger');
        if (data.user_reaction && reactionTypes[data.user_reaction]) {
            var typeInfo = reactionTypes[data.user_reaction];
            $trigger.addClass('has-reaction');
            $trigger.find('.reaction-trigger-icon').html(typeInfo.iconHtml);
            $trigger.find('.reaction-trigger-label').text(typeInfo.label);
        } else {
            $trigger.removeClass('has-reaction');
            // Reset to heart icon
            if (reactionTypes['heart']) {
                $trigger.find('.reaction-trigger-icon').html(reactionTypes['heart'].iconHtml);
            }
            $trigger.find('.reaction-trigger-label').text('React');
        }

        // Update reaction summary
        updateReactionSummary($container, data);

        // Update details panel
        updateReactionDetails($container, data.reactions);

        // Close picker after selection
        $container.find('.reaction-picker-wrapper').removeClass('picker-open');
    }

    /**
     * Update the reaction summary (icons + count)
     * 
     * @param {jQuery} $container The reaction container
     * @param {Object} data Response data
     */
    function updateReactionSummary($container, data) {
        var $summary = $container.find('.reaction-summary');
        var totalCount = data.total_count;

        if (totalCount > 0) {
            // Build summary icons HTML
            var iconsHtml = '';
            $.each(data.reactions, function (typeSlug, reactionData) {
                if (reactionData.count > 0 && reactionTypes[typeSlug]) {
                    var label = reactionTypes[typeSlug].label + ': ' + reactionData.count;
                    iconsHtml += '<span class="reaction-summary-icon" title="' + escapeHtml(label) + '">';
                    iconsHtml += reactionTypes[typeSlug].iconHtml;
                    iconsHtml += '</span>';
                }
            });

            if ($summary.length) {
                $summary.find('.reaction-summary-icons').html(iconsHtml);
                $summary.find('.reaction-details-toggle').text(totalCount);
            } else {
                // Create summary if it doesn't exist
                var summaryHtml = '<div class="reaction-summary">';
                summaryHtml += '<span class="reaction-summary-icons">' + iconsHtml + '</span>';
                summaryHtml += '<button type="button" class="reaction-details-toggle">' + totalCount + '</button>';
                summaryHtml += '</div>';
                $container.find('.reaction-picker-wrapper').after(summaryHtml);
            }
        } else {
            $summary.remove();
            $container.find('.reaction-details').remove();
        }
    }

    /**
     * Update the reaction details panel
     * 
     * @param {jQuery} $container The reaction container
     * @param {Object} reactions Reactions data by type
     */
    function updateReactionDetails($container, reactions) {
        var $details = $container.find('.reaction-details');
        var hasReactions = false;
        var html = '';

        // Build details HTML
        $.each(reactions, function (typeSlug, data) {
            if (data.users && data.users.length > 0) {
                hasReactions = true;

                var iconHtml = reactionTypes[typeSlug] ? reactionTypes[typeSlug].iconHtml : '';
                var label = reactionTypes[typeSlug] ? reactionTypes[typeSlug].label : typeSlug;

                html += '<div class="reaction-detail-group">';
                html += '<span class="reaction-detail-icon">' + iconHtml + '</span>';
                html += '<span class="reaction-detail-label">' + escapeHtml(label) + '</span>';
                html += '<span class="reaction-detail-users">';

                $.each(data.users, function (i, user) {
                    html += '<a href="' + escapeHtml(user.profile_url) + '" class="reaction-user-avatar" title="' + escapeHtml(user.display_name) + '">';
                    html += '<img src="' + escapeHtml(user.avatar_url) + '" alt="' + escapeHtml(user.display_name) + '" loading="lazy" />';
                    html += '</a>';
                });

                html += '</span>';
                html += '</div>';
            }
        });

        if (hasReactions) {
            if ($details.length) {
                $details.html(html);
            } else {
                $container.find('.reaction-summary').after('<div class="reaction-details" style="display: none;">' + html + '</div>');
            }
        } else {
            $details.remove();
        }
    }

    /**
     * Show error message
     * 
     * @param {string} message Error message
     */
    function showError(message) {
        console.error('Reaction error:', message);
        alert(message);
    }

    /**
     * Escape HTML entities
     * 
     * @param {string} text Text to escape
     * @return {string} Escaped text
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize on document ready
    $(document).ready(function () {
        initReactionButtons();
    });

})(jQuery);
