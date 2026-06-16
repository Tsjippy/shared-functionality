<?php

namespace TSJIPPY\GITHUB;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

add_action('init', __NAMESPACE__ . '\init');
function init()
{
    //add action for use in scheduled task
    add_action('update_plugin_action', __NAMESPACE__ . '\checkForPluginUpdates');
}

function scheduleTasks()
{
    TSJIPPY\scheduleTask('update_plugin', 'daily');
}

function checkForPluginUpdates()
{

    // Do not run on localhost
    if (wp_get_environment_type() === 'local') {
        return;
    }

    // Now check for plugin updates
    $github    = new Github();
    foreach (wp_get_active_and_valid_plugins() as $plugin) {

        if (strpos($plugin, 'tsjippy-') === false) {
            continue;
        }

        $slug       = str_replace('tsjippy-', '', basename($plugin, '.php'));
        $nameSpace    = str_replace('-', '', strtoupper($slug));

        if ($nameSpace == 'SHAREDFUNCTIONALITY') {
            $oldVersion    = constant("TSJIPPY\\STYLEVERSION");
        } else {
            $oldVersion    = constant("TSJIPPY\\$nameSpace\\PLUGINVERSION");
        }

        $release    = $github->getLatestRelease('Tsjippy', $slug, true);

        if (is_wp_error($release)) {
            $errorMessage    = $release->get_error_message();
            TSJIPPY\printArray("Error checking for update for plugin $slug: $errorMessage");
            TSJIPPY\printArray($errorMessage);
            TSJIPPY\printArray($release);

            if (
                $errorMessage == 'You have triggered an abuse detection mechanism. Please wait a few minutes before you try again. ' ||
                str_contains($errorMessage, 'You have reached GitHub hourly limit!')
            ) {
                return;
            }
            continue;
        }

        $newVersion    = $release['tag_name'];

        // Download the new version
        if (version_compare($newVersion, $oldVersion) === 1) {
            TSJIPPY\printArray("Updating $slug");

            $github->downloadRelease('Tsjippy', $slug);
        }
    }
}
