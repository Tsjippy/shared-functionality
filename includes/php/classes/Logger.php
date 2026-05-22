<?php
namespace TSJIPPY;

use function TSJIPPY\SIGNAL\getSignalInstance;

if ( ! defined( 'ABSPATH' ) ) exit;

class Logger{
    public string $tableName;

    public function __construct(){
        global $wpdb;

        $this->tableName = $wpdb->prefix.'tsjippy_logs';
    }

    public function createDbTable(){
        if ( !function_exists( 'maybe_create_table' ) ) {
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

        maybe_create_table($this->tableName, $sql );
    }

    public function insertData($timeStamp, $level, $message, $caller){
        global $wpdb;

        $wpdb->insert(
            $this->tableName,
            array(
                'time_stamp'	=> $timeStamp,
                'level'		    => $level,
                'message'	    => $message,
                'caller'	    => $caller
            )
        );

        if(!empty($wpdb->last_error)){
			return new \WP_Error('bookings', $wpdb->last_error);
		}

		return $wpdb->insert_id;
    }

    public function removeEntry($id){
        global $wpdb;

        $wpdb->delete(
			$this->tableName,
			['id' => $id],
			['%d'],
		);

        if(!empty($wpdb->last_error)){
			return new \WP_Error('bookings', $wpdb->last_error);
		}

		return true;
    }

    public function removeSimilarEntries($id){
        global $wpdb;

        $message    = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT message FROM %i where id = %d",
                $this->tableName,
                $id
            )
        );

        if(!empty($wpdb->last_error)){
			return new \WP_Error('bookings', $wpdb->last_error);
		}

        $wpdb->delete(
			$this->tableName,
			['message' => $message],
			['%s'],
		);

        if(!empty($wpdb->last_error)){
			return new \WP_Error('bookings', $wpdb->last_error);
		}

		return true;
    }

    public function getLogs($timeStamp, $page=0){
        global $wpdb;

        $results    = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i where time_stamp > %d ORDER BY time_stamp DESC LIMIT 100 OFFSET %d",
                $this->tableName,
                $timeStamp,
                $page * 100
            )
        );

        if(!empty($wpdb->last_error)){
			return new \WP_Error('bookings', $wpdb->last_error);
		}

        return $results;
    }

    public function clearLogs(){
        global $wpdb;

        // Empty table
        $wpdb->query("TRUNCATE TABLE $this->tableName");

        // Remove Files
        global $wp_filesystem;
        include_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();

        $filepath = WP_CONTENT_DIR.'/notice.log';
        $wp_filesystem->delete( $filepath );

        $filepath = WP_CONTENT_DIR.'/debug.log';
        $wp_filesystem->delete( $filepath );
    }
}