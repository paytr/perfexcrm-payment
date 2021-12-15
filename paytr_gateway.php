<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: PayTR Virtual Pos iFrame API
Description: PayTR Payment Gateway Module
Version: 1.0.0
Author: PayTR Ödeme ve Elektronik Para Kuruluşu A.Ş.
Author URI: https://www.paytr.com
Requires at least: 2.3.*
*/

register_payment_gateway('paytr_gateway', 'paytr_gateway');
hooks()->add_action('admin_init', 'paytr_menu_item_collapsible');
register_activation_hook('paytr_gateway', 'paytr_module_activation_hook');
register_language_files('paytr_gateway', ['paytr_gateway']);

function paytr_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

function paytr_menu_item_collapsible()
{
    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('custom-menu-unique-id', [
        'name'     => 'PayTR',
        'collapse' => true,
        'position' => 100,
        'icon'     => 'fa fa-question-circle',
    ]);

    $CI->app_menu->add_sidebar_children_item('custom-menu-unique-id', [
        'slug'     => 'child-to-custom-menu-item',
        'name'     => _l('paytr_installment_settings'),
        'href'     => site_url('admin/paytr_gateway/installment_module'),
        'position' => 0,
    ]);
}