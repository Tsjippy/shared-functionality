<?php
namespace TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', __NAMESPACE__.'\restApiInitDev');
function restApiInitDev() {
	register_rest_route( 
		RESTAPIPREFIX, 
		'/get_error_log', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\getDebugLog',
			'permission_callback' 	=> function(){
				return current_user_can('activate_plugins');
			}
		)
	);

	register_rest_route( 
		RESTAPIPREFIX, 
		'/clear_error_log', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				return clearLog('debug.log');
			},
			'permission_callback' 	=> function(){
				return current_user_can('activate_plugins');
			}
		)
	);

	register_rest_route( 
		RESTAPIPREFIX, 
		'/get_notice_log', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\getNoticeLog',
			'permission_callback' 	=> function(){
				return current_user_can('activate_plugins');
			},
		)
	);

	register_rest_route( 
		RESTAPIPREFIX, 
		'/clear_notice_log', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				return clearLog('notice.log');
			},
			'permission_callback' 	=> function(){
				return current_user_can('activate_plugins');
			}
		)
	);
}

/**
 * Deletes a files  from the content dir
 * 
 * @param	string	$fileName	the filename
 */
function clearLog($fileName){
	global $wp_filesystem;
	include_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();

	$filepath = WP_CONTENT_DIR.'/notice.log';
	return $wp_filesystem->delete( $filepath );
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
	krsort($logData); // newest one first

	ob_start();
	?>
	<table class=''>
		<?php
		foreach($logData as $date => $value){
			$date	= date(DATEFORMAT.' H:i:s', strtotime($date));
			?>
			<tr>
				<td>
					<?php echo esc_html($date); ?>
				</td>
				<td class=''>
					<?php echo esc_html($value['caller']);?>
				</td>
			</tr>
			<tr>
				<td colspan=2>
					<?php echo esc_html($value['message']);?>
				</td>
			</tr>
			<?php
		}
		?>
	</table>
	<?php

	return ob_get_clean();
}

function getDebugLog(){
	$debugLog	= [];

	foreach(getLog('debug.log') as $line){
		if(preg_match('/\[(.*?)\] PHP (?:([a-zA-Z ]*?):)?(.*)/', $line, $matches)){
			$date	= date('Y-m-d H:i:s', strtotime($matches[1]));
			$type	= trim($matches[2]);
			$message= trim($matches[3]);

			if(isset($debugLog[$date])){
				$debugLog[$date]['message']	.= "<br>$message";
			}else{
				$debugLog[$date] = [
					'caller' 	=> $type,
					'message' 	=> $message
				];
			}
		}
	}

	return logToHtml($debugLog);
}

function getNoticeLog(){
	$lines	= getLog('notice.log');

	$log		= array();

	$caller		= '';
	$pattern = '/(?<=(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})) - /'; // Matches immediately after these characters without consuming them
	foreach($lines as $line){
		if(str_contains($line, 'Called from')){
			$caller	= $line;
		}else{
			$result = preg_split($pattern, $line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

			$log[$result[1]] = [
				'caller' 	=> $caller,
				'message' 	=> $result[2]
			];
		}

	}

	return logToHtml($log);
}

add_shortcode("logs", function ($atts){
	wp_enqueue_script( 'tsjippy-logs', pathToUrl(PLUGINPATH.'includes/js/logs.min.js'), [], '10.0.0', true);

	ob_start();

	?>
	<div class='tablink-wrapper'>
		<button class='button tablink active' type='button' id='show-debug-log' data-target='debug-log' style='margin-right:4px;'>
			Debug Log
		</button>
		<button class='button tablink' type='button' id='show-notice-log' data-target='notice-log' style='margin-right:4px;'>
        	Notice Log
    	</button>
	</div>

	<div class='tabcontent' id='debug-log'>
		<div class="wrapper" style='width:1000px;'>
			<div style='width:500px;'>
				<div class="loader-image-trigger" data-size="50" data-text="Fetching the error log..."></div>
			</div>
		</div>
		<button type='button' class='button' id='clear-error-log'>Clear Debug Log</button>
	</div>

	<div class='tabcontent hidden' id='notice-log'>
		<div class="wrapper" style='width:2000px;'>
			<div style='width:500px;'>
				<div class="loader-image-trigger" data-size="50" data-text="Fetching the notice log..."></div>
			</div>
		</div>
		<button type='button' class='button' id='clear-notice-log'>Clear Notice Log</button>
	</div>

	<?php

	return ob_get_clean();
});