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
        $ignores	= get_option('tsjippy-logs-ignore', []);

		if(in_array($message, $ignores)){
            return true;
        }
        
        global $wpdb;

        $wpdb->insert(
            $this->tableName,
            array(
                'time_stamp'	=> $timeStamp,
                'level'		    => $level,
                'message'	    => str_replace(["\n", "\t"], ["<br>", '    '], $message),
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

    public function getMessage($id){
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

        return $message;
    }
    
    public function getCaller($id){
        global $wpdb;

        $caller    = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT caller FROM %i where id = %d",
                $this->tableName,
                $id
            )
        );

        if(!empty($wpdb->last_error)){
			return new \WP_Error('bookings', $wpdb->last_error);
		}

        return $caller;
    }

    public function removeSimilarEntries($id){
        global $wpdb;

        $caller    = $this->getCaller($id);

        $wpdb->delete(
			$this->tableName,
			['caller' => $caller],
			['%s'],
		);

        if(!empty($wpdb->last_error)){
			return new \WP_Error('bookings', $wpdb->last_error);
		}

		return true;
    }

    public function getLogs($id, $page=0){
        global $wpdb;

        $results    = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i where id > %d ORDER BY id DESC LIMIT 100 OFFSET %d",
                $this->tableName,
                $id,
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
        $wpFileSystem   = TSJIPPY\loadWpFileSystem();

        $filepath = WP_CONTENT_DIR.'/notice.log';
        $wpFileSystem->delete( $filepath );

        $filepath = WP_CONTENT_DIR.'/debug.log';
        $wpFileSystem->delete( $filepath );
    }
}