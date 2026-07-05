<?php
/**
 * Plugin Name: BizTax Quarterly Estimated Tax Calculator
 * Description: Quarterly estimated federal tax calculator with spouse business income support.
 * Version:     2.0.0
 * Author:      BizTax Playbook
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BIZTAX_CALC_PATH', plugin_dir_path( __FILE__ ) );
define( 'BIZTAX_CALC_URL',  plugin_dir_url( __FILE__ ) );

require_once BIZTAX_CALC_PATH . 'includes/class-tax-data.php';
require_once BIZTAX_CALC_PATH . 'includes/class-qbid.php';
require_once BIZTAX_CALC_PATH . 'includes/class-calculator-engine.php';
require_once BIZTAX_CALC_PATH . 'includes/ajax-handler.php';

// Enqueue front-end assets
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'biztax-calc-style',
        BIZTAX_CALC_URL . 'assets/css/calculator.css',
        [],
        '2.0.0'
    );
    
    wp_localize_script( 'biztax-calc-script', 'biztaxCalc', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'biztax_calc_nonce' ),
    ] );
} );

// Shortcode — place [biztax_quarterly_calculator] on any page
add_shortcode( 'biztax_quarterly_calculator', function() {
    ob_start();
    include BIZTAX_CALC_PATH . 'includes/template-calculator.php';
    return ob_get_clean();
} );