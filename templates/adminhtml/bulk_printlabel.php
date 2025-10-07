<?php
/*
Plugin Name:  Aramex Shipping WooCommerce
Plugin URI:   https://aramex.com
Description:  Aramex Shipping WooCommerce plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  aramex
Domain Path:  /languages
*/
?>
<?php
        /**
         *  Render "Bulk" form
         *
         * @return string Template
         */
function aramex_display_bulk_printlabel_in_admin()
{
    $get_userdata = get_userdata(get_current_user_id());
    if (!$get_userdata->allcaps['edit_shop_order'] || !$get_userdata->allcaps['read_shop_order'] || !$get_userdata->allcaps['edit_shop_orders'] || !$get_userdata->allcaps['edit_others_shop_orders']
        || !$get_userdata->allcaps['publish_shop_orders'] || !$get_userdata->allcaps['read_private_shop_orders']
        || !$get_userdata->allcaps['edit_private_shop_orders'] || !$get_userdata->allcaps['edit_published_shop_orders']) {
        return false;
    } ?>
    

        </div>
    </div>
    <script type="text/javascript">
        jQuery.noConflict();
        (function ($) {
            $(document).ready(function () {
                // Bulk Print Label button is now in the dropdown created by bulk.php
                // Just bind the click handler using event delegation
                $(document).on("click", "#bulk_print_label", function () {
                    aramexsend_print();
                });

                // $("#aramex_shipment_creation_submit_id").click(function () {
                //     aramexsend();
                // });

                
            });

            function aramexredirect() {
                window.location.reload(true);
            }

            function aramexsend_print(pdfData) {
                var selected = [];
                var str = $("#massform").serialize();
                $('.type-shop_order input:checked').each(function () {
                    selected.push($(this).val());
                });
                if (selected.length === 0) {
                    alert("<?php echo esc_html__('Please select orders', 'aramex'); ?>");
                    return;
                }
                
                // Show loading indicator only on first call (not when deleting temp PDF)
                if (!pdfData) {
                    if ($('.aramex_print_loader').length === 0) {
                        $('body').append('<div class="aramex_print_loader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center;"><div style="background: white; padding: 40px; border-radius: 8px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3);"><img src="<?php echo esc_url(MO_ARAMEX_PLUGIN_URL . 'assets/img/aramex_loader.gif'); ?>" alt="Loading..." style="width: 80px; height: 80px;" /><p style="margin-top: 20px; font-size: 18px; font-weight: bold; color: #333;">Printing labels...</p><p style="margin-top: 10px; font-size: 14px; color: #666;">Please wait while we generate your shipping labels</p></div></div>');
                    }
                    $('.aramex_print_loader').show();
                }
                
                // var _wpnonce = "<?php echo esc_js(wp_create_nonce('aramex-shipment-nonce' . wp_get_current_user()->user_email)); ?>";

                <!-- alert("Selected say(s) are: " + selected.join(", ")); -->
                var order_ids = selected.join(", ");

                var postData = {
        			action: 'the_aramex_bulk_printlabel',
        			bulk: "bulk_printlabel",
                    pdfData: pdfData,
                    selected_orders : order_ids,
                    _wpnonce :  "<?php echo esc_attr(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email)); ?>"
        		};

                jQuery.post(ajaxurl, postData, function(request) {

                        var responce = JSON.parse(request);
                        var pdfData = responce.file_path;
                        var fileUrl = responce.file_url;

                        success_id = responce.success_id;
                        failed_id = responce.failed_id;

                        if(success_id.length !== 0 && failed_id.length !== 0){
                            alert("Success Id's: "+responce.success_id + " Failed Id's: "+responce.failed_id);
                        }else if(success_id.length !== 0 && failed_id.length == 0){
                            alert("Success Id's: "+responce.success_id);
                        }else if(success_id.length == 0 && failed_id.length !== 0){
                            alert("Failed Id's: "+responce.failed_id);
                        }

                        if(pdfData !== '' && fileUrl !== ''){
                            // Loader will disappear when page redirects to PDF
                            window.location.href = fileUrl;
                            
                            <!-- Repeate function call for delete generated pdf -->
                            aramexsend_print(pdfData);
                        }else{
                            $('.aramex_print_loader').hide();
                            if(failed_id.length !== 0){
                                console.log("Print label failed for orders: " + failed_id);
                            }
                        }
                        
                });
            }
        })(jQuery);
    </script>
<?php 
} ?>