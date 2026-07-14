<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

/**
 * Prints html properly outlined for easy debugging
 */
function printHtml($html)
{
    $tabs    = 0;

    // Split on the < symbol to get a list of opening and closing tags
    $html        = explode('>', $html);
    $newHtml    = '';

    // loop over the elements
    foreach ($html as $index => $el) {
        $el = trim($el);

        if (empty($el)) {
            continue;
        }

        // Split the line on a closing character </
        $lines    = explode('</', $el);

        if (!empty($lines[0])) {
            $newHtml    .= "\n";

            // write as many tabs as need
            for ($x = 0; $x <= $tabs; $x++) {
                $newHtml    .= "\t";
            }

            // then write the first element
            $newHtml    .= $lines[0];
        }

        if (
            substr($el, 0, 1) == '<' &&                         // Element start with an opening symbol
            substr($el, 0, 2) != '</' &&                         // It does not start with a closing symbol
            substr($el, 0, 6) != '<input' &&                     // It does not start with <input (as that one does not have a closing />)
            (
                substr($el, 0, 7) != '<option' ||                 // It does not start with <option (as that one does not have a closing />)
                str_contains($html[$index + 1], '</option')         // or the next element contains a closing option
            ) &&
            $el != '<br'
        ) {
            $tabs++;
        }

        if (isset($lines[1])) {
            $tabs--;

            $newHtml    .= "\n";

            for ($x = 0; $x <= $tabs; $x++) {
                $newHtml    .= "\t";
            }
            $newHtml    .= '</' . $lines[1] . '>';
        } else {
            $newHtml    .= '>';
        }
    }

    printArray($newHtml);
}

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

    testScheduledTasks();

    $posts = get_posts(
        array(
            'post_type'   => 'any',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => array(
                'relation' => 'AND',
                array(
                    'key'     => 'tsjippy_date',
                    'compare' => 'EXISTS',
                )
            )
        )
    );

    foreach($posts as $post){
        $date   = get_post_meta($post->ID, 'tsjippy_date', true);
        delete_post_meta($post->ID, 'tsjippy_date');

        update_post_meta($post->ID, 'tsjippy_date_'.$date, $date);
    }

    $wpdb->query("delete FROM `wp_usermeta` where meta_key = 'tsjippy_account-type' and meta_value <> 'positional'");

    $wpdb->query("delete FROM `wp_postmeta` where meta_key = 'tsjippy_gallery_visibility' and meta_value <> 'hide' ");
});

// turn off incorrect error on localhost
add_filter('wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false');
