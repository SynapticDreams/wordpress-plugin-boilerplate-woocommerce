<?php
$propulsionTypes = array(
    'unknown'   => __( '', 'auspost-shipping' ),
    'light_speed'   => __( 'Light Speed', 'auspost-shipping' ),
    'ftl_speed'   => __( 'Faster Than Light', 'auspost-shipping' ),
);

$settings = array(
        array(
            'name' => __( 'General Configuration', 'auspost-shipping' ),
            'type' => 'title',
            'id'   => $prefix . 'general_config_settings'
        ),
        array(
            'id'        => $prefix . 'battlestar_group',
            'name'      => __( 'Battlestar Group', 'auspost-shipping' ), 
            'type'      => 'number',
            'desc_tip'  => __( ' The numeric designation of this Battlestar Group.', 'auspost-shipping')
        ),
        array(
            'id'        => $prefix . 'flagship',
            'name'      => __( 'Flagship', 'auspost-shipping' ), 
            'type'      => 'text',
            'desc_tip'  => __( ' The name of this Battlestar Group flagship. ', 'auspost-shipping')
        ),
        array(
            'name'      => __( 'General Configuration', 'auspost-shipping' ),
            'type'      => 'sectionend',
            'desc'      => '',
            'id'        => $prefix . 'general_config_settings'
        ),

        array(
            'name' => __( 'Flagship Settings', 'auspost-shipping' ),
            'type' => 'title',
            'id'   => $prefix . 'flagship_settings',
        ),
        array(
            'id'        => $prefix . 'ship_propulsion_type',
            'name'      => __( 'Propulsion Type', 'auspost-shipping' ), 
            'type'      => 'select',
            'class'     => 'wc-enhanced-select',
            'options'   => $propulsionTypes,
            'desc_tip'  => __( ' The primary propulsion type utilized by this flagship.', 'auspost-shipping')
        ),
        array(
            'id'        => $prefix . 'ship_length',
            'name'      => __( 'Length', 'auspost-shipping' ), 
            'type'      => 'number',
            'desc_tip'  => __( ' The length in meters of this ship.', 'auspost-shipping')
        ),
        array(
            'id'        => $prefix . 'ship_in_service',
            'name'      => __( 'In Service?', 'auspost-shipping' ),
            'type'      => 'checkbox',
            'desc'  => __( 'Uncheck this box if the ship is out of service.', 'auspost-shipping' ),
            'default'   => 'yes'
        ),             
        array(
            'name'      => __( 'Flagship Settings', 'auspost-shipping' ),
            'type'      => 'sectionend',
            'desc'      => '',
            'id'        => $prefix . 'flagship_settings',
        ),                        
    );
?>