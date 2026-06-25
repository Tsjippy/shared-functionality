<?php

namespace TSJIPPY;

//Add a shortcode for the displayname
add_shortcode('displayname', __NAMESPACE__ . '\displayName');
function displayName()
{
	if (is_user_logged_in()) {
		$currentUser = wp_get_current_user();
		return $currentUser->first_name;
	} else {
		return "visitor";
	}
}

//Shortcode to return the amount of loggins in words
add_shortcode("login_count", __NAMESPACE__ . '\loginCount');
function loginCount()
{
	$userId				= get_current_user_id();
	$currentLogginCount = get_user_meta($userId, 'login_count', true);
	//Get the word from the array
	if (is_numeric($currentLogginCount)) {
		return numberToWords($currentLogginCount);
		//key not set, assume its the first time
	} else {
		return "your first";
	}
}
