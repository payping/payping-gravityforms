<?php
/*
Plugin Name: PayPing GravityForms
Version: 2.4.4
Description:  افزونه درگاه پرداخت پی‌پینگ برای Gravity forms
Plugin URI: https://www.payping.ir/
Author: Hadi Hosseini
Author URI: https://hosseini-dev.ir/
Requires Plugins: persian-gravity-forms
License: GPLv3 or later
*/
if (!defined('ABSPATH')) exit;

function callback_for_setting_up_scripts(){
    wp_enqueue_script( 'gf-admin-scripts', plugin_dir_url( __FILE__ ) . 'assets/js/scripts.js', array(), false, false );
    wp_enqueue_script( 'shamsi-chart', esc_url(GFPersian_Payments::get_base_url()) . '/assets/js/shamsi_chart.js', array('jquery'), null, false );
    wp_register_script( 'jquery-ui-jdatepicker', GFPersian_Payments::get_base_url() . '/assets/js/jalali-datepicker.js', array('jquery','jquery-migrate','jquery-ui-core',), GFCommon::$version, true );
    wp_enqueue_script( 'jquery-ui-jdatepicker' );
    wp_localize_script(
        'gf-admin-scripts',
        'myLocalizedData',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'gf_payping_update_feed_active_nonce' => wp_create_nonce('gf_payping_update_feed_active'),
            'deactivate_message' => esc_html__('درگاه غیر فعال است', 'payping-gravityforms'),
            'activate_message' => esc_html__('درگاه فعال است', 'payping-gravityforms'),
            'ajax_error_message' => esc_html__('خطای Ajax رخ داده است', 'payping-gravityforms')
        )
    );
    wp_enqueue_style(
        'admin-styles-enqueue', plugin_dir_url( __FILE__ ) . 'assets/css/styles.css'
    );
    if ( is_rtl() ) {
        $custom_css = " table.gforms_form_settings th { text-align: right !important; }";
        wp_add_inline_style( 'admin-styles-enqueue', $custom_css );
	} 
}
add_action( 'admin_enqueue_scripts', 'callback_for_setting_up_scripts' );

require_once('payping.php');

