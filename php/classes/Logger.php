<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

class Logger
{
    public string $tableName;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;

        $this->tableName = $wpdb->prefix . 'tsjippy_logs';
    }

    /**
     * Creates the table
     */
    public function createDbTable()
    {
        if (!function_exists('maybe_create_table')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        //only create db if it does not exist
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        //Main table
        $sql = "CREATE TABLE $this->tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            time_stamp text,
            level text,
            message text,
            caller text,
            url text
       ) $charsetCollate;";

        maybe_create_table($this->tableName, $sql);
    }

    /**
     * Adds a new entry to the log table
     * 
     * @param   int     $timeStamp
     * @param   string  $level
     * @param   string  $message
     * @param   string  $caller
     * @param   string  $url
     */
    public function insertData($timeStamp, $level, $message, $caller, $url)
    {
        $ignores    = get_option('tsjippy-logs-ignore', []);

        if (isset($ignores[$message])) {
            return true;
        }

        /**
         * Insert the new one
         */
        return insertInDb(
            $this->tableName,
            array(
                'time_stamp'    => $timeStamp,
                'level'         => $level,
                'message'       => str_replace(["\n", "\t"], ["<br>", '    '], $message),
                'caller'        => $caller,
                'url'           => $url
            ),
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s'
            ],
            'logger'
        );
    }

    /**
     * Remove a log entry
     * 
     * @param   int $id
     */
    public function removeEntry($id)
    {
        removeFromDb(
            $this->tableName,
            ['id' => $id],
            ['%d'],
            'logger'
        );

        return true;
    }

    /**
     * Find a log entry by message
     * 
     * @param   int $id
     */
    public function getMessage($id)
    {
        $message    = getFromDb(
            "get_message_$id",
            "logger",
            "SELECT message FROM %i where id = %d LIMIT 1",
            $this->tableName,
            $id
        );

        return $message;
    }

    /**
     * Get the caller of a log entry
     * 
     * @param   int $id
     */
    public function getCaller($id)
    {
        global $wpdb;

        // phpcs:disable
        $caller    = getFromDb(
            "get_caller_id_$id",
            "logger",
            "SELECT caller FROM %i where id = %d LIMIT 1",
            $this->tableName,
            $id
        );
        // phpcs:enable

        if (!empty($wpdb->last_error)) {
            return new \WP_Error('bookings', $wpdb->last_error);
        }

        return $caller;
    }

    /**
     * Get all logs starting from an id
     * 
     * @param   int $id     Starting id
     * @param   int $page   The page
     */
    public function getLogs($id, $page = 0)
    {
        global $wpdb;

        // phpcs:disable
        $results    = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i where id > %d ORDER BY id DESC LIMIT 100 OFFSET %d",
                $this->tableName,
                $id,
                $page * 100
            )
        );
        // phpcs:enable

        if (!empty($wpdb->last_error)) {
            return new \WP_Error('bookings', $wpdb->last_error);
        }

        return $results;
    }

    /**
     * Only keep unique messages
     */
    public function tidyTable(){
        global $wpdb;

        $wpdb->query("
            DELETE t1 
                FROM $this->tableName t1
                LEFT JOIN (
                    SELECT caller, MAX(time_stamp) as max_date
                    FROM $this->tableName
                    GROUP BY caller
                ) t2 ON t1.caller = t2.caller AND t1.time_stamp = t2.max_date
                WHERE t2.caller IS NULL;"
        );
    }

    /**
     * Clear the log table
     */
    public function clearLogs()
    {
        global $wpdb;

        // Empty table
        $wpdb->query($wpdb->prepare("TRUNCATE TABLE %i", $this->tableName));

        /**
         * Flush db cache
         */
        if(wp_cache_supports( 'flush_group' )){
            wp_cache_flush_group('logger');
        }else{
            wp_cache_flush();
        }
    }
}
