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
 * Controller for Shipment functionality
 */
class Aramex_Shipment_Method extends Aramex_Helper
{
    /**
     * Starting method
     *
     * @return mixed|string
     */
    public function run()
    {
        check_admin_referer('aramex-shipment-check' . wp_get_current_user()->user_email);
        $post = $this->formatPost($_POST);

        if ($post['aramex_shipment_shipper_account_show'] == 1) {
            $info = $this->getInfo(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email));
        } else {
            $info = $this->getInfoCod(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email));
        }

        //SOAP object

        $soapClient = new SoapClient($info['baseUrl'] . 'shipping.wsdl',
                array("trace" => true, 'cache_wsdl' => WSDL_CACHE_NONE));

        $aramex_errors = false;
    
        try {
            /* here's your form processing */
            $order = new WC_Order($post['aramex_shipment_original_reference']);
            $items = $order->get_items();
            $descriptionOfGoods = '';
            foreach ($items as $itemvv) {
                $descriptionOfGoods .= $itemvv['product_id'] . ' - ' . trim($itemvv['name'] . ' ');
            }
            $descriptionOfGoods = substr($descriptionOfGoods, 0, 65);
            $aramex_items_counter = 0;
            $totalItems = (trim($post['number_pieces']) == '') ? 1 : (int)$post['number_pieces'];
            $aramex_atachments = array();
            //attachment
            for ($i = 1; $i <= 3; $i++) {
                $fileName = $_FILES['file' . $i]['name'];
                if (isset($fileName) != '') {
                    $fileName = explode('.', $fileName);
                    $fileName = $fileName[0]; //filename without extension
                    $fileData = '';
                    if ($_FILES['file' . $i]['tmp_name'] != '') {
                        $fileData = file_get_contents($_FILES['file' . $i]['tmp_name']);
                    }
                    $ext = pathinfo($_FILES['file' . $i]['name'], PATHINFO_EXTENSION); //file extension
                    if ($fileName && $ext && $fileData) {
                        $aramex_atachments[] = array(
                            'FileName' => $fileName,
                            'FileExtension' => $ext,
                            'FileContents' => $fileData
                        );
                    }
                }
            }


            $totalWeight = $post['order_weight'];
            $params = array();
            
            if ($post['aramex_shipment_shipper_account_show'] == 1) {
                $AccountNumber_1 = ($post['aramex_shipment_info_billing_account'] == 1) ? $post['aramex_shipment_shipper_account'] : $post['aramex_shipment_shipper_account'];
                $AccountPin_1 =  ($post['aramex_shipment_info_billing_account'] == 1) ? $post['aramex_shipment_shipper_account_pin'] : $post['aramex_shipment_shipper_account_pin'];
                $AccountNumber_2 = ($post['aramex_shipment_info_billing_account'] == 2) ? $post['aramex_shipment_shipper_account'] : $post['aramex_shipment_shipper_account'];
                $AccountPin_2 = ($post['aramex_shipment_info_billing_account'] == 2) ? $post['aramex_shipment_shipper_account_pin'] : $post['aramex_shipment_shipper_account_pin'];
                $AccountNumber_3 = $post['aramex_shipment_shipper_account'];
                $AccountPin_3 = $post['aramex_shipment_shipper_account_pin'];
            } else {
                $AccountNumber_1 = ($post['aramex_shipment_info_billing_account'] == 1) ? $post['aramex_shipment_shipper_account_cod'] : $post['aramex_shipment_shipper_account_cod'];
                $AccountPin_1 =  ($post['aramex_shipment_info_billing_account'] == 1) ? $post['aramex_shipment_shipper_account_pin_cod'] : $post['aramex_shipment_shipper_account_pin_cod'];
                $AccountNumber_2 = ($post['aramex_shipment_info_billing_account'] == 2) ? $post['aramex_shipment_shipper_account_cod'] : '';
                $AccountPin_2 = ($post['aramex_shipment_info_billing_account'] == 2) ? $post['aramex_shipment_shipper_account_pin_cod'] : '';
                $AccountNumber_3 = $post['aramex_shipment_shipper_account_cod'];
                $AccountPin_3 = $post['aramex_shipment_shipper_account_pin_cod'];
            }
            
            //shipper parameters
            $params['Shipper'] = array(
                'Reference1' => $post['aramex_shipment_shipper_reference'], //'ref11111',
                'Reference2' => '',
                'AccountNumber' => $AccountNumber_1,
                'AccountPin' => $AccountPin_1,
                //Party Address
                'PartyAddress' => array(
                    'Line1' => addslashes($post['aramex_shipment_shipper_street']), //'13 Mecca St',
                    'Line2' => '',
                    'Line3' => '',
                    'City' => $post['aramex_shipment_shipper_city'], //'Dubai',
                    'StateOrProvinceCode' => $post['aramex_shipment_shipper_state'], //'',
                    'PostCode' => str_replace(" ","",$post['aramex_shipment_shipper_postal']),
                    'CountryCode' => $post['aramex_shipment_shipper_country'], //'AE'
                ),
                //Contact Info
                'Contact' => array(
                    'Department' => '',
                    'PersonName' => $post['aramex_shipment_shipper_name'], //'Suheir',
                    'Title' => '',
                    'CompanyName' => $post['aramex_shipment_shipper_company'], //'Aramex',
                    'PhoneNumber1' => $post['aramex_shipment_shipper_phone'], //'55555555',
                    'PhoneNumber1Ext' => '',
                    'PhoneNumber2' => '',
                    'PhoneNumber2Ext' => '',
                    'FaxNumber' => '',
                    'CellPhone' => $post['aramex_shipment_shipper_phone'],
                    'EmailAddress' => $post['aramex_shipment_shipper_email'], //'',
                    'Type' => ''
                ),
            );
            //consinee parameters
            $params['Consignee'] = array(
                'Reference1' => $post['aramex_shipment_receiver_reference'], //'',
                'Reference2' => '',
                'AccountNumber' => $AccountNumber_2,
                'AccountPin' => $AccountPin_2,
                //Party Address
                'PartyAddress' => array(
                    'Line1' => $post['aramex_shipment_receiver_street'], //'15 ABC St',
                    'Line2' => '',
                    'Line3' => '',
                    'City' => $post['aramex_shipment_receiver_city'], //'Amman',
                    'StateOrProvinceCode' => '',
                    'PostCode' => str_replace(" ","",$post['aramex_shipment_receiver_postal']),
                    'CountryCode' => $post['aramex_shipment_receiver_country'], //'JO'
                ),
                //Contact Info
                'Contact' => array(
                    'Department' => '',
                    'PersonName' => $post['aramex_shipment_receiver_name'], //'Mazen',
                    'Title' => '',
                    'CompanyName' => $post['aramex_shipment_receiver_company'], //'Aramex',
                    'PhoneNumber1' => $post['aramex_shipment_receiver_phone'], //'6666666',
                    'PhoneNumber1Ext' => '',
                    'PhoneNumber2' => '',
                    'PhoneNumber2Ext' => '',
                    'FaxNumber' => '',
                    'CellPhone' => $post['aramex_shipment_receiver_phone'],
                    'EmailAddress' => $post['aramex_shipment_receiver_email'], //'mazen@aramex.com',
                    'Type' => ''
                )
            );
            //new
            if ($post['aramex_shipment_info_billing_account'] == 3) {
                $params['ThirdParty'] = array(
                    'Reference1' => $post['aramex_shipment_shipper_reference'], //'ref11111',
                    'Reference2' => '',
                    'AccountNumber' => $AccountNumber_3,
                    'AccountPin' => $AccountPin_3,
                    //Party Address
                    'PartyAddress' => array(
                        'Line1' => $info['clientInfo']['address'],
                        'Line2' => '',
                        'Line3' => '',
                        'City' => $info['clientInfo']['city'],
                        'StateOrProvinceCode' => $info['clientInfo']['state'],
                        'PostCode' => str_replace(" ","", $info['clientInfo']['postalcode']),
                        'CountryCode' => $info['clientInfo']['country'],
                    ),
                    //Contact Info
                    'Contact' => array(
                        'Department' => '',
                        'PersonName' => $info['clientInfo']['name'],
                        'Title' => '',
                        'CompanyName' => $info['clientInfo']['company'],
                        'PhoneNumber1' => $info['clientInfo']['phone'],
                        'PhoneNumber1Ext' => '',
                        'PhoneNumber2' => '',
                        'PhoneNumber2Ext' => '',
                        'FaxNumber' => '',
                        'CellPhone' => $info['clientInfo']['phone'],
                        'EmailAddress' => $info['clientInfo']['email'],
                        'Type' => ''
                    ),
                );
            }
         
            
            ////// add COD
            $services = array();
            if ($post['aramex_shipment_info_product_type'] == "CDA") {
                if ($post['aramex_shipment_info_service_type'] == null) {
                    array_push($services, "");
                } elseif (!in_array("", $post['aramex_shipment_info_service_type'])) {
                    $services = array_merge($services, $post['aramex_shipment_info_service_type']);
                    array_push($services, "");
                } else {
                    $services = array_merge($services, $post['aramex_shipment_info_service_type']);
                }
            } else {
                if ($post['aramex_shipment_info_service_type'] == null) {
                    $post['aramex_shipment_info_service_type'] = array();
                }

                $services = array_merge($services, $post['aramex_shipment_info_service_type']);
            }

            $services = implode(',', $services);

            $AdditionalPropertyDetails = array();
            if (isset($post['aramex_shipment_shipper_country']) && $post['aramex_shipment_shipper_country'] == 'IN') {
                if ($post['ShipperTaxIdVATEINNumber'] != null)
                {
                    array_push($AdditionalPropertyDetails,[
                        'CategoryName' => "CustomsClearance",
                        'Name' => "ShipperTaxIdVATEINNumber",
                        'Value' => $post['ShipperTaxIdVATEINNumber']
                    ]);
                }
                if ($post['ConsigneeTaxIdVATEINNumber'] != null)
                {
                    array_push($AdditionalPropertyDetails,[
                        'CategoryName' => "CustomsClearance",
                        'Name' => "ConsigneeTaxIdVATEINNumber",
                        'Value' => $post['ConsigneeTaxIdVATEINNumber']
                    ]);
                }
                if ($post['TaxPaid'] != null)
                {
                    array_push($AdditionalPropertyDetails,[
                        'CategoryName' => "CustomsClearance",
                        'Name' => "TaxPaid",
                        'Value' => $post['TaxPaid']
                    ]);
                }
                if ($post['InvoiceDate'] != null)
                {
                    $orgDate = $post['InvoiceDate'];  
                    $newDate = date("m/d/Y", strtotime($orgDate));  
                    array_push($AdditionalPropertyDetails,[
                        'CategoryName' => "CustomsClearance",
                        'Name' => "InvoiceDate",
                        'Value' => $newDate
                    ]);
                }
                if ($post['InvoiceNumber'] != null)
                {
                    array_push($AdditionalPropertyDetails,[
                        'CategoryName' => "CustomsClearance",
                        'Name' => "InvoiceNumber",
                        'Value' => $post['InvoiceNumber']
                    ]);
                }
                if ($post['TaxAmount'] != null)
                {
                    array_push($AdditionalPropertyDetails,[
                        'CategoryName' => "CustomsClearance",
                        'Name' => "TaxAmount",
                        'Value' => $post['TaxAmount']
                    ]);
                }
                if ($post['ExporterType'] != null)
                {
                    array_push($AdditionalPropertyDetails,[
                        'CategoryName' => "CustomsClearance",
                        'Name' => "ExporterType",
                        'Value' => $post['ExporterType']
                    ]);
                }
            }
            if ($post['aramex_shipment_ioss_number'] != null){
                    array_push($AdditionalPropertyDetails,[
                        'CategoryName' => "CustomsClearance",
                        'Name' => "IOSS",
                        'Value' => $post['aramex_shipment_ioss_number']
                    ]);
            }
            
            if ($post['aramex_shipment_shipper_taxidvat'] != null){
                    array_push($AdditionalPropertyDetails,[
                        'CategoryName' => "CustomsClearance",
                        'Name' => "ShipperTaxIdVATEINNumber",
                        'Value' => $post['aramex_shipment_shipper_taxidvat']
                    ]);
            }
            if ($post['aramex_shipment_receiver_taxidvat'] != null){
                    array_push($AdditionalPropertyDetails,[
                        'CategoryName' => "CustomsClearance",
                        'Name' => "ReceiverTaxIdVATEINNumber",
                        'Value' => $post['aramex_shipment_receiver_taxidvat']
                    ]);
            }

            $itemNumbers = explode(',',$post['item_details']);
            $itemDetails = array();
            $PiecesDimensions = array();
            foreach ($itemNumbers as $val)
            {
                $itemTitle = "aramex_items_Title_".$val;
                $itemQuantity = "aramex_items_total_".$val;
                $itemPrice = "aramex_items_base_price_".$val;
                $itemWeight = "aramex_items_base_weight_".$val;
                $product_id = $val;

                // Get product length
                $product_length = get_post_meta($product_id, '_length', true);

                // Get product width
                $product_width = get_post_meta($product_id, '_width', true);

                // Get product height
                $product_height = get_post_meta($product_id, '_height', true);
                $product_unit = get_option('woocommerce_dimension_unit');

                $product = wc_get_product($product_id);
             
                $attribute_value = $product->get_attribute('hscode');
                if(empty($attribute_value )){
                    $attribute_value = "";
                }
            
                array_push($itemDetails,[
                    'Quantity' => $post[$itemQuantity],
                    'Weight' => [
                        'Value' => $post[$itemWeight],
                        'Unit' => $post['weight_unit']
                    ],
                    'CommodityCode' => $attribute_value,
                    'GoodsDescription' => $post[$itemTitle],
                    'CustomsValue' => [
                        'Value' => $post[$itemPrice],
                        'CurrencyCode' => $post['aramex_shipment_currency_code_custom_hidden_item']
                    ],
                       
                ]);
                $ActualWeight = (float) $post[$itemQuantity] * (float) $post[$itemWeight];
                array_push($PiecesDimensions,[
                    'Length' => $product_length,
                    'Width' => $product_width,
                    'Height' => $product_height,
                    'ActualWeight' => array(
                        'Value' => $ActualWeight,
                        'Unit' => $post['weight_unit']
                    ),
                   // 'NumberOfPieces' => $post[$itemQuantity],
                    'Unit' => $product_unit,  
                ]);
            }

            ///// add COD end
            // Other Main Shipment Parameters
            $params['ForeignHAWB'] = $post['aramex_shipment_info_foreignhawb'];
            $params['Reference1'] = $post['aramex_shipment_info_reference']; //'Shpt0001';
            $params['Reference2'] = '';
            $params['Reference3'] = '';
            $params['ForeignHAWB'] = $post['aramex_shipment_info_foreignhawb'];
            $params['TransportType'] = 0;
            $params['ShippingDateTime'] = time();
            $params['DueDate'] = time() + (7 * 24 * 60 * 60);
            $params['PickupLocation'] = 'Reception';
            $params['PickupGUID'] = '';
            $params['Comments'] = $post['aramex_shipment_info_comment'];
            $params['AccountingInstrcutions'] = '';
            $params['OperationsInstructions'] = '';
            $params['Details'] = array(
                'Dimensions' => array(
                    'Length' => '0',
                    'Width' => '0',
                    'Height' => '0',
                    'Unit' => 'cm'
                ),
                'PieceDimensions' => [
                    'Dimensions' => $PiecesDimensions
                ],
                'ActualWeight' => array(
                    'Value' => $totalWeight,
                    'Unit' => $post['weight_unit']
                ),
                'ProductGroup' => $post['aramex_shipment_info_product_group'],
                //'EXP',
                'ProductType' => $post['aramex_shipment_info_product_type'],
                //,'PDX'
                'PaymentType' => $post['aramex_shipment_info_payment_type'],
                'PaymentOptions' => $post['aramex_shipment_info_payment_option'],
                'Services' => $services,
                'NumberOfPieces' => $totalItems,
                'DescriptionOfGoods' => (trim($post['aramex_shipment_description']) == '') ? $descriptionOfGoods : trim(substr($post['aramex_shipment_description'], 0, 65)),
                'GoodsOriginCountry' => $post['aramex_shipment_shipper_country'],
                //'JO',
                'Items' => [
                    'ShipmentItem' => $itemDetails
                ],
                'AdditionalProperties' => [
                    'AdditionalProperty' => $AdditionalPropertyDetails
                ]
            );
            if (count($aramex_atachments)) {
                $params['Attachments'] = $aramex_atachments;
            }
            if ($post['aramex_shipment_info_service_type'] != null)
            {
                $hasCODS= array_search("CODS",$post['aramex_shipment_info_service_type'],false);
                if ($hasCODS !== false)
                {         
                    $params['Details']['CashOnDeliveryAmount'] = array(
                        'Value' => $post['aramex_shipment_info_cod_amount'],
                        'CurrencyCode' => $post['aramex_shipment_currency_code']
                        );
                }
                else
                {
                    $params['Details']['CashOnDeliveryAmount'] = null ;
                }
            }
            else
            {
                $params['Details']['CashOnDeliveryAmount'] = null ;       
            }
            
            $data = $order->get_data();
            $payment_method = $data['payment_method'];

            if($post['aramex_shipment_info_product_group'] === 'DOM' &&  
            $post['aramex_shipment_info_payment_method'] == 'aramex_smart')
            {
                $params['Details']['CustomsValueAmount'] = array(
                    'Value' => $post['aramex_shipment_info_custom_amount_hidden'],
                    'CurrencyCode' => $post['aramex_shipment_currency_code_custom_hidden']
                );
            }
            else {
                $params['Details']['CustomsValueAmount'] = array(
                    'Value' => $post['aramex_shipment_info_custom_amount'],
                    'CurrencyCode' => $post['aramex_shipment_currency_code_custom']
                );
            }
            
            $params['Details']['InsuranceAmount'] = array(
                'Value' => $post['insurance_amount'],
                'CurrencyCode' => $post['aramex_shipment_currency_code']
            );
            
            $params['ShipmentDetails']['InsuranceAmount'] =  $post['insurance_amount'];
            
            $CurrencyCode = $post['aramex_shipment_currency_code'];
            if (trim($CurrencyCode) === "") {
                $CurrencyCode = $post['aramex_shipment_currency_code_custom'];
            }
            
            $params['Details']['CashAdditionalAmount'] = array(
                'Value' => $post['aramex_shipment_info_cash_additional_amount'],
                'CurrencyCode' => $CurrencyCode
            );

            $major_par['Shipments'][] = $params;
            if($post['aramex_shipment_info_payment_method'] == 'aramex_smart')
            {
                $info['clientInfo']['Source'] = 47;
            }           
            $major_par['ClientInfo'] = $info['clientInfo'];
            $report_id = (int)$info['clientInfo']['report_id'];
            if (!$report_id) {
                $report_id = 9729;
            }
            $major_par['LabelInfo'] = array(
                'ReportID' => $report_id,
                'ReportType' => 'URL'
            );
            if (!session_id()) {
                session_start();
            }
            $_SESSION['form_data'] = $post;
    /**
     * Used for tracking error messages
     *
     * @return mixed|strng
     */
            function aramex_errors()
            {
                static $wp_error; // Will hold global variable safely
                return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
            }

            try {

                session_start();
                // echo "<pre>";
                // echo json_encode($major_par);
                // die;
                $auth_call = $soapClient->CreateShipments($major_par);
                // error_log(print_r($auth_call, true));
                           
                if ($auth_call->HasErrors) {
                    if (empty($auth_call->Shipments)) {
                        if (count((array)$auth_call->Notifications->Notification) > 1) {
                            foreach ($auth_call->Notifications->Notification as $notify_error) {
                                aramex_errors()->add('error',
                                    __('Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message));
                            }
                        } else {
                            aramex_errors()->add('error',
                                __('Aramex: ' . $auth_call->Notifications->Notification->Code . ' - ' . $auth_call->Notifications->Notification->Message),
                                'aramex');
                        }
                    } else {
                        if (count((array)$auth_call->Shipments->ProcessedShipment->Notifications->Notification) > 1) {
                            $notification_string = '';
                            if(is_array($auth_call->Shipments->ProcessedShipment->Notifications->Notification)){
                                foreach ($auth_call->Shipments->ProcessedShipment->Notifications->Notification as $notification_error) {
                                    $notification_string .= $notification_error->Code . ' - ' . $notification_error->Message . '';
                                }
                            }else{
                                $notification_string .= $auth_call->Shipments->ProcessedShipment->Notifications->Notification->Code . ' - ' . $auth_call->Shipments->ProcessedShipment->Notifications->Notification->Message . '';
                            }
                            aramex_errors()->add('error', __($notification_string));
                        } else {
                            
                            aramex_errors()->add('error',
                                __('Aramex: ' . $auth_call->Shipments->ProcessedShipment->Notifications->Notification->Code . ' - ' . $auth_call->Shipments->ProcessedShipment->Notifications->Notification->Message),
                                'aramex');
                        }
                    }
                    $_SESSION['aramex_errors'] = aramex_errors();
                    wp_redirect(sanitize_text_field(esc_url_raw($_SERVER["HTTP_REFERER"])) . '&aramexpopup/show');
                    exit;
                } else {
                    if ($post['aramex_return_shipment_creation_date'] == "create") {
                        $commentdata = array(
                            'comment_post_ID' => $post['aramex_shipment_original_reference'],
                            'comment_author' => '',
                            'comment_author_email' => '',
                            'comment_author_url' => '',
                            'comment_content' => "AWB No. " . $auth_call->Shipments->ProcessedShipment->ID . " - Order No. " . $auth_call->Shipments->ProcessedShipment->Reference1,
                            'comment_type' => 'order_note',
                            'user_id' => "0",
                        );
                        wp_new_comment($commentdata);
                        $order = new WC_Order($post['aramex_shipment_original_reference']);
                        $order->add_order_note($commentdata['comment_content']);
                        $order->save();
                        if (!empty($order)) {
                            $order->update_status('printing-aramex', __('Aramex shipment created.', 'aramex'));
                            update_post_meta($order->id, 'aramex_awb_no', $auth_call->Shipments->ProcessedShipment->ID);
                        }

                        /* sending mail */
                        global $woocommerce;
                        $mailer = $woocommerce->mailer();
                        $supportEmail = $mailer->get_from_address();
                        $message_body = sprintf(__('<p>Dear <b>%s</b> </p>'), $post['aramex_shipment_receiver_name']);
                        $message_body .= sprintf(__('<p>Your order is #%s </p>'),
                            $auth_call->Shipments->ProcessedShipment->Reference1);
                        $message_body .= sprintf(__('<p>Created Airway bill number: %s </p>'),
                            $auth_call->Shipments->ProcessedShipment->ID);
                        $message_body .= __('<p>You can track shipment on <a href="http://www.aramex.com/express/track.aspx">http://www.aramex.com/express/track.aspx</a> </p>');
                        $message_body .= __('<p>If you have any questions, please feel free to contact us <b>'.$supportEmail.'</b> </p>',
                            'aramex');
                        $message = $mailer->wrap_message(
                        // Message head and message body.
                            sprintf(__('Aramex shipment #%s created', 'aramex'), $order->get_order_number()),
                            $message_body);
                        if (isset($post['aramex_email_customer']) && $post['aramex_email_customer'] == 'yes') {
                            // Cliente email
                            $to = array();
                            $to[] = $order->billing_email;
                            $to[] = $info['copyInfo']['copy_to'];
                            $emailsTo = implode(',', $to);
                            if (trim($info['copyInfo']['copy_to']) == "") {
                                $emailsTo = trim($emailsTo, ',');
                            }
                            $mailheader = array();
                            if ($info['copyInfo']['copy_method'] == "1" && trim($info['copyInfo']['copy_to']) != "") {
                                $emails = explode(',', trim($info['copyInfo']['copy_to']));
                                foreach ($emails as $email) {
                                    $mailheader[] = 'Bcc: ' . $email;
                                }
                            }
                            if ($info['copyInfo']['copy_method'] == "0" && trim($info['copyInfo']['copy_to']) != "") {
                                $emails = explode(',', trim($info['copyInfo']['copy_to']));
                                foreach ((array)$emails as $email) {
                                    $mailheader[] = 'Cc: ' . $email;
                                }
                            }
                            try {
                                $mailer->send($emailsTo,
                                    sprintf(__('Aramex shipment #%s created', 'aramex'), $order->get_order_number()),
                                    $message,
                                    $mailheader);
                            } catch (Exception $ex) {
                                aramex_errors()->add('error', $ex->getMessage());
                            }
                        }

                        aramex_errors()->add('success',
                            __('Aramex Shipment Number: ',
                                'aramex') . $auth_call->Shipments->ProcessedShipment->ID . __(' has been created.',
                                'aramex'));
                    } elseif ($post['aramex_return_shipment_creation_date'] == "return") {
                        aramex_errors()->add('success',
                            __('Aramex Shipment Return Order Number: ',
                                'aramex') . $auth_call->Shipments->ProcessedShipment->ID . __(' has been created.',
                                'aramex'));
                        $message = "Aramex Shipment Return Order AWB No. " . $auth_call->Shipments->ProcessedShipment->ID . " - Order No. " . $auth_call->Shipments->ProcessedShipment->Reference1;
                        $commentdata = array(
                            'comment_post_ID' => $post['aramex_shipment_original_reference'],
                            'comment_author' => '',
                            'comment_author_email' => '',
                            'comment_author_url' => '',
                            'comment_content' => $message,
                            'comment_type' => 'order_note',
                            'user_id' => "0",
                        );
                        wp_new_comment($commentdata);
                    } else {
                        aramex_errors()->add('error', __('Cannot do shipment for the order.', 'aramex'));
                    }
                }
            } catch (Exception $e) {
                $aramex_errors = true;
                aramex_errors()->add('error', $e->getMessage());
            }

            if ($aramex_errors) {
                $_SESSION['aramex_errors'] = aramex_errors();
                wp_redirect(sanitize_text_field(esc_url_raw($_SERVER["HTTP_REFERER"])) . '&aramexpopup/show');
                exit;
            } else {
                //success exit
                $_SESSION['aramex_errors'] = aramex_errors();
                wp_redirect(sanitize_text_field(esc_url_raw($_SERVER["HTTP_REFERER"])) . '&aramexpopup/show');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['aramex_errors'] = aramex_errors();
            wp_redirect(sanitize_text_field(esc_url_raw($_SERVER["HTTP_REFERER"])) . '&aramexpopup/show');
            exit;
        }
    }
}