<?php

namespace TSJIPPY;

use TSJIPPY\ADMIN;

use function TSJIPPY\addRawHtml;

if (! defined('ABSPATH')) {
    exit;
}

class AdminMenu extends ADMIN\SubAdminMenu
{

    /**
     * AdminMenu constructor.
     *
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name)
    {
        parent::__construct($settings, $name);
    }

    public function settings($parent)
    {
        return false;
    }

    public function emails($parent)
    {
        return false;
    }

    public function data($parent)
    {
        wp_enqueue_script('tsjippy-logs', pathToUrl(__DIR__ . '/../../js/logs.min.js'), ['tsjippy_formsubmit_script'], STYLEVERSION, true);

        ob_start();

    ?>
        Log Type<br>
        <label>
            <input type='radio' name='log-level' id='error' value='error'>
            <span>Error</span>
        </label>
        <label>
            <input type='radio' name='log-level' id='warning' value='warning'>
            <span>Warning</span>
        </label>
        <label>
            <input type='radio' name='log-level' id='info' value='info' checked>
            <span>Info</span>
        </label>
        <br>
        <button type='button' class='tsjippy button' id='clear-logs' data-nonce='<?php echo esc_attr(wp_create_nonce('delete_logs')); ?>'>
            Clear Logs
        </button>

        <div class="logs-wrapper" style='width:1000px;' data-nonce='<?php echo esc_attr(wp_create_nonce('update_logs')); ?>'>
            <div style='width:500px;'>
                <div class="loader-image-trigger" data-size="50" data-text="Fetching the logs... "></div>
            </div>
        </div>

<?php
        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function functions($parent)
    {
        return false;
    }

    /**
     * Schedules the tasks for this plugin
     *
     */
    public function postSettingsSave()
    {
        return true;
    }
}
