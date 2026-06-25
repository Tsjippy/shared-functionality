<?php

namespace TSJIPPY\ADMIN;

use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('rest_api_init', function () {
    //Route for first names
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX,
        '/get-changelog',
        array(
            'methods'             => 'POST',
            'callback'            => __NAMESPACE__ . '\getChangelog',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'args'                => array(
                'plugin-name'     => array(
                    'required'    => true
                )
            )
        )
    );
});

function getChangelog()
{
    if (empty($_POST['plugin-name'])) {
        return;
    }

    $github        = new TSJIPPY\GITHUB\Github();

    $pluginName = TSJIPPY\sanitize($_POST['plugin-name']);

    $release    = $github->getFileContents('tsjippy', $pluginName, 'CHANGELOG.md');
    if ($release) {
        return $release;
    }

    return "Unable to fetch changelog";
}
