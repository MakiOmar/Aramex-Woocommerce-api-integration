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
*  Render "Tracking template" under account
*
* @param $order_id string order id
* @return string Template
*/

function aramex_view_order_tracking($order_id)
{
    ?>
    <h2>Aramex order tracking</h2>
    <table class="woocommerce-table shop_table aramex_info">
        <tbody>
            <tr>
                <th>Aramex AWB No.</th>
                <td><?php 
                  global $wpdb;
				    $table_perfixed = $wpdb->prefix . 'comments';
				    $history = $wpdb->get_results("
								        SELECT *
								        FROM $table_perfixed
								        WHERE  `comment_post_ID` = $order_id
								        AND  `comment_type` LIKE  'order_note'
								    ");
				    $history_list = array();
				    foreach ($history as $shipment) {
				        $history_list[] = $shipment->comment_content;
				    }
				    $last_track = "";
				    if (!empty($history_list)) {
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
				    }
    echo (!empty($last_track)) ? "<a target='_blank' href='https://www.aramex.com/track/results?ShipmentNumber=". esc_attr($last_track) . "'>". esc_attr($last_track) . "</a>"   : 'Not created'; ?>
    </td>
            </tr>
        </tbody>
    </table>
<?php 
}
