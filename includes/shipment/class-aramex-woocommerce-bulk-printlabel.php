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
include_once __DIR__ . '../../core/class-mo-aramex-helper.php';


$plugin_dir = MO_ARAMEX_PLUGIN_DIR;
require_once $plugin_dir . '/vendor/autoload.php';


/**
 * Controller for Printing label
 */
class Aramex_Bulk_Printlabel_Method extends MO_Aramex_Helper
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

        if(isset($post['bulk'])){
            $output = array();
            $selected_orders = $post['selected_orders'];
            
            $bulk_pdf = array();
            $ordersIds = explode(",", $selected_orders);

            $upload_dir = wp_upload_dir();
            $print_label_dirname = $upload_dir['basedir'] . '/print-label';
            $print_label_uploads_url = $upload_dir['baseurl'] . '/print-label';

            $pdfData = $post['pdfData'];
            $success_pdf_ID = array();
            $failed_pdf_ID = array();
            if(empty($pdfData)){
                foreach ($ordersIds as $id) {
                    $order_id = (int)$id;
                    
                    if ($order_id) {
                        // First, try to get the AWB number from order meta (new method)
                        $last_track = get_post_meta($order_id, 'aramex_awb_no', true);
                        
                        // If not found in meta, fall back to parsing comments (legacy method)
                        if (empty($last_track)) {
                            global $wpdb;
                            $comments_table = $wpdb->prefix . 'comments';
                            
                            $query = $wpdb->prepare(
                                "SELECT comment_content
                                FROM $comments_table
                                WHERE comment_post_ID = %d
                                AND comment_type = 'order_note'
                                ORDER BY comment_ID DESC",
                                $order_id
                            );
                            
                            $history = $wpdb->get_results($query);
                           
                            $history_list = array();
                            foreach ($history as $shipment) {
                                $history_list[] = $shipment->comment_content;
                            }
                            
                            if (count($history_list)) {
                                foreach ($history_list as $history) {
                                    $awbno = strstr($history, "- Order No", true);
                                    $awbno = trim($awbno, "AWB No.");
                                    $awbno = trim($awbno);
                                    if (isset($awbno) && is_numeric($awbno) && $awbno > 0) {
                                        $last_track = $awbno;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        if (!empty($last_track)) {
                            // Use REST/JSON API instead of SOAP
                            $rest_base = MO_Aramex_Helper::getRestJsonBaseUrl();
                            $endpoint = rtrim($rest_base, '/') . '/PrintLabel';
                            
                            $report_id = $info['clientInfo']['report_id'];
                            if (!$report_id || !is_numeric($report_id)) {
                                $report_id = 9729;
                            }
                            
                            try {
                                // Prepare REST payload
                                $rest_payload = array(
                                    'ClientInfo' => MO_Aramex_Helper::getRestClientInfo(),
                                    'Transaction' => array(
                                        'Reference1' => (string)$order_id,
                                        'Reference2' => '',
                                        'Reference3' => '',
                                        'Reference4' => '',
                                        'Reference5' => '',
                                    ),
                                    'LabelInfo' => array(
                                        'ReportID' => (int)$report_id,
                                        'ReportType' => 'URL',
                                    ),
                                    'ShipmentNumber' => (string)$last_track,
                                );
                                
                                // Log API call
                                $start_time = microtime(true);
                                mo_aramex_log_api_call(
                                    'PrintLabel (Bulk)',
                                    $rest_payload,
                                    'REST',
                                    array('endpoint' => $endpoint, 'order_id' => $order_id, 'awb' => $last_track)
                                );
                                
                                // Make REST API call
                                $response = wp_remote_post($endpoint, array(
                                    'method' => 'POST',
                                    'timeout' => 60,
                                    'headers' => array(
                                        'Content-Type' => 'application/json',
                                        'Accept' => 'application/json',
                                    ),
                                    'body' => wp_json_encode($rest_payload),
                                ));
                                
                                $execution_time = microtime(true) - $start_time;
                                
                                if (is_wp_error($response)) {
                                    throw new Exception($response->get_error_message());
                                }
                                
                                $status = wp_remote_retrieve_response_code($response);
                                $body = wp_remote_retrieve_body($response);
                                $auth_call = json_decode($body);
                                
                                // Log API response
                                mo_aramex_log_api_response(
                                    'PrintLabel (Bulk)',
                                    $auth_call,
                                    $status,
                                    array(),
                                    $execution_time
                                );
                                      
                                /* bof  PDF demaged Fixes debug */

                                if (isset($auth_call->HasErrors) && $auth_call->HasErrors) {
                                    custom_plugin_log('PrintLabel has errors for order ' . $order_id);
                                    array_push($failed_pdf_ID,$order_id);
                                    continue;
                                }
                                /* eof  PDF demaged Fixes */
                                
                                $filepath = $auth_call->ShipmentLabel->LabelURL ?? '';
                                
                                if (empty($filepath)) {
                                    custom_plugin_log('No label URL in PrintLabel response for order ' . $order_id);
                                    array_push($failed_pdf_ID,$order_id);
                                    continue;
                                }
                                
                                custom_plugin_log('Downloading label from: ' . $filepath . ' for order ' . $order_id);

                                if (!file_exists($print_label_dirname)) {
                                    mkdir($print_label_dirname, 0777, true);
                                }

                                $time = time();
                                $pdf_filename_by_order_id = $print_label_dirname . "/".$order_id . "_" .$time.".pdf";
                          
                                // Download PDF from Aramex URL using wp_remote_get
                                $pdf_response = wp_remote_get($filepath, array(
                                    'timeout' => 60,
                                    'sslverify' => false
                                ));
                                
                                if (is_wp_error($pdf_response)) {
                                    custom_plugin_log('Failed to download PDF for order ' . $order_id . ': ' . $pdf_response->get_error_message());
                                    array_push($failed_pdf_ID,$order_id);
                                    continue;
                                }
                                
                                $pdf_content = wp_remote_retrieve_body($pdf_response);
                                
                                if (empty($pdf_content)) {
                                    custom_plugin_log('Empty PDF content for order ' . $order_id);
                                    array_push($failed_pdf_ID,$order_id);
                                    continue;
                                }
                                
                                file_put_contents($pdf_filename_by_order_id, $pdf_content);
                                
                                if (!file_exists($pdf_filename_by_order_id)) {
                                    custom_plugin_log('Failed to save PDF file for order ' . $order_id);
                                    array_push($failed_pdf_ID,$order_id);
                                    continue;
                                }
                                
                                custom_plugin_log('Successfully saved PDF for order ' . $order_id . ' at ' . $pdf_filename_by_order_id);
                                
                                array_push($bulk_pdf, $pdf_filename_by_order_id);
                                array_push($success_pdf_ID,$order_id);
                                
                            } catch (Exception $e) {
                                custom_plugin_log('PrintLabel error for order ' . $order_id . ': ' . $e->getMessage());
                                mo_aramex_log_api_error('PrintLabel (Bulk)', $e->getMessage(), 0, ['order_id' => $order_id, 'awb' => $last_track]);
                                array_push($failed_pdf_ID,$order_id);
                            }
                        } else {
                            custom_plugin_log('No AWB number found for order ' . $order_id);
                            array_push($failed_pdf_ID,$order_id);
                        }

                    } else {
                        $this->aramex_errors()->add('error', 'This order no longer exists.');
                        $_SESSION['aramex_errors_printlabel'] = $this->aramex_errors();
                        wp_redirect(sanitize_text_field(esc_url_raw($_POST['aramex_shipment_referer'])) . '&aramexpopup/show_printlabel');
                        exit();
                    }
                }

                // create merger instance
                $merge_pdf = '';
                $merge_pdf_path = '';
                
                if(!empty($bulk_pdf)){
                    try {
                        custom_plugin_log('Starting PDF merge for ' . count($bulk_pdf) . ' PDFs');
                        
                        $pdf = new \Jurosh\PDFMerge\PDFMerger;
                        foreach ($bulk_pdf as $item) {
                            if (file_exists($item)) {
                                custom_plugin_log('Adding PDF to merger: ' . $item);
                                $pdf->addPDF($item, 'all', 'vertical');
                            } else {
                                custom_plugin_log('PDF file not found for merging: ' . $item);
                            }
                        }
                        
                        $time = time();
                        $pdf_name = $time.'.pdf';
                        $merge_pdf_path = $print_label_dirname.'/'.$pdf_name;
                        $merge_pdf = $print_label_uploads_url.'/'.$pdf_name;
                        
                        custom_plugin_log('Merging PDFs to: ' . $merge_pdf_path);
                        $pdf->merge('file', $merge_pdf_path);
                        
                        if (file_exists($merge_pdf_path)) {
                            custom_plugin_log('Merged PDF created successfully: ' . $merge_pdf_path . ' (Size: ' . filesize($merge_pdf_path) . ' bytes)');
                        } else {
                            custom_plugin_log('Merged PDF file was not created: ' . $merge_pdf_path);
                        }
                        
                    } catch (Exception $e) {
                        custom_plugin_log('PDF merge error: ' . $e->getMessage());
                        mo_aramex_log_api_error('PrintLabel (Bulk) - PDF Merge', $e->getMessage(), 0, ['pdf_count' => count($bulk_pdf)]);
                    }
                    
                    // Clean up individual PDFs
                    foreach ($bulk_pdf as $item) {
                        if (file_exists($item)) {
                            unlink($item);
                        }
                    }
                }

                $output = array(
                  "file_url" => $merge_pdf,
                  "file_path" => $merge_pdf_path,
                  "success_id"=> $success_pdf_ID,
                  "failed_id"=> $failed_pdf_ID,
                  "sucess" => false
                );
            }else{
                unlink($pdfData);
                
                $output = array(
                    "file_url" => '',
                    "file_path" => '',
                    "success_id"=> $success_pdf_ID,
                    "failed_id"=> $failed_pdf_ID,
                    "sucess" => true
                );
            }

            echo json_encode($output);
        }
        
       die();
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