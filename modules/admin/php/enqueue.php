<?php

namespace TSJIPPY\ADMIN;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

//load js and css
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\loadAdminAssets');
function loadAdminAssets($hook)
{
    //Only load on tsjippy settings pages
    if (!str_contains($hook, '_tsjippy')) {
        return;
    }

    wp_enqueue_style('tsjippy_admin_css', plugins_url('css/admin.min.css', __DIR__), array(), TSJIPPY\STYLEVERSION);
    wp_enqueue_script_module('@tsjippy/admin_js', plugins_url('js/admin' . TSJIPPY\JSEXTENSION, __DIR__), array('@tsjippy/main'), TSJIPPY\STYLEVERSION);

    add_filter( 'script_module_data_@tsjippy/admin_js', function($data){
        $data['baseUrl']       = get_home_url();
        $data['restApiPrefix'] = '/' . RESTAPIPREFIX;
        $data['restNonce']     = wp_create_nonce('wp_rest');

        return $data; 
    } );
}
