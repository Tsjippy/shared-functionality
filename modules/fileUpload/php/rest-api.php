<?php

namespace TSJIPPY\FILEUPLOAD;

use TSJIPPY;
use WP_Error;

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
            'permission_callback' => __NAMESPACE__ . '\removeDocumentPermissions',
            'args' => array(
                'url'  => array(
                    'required'           => true,
                    'validate_callback'  => __NAMESPACE__ . '\validateUrl'
                )
            )
        )
    );
}

function removeDocumentPermissions()
{
    // The nonce action includes the file name
    // A valid nonce is only valid for one file
    // phpcs:ignore
    $verified   = TSJIPPY\verifyNonce('nonce', "file-delete-" . TSJIPPY\sanitize($_POST['url']));

    if (!$verified) {
        return false;
    }

    /**
     * Check file ownership
     */
    // We can edit files
    if(current_user_can('delete_others_posts')){
        return true;
    }

    /**
     * The file is stored in our meta data
     */
    // phpcs:ignore
    if(!empty($_POST['metakey'])){
        // phpcs:ignore
        $metaKey    = TSJIPPY\sanitize($_POST['metakey']);
        $keys       = explode('[', $metaKey);
        $metaKey    = $keys[0];
        unset($keys[0]);
        $values     = get_user_meta(get_current_user_id(), $metaKey);

        /**
         * Find indexed value
         */
        foreach($keys as $key){
            $values = (array)$values[$key];
        }

        foreach($values as $value){
            // Its a library file
            if(is_numeric($value)){
                // phpcs:ignore
                if(wp_get_attachment_url($value) == $_POST['url']){
                    return true;
                }
            // phpcs:ignore
            }elseif($value == $_POST['url']){
                return true;
            }
        }
    }

    /**
     * Filters if we have permission to delete a file
     * 
     * @param   bool $permission
     */
    return apply_filters('tsjippy-file-upload-delete-permission', false);
}

function validateUrl($url)
{
    // File should be in the uploads folder or a sub folder
    return str_contains($url, wp_upload_dir()['url']);
}

function removeDocument()
{
    if(!removeDocumentPermissions()){
        return new WP_Error('file-upload', 'No Permission, sorry');
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
    if (is_numeric($_POST['libraryid'] ?? '')) {
        // phpcs:ignore
        wp_delete_attachment((int) $_POST['libraryid']);
    } else {
        // phpcs:ignore
        wp_delete_file(TSJIPPY\urlToPath(TSJIPPY\sanitize($_POST['url'], 'url')));
    }

    $metaValue = '';

    // phpcs:ignore
    $userId    = (int) $_POST['user-id'] ?? 0;

    // Check if we have permission when uploading for someone else
    if($userId != get_current_user_id() && !current_user_can('delete_others_posts')){
        return new \WP_Error('tsjippy-file-upload', 'You are not allowed to do this, sorry');
    }

    //Remove the path from db
    if (is_numeric($userId)) {
        //Get document array from db
        $metaValue = get_user_meta($userId, $baseMetaKey, true);
    }

    //remove from array
    if (is_array($metaKeys) && !empty($metaKeys)) {
        TSJIPPY\removeFromNestedArray($metaValue, $metaKeys);
    } else {
        $metaValue = '';
    }

    //Personnal document
    if (is_numeric($userId)) {
        if (empty($metaValue)) {
            delete_user_meta($userId, $baseMetaKey);
        } else {
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
