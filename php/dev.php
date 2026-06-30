<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

/**
 * Prints html properly outlined for easy debugging
 */
function printHtml($html)
{
    $tabs    = 0;

    // Split on the < symbol to get a list of opening and closing tags
    $html        = explode('>', $html);
    $newHtml    = '';

    // loop over the elements
    foreach ($html as $index => $el) {
        $el = trim($el);

        if (empty($el)) {
            continue;
        }

        // Split the line on a closing character </
        $lines    = explode('</', $el);

        if (!empty($lines[0])) {
            $newHtml    .= "\n";

            // write as many tabs as need
            for ($x = 0; $x <= $tabs; $x++) {
                $newHtml    .= "\t";
            }

            // then write the first element
            $newHtml    .= $lines[0];
        }

        if (
            substr($el, 0, 1) == '<' &&                         // Element start with an opening symbol
            substr($el, 0, 2) != '</' &&                         // It does not start with a closing symbol
            substr($el, 0, 6) != '<input' &&                     // It does not start with <input (as that one does not have a closing />)
            (
                substr($el, 0, 7) != '<option' ||                 // It does not start with <option (as that one does not have a closing />)
                str_contains($html[$index + 1], '</option')         // or the next element contains a closing option
            ) &&
            $el != '<br'
        ) {
            $tabs++;
        }

        if (isset($lines[1])) {
            $tabs--;

            $newHtml    .= "\n";

            for ($x = 0; $x <= $tabs; $x++) {
                $newHtml    .= "\t";
            }
            $newHtml    .= '</' . $lines[1] . '>';
        } else {
            $newHtml    .= '>';
        }
    }

    printArray($newHtml);
}

// disable auto updates for this plugin on localhost
add_filter('auto_update_plugin', __NAMESPACE__ . '\disableAutoUpdate', 10, 2);
function disableAutoUpdate($value, $item)
{
    if ('tsjippy-shared-functionality' === $item->slug && (wp_get_environment_type() === 'local')) {
        return false; // disable auto-updates for the specified plugin
    }

    return $value; // Preserve auto-update status for other plugins
}

//Shortcode for testing
add_shortcode("tsjippy_test", function ($atts) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    global $wpdb;

   $results = $wpdb->get_results("select * from wp_tsjippy_form_elements where booking_details <> ''");
   foreach($results as $result){
    $result = map_deep($result, 'maybe_unserialize');

    if(empty($result->booking_details['subjects'][0]['name'])){
        $wpdb->update(
            'wp_tsjippy_form_elements',
            [
                'booking_details' => ''
            ],
            [
                'id' => $result->id
            ],
            [
                '%s'
            ],
            [
                '%d'
            ]
        );
    }else{
        foreach($result->booking_details['subjects'] as &$subject){
            unset($subject['confirmed_booking_roles']);
            $subject['managers']    = array_flip($subject['managers']);
        }

        $wpdb->update(
            'wp_tsjippy_form_elements',
            [
                'booking_details' => maybe_serialize($result->booking_details)
            ],
            [
                'id' => $result->id
            ],
            [
                '%s'
            ],
            [
                '%d'
            ]
        );
    }
    foreach(get_users() as $user){
        $privacy    = get_user_meta($user->ID, 'tsjippy_privacy_preference', true);

        if(is_array($privacy)){
            delete_user_meta($user->ID, 'tsjippy_privacy_preference');

            foreach($privacy as $p){
                add_user_meta($user->ID, 'tsjippy_privacy_preference', $p);
            }
        }
    }
   }

   $ignores    = get_option('tsjippy-logs-ignore', []);
   if(isset($ignores[0])){
        update_option('tsjippy-logs-ignore', array_flip($ignores));
    }

    $settings = get_option('tsjippy_comments_settings');
    if(isset($settings['posttypes'])){
        $settings['posttypes'] = array_flip(array_unique($settings['posttypes']));
        update_option('tsjippy_media-gallery_settings', $settings);
    }

    $settings = get_option('tsjippy_contentfilter_settings');
    delete_option('tsjippy_contentfilter_settings');
    if(isset($settings['confidential-roles'])){
        $settings['confidential-roles'] = array_flip(array_unique($settings['confidential-roles']));
        update_option('tsjippy_content-filter_settings', $settings);
    }

    $settings = get_option('tsjippy_defaultpictures_settings');
    delete_option('tsjippy_defaultpictures_settings');
    add_option('tsjippy_default-pictures_settings', $settings);

    $settings = get_option('tsjippy_forms_settings');
    if(isset($settings['forms-pages'][0])){
        $settings['forms-pages']  = array_flip(array_unique($settings['forms-pages']));
    }
    if(isset($settings['formbuilder-pages'][0])){
        $settings['formbuilder-pages']  = array_flip(array_unique($settings['formbuilder-pages']));
    }
    update_option('tsjippy_forms_settings', $settings);

    $settings = get_option('tsjippy_media-gallery_settings');
    delete_option('tsjippy_mediagallery_settings');
    if(isset($settings['mediagallery-pages'])){
        $settings['pages'] = array_flip(array_unique($settings['mediagallery-pages']));
        unset($settings['media-gallery-pages']);
        update_option('tsjippy_media-gallery_settings', $settings);
    }

    $settings = get_option('tsjippy_frontendposting_settings');
    delete_option('tsjippy_frontendposting_settings');
    if(isset($settings['front-end-post-pages'][0])){
        $settings['front-end-post-pages']  = array_flip(array_unique($settings['front-end-post-pages']));
        update_option('tsjippy_frontend-posting_settings', $settings);
    }

    $settings = get_option('tsjippy_login_settings');
    if(isset($settings['login-menu'][0])){
        $settings['login-menu']  = array_flip(($settings['login-menu']));
    }
    if(isset($settings['visibilty-login-menu'][0])){
        $settings['visibilty-login-menu']  = array_flip(($settings['visibilty-login-menu']));
    }
    if(isset($settings['logout-menu'][0])){
        $settings['logout-menu']  = array_flip(($settings['logout-menu']));
    }
    if(isset($settings['visibilty-logout-menu'][0])){
        $settings['visibilty-logout-menu']  = array_flip(($settings['visibilty-logout-menu']));
    }
    update_option('tsjippy_login_settings', $settings);

    $settings = get_option('tsjippy_locations_settings');
    if(isset($settings['google-maps-api-forms'][0])){
        $settings['google-maps-api-forms']  = array_flip(($settings['google-maps-api-forms']));
        update_option('tsjippy_locations_settings', $settings);
    }

    $settings = get_option('tsjippy_usermanagement_settings');
    delete_option('tsjippy_usermanagement_settings');
    delete_option('tsjippy_user-management_settings');
    if(isset($settings['enabled-forms'][0])){
        $settings['enabled-forms']  = array_flip(array_unique($settings['enabled-forms']));
        update_option('tsjippy_user-management_settings', $settings);
    }

    delete_option('tsjippy_welcomemessage_settings');

    foreach($wpdb->get_results("select * from wp_tsjippy_form_shortcode_column_settings") as $result){
        $result->view_right_roles   = maybe_unserialize($result->view_right_roles);
        if(!empty($result->view_right_roles) && is_array(($result->view_right_roles))){
            $result->view_right_roles = array_flip($result->view_right_roles);
        }

        $result->edit_right_roles   = maybe_unserialize($result->edit_right_roles);
        if(!empty($result->edit_right_roles) && is_array(($result->edit_right_roles))){
            $result->edit_right_roles = array_flip($result->edit_right_roles);
        }

        $wpdb->update(
            'wp_tsjippy_form_shortcode_column_settings',
            [
                'view_right_roles'  => maybe_serialize($result->edit_right_roles),
                'edit_right_roles'  => maybe_serialize($result->edit_right_roles),
            ],
            [
                'id' => $result->id
            ]
        );
    }

    foreach($wpdb->get_results("select * from wp_tsjippy_form_shortcodes") as $result){
        $result->view_right_roles   = maybe_unserialize($result->view_right_roles);
        if(!empty($result->view_right_roles) && is_array(($result->view_right_roles))){
            $result->view_right_roles = array_combine($result->view_right_roles, $result->view_right_roles);
        }

        $result->edit_right_roles   = maybe_unserialize($result->edit_right_roles);
        if(!empty($result->edit_right_roles) && is_array(($result->edit_right_roles))){
            $result->edit_right_roles = array_flip($result->edit_right_roles);
        }

        $wpdb->update(
            'wp_tsjippy_form_shortcodes',
            [
                'view_right_roles'  => maybe_serialize($result->edit_right_roles),
                'edit_right_roles'  => maybe_serialize($result->edit_right_roles),
            ],
            [
                'id' => $result->id
            ]
        );
    }

    foreach($wpdb->get_results("select * from wp_tsjippy_forms") as $result){
        $result->full_right_roles   = maybe_unserialize($result->full_right_roles);
        if(!empty($result->full_right_roles) && is_array(($result->full_right_roles))){
            $result->full_right_roles = array_flip($result->full_right_roles);
        }

        $result->submit_others_form   = maybe_unserialize($result->submit_others_form);
        if(!empty($result->submit_others_form) && is_array(($result->submit_others_form))){
            $result->submit_others_form = array_flip($result->submit_others_form);
        }

        $wpdb->update(
            'wp_tsjippy_forms',
            [
                'full_right_roles'  => maybe_serialize($result->full_right_roles),
                'submit_others_form'  => maybe_serialize($result->submit_others_form),
            ],
            [
                'id' => $result->id
            ]
        );
    }

    
});

// turn off incorrect error on localhost
add_filter('wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false');
