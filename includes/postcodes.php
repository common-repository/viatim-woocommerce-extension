<?php
    global $woocommerce;
    $customer = new WC_Customer();
    $postcode = $woocommerce->customer->get_shipping_postcode();    //Gets postcode from the logged in user.
    $country = $woocommerce->customer->get_shipping_country();      //Gets country from the logged in user.
    $total = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->get_cart_total())); //Gets total in cart from the logged in user.
    $GLOBALS['postcodes'] = false;
    
    function checkPostcode($apiKey) {
        $apiBase = 'https://api.viatim.nl:8001/api/v1';
        global $woocommerce;
        $customer = new WC_Customer();
        $postcode = $woocommerce->customer->get_shipping_postcode();
        $url = "$apiBase/coverage/?postcode=$postcode";

        httpGet($url, $apiKey);
    }

    function httpGet($url, $apiKey) {
        $headers = array(
            'headers' => array(
                'x-auth-token' => $apiKey, "Content-Type" => "application/json",
            )
        );
        $response = wp_remote_get( $url, $headers );
        $body = wp_remote_retrieve_body($response);
        $outputJson = json_decode($body);

        // print_r($outputJson);

        if($outputJson->hasCoverage === true) {
            $GLOBALS['postcodes'] = true;
        }
    }
?>