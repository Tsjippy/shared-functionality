<?php

namespace TSJIPPY\ADMIN;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

//load js and css
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\loadAdminAssets');
/**
 * enquesus the admin css and js
 * 
 * @param   string  $hook
 */
function loadAdminAssets($hook)
{
    //Only load on tsjippy settings pages
    if (!str_contains($hook, '_tsjippy')) {
        return;
    }

    wp_enqueue_style('tsjippy_admin_css', plugins_url('css/admin.min.css', __DIR__), array(), TSJIPPY\STYLEVERSION);

    $deps   = SCRIPT_DEBUG ? [  
        '@tsjippy/form_submit_functions', 
        "@tsjippy/tabs", 
        "@tsjippy/show_loader", 
        "@tsjippy/form_exports", 
        "@tsjippy/modals",
        "@tsjippy/alert",
        "@tsjippy/nice_select"
    ] :
    [];

    $deps[] = "@tsjippy/nonce_script";
    wp_enqueue_script_module('@tsjippy/admin_js', plugins_url('js/admin' . TSJIPPY\JSEXTENSION, __DIR__), $deps, TSJIPPY\STYLEVERSION);
}
