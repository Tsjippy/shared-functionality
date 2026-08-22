<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

/**
 * Schedule a function
 * 
 * @param   string  $taskName   The name to be used for the task
 * @param   string  $recurrence The recurence one of: weekly, monthly, threemonthly, sixmonthly,yearly. Default daily
 * @param   string  $namespace  Target callback namespace
 * @param   string  $callback   The callback for the task
 */
function scheduleTask($taskName, $recurrence, $namespace, $callback)
{
    /**
     * Checks if the action for this task exists
     * Creates it if needed
     */
    if(!has_filter($taskName)){
        $callback   = "$namespace\\$callback";
        add_action($taskName, $callback);
    }

    /**
     * Check if task exists and the same
     */
    $existingTask   = wp_get_scheduled_event($taskName);
    if(
        !empty($existingTask) &&                        // There is an existing task
        (
            $existingTask->schedule == $recurrence ||   // It has the same recurrence
            !$existingTask->schedule                    // Or no recurrence at all
        )
    ){
        return;
    }

    // Clear before re-adding if needed
    if ($existingTask) {
        wp_clear_scheduled_hook($taskName);
    }

    $time    = time();
    switch ($recurrence) {
        case 'weekly':
            $time    = strtotime('next Monday');
            break;
        case 'monthly':
            $time    = strtotime('first day of next month');
            break;
        case 'threemonthly':
            //calculate start of next quarter
            $monthCount = 0;
            $month      = 0;
            while (!isset([1 => 1, 4 => 1, 7 => 1, 10 => 1][$month])) {
                $monthCount++;
                $time    = strtotime("first day of +$monthCount month");
                $month   = gmdate('n', $time);
            }
            break;
        case 'sixmonthly':
            //calculate start of next half year
            $monthCount = 0;
            $month      = 0;
            while (!isset([1 => 1, 7 => 1][$month])) {
                $monthCount++;
                $time    = strtotime("first day of +$monthCount month");
                $month    = gmdate('n', $time);
            }
            break;
        case 'yearly':
            $time    = strtotime('first day of next year');
            break;
        default:
            $time    = time();
    }

    //schedule
    if (wp_schedule_event($time, $recurrence, $taskName)) {
        printArray("Succesfully scheduled $taskName to run $recurrence");
    } else {
        printArray("Scheduling of $taskName unsuccesfull");
    }
}

add_filter('cron_schedules', __NAMESPACE__ . '\addCronSchedule');
/**
 * Adds extra schedule recurrences
 * 
 * @param   array   $schedules  The current recurrences
 */
function addCronSchedule($schedules)
{
    // Adds once every 15 minutes to the existing schedules.
    $schedules['quarterly'] = array(
        'interval'    => 900,
        'display'     => __('Once every 15 minutes', '%TEXTDOMAIN%')
    );

    // Adds once monthly to the existing schedules.
    $schedules['monthly'] = array(
        'interval'    => 2628000,
        'display'     => __('Once every month', '%TEXTDOMAIN%')
    );

    // Adds threemonthly to the existing schedules.
    $schedules['threemonthly'] = array(
        'interval' => 7884000,
        'display' => __('Once every 3 months', '%TEXTDOMAIN%')
    );

    // Adds sixmonthly to the existing schedules.
    $schedules['sixmonthly'] = array(
        'interval'    => 60 * 60 * 24 * 182,
        'display'    => __('Once every 6 months', '%TEXTDOMAIN%')
    );

    // Adds yearly to the existing schedules.
    $schedules['yearly'] = array(
        'interval' => 31557600,
        'display' => __('Once every year', '%TEXTDOMAIN%')
    );

    return $schedules;
}

/**
 * Remove scheduled hooks
 *
 * @param string $plugin Path to the plugin file relative to the plugins directory.
 */
add_action( 'deactivated_plugin', function($plugin){

    foreach( _get_cron_array() as $jobs){
        foreach(array_keys($jobs) as $taskName){
            if(str_contains($taskName, basename($plugin, '.php'))){
                wp_clear_scheduled_hook($taskName);
            }
        }
    }
});

add_action('init', function(){
    scheduleTask('tsjippy-clean-up-db', 'daily', __NAMESPACE__, 'cleanUpErrorDb');
});

/**
 * Runs the error db clean up
 */
function cleanUpErrorDb(){
    $logger = new Logger();

    $logger->tidyTable();
}