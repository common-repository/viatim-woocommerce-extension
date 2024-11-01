<?php
    $sameDay = array(
        'id'    => __('same_day_delivery', 'viatim-woocommerce-plugin'),   // ID for the rate
        'label' => __("ViaTim Same Day", 'viatim-woocommerce-plugin'),   // Label for the rate
        'cost' => $this->settings['same_day_delivery'], // Rate costs
    );

    $nextDay = array(
        'id'    => __('next_day_delivery', 'viatim-woocommerce-plugin'),
        'label' => 'ViaTim',
        'cost' => $this->settings['next_day_delivery']
    );

    // $international = array(
    //     'id'    => __('international_delivery', 'viatim-woocommerce-plugin'),
    //     'label' => __('ViaTim International', 'viatim-woocommerce-plugin'),
    //     'cost' => $this->settings['international_delivery']
    // );

    $freeDelivery = array(
        'id'    =>  __('free_delivery', 'viatim-woocommerce-plugin'),
        'label' =>  __('Free ViaTim Delivery', 'viatim-woocommerce-plugin'),
        'cost'  => '0.00'
    );
?>