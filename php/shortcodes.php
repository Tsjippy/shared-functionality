<?php
namespace TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

//Add a shortcode for the display name
add_shortcode('tsjippy-display-name', __NAMESPACE__ . '\displayName');
function displayName()
{
	if (is_user_logged_in()) {
		$currentUser = wp_get_current_user();
		return esc_html($currentUser->first_name);
	} else {
		return "visitor";
	}
}