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
        wp_enqueue_script('tsjippy_nonce_script', plugins_url('js/nonce.min.js', __DIR__), [], STYLEVERSION, false);
        wp_localize_script(
            'tsjippy_nonce_script',
            'tsjippy',
            array(
                'baseUrl'         => get_home_url(),
                'restApiPrefix'    => '/' . RESTAPIPREFIX,
                'restNonce'        => wp_create_nonce('wp_rest')
            )
        );
    }

    //LIBRARIES
    //selectable select table cells https://github.com/Mobius1/Selectable
    wp_register_script('selectable', plugins_url('js/selectable.min.js', __DIR__), array(), '0.22.0', true);

    //add main.js
    wp_register_script('tsjippy_script', plugins_url('js/main.min.js', __DIR__), [], STYLEVERSION, true);

    // purify library
    wp_register_script('tsjippy_purify', plugins_url('js/purify.min.js', __DIR__), array(), '3.4.8', true);

    //Submit forms
    wp_register_script('tsjippy_user_select_script', plugins_url('js/user_select.min.js', __DIR__), [], STYLEVERSION, true);
    wp_register_script('tsjippy_formsubmit_script', plugins_url('js/formsubmit.min.js', __DIR__), array('tsjippy_script'), STYLEVERSION, true);

    //table request shortcode
    wp_register_script('tsjippy_table_script', plugins_url('js/table.min.js', __DIR__), array('tsjippy_formsubmit_script'), STYLEVERSION, true);

    // Debug request shortcode
    wp_register_script('tsjippy_debug_script', plugins_url('js/debug.js', __DIR__), [], STYLEVERSION, false);

    wp_localize_script(
        'tsjippy_script',
        'tsjippy',
        array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            "userId"        => wp_get_current_user()->ID,
            'baseUrl'       => get_home_url(),
            'maxFileSize'   => wp_max_upload_size(),
            'restApiPrefix' => '/' . RESTAPIPREFIX,
            'restNonce'     => wp_create_nonce('wp_rest')
        )
    );

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

    wp_enqueue_script('tsjippy_script');

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
