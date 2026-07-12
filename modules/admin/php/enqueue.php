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
    wp_enqueue_script('tsjippy_admin_js', plugins_url('js/admin.min.js', __DIR__), array('tsjippy_script'), TSJIPPY\STYLEVERSION, true);

    wp_localize_script(
        'tsjippy_admin_js',
        'tsjippy',
        array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            "userId"        => wp_get_current_user()->ID,
            'baseUrl'       => get_home_url(),
            'maxFileSize'   => wp_max_upload_size(),
            'restNonce'     => wp_create_nonce('wp_rest'),
            'restApiPrefix' => '/' . TSJIPPY\RESTAPIPREFIX
        )
    );
}
