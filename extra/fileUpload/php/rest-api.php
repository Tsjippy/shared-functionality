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
            'methods'                => 'POST',
            'callback'                => __NAMESPACE__ . '\removeDocument',
            'permission_callback'     => function () {
                // phpcs:disable
                if(!empty($_POST['metakey']) && $_POST['user-id'] == get_current_user_id()){
                    $metaKey    = TSJIPPY\sanitize($_POST['metakey']);
                    if (preg_match_all('/(.*?)\[(.*?)\]/i', $metaKey, $matches)) {
                        $baseMetaKey    = $matches[1][0];
                        $keys           = $matches[2];
                    } else {
                        //just use the whole, it is not indexed
                        $baseMetaKey    = $metaKey;
                    }
                    return !empty(get_user_meta((int) $_POST['user-id'], $baseMetaKey));
                }elseif(str_contains($_POST['url'], 'wp-content/uploads')){
                    return true;
                }else{
                    // TO DO Check When this happens
                    return true;
                }
                // phpcs:enable
            },
            'args'                    => array(
                'url'        => array(
                    'required'           => true,
                    'validate_callback'  => __NAMESPACE__ . '\validateUrl'
                )
            )
        )
    );
}

function validateUrl($param)
{
    // File should be in the uploads folder or a sub folder
    return str_contains($param, 'wp-content/uploads');
}

function removeDocument()
{

    if (!TSJIPPY\verifyNonce('nonce', 'file-delete')) {
        return new \WP_Error('file uploader', 'Please reload the page and try again');
    }

    // phpcs:ignore
    if (empty($_POST['url'])) {
        return false;
    }

    $userId = '';
    // phpcs:ignore
    if (isset($_POST['user-id'])) {
        // phpcs:ignore
        $userId = (int) $_POST["user-id"];
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

    //Remove the path from db
    if (is_numeric($userId)) {
        //Get document array from db
        $metaValue = get_user_meta($userId, $baseMetaKey, true);
        //Generic document
    } else {
        //get documents array from db
        $metaValue = get_option($baseMetaKey);
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
