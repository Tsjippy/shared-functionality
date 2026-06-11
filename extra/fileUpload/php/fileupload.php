<?php

namespace TSJIPPY\FILEUPLOAD;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

//Make upload_files function availbale for AJAX request
add_action('wp_ajax_upload-files', __NAMESPACE__ . '\ajaxUploadFiles');
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

    $fileUploader = new FileUploader(
        files:        $_FILES["files"], 
        userId:       $settings['user-id'] ?? 0, 
        library:      $settings['library'] ?? false, 
        callback:     $settings['callback'] ?? '', 
        targetDir:    $settings['target-dir'] ?? '', 
        metaKey:      $settings['metakey'] ?? '',
        metaKeyIndex: $settings['meta-key-index'] ?? '',
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

    $html         = $fileUploader->getUploadHtml(inputName: $name, targetDir: $fileUploader->targetDir, multiple: str_contains($key, '[]'), metaKey: $fileUploader->metaKey ?? '', value: $values, options: json_decode($settings['options'] ?? [], TRUE));
    // phpcs:enable

    echo json_encode([
        'urls'  => $fileUploader->filesArr,
        'nonce' => wp_create_nonce('file-delete'),
        'html'  => $html
    ]);

    wp_die();
}
