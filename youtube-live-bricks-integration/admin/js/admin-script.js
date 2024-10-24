jQuery(document).ready(function($) {
    const statusDisplay = $('#ylbi-status-display');
    const checkButton = $('#ylbi-check-now');

    checkButton.on('click', function() {
        // Prevent multiple clicks
        if (checkButton.hasClass('checking')) {
            return;
        }

        // Add loading state
        checkButton.addClass('checking');
        statusDisplay.addClass('checking');

        // Make AJAX call
        $.ajax({
            url: ylbiAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ylbi_check_status',
                nonce: ylbiAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update status indicator
                    const statusDot = statusDisplay.find('.status-dot');
                    const statusText = statusDot.parent();
                    
                    statusDot.removeClass('live offline')
                            .addClass(response.data.is_live ? 'live' : 'offline');
                    
                    statusText.html(
                        `<span class="status-dot ${response.data.is_live ? 'live' : 'offline'}"></span>
                        ${response.data.message}`
                    );

                    // Update last check time
                    statusDisplay.find('.last-check').text(`Last checked: ${response.data.last_check}`);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Failed to check status. Please try again.');
            },
            complete: function() {
                // Remove loading state
                checkButton.removeClass('checking');
                statusDisplay.removeClass('checking');
            }
        });
    });
});
