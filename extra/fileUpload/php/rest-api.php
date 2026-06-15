<?php

namespace TSJIPPY\FILEUPLOAD;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

add_action('rest_api_init', __NAMESPACE__ . '\uploadRestApiInit');
function uploadRestApiInit()
{
    //Route for first names
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX,
        '/remove-document',
        array(
            'methods'             => 'POST',
            'callback'            => __NAMESPACE__ . '\removeDocument',
            'permission_callback' => function () {
                // The nonce action includes the file name
                // A valid nonce is only valid for one file
                // phpcs:ignore
                $verified   = TSJIPPY\verifyNonce('nonce', "file-delete-".esc_url($_POST['url']));

                if(!$verified){
                    return false;
                }

                /**
                 * Check file ownership
                 */
                // File should include our username or we have power
                if(
                    !str_contains($_POST['url'], wp_get_current_user()->user_login) &&
                    !current_user_can('delete_others_posts')
                ){
                    /**
                     * Filters if we have permission to delete a file
                     * 
                     * @param   bool $permission
                     */
                    return apply_filters('tsjippy-file-upload-delete-permission', false);
                }

                return true;
            },
            'args' => array(
                'url'  => array(
                    'required'           => true,
                    'validate_callback'  => __NAMESPACE__ . '\validateUrl'
                )
            )
        )
    );
}

function validateUrl($url)
{
    // File should be in the uploads folder or a sub folder
    return str_contains($url, wp_upload_dir()['url']);
}

function removeDocument()
{
    // phpcs:ignore
    if (empty($_POST['url'])) {
        return new \WP_Error('file upload', 'No Permission to delete a File');
    }

    /**
     * Determine the user id for whom we are doing this action. Not the logged in user id.
     */
    $userId = '';
    // phpcs:ignore
    if (isset($_POST['user-id'])) {
        // phpcs:ignore
        $userId = (int) $_POST["user-id"];
    }

    /**
     * Verify Permissions
     */
    // The nonce action includes the file name
    // A valid nonce is only valid for one file
    // phpcs:ignore
    $verified   = TSJIPPY\verifyNonce('nonce', "file-delete-".esc_url($_POST['url']));

    if(!$verified){
        return new \WP_Error('file upload', 'No Permission to delete a File');
    }

    /**
     * Check file ownership
     */
    // File should include our username or we have power
    if(
        !str_contains($_POST['url'], wp_get_current_user()->user_login) &&
        !current_user_can('delete_others_posts')
    ){
        /**
         * Filters if we have permission to delete a file return false to skip deletion
         * 
         * @param   bool $permission
         */
        if(!apply_filters('tsjippy-file-upload-delete-permission', false)){
            return new \WP_Error('file upload', 'No Permission to delete a File');
        }
    }

    $baseMetaKey    = '';
    $metaKeys       = [];
    $metaKey        = '';
    // phpcs:ignore
    if (isset($_POST['metakey'])) {
        // phpcs:ignore
        $metaKey        = TSJIPPY\sanitize($_POST['metakey']);
        $metaKeys       = str_replace(']', '', explode('[', $metaKey));
        $baseMetaKey    = $metaKeys[0];
        unset($metaKeys[0]);
    }

    //remove the file
    // phpcs:ignore
    if (isset($_POST['libraryid']) && is_numeric($_POST['libraryid'])) {
        // phpcs:ignore
        wp_delete_attachment((int) $_POST['libraryid']);
    } else {
        // phpcs:ignore
        wp_delete_file(TSJIPPY\urlToPath(TSJIPPY\sanitize($_POST['url'], 'url')));
    }

    $metaValue = '';
    //Remove the path from db
    if (is_numeric($userId)) {
        //Get document array from db
        $metaValue = get_user_meta($userId, $baseMetaKey, true);
        //Generic document
    }

    //remove from array
    if (is_array($metaKeys) && !empty($metaKeys)) {
        TSJIPPY\removeFromNestedArray($metaValue, $metaKeys);
    } else {
        $metaValue = '';
    }

    //Personnal document
    if (is_numeric($userId)) {
        if(empty($metaValue)){
            delete_user_meta($userId, $baseMetaKey);
        }else{
            //Store the array in db
            update_user_meta($userId, $baseMetaKey, $metaValue);
        }  
    } 
    
    //Generic document
    else {
        //Save it in db
        update_option($baseMetaKey, $metaValue);
    }

    return "File successfully removed";
}
