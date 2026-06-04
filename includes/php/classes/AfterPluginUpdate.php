<?php
namespace TSJIPPY;

if ( ! defined('ABSPATH')) exit;

abstract class AfterPluginUpdate {
    public function __construct() {

    }

    /**
     * Runs when a Tsjippy plugin was updated
     * @param   object  $upgraderObject     The upgrader object
     * @param   array   $options            The options array
     */
    public function upgradeSucces($upgraderObject, $options) {
        // If an update has taken place and the updated type is plugins and the plugins element exists
        if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins'])) {
            foreach ( $options['plugins'] as $plugin) {
                // Check to ensure it's a tsjippy plugin

                if ( str_contains($plugin, 'tsjippy-')) {
                    $slug = str_replace(['tsjippy-', '-'], '', basename($plugin, ' .php'));

                    error_log("Scheduling update actions for {$slug}");

                    $oldVersion = $upgraderObject->skin->plugin_info['Version'];

                    wp_schedule_single_event(time() + 10, 'schedule_tsjippy_plugin_update_action', [ $slug, $oldVersion ]);
                }
            }
        }
    }

    /**
     * Runs actions after a plugin update
     *
     * @param   string  $oldVersion     The old version string
     */
    abstract public function afterPluginUpdate($oldVersion);
}