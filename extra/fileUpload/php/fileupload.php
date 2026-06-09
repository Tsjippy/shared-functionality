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
    $settings = TSJIPPY\sanitize($_POST['fileupload'] ?? []);

    $files    = TSJIPPY\sanitize($_FILES["files"]);
    // phpcs:enable

    $fileUploader    = new FileUploader($settings, $files);

    echo json_encode([
        'urls'  => $fileUploader->filesArr,
        'nonce' => wp_create_nonce('file-delete')
    ]);

    wp_die();
}
