<?php

namespace TSJIPPY\FILEUPLOAD;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\registerUploadScripts', 1);

/**
 * Enqueues the upload script
 */
function registerUploadScripts()
{
    /**
     * Modules
     */
    wp_register_script_module('@tsjippy/croppr', plugins_url('js/modules/croppr.js', __DIR__), [], TSJIPPY\STYLEVERSION);
    wp_register_script_module('@tsjippy/image_edit', plugins_url('js/modules/image-edit.js', __DIR__), ['@tsjippy/croppr'], TSJIPPY\STYLEVERSION);
    wp_register_script_module('@tsjippy/file_upload_exports', plugins_url('js/modules/file-upload-exports.js', __DIR__), [], TSJIPPY\STYLEVERSION);

    //File upload js
    $deps   = SCRIPT_DEBUG ? [  
        '@tsjippy/form_submit_functions', 
        "@tsjippy/image_edit", 
        "@tsjippy/show_loader", 
        "@tsjippy/display_message", 
        "@tsjippy/file_upload_exports"
    ] :
    [];

    $deps[] = "@tsjippy/nonce_script";
    wp_register_script_module('@tsjippy/fileupload_script', plugins_url('js/fileupload' . TSJIPPY\JSEXTENSION, __DIR__),$deps, TSJIPPY\STYLEVERSION);

    wp_register_style('tsjippy_image-edit', plugins_url('css/image-edit.min.css', __DIR__), array(), TSJIPPY\STYLEVERSION);
}
