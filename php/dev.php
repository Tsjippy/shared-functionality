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

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    $args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image/jpeg', // Uses a wildcard internally (image/*)
        'numberposts'    => -1,
        'post_status'    => 'any',
    );

    $images = get_posts($args);

    foreach ($images as $image) {
        if (strpos($image->guid, '.jpe') === false) {
            continue;
        }
        $path = get_attached_file($image->ID, true);

        if (!file_exists($path)) {
            $ext    = pathinfo($path, PATHINFO_EXTENSION);

            $path   = str_replace(' . ' . $ext, '.jpg', $path);

            if (!file_exists($path)) {
                $path = str_replace('.jpg', '.jpeg', $path);
            }

            if (!file_exists($path)) {
                continue;
            }
        }

        update_attached_file($image->ID, $path);
    }
});

add_shortcode("test", function ($atts) {
    global $wpdb;

    $shortcodes = [
        'content_filter',
        'embed_page',
        'schedules',
        'missing_events',
        'formselector',
        'formbuilder',
        'formresults',
        'missing_form_fields',
        'your_posts',
        'pending-pages',
        'pending_post_icon',
        'front_end_post',
        'old-pages',
        'email_stats',
        'markerdescription',
        'ministry_description',
        'location_description',
        'twofa_setup',
        'change_password',
        'request_account',
        'mailchimp',
        'must_read_documents',
        'mediagallery',
        'debug',
        'test',
        'signal_messages',
        'repairfund',
        'quotadocuments',
        'create_user_account',
        'pending_user',
        'pending_user_icon',
        'delete_user',
        'expiry_warnings',
        'userstatistics',
        'user-info',
        'all_contacts',
        'user_link',
        'vimeo_video',
        'welcome',
    ];

    foreach ($shortcodes as $shortcode) {
        $wpdb->query(
            "UPDATE wp_posts
            SET post_content = REPLACE(post_content, '[$shortcode', '[tsjippy_$shortcode')
            WHERE post_content LIKE '%[$shortcode%'"
        );
    }

    $postMetas = [
        'icon_id',
        'response_body',
        'status',
        'recipients',
        'marker_ids',
        'audience',
        'static_content',
        'add_print_button',
        'ingredients',
        'time_needed',
        'serves',
        'location',
        'map_id',
        'url',
        'tel',
        'expirydate',
        'signal',
        'signalmessagetype',
        'mailchimp_segment_id',
        'mailchimp_email',
        'eventdetails',
        'celebrationdate',
        'visibility',
        'pending_notification_send',
        'gallery_visibility',
        'vimeo_id',
        'thumbnail',
        'video_path',
        'user_id',
        'signal_extra_message',
        'mailchimp_message_send',
        'signal_message_type',
        'signal_url',
        'manager',
        'number',
        'ministry',
        'onlyfor',
        'mailchimp_extra_message',
        'reminders',
        'excluded_roles',
        'send_signal',
        'footnotes',
        'skipgallery',
        'mailchimp_campaign_id',
        'mailchimp_segment_ids',
        'year',
        'image',
        'pages',
        'subtitle',
        'author',
        'series',
        'mailchimp_height',
        'mailchimp_url',
        'payments',
        'overlap',
        'overlap-period',
        'default-booking-state',
        'confirmed-booking-roles',
        'amount',
        'element-id',
        'name',
        'nrtype',
        'managers',
        'post_view_roles',
        'date',
        'user-id',
        'only_for',
    ];

    foreach($postMetas as $metaKey){
        $wpdb->query(
            "UPDATE wp_postmeta
            SET meta_key = REPLACE(meta_key, '$metaKey', 'tsjippy_$metaKey')
            WHERE meta_key = '$metaKey'"
        );
    }

    $userMetas = [
        'login_count',
        'geo_compound',
        'geo_address',
        'geo_latitude',
        'geo_longitude',
        'gender',
        'user_last_view_date',
        'user_last_view_date_events',
        'birthday',
        'sending_office',
        'welcomemessage',
        'location',
        'arrival_date',
        'SIM_Nigeria_postion',
        'Hillcrest_postion',
        'Egbe_Hospital_postion',
        'Fulani_postion',
        'SIM_Office_postion',
        'first_login',
        'marker_id',
        'Boys_Transition_House_postion',
        'Other_postion',
        'Theological_ministries_postion',
        'account_validity',
        'BHUTH_postion',
        'financial_account_id',
        'phonenumbers',
        'user_ministries',
        'MailchimpStatus',
        'visa_info',
        'personnel_documents',
        'online_statements',
        'personnel',
        'SIM Nigeria anniversary of_event_id',
        'last_login_date',
        'read_pages',
        'profile_picture',
        'medical',
        'userid',
        'ministries',
        'SIM Nigeria anniversary_event_id',
        'Wedding anniversary_event_id',
        'birthday_event_id',
        'validity',
        '2fa_key',
        '2fa_hash',
        '2fa_webauthn_key',
        '2fa_webautn_cred',
        'nigerian',
        'hide_annual_review',
        '2fa_last',
        'email',
        'age_preference',
        'privacy_preference',
        'understudies',
        'understudy_1',
        'understudy_2',
        'formid',
        'hidden_columns_travel',
        'imei_number',
        'qualification',
        'qualification_1',
        'qualification_2',
        'action',
        'user_page_id',
        'signal_preferences',
        'description',
        'jobs',
        'hidden_columns_20',
        'hidden_columns_8',
        'submissiontime',
        'edittime',
        'signal_number',
        'account-type',
        'user_id',
        'schedule',
        'hidden_columns_52',
        'last_contact_download',
        'phone-last-changed',
        'hidden_columns_21',
        'prayers',
        'linked-accounts',
        'linked-account',
        'viewhash',
        'pending-prayer-update-data',
        'hidden_columns_130',
        'edit_prayer-request_per_page',
        'hidden_columns_129',
        '2fa_webautn_cred_meta',
        'family',
        'show-name',
        '2fa_methods',
        'account_statements',
        'partner',
        'weddingdate',
        'family_name',
        'family_picture',
        'hidden_columns_19',
        'hidden_columns_71'
    ];

    foreach ($userMetas as $metaKey) {
        $wpdb->query(
            "UPDATE wp_usermeta
            SET meta_key = REPLACE(meta_key, '$metaKey', 'tsjippy_$metaKey')
            WHERE meta_key = '$metaKey'"
        );
    }

    $wpdb->query(
            "UPDATE wp_termmeta
            SET meta_key = REPLACE(meta_key, 'map_id', 'tsjippy_map_id')
            WHERE meta_key = 'map_id'"
        );
});

// turn off incorrect error on localhost
add_filter('wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false');
