<?php

namespace TSJIPPY\ADMIN;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

function updatePlugin($pluginFile)
{
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    include_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
    $plugin_Upgrader    = new \Plugin_Upgrader(new \Plugin_Installer_Skin(compact('title', 'url', 'nonce', 'plugin', 'api')));
    $plugin_Upgrader->upgrade($pluginFile);
    activate_plugin($pluginFile);

    wp_clean_plugins_cache();
}

/**
 * Installs a plugin using the wp api for that
 *
 * @param    string    $pluginFile        The relative path of the plugin file
 *
 * @return    boolean|string            true if already activated. Result if installed or activated
 */
function installPlugin($pluginFile)
{
    //check if plugin is already installed
    $plugins        = get_plugins();
    $activePlugins    = get_option('active_plugins');
    $pluginName        = str_replace('.php', '', explode('/', $pluginFile)[1]);
    $pluginSlug        = str_replace('.php', '', explode('/', $pluginFile)[0]);

    if (in_array($pluginFile, $activePlugins)) {
        // Already installed and activated
        return true;
    } elseif (isset($plugins[$pluginFile])) {
        // Installed but not active
        activate_plugin($pluginFile);

        TSJIPPY\storeInTransient('plugin', ['activated' => $pluginName]);

        wp_clean_plugins_cache();

        return 'Activated';
    }

    ob_start();
    include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');

    $api = plugins_api('plugin_information', array(
        'slug' => $pluginSlug,
        'fields' => array(
            'short_description' => false,
            'sections'             => false,
            'requires'             => false,
            'rating'             => false,
            'ratings'             => false,
            'downloaded'         => false,
            'last_updated'         => false,
            'added'             => false,
            'tags'                 => false,
            'compatibility'     => false,
            'homepage'             => false,
            'donate_link'         => false,
        ),
    ));

    if (is_wp_error($api)) {
        return ob_get_clean();
    }

    //includes necessary for Plugin_Upgrader and Plugin_Installer_Skin
    include_once(ABSPATH . 'wp-admin/includes/file.php');
    include_once(ABSPATH . 'wp-admin/includes/misc.php');
    include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

    $upgrader = new \Plugin_Upgrader(new \Plugin_Installer_Skin(compact('title', 'url', 'nonce', 'plugin', 'api')));

    $upgrader->install($api->download_link);

    activate_plugin($pluginFile);

    TSJIPPY\storeInTransient('plugin', ['installed' => $pluginName]);

    session_write_close();

    printJs();

    return ob_get_clean();
}

function printJs()
{
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.wrap').remove();
            document.getElementById('wpfooter').remove();
        });
    </script>
<?php
}
