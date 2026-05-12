<?php
namespace TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init',  __NAMESPACE__.'\blockRestApiInit');
function blockRestApiInit() {
	// show post children
	register_rest_route( 
		RESTAPIPREFIX, 
		'/show_children', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\showChildren',
			'permission_callback' 	=> '__return_true',		// Allow non-logged in users to access this endpoint
		)
	);
}

/**
 * Displays the children of a post based on the provided request parameters.
 *
 * @param \WP_REST_Request $wpRestRequest The REST request object.
 * @return array The list of child posts.
 */
function showChildren($wpRestRequest){
	return displayChildren($wpRestRequest->get_params());
}