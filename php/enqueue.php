<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

//Add js and css files
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\enqueueScripts', 1);
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\registerScripts', 1);

// Style the buttons in the media library
add_action('wp_enqueue_media', __NAMESPACE__ . '\enqueuMediaStyle');
function enqueuMediaStyle()
{
    wp_enqueue_style('tsjippy_media_style', plugins_url('css/media.min.css', __DIR__), [], STYLEVERSION);
}

function registerScripts($hook = '')
{
    if (!is_user_logged_in()) {
        wp_enqueue_script_module('@tsjippy/nonce_script', plugins_url("js/nonce" . JSEXTENSION, __DIR__), [], STYLEVERSION);

        add_filter( 'script_module_data_@tsjippy/nonce_script', function($data){
            $data['baseUrl']       = get_home_url();
            $data['restApiPrefix'] = '/' . RESTAPIPREFIX;
            $data['restNonce']     = wp_create_nonce('wp_rest');

            return $data; 
        } );
    }

    //LIBRARIES
    //selectable select table cells https://github.com/Mobius1/Selectable
    wp_register_script_module('selectable', plugins_url("js/selectable" . JSEXTENSION, __DIR__), array(), '0.22.0');

    //add main.js
    wp_register_script_module('@tsjippy/main', plugins_url("js/main" . JSEXTENSION, __DIR__), [], STYLEVERSION);

    //Submit forms
    wp_register_script_module('@tsjippy/user_select_script', plugins_url("js/user_select" . JSEXTENSION, __DIR__), [], STYLEVERSION);

    //table request shortcode
    wp_register_script_module('@tsjippy/table_script', plugins_url("js/table" . JSEXTENSION, __DIR__), array('@tsjippy/formsubmit_script'), STYLEVERSION);

    // Debug request shortcode
    wp_register_script_module('@tsjippy/debug_script', plugins_url("js/debug" . JSEXTENSION, __DIR__), [], STYLEVERSION);

    wp_register_style('tsjippy_taxonomy_style', plugins_url('css/taxonomy.min.css', __DIR__), array(), STYLEVERSION);

    wp_register_style('tsjippy_template', pathToUrl(PLUGINPATH . 'css/template.min.css'), array(), STYLEVERSION);

    if ($hook == 'post.php') {
        enqueueScripts();
    }
}

function enqueueScripts()
{
    global $tsjippyEnqueingRunned;

    if ($tsjippyEnqueingRunned) {
        return;
    }

    registerScripts();

    if (is_home() || is_search() || is_category() || is_tax()) {
        wp_enqueue_style('tsjippy_taxonomy_style');
    }

    wp_enqueue_script_module('@tsjippy/main');

    //add main css
    add_editor_style(plugins_url('css/main.min.css', __DIR__));

    //style fo main site
    if (!is_admin()) {
        wp_enqueue_style('tsjippy_style', plugins_url('css/main.min.css', __DIR__), array(), STYLEVERSION);
    }
}

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\loadScripts', 99999);
function loadScripts()
{
    //Do no load these css files
    $dequeueStyles = [];
    //Do no load these js files
    $dequeueScripts = [];

    $dequeueScripts[] = 'featherlight';
    $dequeueScripts[] = 'jquery';
    $dequeueScripts[] = 'jquery-ui-datepicker';
    $dequeueScripts[] = 'jquery-ui-autocomplete';

    //Dequeue the css files
    foreach ($dequeueStyles as $dequeue_style) {
        wp_dequeue_style($dequeue_style);
    }

    //dequeue the js files
    foreach ($dequeueScripts as $dequeue_script) {
        wp_dequeue_script($dequeue_script);
    }
}

add_action('wp_default_scripts', __NAMESPACE__ . '\loadDefaultScripts');
function loadDefaultScripts($scripts)
{
    if (! is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];
        if ($script->deps) {
            // Check whether the script has any dependencies
            $script->deps = array_diff($script->deps, array('jquery-migrate'));
        }
    }
}
