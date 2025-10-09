/**
 * MO Aramex AWB Manager
 * Handles manual AWB number management
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('AWB Manager: JavaScript loaded and ready');
        
        /**
         * Handle AWB save button click
         */
        $(document).on('click', '.mo-aramex-save-awb', function(e) {
            console.log('AWB Manager: Save button clicked');
            e.preventDefault();
            
            var $btn = $(this);
            var $container = $btn.closest('.mo-aramex-awb-editor');
            var $input = $container.find('.mo-aramex-awb-input');
            var $message = $container.find('.mo-aramex-awb-message');
            var orderId = $container.data('order-id');
            var awbNumber = $input.val().trim();
            
            console.log('AWB Manager: Data collected', {
                order_id: orderId,
                awb_number: awbNumber
            });
            
            // Validate AWB number
            if (!awbNumber) {
                showMessage($message, moAramexAwb.i18n.error, 'error');
                return;
            }
            
            // Validate that AWB contains only digits
            if (!/^\d+$/.test(awbNumber)) {
                showMessage($message, 'AWB number should contain only digits', 'error');
                return;
            }
            
            // Disable button and show loading state
            $btn.prop('disabled', true).text(moAramexAwb.i18n.saving);
            
            // Debug logging
            console.log('AWB Manager: Saving AWB', {
                order_id: orderId,
                awb_number: awbNumber,
                ajax_url: moAramexAwb.ajax_url
            });
            
            // Send AJAX request
            $.ajax({
                url: moAramexAwb.ajax_url,
                type: 'POST',
                data: {
                    action: 'mo_aramex_save_awb',
                    nonce: moAramexAwb.nonce,
                    order_id: orderId,
                    awb_number: awbNumber
                },
                success: function(response) {
                    console.log('AWB Manager: Save response', response);
                    
                    if (response.success) {
                        showMessage($message, response.data.message, 'success');
                        
                        // Update the meta box display
                        updateMetaBoxDisplay(orderId, response.data.awb_number, response.data.track_url);
                        
                        // Show delete button if hidden
                        if ($container.find('.mo-aramex-delete-awb').length === 0) {
                            var deleteBtn = $('<button type="button" class="button button-secondary mo-aramex-delete-awb">' +
                                '<span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: 3px;"></span> ' +
                                moAramexAwb.i18n.deleted.replace(' successfully!', '') +
                                '</button>');
                            $container.find('.mo-aramex-awb-actions').append(deleteBtn);
                        }
                        
                        // Reload page after 1.5 seconds to refresh all displays
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage($message, response.data.message || moAramexAwb.i18n.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AWB Manager: AJAX error', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    showMessage($message, moAramexAwb.i18n.error, 'error');
                },
                complete: function() {
                    // Re-enable button
                    $btn.prop('disabled', false).html(
                        '<span class="dashicons dashicons-yes" style="vertical-align: middle; margin-top: 3px;"></span> Save AWB'
                    );
                }
            });
        });
        
        /**
         * Handle AWB deletion
         */
        $(document).on('click', '.mo-aramex-delete-awb', function(e) {
            e.preventDefault();
            
            if (!confirm(moAramexAwb.i18n.confirm_delete)) {
                return;
            }
            
            var $btn = $(this);
            var $container = $btn.closest('.mo-aramex-awb-editor');
            var $message = $container.find('.mo-aramex-awb-message');
            var $input = $container.find('.mo-aramex-awb-input');
            var orderId = $container.data('order-id');
            
            // Disable button and show loading state
            $btn.prop('disabled', true).text(moAramexAwb.i18n.deleting);
            
            // Send AJAX request
            $.ajax({
                url: moAramexAwb.ajax_url,
                type: 'POST',
                data: {
                    action: 'mo_aramex_delete_awb',
                    nonce: moAramexAwb.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        showMessage($message, response.data.message, 'success');
                        
                        // Clear input
                        $input.val('');
                        
                        // Hide delete button
                        $btn.remove();
                        
                        // Reload page after 1.5 seconds to refresh all displays
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage($message, response.data.message || moAramexAwb.i18n.error, 'error');
                        $btn.prop('disabled', false).html(
                            '<span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: 3px;"></span> Delete AWB'
                        );
                    }
                },
                error: function() {
                    showMessage($message, moAramexAwb.i18n.error, 'error');
                    $btn.prop('disabled', false).html(
                        '<span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: 3px;"></span> Delete AWB'
                    );
                }
            });
        });
        
        /**
         * Show message
         */
        function showMessage($element, message, type) {
            $element
                .removeClass('success error')
                .addClass(type)
                .text(message)
                .slideDown()
                .delay(5000)
                .slideUp();
        }
        
        /**
         * Update meta box display
         */
        function updateMetaBoxDisplay(orderId, awbNumber, trackUrl) {
            // This will be handled by page reload for consistency
            // but we can add immediate visual feedback if needed
        }
    });
    
})(jQuery);

