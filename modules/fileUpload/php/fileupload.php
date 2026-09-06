<?php

namespace TSJIPPY\FILEUPLOAD;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

//Make upload_files function availbale for AJAX request
add_action('wp_ajax_tsjippy-upload-files', __NAMESPACE__ . '\ajaxUploadFiles');
function ajaxUploadFiles()
{
    // phpcs:ignore
    if (empty($_FILES["files"])) {
        // Set http header error
        header('HTTP/1.0 422 Unprocessable Entity');

        // Return error message
        die(json_encode(array('error' => 'No files found')));
    }

    // Verify the nonce
    if (!TSJIPPY\verifyNonce('nonce', 'file-upload')) {
        return new \WP_Error('tsjippy-file-upload', 'Please refresh the page and try again');
    }

    // phpcs:disable
    $settings     = TSJIPPY\sanitize($_POST['fileupload'] ?? []);

    $userId       = $settings['user-id'] ?? 0;

    // Check if we have permission when uploading for someone else
    if($userId != get_current_user_id() && !current_user_can('delete_others_posts')){
        return new \WP_Error('tsjippy-file-upload', 'You are not allowed to do this, sorry');
    }

    $fileUploader = new FileUploader(
        userId:       $userId, 
        library:      $settings['library'] ?? false, 
        callback:     $settings['callback'] ?? '', 
    );

    $fileUploader->processFiles(
        files:        $_FILES["files"], 
        targetDir:    TSJIPPY\sanitize($_POST['file-upload-target-dir'] ?? ''), 
        metaKey:      $settings['metakey'] ?? '',
        metaKeyIndex: $settings['meta-key-index'] ?? ''
    );

    $name         = '';
    $key          = '';
    foreach(array_keys($_POST) as $key){
        if(str_ends_with($key, '-files')){
            $name   = str_replace('-files', '', $key);
            break;
        }
    }

    $values       = [];
    foreach($fileUploader->filesArr as $data){
        $values[]   = $data['url'];
    }

    $multiple   =  str_contains($key, '[]');

    // Only return one value if multiple is not allowed
    if(!$multiple){
        $values = array_values($values)[0];
    }

    $html         = $fileUploader->getUploadHtml(inputName: $name, targetDir: $fileUploader->targetDir, multiple: $multiple, metaKey: $fileUploader->metaKey ?? '', value: $values, options: json_decode($settings['options'] ?? [], TRUE));
    // phpcs:enable

    if(count($fileUploader->filesArr) > 1){
        $message    = "The files have been uploaded succesfully.";
    }else{
        $message    = "The file ".basename($fileUploader->filesArr[0]['url'])." has been uploaded succesfully.";
    }

    echo wp_json_encode([
        'message' => $message,
        'html'    => $html
    ]);

    wp_die();
}
