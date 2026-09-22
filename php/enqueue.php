<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

//Add js and css files
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\enqueueScripts', 1);
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\registerScripts', 1);

// Style the buttons in the media library
add_action('wp_enqueue_media', __NAMESPACE__ . '\enqueueMediaStyle');
function enqueueMediaStyle()
{
    wp_enqueue_style('tsjippy_media_style', plugins_url('css/media.min.css', __DIR__), [], STYLEVERSION);
}

function registerScripts()
{
    if (is_user_logged_in()) {
        wp_enqueue_script_module('@tsjippy/nonce_script', plugins_url("js/nonce" . JSEXTENSION, __DIR__), [], STYLEVERSION);

        add_filter( 'script_module_data_@tsjippy/nonce_script', function($data){
            $data['baseUrl']       = get_home_url();
            $data['restApiPrefix'] = '/' . RESTAPIPREFIX;
            $data['restNonce']     = wp_create_nonce('wp_rest');

            return $data; 
        } );
    }

    /**
     * CSS
     */
    wp_register_style('tsjippy_taxonomy_style', plugins_url('css/taxonomy.min.css', __DIR__), array(), STYLEVERSION);

    wp_register_style('tsjippy_template', pathToUrl(PLUGINPATH . 'css/template.min.css'), array(), STYLEVERSION);

    /**
     * LIBRARIES
     */
    //selectable select table cells https://github.com/Mobius1/Selectable
    wp_register_script_module('selectable', plugins_url("js/selectable" . JSEXTENSION, __DIR__), array(), '0.22.0');

    wp_register_script_module('nice-select2', plugins_url("js/nice-select2.js", __DIR__), array(), '2.5.0');

    /**
     * Modules
     */
    $deps   = SCRIPT_DEBUG ? [
        "@tsjippy/show_loader",  
        "@tsjippy/modals"
    ] :
    [];
    wp_register_script_module('@tsjippy/alert', plugins_url("js/modules/alert.js", __DIR__), $deps, STYLEVERSION);

    $deps   = SCRIPT_DEBUG ? [
        "@tsjippy/alert",
    ] :
    [];
    wp_register_script_module('@tsjippy/display_message', plugins_url("js/modules/display_message.js", __DIR__), $deps, STYLEVERSION);

    wp_register_script_module('@tsjippy/internet_connection', plugins_url("js/modules/internet_connection.js", __DIR__), [], STYLEVERSION);

    $deps   = SCRIPT_DEBUG ? [ 
        "@tsjippy/nice_select"
    ] :
    [];
    wp_register_script_module('@tsjippy/load_assets', plugins_url("js/modules/load_assets.js", __DIR__), $deps, STYLEVERSION);

    wp_register_script_module('@tsjippy/mobile', plugins_url("js/modules/mobile.js", __DIR__), [], STYLEVERSION);

    wp_register_script_module('@tsjippy/modals', plugins_url("js/modules/modals.js", __DIR__), [], STYLEVERSION);

    $deps   = SCRIPT_DEBUG ? [
        "nice-select2"
    ] :
    [];
    wp_register_script_module('@tsjippy/nice_select', plugins_url("js/modules/nice_select.js", __DIR__), $deps, STYLEVERSION);

    wp_register_script_module('@tsjippy/show_loader', plugins_url("js/modules/show_loader.js", __DIR__), [], STYLEVERSION);

    wp_register_script_module('@tsjippy/tabs', plugins_url("js/modules/tabs.js", __DIR__), [], STYLEVERSION);

    /**
     * Scripts
     */

    // Debug requests
    wp_register_script_module('@tsjippy/debug_script', plugins_url("js/debug" . JSEXTENSION, __DIR__), [], STYLEVERSION);

    // Main
    $deps   = SCRIPT_DEBUG ? [  
        '@tsjippy/mobile', 
        "@tsjippy/show_loader", 
        "@tsjippy/display_message", 
        "@tsjippy/modals",
        "@tsjippy/tabs",
        "@tsjippy/nice_select"
    ] :
    [];
    wp_register_script_module('@tsjippy/main', plugins_url("js/main" . JSEXTENSION, __DIR__), $deps, STYLEVERSION);

    //table requests
    $deps   = SCRIPT_DEBUG ? [  
        '@tsjippy/form_submit_functions', 
        "@tsjippy/field_value", 
        "@tsjippy/show_loader",
        "@tsjippy/nice_select"
    ] :
    [];
    wp_register_script_module('@tsjippy/table_script', plugins_url("js/table" . JSEXTENSION, __DIR__), $deps, STYLEVERSION);

    //User Selector
    wp_register_script_module('@tsjippy/user_select_script', plugins_url("js/user_select" . JSEXTENSION, __DIR__), [], STYLEVERSION);
}

/**
 * Enqueues registered scripts
 */
function enqueueScripts()
{
    global $tsjippyEnqueingRunned;

    if ($tsjippyEnqueingRunned) {
        return;
    }
    $tsjippyEnqueingRunned = true;

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