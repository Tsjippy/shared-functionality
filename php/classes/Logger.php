<?php

namespace TSJIPPY;

use function TSJIPPY\SIGNAL\getSignalInstance;

if (! defined('ABSPATH')) exit;

class Logger
{
    public string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName = $wpdb->prefix . 'tsjippy_logs';
    }

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
            caller text
       ) $charsetCollate;";

        maybe_create_table($this->tableName, $sql);
    }

    public function insertData($timeStamp, $level, $message, $caller)
    {
        $ignores    = get_option('tsjippy-logs-ignore', []);

        if (in_array($message, $ignores)) {
            return true;
        }

        global $wpdb;

        /**
         * Keep the db small
         */
        $rowCount   = TSJIPPY\getFromDb(
            "get_row_count",
            "logger",
            "SELECT COUNT(*) FROM %i",
            $this->tableName
        );

        if($rowCount > 1000){
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM %i WHERE id NOT IN ( SELECT MIN(id) FROM %i GROUP BY caller",
                    $this->tableName,
                    $this->tableName
                )
            );
        }

        /**
         * Insert the new one
         */
        // phpcs:disable
        $wpdb->insert(
            $this->tableName,
            array(
                'time_stamp'    => $timeStamp,
                'level'         => $level,
                'message'       => str_replace(["\n", "\t"], ["<br>", '    '], $message),
                'caller'        => $caller
            )
        );
        // phpcs:enable

        if (!empty($wpdb->last_error)) {
            return new \WP_Error('bookings', $wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    public function removeEntry($id)
    {
        global $wpdb;

        // phpcs:disable
        $wpdb->delete(
            $this->tableName,
            ['id' => $id],
            ['%d'],
        );
        // phpcs:enable

        if (!empty($wpdb->last_error)) {
            return new \WP_Error('bookings', $wpdb->last_error);
        }

        return true;
    }

    public function getMessage($id)
    {
        global $wpdb;

        // phpcs:disable
        $message    = TSJIPPY\getFromDb(
            "get_message_$id",
            "logger",
            "SELECT message FROM %i where id = %d LIMIT 1",
            $this->tableName,
            $id
        );
        // phpcs:enable

        if (!empty($wpdb->last_error)) {
            return new \WP_Error('bookings', $wpdb->last_error);
        }

        return $message;
    }

    public function getCaller($id)
    {
        global $wpdb;

        // phpcs:disable
        $caller    = TSJIPPY\getFromDb(
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

    public function removeSimilarEntries($id)
    {
        global $wpdb;

        $caller    = $this->getCaller($id);

        // phpcs:disable
        $wpdb->delete(
            $this->tableName,
            ['caller' => $caller],
            ['%s'],
        );
        // phpcs:enable

        if (!empty($wpdb->last_error)) {
            return new \WP_Error('bookings', $wpdb->last_error);
        }

        return true;
    }

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

    public function clearLogs()
    {
        global $wpdb;

        // Empty table
        $wpdb->query($wpdb->prepare("TRUNCATE TABLE %i", $this->tableName));
    }
}
