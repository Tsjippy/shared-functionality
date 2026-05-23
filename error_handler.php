<?php
namespace TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function(){
	// Sub menu for Logs
	add_submenu_page(
		'tsjippy', 
		'Logs', 
		'Logs', 
		"edit_others_posts", 
		'tsjippy-logs', 
		function(){
			$mainAdminMenu = new ADMIN\MainAdminMenu();
			$mainAdminMenu->buildSubMenu('Logs', 'logs');
		},
		1
	);
}, 20);

function hasPermission(){
	$user = wp_get_current_user();
	return array_intersect(get_option('tsjippy_logs_settings', [])['roles'] ?? ['administrator'], (array) $user->roles );
}

add_action( 'rest_api_init', __NAMESPACE__.'\restApiInitDev');
function restApiInitDev() {
	register_rest_route( 
		RESTAPIPREFIX, 
		'/get_logs', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\getLogs',
			'permission_callback' 	=> __NAMESPACE__.'\hasPermission',
			'args'					=> array(
				'timestamp'		=> array(
					'required'	=> true,
					'validate_callback' => function($timestamp){
						return is_numeric($timestamp);
					}
				),
				'nonce'		=> array(
					'required'	=> true,
					'validate_callback' => function($nonce){
						return wp_verify_nonce($nonce, 'update_logs');
					}
				),
				'page' => []
			)
		)
	);

	register_rest_route( 
		RESTAPIPREFIX, 
		'/clear_logs', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=>__NAMESPACE__.'\clearLogs',
			'permission_callback' 	=> __NAMESPACE__.'\hasPermission',
			'args'					=> array(
				'nonce'		=> array(
					'required'	=> true,
					'validate_callback' => function($nonce){
						return wp_verify_nonce($nonce, 'delete_logs');
					}
				)
			)
		)
	);

	register_rest_route( 
		RESTAPIPREFIX, 
		'/delete_log_entry', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=>__NAMESPACE__.'\removeEntry',
			'permission_callback' 	=> __NAMESPACE__.'\hasPermission',
			'args'					=> array(
				'id'		=> array(
					'required'	=> true,
					'validate_callback' => function($id){
						return is_numeric($id);
					}
				),
				'nonce'		=> array(
					'required'	=> true,
					'validate_callback' => function($nonce){
						return wp_verify_nonce($nonce, 'delete_log_entry');
					}
				)
			)
		)
	);

	register_rest_route( 
		RESTAPIPREFIX, 
		'/delete_similar_log_entry', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=>__NAMESPACE__.'\removeSimilarEntries',
			'permission_callback' 	=> __NAMESPACE__.'\hasPermission',
			'args'					=> array(
				'id'		=> array(
					'required'	=> true,
					'validate_callback' => function($id){
						return is_numeric($id);
					}
				),
				'nonce'		=> array(
					'required'	=> true,
					'validate_callback' => function($nonce){
						return wp_verify_nonce($nonce, 'delete_log_entry');
					}
				)
			)
		)
	);

	register_rest_route( 
		RESTAPIPREFIX, 
		'/ignore_log_entry', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=>__NAMESPACE__.'\storeIgnore',
			'permission_callback' 	=> __NAMESPACE__.'\hasPermission',
			'args'					=> array(
				'id'		=> array(
					'required'	=> true,
					'validate_callback' => function($id){
						return is_numeric($id);
					}
				),
				'nonce'		=> array(
					'required'	=> true,
					'validate_callback' => function($nonce){
						return wp_verify_nonce($nonce, 'ignore_log_entry');
					}
				)
			)
		)
	);
}

function shutdown() {
    $error = error_get_last();
    if(!empty($error)){
		printError( $error['type'], $error['message'], $error['file'], $error['line'] );
	}
}
register_shutdown_function(__NAMESPACE__.'\shutdown');

/**
 * Prints error messages
 *
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 */
function printError( $errno, $errstr, $errfile, $errline ) {
	if(in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])){
		$type = 'error';
	}elseif(in_array($errno, [E_WARNING, E_NOTICE, E_CORE_WARNING, E_COMPILE_WARNING, E_DEPRECATED, E_USER_WARNING, E_USER_DEPRECATED])){
		$type = 'warning';
	}else{
		$type = 'info';
	}

    if( 
        !str_contains($errstr, '_load_textdomain_just_in_time') &&
        !str_contains($errfile, '/lib/vendor/')
    ) {

        $message = 'You have an error notice: "%s" in file "%s" at line: "%s".' ;
        $message = sprintf($message, $errstr, $errfile, $errline);

		// Store in file
        error_log(print_r($message, true));
        error_log("\n".print_r(generateStackTrace(), true)."\n");

		// Store in db
		$logger = new Logger();
		$logger->insertData(time(), $type, $errstr, str_replace("\n", "<br>", generateStackTrace()));
    }
}
set_error_handler(__NAMESPACE__.'\printError');

// Function from php.net https://php.net/manual/en/function.debug-backtrace.php#112238
function generateStackTrace() {

    $e = new \Exception();

    $trace = explode( "\n" , $e->getTraceAsString() );

    // reverse array to make steps line up chronologically
    $trace = array_reverse($trace);

    array_shift($trace); // remove {main}
    array_pop($trace); // remove call to this method

    $length = count($trace);
    $result = array();

    for ($i = 0; $i < $length; $i++) {
        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
    }

    $result = implode("\n", $result);;

    return $result;
}

/**
 * Prints something to the log file and optional to the screen
 * @param 	string		$message	 			The message to be printed
 * @param	bool		$display				Whether to print the message to the screen or not
 * @param	bool|int	$printFunctionHiearchy	Whether to print the full backtrace, false for not printing, true for all, number for max depth
*/
function printArray($message, $display=false, $printFunctionHiearchy=false, $error=false){
	$logger = new Logger();

	$bt		= debug_backtrace();

	if($error){
		$type 			= 0;
		$destination 	= null;
		$level			= 'error';
	}else{
		$type 			= 3;
		$destination	= WP_CONTENT_DIR.'/notice.log';
		$level			= 'info';
	}

	$path	= '';

	if($printFunctionHiearchy){
		error_log("Called from:", $type, $destination);
		$caller	= "";
		foreach($bt as $index => $trace){
			// stop if we have reached the max depth
			if(is_numeric($printFunctionHiearchy) && $index == $printFunctionHiearchy){
				break;
			}
			
			$path	= str_replace(PLUGINPATH, '', $trace['file']);
			$line	= $trace['line'];

			$caller		.= "$index\n";
			$caller		.= "    File: $path\n";
			$caller		.= "    Line $line\n";
			$caller		.= "    Function: {$trace['function']}\n";
			$caller		.= "    Args:\n";
			$caller		.= "    ".print_r($trace['args'], true);
		}

		error_log($caller, $type, $destination);
	}else{
		$caller = array_shift($bt);
		$path	= str_replace(PLUGINPATH, '', $caller['file']);
		$line	= $caller['line'];

		$caller	= "Called from file $path line $line\n";
		error_log($caller, $type, $destination);
	}

	if(is_array($message) || is_object($message)){
		$messageWithDate = $message	= print_r($message, true);
	}else{
		$messageWithDate	= gmdate('Y-m-d H:i:s', time()).' - '.$message."\n";
	}

	$logger->insertData(time(), $level, $message, $caller);

	error_log($messageWithDate, $type, $destination);
	
	if($display){
		?>
		<pre>
			Called from <?php echo esc_html($caller);?>
			<br>
			<br>
			<?php 
			echo wp_kses_post(print_r($message));
			?>
		</pre>
		<?php
	}
}

/**
 * Deletes a specific log entry
 */
function removeEntry($wpRest){
	$logger	= new Logger();
	$result	= $logger->removeEntry($wpRest->get_param('id'));

	return $result;
}

/**
 * Deletes a files  from the content dir
 */
function clearLogs(){
	$logger	= new Logger();
	$logger->clearLogs();

	return true;
}

/**
 * Retrieves a files contents from the content dir
 * 
 * @param	string	$fileName	the filename					
 */
function getLog($fileName){
	global $wp_filesystem;

	include_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();

	$filePath = WP_CONTENT_DIR.'/'.$fileName;
	if(!file_exists($filePath)){
		return 'There is nothing to show';
	}

	$fileHandle = fopen( $filePath, 'r' ); 
    if ( $fileHandle ) {
        while ( ( $line = fgets( $fileHandle ) ) !== false ) {
            yield trim( $line ); // Memory is maintained per line
        }
        fclose( $fileHandle );
    }
}

function logToHtml($logData){
	ob_start();

	foreach($logData as $value){
		$date	= gmdate(DATEFORMAT.' H:i:s', $value->time_stamp);

		?>
		<div class='log-block' data-level='<?php echo esc_attr($value->level);?>'>
			<b><?php echo esc_html($date);?></b>
			<button class="button tsjippy small delete-message" data-id="<?php echo esc_attr($value->id);?>" data-nonce="<?php echo esc_attr(wp_create_nonce('delete_log_entry'));?>">
				Delete
			</button>
			<button class="button tsjippy small delete-similar" data-id="<?php echo esc_attr($value->id);?>" data-nonce="<?php echo esc_attr(wp_create_nonce('delete_log_entry'));?>">
				Delete All Similar
			</button>
			<button class="button tsjippy small ignore" data-id="<?php echo esc_attr($value->id);?>" data-nonce="<?php echo esc_attr(wp_create_nonce('ignore_log_entry'));?>">
				Ignore
			</button>
			<br>

			<i><?php echo wp_kses_post($value->message);?></i>
			<br>
			<?php echo strip_tags(wp_kses_post($value->caller), '<br>');?>
			<br><br>
		</div>
		<?php
	}

	return ob_get_clean();
}

function getLogs($wpRest){
	$logger		= new Logger();

	$logs		= $logger->getLogs($wpRest->get_param('timestamp'), $wpRest->get_param('page'));

	return logToHtml($logs);
}

function removeSimilarEntries($wpRest){
	$logger	= new Logger();
	$result	= $logger->removeSimilarEntries($wpRest->get_param('id'));

	return $result;
}

function storeIgnore($wpRest){
	$logger		= new Logger();

	$ignores	= get_option('tsjippy-logs-ignore', []);
	$ignores[]	= $logger->getMessage($wpRest->get_param('id'));

	update_option('tsjippy-logs-ignore', $ignores);

	removeSimilarEntries($wpRest);

	return true;
}
