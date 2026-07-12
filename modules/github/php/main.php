<?php

namespace TSJIPPY\GITHUB;

use TSJIPPY;
use Github\Exception\ApiLimitExceedException;
use Github\Client;

if (! defined('ABSPATH')) exit;

require(__DIR__  . '/../lib/vendor/autoload.php');

// https://github.com/KnpLabs/php-github-api     -- github api
// https://github.com/michelf/php-markdown        -- convert markdown to html

/**
 * Adds a custom description to the plugin in the plugin page
 */
add_filter('plugins_api', __NAMESPACE__ . '\customDescription', 10, 3);
/**
 * Customizes the plugin description for the plugin page
 * @param mixed $res The plugin information
 * @param string $action The action being performed
 * @param object $args The arguments for the action
 * @return mixed The modified plugin information
 */
function customDescription($res, $action, $args)
{
    // do nothing if you're not getting plugin information or this is not our plugin
    if ('plugin_information' !== $action || !str_contains($args->slug, 'tsjippy-')) {
        return $res;
    }

    $repo       = str_replace('tsjippy-', '', $args->slug);
    $nameSpace  = str_replace('-', '', strtoupper($repo));

    if($nameSpace == 'SHAREDFUNCTIONALITY' || !defined("TSJIPPY\\$nameSpace\PLUGIN")){
        return $res;
    }

    $github                 = new Github();
    return $github->pluginData(
        constant("TSJIPPY\\$nameSpace\PLUGINPATH").basename(constant("TSJIPPY\\$nameSpace\PLUGIN")), 
        'tsjippy', 
        $repo, 
        [
            'active_installs'    => 2,
            'donate_link'        => 'harmseninnigeria.nl',
            'rating'             => 5,
            'ratings'            => [4, 5, 5, 5, 5, 5],
            'banners'            => [
                'high'   => TSJIPPY\PICTURESURL . "/banner-1544x500.jpg",
                'low'    => TSJIPPY\PICTURESURL . "/banner-772x250.jpg"
            ],
            'tested'             => '6.6.2'
        ]
    );
}

/**
 * Checks and shows plugin updates from github
 */
add_filter('pre_set_site_transient_update_plugins', __NAMESPACE__ . '\showPluginUpdate');
/**
 * Adds updates to the site transient for all Tsjippy plugins
 * @param mixed $transient  Transient name. Expected to not be SQL-escaped. Must be 167 characters or fewer in length.
 */
function showPluginUpdate($transient)
{
    $github            = new Github();

    /**
     * Check for plugin updates for each of the tsjippy plugins
     */
    foreach (wp_get_active_and_valid_plugins() as $plugin) {
        // Only add submenu for tsjippy plugins
        if (strpos($plugin, 'tsjippy-') === false) {
            continue;
        }

        $repo    = str_replace('tsjippy-', '', basename($plugin, '.php'));

        $item    = $github->getVersionInfo($plugin, 'tsjippy', $repo);

        if (!is_object($item)) {
            return $transient;
        }

        // Git has a newer version
        if (isset($item->new_version)) {
            $transient->response[plugin_basename($plugin)]    = $item;
        } else {
            $transient->no_update[plugin_basename($plugin)]    = $item;
        }
    }

    return $transient;
}

define(__NAMESPACE__ . '\SETTINGS', get_option('tsjippy_github_settings', []));

add_filter('upgrader_pre_download', function ($reply, $package, $upgrader, $args) {
    if (str_contains($package, "https://github.com/Tsjippy/")) {
        $github        = new Github();

        $fileName    = basename($package);

        $repo        = str_replace(['tsjippy-', '.zip'], '', $fileName);

        $path        = $github->downloadRelease('tsjippy', $repo, $fileName, false, true);

        if (is_wp_error($path)) {
            TSJIPPY\printArray($path->get_error_message());
            return $reply;
        }

        if (file_exists($path)) {
            return $path;
        }
    }

    return $reply;
}, 10, 4);

add_action('admin_menu', function () {

    // Sub menu for Github
    add_submenu_page(
        'tsjippy',
        'Github',
        'Github',
        "edit_others_posts",
        'tsjippy-github',
        function () {
            $mainAdminMenu = new TSJIPPY\ADMIN\MainAdminMenu();
            $mainAdminMenu->buildSubMenu('Github', 'github');
        },
        1
    );
}, 12);
