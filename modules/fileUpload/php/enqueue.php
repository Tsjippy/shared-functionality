<?php

namespace TSJIPPY\FILEUPLOAD;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\registerUploadScripts', 1);

function registerUploadScripts()
{
    //File upload js
    wp_register_script_module('@tsjippy/fileupload_script', plugins_url('js/fileupload.min.js', __DIR__), array('@tsjippy/formsubmit_script', '@tsjippy/purify'), TSJIPPY\STYLEVERSION);

    wp_register_style('tsjippy_image-edit', plugins_url('css/image-edit.min.css', __DIR__), array(), TSJIPPY\STYLEVERSION);
}
