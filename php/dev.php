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

    foreach(get_users() as $user){
        $phonenumbers   = get_user_meta($user->ID, 'tsjippy_phonenumbers');
        delete_user_meta($user->ID, 'tsjippy_phonenumbers');

        foreach($phonenumbers as $phonenumber){
            if(is_array($phonenumber)){
                foreach($phonenumber as $nr){
                    add_user_meta($user->ID, 'tsjippy_phonenumbers', $nr);
                }
            }else{
                add_user_meta($user->ID, 'tsjippy_phonenumbers', $phonenumber);
            }
        }
        $readpages      = get_user_meta($user->ID, 'tsjippy_read_pages');
        delete_user_meta($user->ID, 'tsjippy_read_pages');
        foreach($readpages as $readpage){
            if(is_array($readpage)){
                $readpage = array_unique($readpage);
                
                foreach($readpage as $page){
                    add_user_meta($user->ID, 'tsjippy_read_pages', $page);
                }
            }else{
                add_user_meta($user->ID, 'tsjippy_read_pages', $readpage);
            }
        }

        $linkedAccountIds    = get_user_meta($userId, 'tsjippy_linked-accounts');
        delete_user_meta($user->ID, 'tsjippy_linked-accounts');
        foreach($linkedAccountIds as $linkedAccountId){
            if(is_array($linkedAccountId)){
                $linkedAccountId = array_unique($linkedAccountId);
                
                foreach($linkedAccountId as $id){
                    add_user_meta($user->ID, 'tsjippy_linked_accounts', $id);
                }
            }else{
                add_user_meta($user->ID, 'tsjippy_linked_accounts', $linkedAccountId);
            }
        }

        if(is_numeric(get_user_meta($user->ID, 'tsjippy_profilepicture', true )) && empty(get_user_meta($user->ID, 'tsjippy_profile_picture'))){
            add_user_meta($user->ID, 'tsjippy_profile_picture', get_user_meta($user->ID, 'tsjippy_profilepicture', true ));
        }
        delete_user_meta($user->ID, 'tsjippy_profilepicture');

        delete_user_meta($user->ID, 'tsjippy_financial_account_id');
        delete_user_meta($user->ID, 'tsjippy_online_statements');
        delete_user_meta($user->ID, 'tsjippy_medical');
        delete_user_meta($user->ID, 'tsjippy_userid');
        delete_user_meta($user->ID, 'tsjippy_formid');
        delete_user_meta($user->ID, '_wpnonce');
        delete_user_meta($user->ID, 'tsjippy_description');
        delete_user_meta($user->ID, 'tsjippy_submissiontime');
        delete_user_meta($user->ID, 'tsjippy_edittime');
        delete_user_meta($user->ID, 'tsjippy_prayers');
        delete_user_meta($user->ID, 'user-id');
        delete_user_meta($user->ID, 'tsjippy_viewhash');
        delete_user_meta($user->ID, 'tsjippy_account_statements');
        delete_user_meta($user->ID, '2fa_webautn_cred');
        delete_user_meta($user->ID, 'tsjippy_2fa_webautn_cred_meta');
        delete_user_meta($user->ID, '2fa_webautn_cred_meta');
        delete_user_meta($user->ID, 'tsjippy_personnel');
        delete_user_meta($user->ID, 'tsjippy_personnel_documents');
        


    }
});


// turn off incorrect error on localhost
add_filter('wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false');
