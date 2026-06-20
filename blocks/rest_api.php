<?php

namespace TSJIPPY\FRONTPAGE;

use TSJIPPY;

add_action('rest_api_init', function () {
	// show displayname
	register_rest_route(
		TSJIPPY\RESTAPIPREFIX . '/frontpage',
		'/show_display_name',
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__ . '\displayName',
			'permission_callback' 	=> function () {
				return current_user_can('read');
			},
		)
	);

	// show login count
	register_rest_route(
		TSJIPPY\RESTAPIPREFIX . '/frontpage',
		'/show_login_count',
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__ . '\loginCount',
			'permission_callback' 	=> function () {
				return current_user_can('read');
			},
		)
	);

	// show welcome_message
	register_rest_route(
		TSJIPPY\RESTAPIPREFIX . '/frontpage',
		'/show_welcome_message',
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__ . '\welcomeMessage',
			'permission_callback' 	=> function () {
				return current_user_can('read');
			},
		)
	);

	// show post children
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX,
        '/show_children',
        array(
            'methods'                 => 'POST',
            'callback'                 => __NAMESPACE__ . '\showChildren',
            'permission_callback'     => '__return_true',        // Allow non-logged in users to access this endpoint
        )
    );
});

/**
 * Displays the children of a post based on the provided request parameters.
 *
 * @param \WP_REST_Request $wpRestRequest The REST request object.
 * @return array The list of child posts.
 */
function showChildren($wpRestRequest)
{
    return displayChildren($wpRestRequest->get_params());
}
