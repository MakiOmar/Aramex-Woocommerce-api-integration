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
         *  Render "Print label" form
         *
         * @param $order object Order object
         * @return string Template
         */
function aramex_display_printlabel_in_admin($order)
{
    $get_userdata = get_userdata(get_current_user_id());
    if (!$get_userdata->allcaps['edit_shop_order'] || !$get_userdata->allcaps['read_shop_order'] || !$get_userdata->allcaps['edit_shop_orders'] || !$get_userdata->allcaps['edit_others_shop_orders']
        || !$get_userdata->allcaps['publish_shop_orders'] || !$get_userdata->allcaps['read_private_shop_orders']
        || !$get_userdata->allcaps['edit_private_shop_orders'] || !$get_userdata->allcaps['edit_published_shop_orders']) {
        return false;
    }
    $order_id = $order->get_id();
    $currentUrl = home_url(add_query_arg(null, null));
    $history = get_comments(array(
        'post_id' => $order_id,
        'orderby' => 'comment_ID',
        'order' => 'DESC',
        'approve' => 'approve',
        'type' => 'order_note',
    ));

    $history_list = array();
    if (count($history) > 0) {
        foreach ($history as $shipment) {
            $history_list[] = $shipment->comment_content;
        }
    }
    $last_track = "";
    if (count($history_list)) {
        foreach ($history_list as $history) {
            $awbno = strstr($history, "- Order No", true);
            $awbno = trim($awbno, "AWB No.");
            if (isset($awbno)) {
                if ((int)$awbno) {
                    $last_track = $awbno;
                    break;
                }
            }
            $awbno = trim($awbno, "Aramex Shipment Return Order AWB No.");
            if (isset($awbno)) {
                if ((int)$awbno) {
                    $last_track = $awbno;
                    break;
                }
            }
        }
    } ?>
    <?php
    if (isset($_SESSION['aramex_errors_printlabel'])) {
        unset($_SESSION['aramex_errors_printlabel']);
    } ?>
    <div id="printlabel_overlay" style="display:none;">
        <form method="post"
              action=" <?php echo esc_url(plugins_url() . '/aramex-shipping-woocommerce/includes/shipment/class-aramex-woocommerce-printlabel.php'); ?>"
              id="printlabel-form">
            <input name="_wpnonce" id="aramex-shipment-nonce" type="hidden"
                   value="<?php echo esc_attr(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email)); ?>"/>
            <input type="hidden" name="aramex_shipment_referer" value="<?php echo esc_attr(esc_url($currentUrl)); ?>"/>
            <input name="aramex-printlabel" id="aramex-printlabel-field" type="hidden"
                   value="<?php echo esc_attr($order_id); ?>"/>
            <input name="aramex-lasttrack" id="aramex-lasttrack-field" type="hidden"
                   value="<?php echo esc_attr($last_track); ?>"/>

        </form>
    </div>
<?php 
}
