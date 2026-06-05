<?php

namespace TSJIPPY\FILEUPLOAD;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\registerUploadScripts', 1);

function registerUploadScripts()
{
    //File upload js
    wp_register_script('tsjippy_fileupload_script', plugins_url('js/fileupload.min.js', __DIR__), array('tsjippy_formsubmit_script', 'tsjippy_purify'), TSJIPPY\STYLEVERSION, true);

    wp_register_style('tsjippy_image-edit', plugins_url('css/image-edit.min.css', __DIR__), array(), TSJIPPY\STYLEVERSION);
}
