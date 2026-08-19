<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

// disable auto updates for this plugin on localhost
add_filter('auto_update_plugin', __NAMESPACE__ . '\disableAutoUpdate', 10, 2);
/** 
 * Disable auto-updates for the specified plugin on localhost.
 * 
 * @param bool $value The current auto-update status.
 * @param object $item The plugin item object.
 * 
 * @return bool The modified auto-update status.
*/
function disableAutoUpdate($value, $item)
{
    if ('tsjippy-shared-functionality' === $item->slug && (wp_get_environment_type() === 'local')) {
        return false; // disable auto-updates for the specified plugin
    }

    return $value; // Preserve auto-update status for other plugins
}

/**
 * Checks for bad behaving scheduled tasks
 */
function testScheduledTasks(){
    $cronJobs = get_option( 'cron' );

    foreach($cronJobs as $jobs){
        foreach($jobs as $hookName => $data){
            if(!str_contains($hookName, 'tsjippy') || $hookName == 'tsjippy-signal-process-queue'){
                continue;
            }

            foreach($data as $d){
                if(empty($d['schedule'])){
                    continue;
                }
            }

            echo "Processing $hookName Started<br>";

            $start = microtime(true);

            try{
                do_action($hookName);
            } catch (\Throwable $e) {
                printArray($e);

                printArray(generateStackTrace());

                echo $e->getMessage();
            }

            $duration   = microtime(true) - $start;

            if($duration > 5){
                printArray("Execution of $hookName took $duration seconds");
            }
            echo "Processing $hookName Finished, took $duration seconds<br>";
        }
    }
}

//Shortcode for testing
add_shortcode("tsjippy_test", function ($atts) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    //testScheduledTasks();

    
});

// turn off incorrect error on localhost
add_filter('wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false');
