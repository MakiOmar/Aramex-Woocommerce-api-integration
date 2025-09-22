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
include_once __DIR__ . '../../core/class-aramex-helper.php';

/**
 * Controller for Printing label
 */
class Aramex_Printlabel_Method extends Aramex_Helper
{

    /**
     * Starting method
     *
     * @return mixed|string|void
     */
    public function run()
    {
        check_admin_referer('aramex-shipment-check' . wp_get_current_user()->user_email);
        $info = $this->getInfo(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email));
        $post = $this->formatPost($_POST);

        if (!session_id()) {
            session_start();
        }
        if ($post['aramex_printlabel']) {
            //SOAP object
            $soapClient = new SoapClient($info['baseUrl'] . 'shipping.wsdl', array('soap_version' => SOAP_1_1));
            $awbno = array();

            if ($post['aramex_lasttrack']) {
                $report_id = $info['clientInfo']['report_id'];
                if (!$report_id) {
                    $report_id = 9729;
                }
                $params = array(
                    'ClientInfo' => $info['clientInfo'],
                    'Transaction' => array(
                        'Reference1' => $post['aramex_printlabel'],
                        'Reference2' => '',
                        'Reference3' => '',
                        'Reference4' => '',
                        'Reference5' => '',
                    ),
                    'LabelInfo' => array(
                        'ReportID' => $report_id,
                        'ReportType' => 'URL',
                    ),
                );
                $params['ShipmentNumber'] = $post['aramex_lasttrack'];
                
                try {
                    $auth_call = $soapClient->PrintLabel($params);
                          
                    /* bof  PDF demaged Fixes debug */
                    if ($auth_call->HasErrors) {
                        if (count($auth_call->Notifications->Notification) > 1) {
                            foreach ($auth_call->Notifications->Notification as $notify_error) {
                                $error = "";
                                $error .= 'Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message;
                                aramex_errors()->add('error', $error);
                            }
                        } else {
                            aramex_errors()->add('error',
                                'Aramex: ' . $auth_call->Notifications->Notification->Code . ' - ' . $auth_call->Notifications->Notification->Message);
                        }
                        $_SESSION['aramex_errors'] = aramex_errors();
                        wp_redirect(sanitize_text_field(esc_url_raw($_POST['aramex_shipment_referer'])) . '&aramexpopup/show_printlabel');
                        exit();
                    }
                    /* eof  PDF demaged Fixes */
                    $filepath = $auth_call->ShipmentLabel->LabelURL;
                    echo($filepath);
                    exit();
                } catch (SoapFault $fault) {
                    $this->aramex_errors()->add('error', $fault->faultstring);
                    $_SESSION['aramex_errors_printlabel'] = $this->aramex_errors();
                    wp_redirect(sanitize_text_field(esc_url_raw($_POST['aramex_shipment_referer'])) . '&aramexpopup/show_printlabel');
                    exit();
                } catch (Exception $e) {
                    $this->aramex_errors()->add('error', $e->getMessage());
                    $_SESSION['aramex_errors_printlabel'] = $this->aramex_errors();
                    wp_redirect(sanitize_text_field(esc_url_raw($_POST['aramex_shipment_referer'])) . '&aramexpopup/show_printlabel');
                    exit();
                }
            } else {
                $this->aramex_errors()->add('error', 'Shipment is empty or not created yet.');
                $_SESSION['aramex_errors_printlabel'] = $this->aramex_errors();
                wp_redirect(sanitize_text_field(esc_url_raw($_POST['aramex_shipment_referer'])) . '&aramexpopup/show_printlabel');
                exit();
            }
        } else {
            $this->aramex_errors()->add('error', 'This order no longer exists.');
            $_SESSION['aramex_errors_printlabel'] = $this->aramex_errors();
            wp_redirect(sanitize_text_field(esc_url_raw($_POST['aramex_shipment_referer'])) . '&aramexpopup/show_printlabel');
            exit();
        }
    }

    /**
     * Get errors
     *
     * @return WP_Error  WP Errors
     */
    public function aramex_errors()
    {
        static $wp_error; // Will hold global variable safely
        return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
    }
}