<?php
    function getOrder($apiKey, $enabled) {
        // Get an instance of the WC_Order object
        $order_key = $_GET['key'];
        $order_id = wc_get_order_id_by_order_key($order_key);
        $order = new WC_Order( $order_id );
        $shipping_method = $order->get_shipping_method();

        if($enabled === 'yes') {
            if($shipping_method === 'Free ViaTim Delivery' || $shipping_method === 'ViaTim' || $shipping_method === 'ViaTim Same Day') {
                createPackage($order, $apiKey);
            }
        }
    }

    function createPackage($order, $apiKey) {
        $apiBase = 'https://api.viatim.nl:8001/api/v1';

        $order_data = $order->get_data(); // The Order data
        $order_shipping = $order->get_items('shipping'); //Order shipping data 

        $order_parent_id = $order_data['parent_id'];
        $order_status = $order_data['status'];
        $order_currency = $order_data['currency'];
        $order_version = $order_data['version'];
        $order_payment_method = $order_data['payment_method'];
        $order_payment_method_title = $order_data['payment_method_title'];
        $order_payment_method = $order_data['payment_method'];
        $order_payment_method = $order_data['payment_method'];

        ## Creation and modified WC_DateTime Object date string ##

        // Using a formated date ( with php date() function as method)
        $order_date_created = $order_data['date_created']->date('Y-m-d H:i:s');
        $order_date_modified = $order_data['date_modified']->date('Y-m-d H:i:s');

        // Using a timestamp ( with php getTimestamp() function as method)
        $order_timestamp_created = $order_data['date_created']->getTimestamp();
        $order_timestamp_modified = $order_data['date_modified']->getTimestamp();

        $order_discount_total = $order_data['discount_total'];
        $order_discount_tax = $order_data['discount_tax'];
        $order_shipping_total = $order_data['shipping_total'];
        $order_shipping_tax = $order_data['shipping_tax'];
        $order_total = $order_data['cart_tax'];
        $order_total_tax = $order_data['total_tax'];
        $order_customer_id = $order_data['customer_id']; // ... and so on

        //Billing information:
        $order_billing_first_name = $order_data['billing']['first_name'];
        $order_billing_last_name = $order_data['billing']['last_name'];
        $order_billing_company = $order_data['billing']['company'];
        $order_billing_address_1 = $order_data['billing']['address_1'];
        $order_billing_address_2 = $order_data['billing']['address_2'];
        $order_billing_city = $order_data['billing']['city'];
        $order_billing_state = $order_data['billing']['state'];
        $order_billing_postcode = $order_data['billing']['postcode'];
        $order_billing_country = $order_data['billing']['country'];
        $order_billing_email = $order_data['billing']['email'];
        $order_billing_phone = $order_data['billing']['phone'];

        //Shipping information:
        $order_shipping_first_name = $order_data['shipping']['first_name'];
        $order_shipping_last_name = $order_data['shipping']['last_name'];
        $order_shipping_company = $order_data['shipping']['company'];
        $order_shipping_address_1 = $order_data['shipping']['address_1'];
        $order_shipping_housenr = $order_data['meta_data'][4]->value;
        $order_shipping_address_2 = $order_data['shipping']['address_2'];
        $order_shipping_city = $order_data['shipping']['city'];
        $order_shipping_state = $order_data['shipping']['state'];
        $order_shipping_postcode = $order_data['shipping']['postcode'];
        $order_shipping_country = $order_data['shipping']['country'];
        $shipping_method = $order->get_shipping_method();

        //Delivery Date, customers choose this on checkout
        $originalDate = $order_data['meta_data'][0]->value;
        $street_name = $order_data['meta_data'][3]->value;
        $order_delivery_date = date("Y-m-d", strtotime($originalDate));

        if($shipping_method === 'ViaTim Same Day Bezorging' || $shipping_method === 'ViaTim Same Day') {
            $type = 3;
        } else {
            $type = 8;
        }

        $url = "$apiBase/packages";
        $data = array(
            "type" => $type,
            "recipient" => array(
                "postcode" => $order_shipping_postcode,
                "housenr" => $order_shipping_housenr,
                "phone" => $order_billing_phone,
                "type" => 0,
                "firstname" => $order_shipping_first_name,
                "lastname" => $order_shipping_last_name,
                "email" => $order_billing_email,
            ),
            "label" => true,
            // "agreed_delivery_timestamp" => $order_delivery_date . "T07:20:58.321Z" 
        );

        $result = checkAddress("$apiBase/postcode/$order_shipping_postcode/$order_shipping_housenr", $apiKey, $street_name);
        if($result === true) {
            // print_r($data);
            httpPost($url, $data, $apiKey);
        } elseif ($result === false) {
            echo 'Uw adresgegevens komen niet overeen met uw postcode. Weet u zeker dat deze kloppen?';
        }
    }

    function checkAddress($url, $apiKey, $shippingstreet) {
        $headers = array(
            'headers' => array(
                'x-auth-token' => $apiKey, 
                "Content-Type" => "application/json",
            )
        );
  
        $response = wp_remote_get($url, $headers);
        $body = wp_remote_retrieve_body($response);
        $outputJson = json_decode($body);

        // print_r($outputJson);

        $street = $outputJson->data->street;

        if(strtolower($street) == strtolower($shippingstreet)) {
            return true;
        } else {
            return false;
        }
    }

    function httpPost($url, $data, $apiKey) {
        $body = json_encode($data);
        $headers = array(
            'body' => $body,
            'headers' => array(
                'x-auth-token' => $apiKey, "Content-Type" => "application/json",
            )
        );
         
        $response = wp_remote_post($url, $headers);
        // print_r($response);
    }
?>