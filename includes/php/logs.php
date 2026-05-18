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
			'callback' 				=> function(){
				global $wp_filesystem;
				include_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();

				$filepath = WP_CONTENT_DIR.'/debug.log';
				return str_replace(["\n[", "\n", "\t"], ['<br><br>[', '<br>', "   "], $wp_filesystem->get_contents( $filepath ));
			},
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
				global $wp_filesystem;
				include_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();

				$filepath = WP_CONTENT_DIR.'/debug.log';
				return $wp_filesystem->delete( $filepath );
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
			'callback' 				=> function(){
				global $wp_filesystem;
				include_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();

				$filepath = WP_CONTENT_DIR.'/notice.log';

				$contents	= $wp_filesystem->get_contents( $filepath );

				$log		= array();

				$blocks		= preg_split('/Called from/', $contents, -1, PREG_SPLIT_NO_EMPTY);

				$pattern = '/(?<=(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})) - /'; // Matches immediately after these characters without consuming them
				foreach($blocks as $block){
					$result = preg_split($pattern, $block, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

					$log[$result[1]] = [
						'caller' 	=> "Called from ".$result[0],
						'message' 	=> $result[2]
					];
				}
			
				krsort($log); // newest one first

				ob_start();
				?>
				<table class='tsjippy table'>
					<?php
					foreach($log as $key => $value){
						?>
						<tr>
							<td>
								<?php echo $key; ?>
							</td>
							<td class='hidden'>
								<?php echo $value['caller'];?>
							</td>
							<td>
								<?php echo $value['message'];?>
							</td>
						</tr>
						<?php
					}
					?>
				</table>
				<?php


				return ob_get_clean();
			},
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
				global $wp_filesystem;
				include_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();

				$filepath = WP_CONTENT_DIR.'/notice.log';
				return $wp_filesystem->delete( $filepath );
			},
			'permission_callback' 	=> function(){
				return current_user_can('activate_plugins');
			}
		)
	);
}

add_shortcode("logs", function ($atts){
	wp_enqueue_script( 'tsjippy-logs', pathToUrl(PLUGINPATH.'includes/js/logs.min.js'), [], PLUGINVERSION, true);

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
		<div class="wrapper" style='width:2000px;'>
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