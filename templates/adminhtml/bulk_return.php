<?php
/**
 * Render "Bulk Return Shipment" form
 *
 * @return string Template
 */
function aramex_display_bulk_return_in_admin()
{
    $get_userdata = get_userdata(get_current_user_id());
    if (!$get_userdata->allcaps['edit_shop_order'] || !$get_userdata->allcaps['read_shop_order'] || !$get_userdata->allcaps['edit_shop_orders'] || !$get_userdata->allcaps['edit_others_shop_orders'] || !$get_userdata->allcaps['publish_shop_orders'] || !$get_userdata->allcaps['read_private_shop_orders'] || !$get_userdata->allcaps['edit_private_shop_orders'] || !$get_userdata->allcaps['edit_published_shop_orders']) {
        return false;
    }
    ?>

    <script type="text/javascript">
        jQuery.noConflict();
        (function ($) {
            $(document).ready(function () {
                // Bulk Return Shipment button is in the dropdown created by bulk.php
                $(document).on("click", "#bulk_return_shipment", function () {
                    aramexReturnShipment();
                });
            });

            function aramexReturnShipment() {
                var selected = [];
                $('.type-shop_order input:checked').each(function () {
                    selected.push($(this).val());
                });

                if (selected.length === 0) {
                    alert("<?php echo esc_html__('Please select orders', 'aramex'); ?>");
                    return;
                }

                // Use smart defaults - no prompts needed
                var pickupLocation = "Home"; // Default pickup location
                var numberOfPieces = 1; // One piece per order
                var pickupComments = "Return shipment for order(s): " + selected.join(', '); // Auto-generated comment

                // Show loader
                if ($('.aramex_return_loader').length === 0) {
                    $('body').append('<div class="aramex_return_loader" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 99999; justify-content: center; align-items: center;"><div style="text-align: center; color: white;"><img src="<?php echo esc_url(plugins_url('../../assets/img/aramex_loader.gif', __FILE__)); ?>" alt="Loading..." style="width: 100px; height: 100px; margin-bottom: 20px;"><p style="font-size: 18px; font-weight: bold;"><?php echo esc_html__('Creating return pickup, please wait...', 'aramex'); ?></p></div></div>');
                }
                $('.aramex_return_loader').css('display', 'flex');

                var order_ids = selected.join(", ");
                var postData = {
                    action: 'the_aramex_bulk_return',
                    selected_orders: order_ids,
                    pickup_location: pickupLocation,
                    number_of_pieces: numberOfPieces,
                    pickup_comments: pickupComments,
                    _wpnonce: "<?php echo esc_attr(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email)); ?>"
                };

                jQuery.post(ajaxurl, postData, function (request) {
                    try {
                        console.log("Return shipment raw response:", request);

                        var response = JSON.parse(request);
                        console.log("Return shipment parsed response:", response);

                        $('.aramex_return_loader').hide();

                        if (response.error) {
                            alert("<?php echo esc_html__('Error:', 'aramex'); ?> " + response.error);
                            return;
                        }

                        var message = "<?php echo esc_html__('Return Shipment Results:', 'aramex'); ?>\n\n";
                        
                        if (response.messages && response.messages.length > 0) {
                            message += response.messages.join('\n');
                        }

                        if (response.success_ids && response.success_ids.length > 0) {
                            message += "\n\n<?php echo esc_html__('Success IDs:', 'aramex'); ?> " + response.success_ids.join(', ');
                        }

                        if (response.failed_ids && response.failed_ids.length > 0) {
                            message += "\n\n<?php echo esc_html__('Failed IDs:', 'aramex'); ?> " + response.failed_ids.join(', ');
                        }

                        alert(message);

                        // Reload page if at least one was successful
                        if (response.success_ids && response.success_ids.length > 0) {
                            window.location.reload();
                        }

                    } catch (e) {
                        console.error("Error parsing return shipment response:", e);
                        $('.aramex_return_loader').hide();
                        alert("<?php echo esc_html__('Error processing response. Check console for details.', 'aramex'); ?>");
                    }
                }).fail(function (xhr, status, error) {
                    console.error("Return shipment AJAX failed:", error);
                    console.error("Status:", status);
                    console.error("Response:", xhr.responseText);
                    $('.aramex_return_loader').hide();
                    alert("<?php echo esc_html__('Error processing return shipment request:', 'aramex'); ?> " + error);
                });
            }
        })(jQuery);
    </script>
<?php
}
?>

