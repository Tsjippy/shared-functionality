<?php

namespace TSJIPPY\FILEUPLOAD;

use DOMDocument;
use TSJIPPY;

if (! defined('ABSPATH')) exit;

class FileUploadHtml
{
    public $userId;
    public $metaKey;
    public $metaValue;
    public $library;
    public $callback;
    public $updatemeta;

    /**
     * Constructs the fileupload object
     *
     * @param     int        $userId        The wp WP_User id
     * @param    string    $metaKey    The key for storage in the user meta or options table. Default empty
     * @param    bool    $library    Whether to attach the upload to the wp library. Default false
     * @param    string    $callback    The callback function to call after upload. Default empty
     * @param    bool    $updatemeta    Whether or not to update the user meta. Default true
     * @param    string    $metaValue    The key for storage in the user meta or options table. Default empty
     */
    public function __construct($userId, $metaKey = '', $library = false, $callback = '', $updatemeta = true, $metaValue = '')
    {
        $this->userId       = $userId;
        $this->metaKey      = $metaKey;
        $this->metaValue    = $metaValue;
        $this->library      = $library;
        $this->callback     = $callback;
        $this->updatemeta   = $updatemeta;

        //Load js
        wp_enqueue_script('tsjippy_fileupload_script');

        // Will only work if vimeo plugin is enabled
        // Exposes the vimeoUploader variable
        wp_enqueue_script('tsjippy_vimeo_uploader_script');

        wp_enqueue_style('tsjippy_image-edit');
    }

    /**
     * Finds the value in the user meta or options table of a given metaKey
     */
    public function processMetaKey()
    {
        if (empty($this->metaKey)) {
            return '';
        }

        if (!empty($this->metaValue)) {
            return $this->metaValue;
        }

        //get the basemetaKey in case of an indexed one
        if (preg_match('/(.*?)\[/', $this->metaKey, $match)) {
            $baseMetaKey    = $match[1];
        } else {
            //just use the whole, it is not indexed
            $baseMetaKey    = $this->metaKey;
        }

        //get the db value
        if (is_numeric($this->userId)) {
            $documentArray = get_user_meta($this->userId, $baseMetaKey, true);
        } else {
            $documentArray = get_option($baseMetaKey);
        }

        //get subvalue if needed
        $documentArray = TSJIPPY\getMetaArrayValue($this->userId, $this->metaKey, $documentArray);

        return $documentArray;
    }

    /**
     * Renders the upload button
     * @param    string    $documentName        The name to use for the files input and storage in db
     * @param    string    $targetDir           The subfolder of the uploads folder. Default empty
     * @param    bool      $multiple            Whether to allow multiple files to be uploaded. Default false
     * @param    array     $options             Extra options to add to the files input element
     * @param    bool      $editBeforeUpload    Whether or not people can edit a picture before uploading it, default false
     *
     * @return    string                        The input html
     */
    public function getUploadHtml($documentName, $targetDir = '', $multiple = false, $options = [], $editBeforeUpload = false)
    {
        $documentArray  = $this->processMetaKey();

        $fileClass      = '';

        $dom            = new DOMDocument();
        if ($editBeforeUpload) {
            TSJIPPY\addElement('div', $dom, ['class' => 'image-edit-modal-trigger']);
            $fileClass    = 'should-edit';
        }

        $wrapper    = TSJIPPY\addElement('div', $dom, ['class' => 'file-upload-wrap']);
        $preview    = TSJIPPY\addElement('div', $wrapper, ['class' => 'document-preview']);

        if (is_array($documentArray) && !empty($documentArray)) {
            foreach ($documentArray as $documentKey => $document) {
                if (!$this->documentPreview($document, $documentKey, $preview, $multiple)) {
                    // remove from document array if the file is not valid
                    unset($documentArray[$documentKey]);
                }
            }
        } elseif (!is_array($documentArray) && $documentArray != "") {
            if (!$this->documentPreview($documentArray, -1, $preview, $multiple)) {
                $documentArray    = '';
            }
        }

        $class         = '';
        $inputName    = "{$documentName}-files";
        if ($multiple) {
            $inputName            .= '[]';
        } else {
            if (!empty($documentArray)) {
                $class = "hidden";
            }
        }

        $uploadWrapper    = TSJIPPY\addElement('div', $preview, ['class' => "upload-div $class"]);
        $attributes = [
            'class' => "file-upload $fileClass", 
            'type'  => 'file', 
            'name'  => $inputName
        ];
        
        if ($multiple) {
            $attributes['multiple'] = 'multiple';
        }

        $attributes = $attributes + $options;

        TSJIPPY\addElement('input', $uploadWrapper, $attributes);

        $flexDiv    = TSJIPPY\addElement('div', $uploadWrapper, ['style' => 'width:100%; display:flex']);

        if (is_numeric($this->userId)) {
            TSJIPPY\addElement(
                'input', 
                $flexDiv, 
                [
                    'type'  => 'hidden', 
                    'class' => 'no-reset', 
                    'name'  => 'fileupload[user-id]', 
                    'value' => $this->userId
                ]
            );
        }
        if (!empty($targetDir)) {
            $targetDir    = str_replace('\\', '/', $targetDir);
            TSJIPPY\addElement(
                'input', 
                $flexDiv, 
                [
                    'type'  => 'hidden', 
                    'class' => 'no-reset',
                    'name'  => 'fileupload[targetDir]', 
                    'value' => $targetDir
                ]
            );
        }
        if (!empty($this->metaKey)) {
            TSJIPPY\addElement(
                'input', 
                $flexDiv, 
                [
                    'type'  => 'hidden', 
                    'class' => 'no-reset',
                    'name'  => 'fileupload[metakey]', 
                    'value' => $this->metaKey
                ]
            );

            TSJIPPY\addElement(
                'input', 
                $flexDiv, 
                [
                    'type'  => 'hidden', 
                    'class' => 'no-reset',
                    'name'  => 'fileupload[metakey-index]', 
                    'value' => $documentName
                ]
            );
        }

        if (!empty($this->library)) {
            TSJIPPY\addElement(
                'input', 
                $flexDiv, 
                [
                    'type'  => 'hidden', 
                    'class' => 'no-reset',
                    'name'  => 'fileupload[library]', 
                    'value' => $this->library
                ]
            );
        }
        if (!empty($this->callback)) {
            TSJIPPY\addElement(
                'input', 
                $flexDiv, 
                [
                    'type'  => 'hidden', 
                    'class' => 'no-reset',
                    'name'  => 'fileupload[callback]', 
                    'value' => $this->callback
                ]
            );
        }

        TSJIPPY\addElement(
            'input', 
            $flexDiv, 
            [
                'type'  => 'hidden', 
                'class' => 'no-reset',
                'name'  => 'fileupload[updatemeta]', 
                'value' => $this->updatemeta
            ]
        );

        return $dom->saveHTML();
    }

    /**
     * Renders the already uploaded images or show the link to a file
     *
     * @param    string|int  $documentPath    The url, filepath or WP attachment id of a file
     * @param    int         $index           The metakey sub key
     * @param    \DOMElement $parent          Parent DOMElement to append to
     * @param    bool        $multiple        Whether to allow multiple files to be uploaded. Default false
     * 
     * @return   \WP_Error|false              False or error on failure, true on succes
     */
    public function documentPreview($documentPath, $index, $parent, $multiple = false)
    {
        $metaValue        = $documentPath;

        if (is_array($documentPath)) {
            if (count($documentPath) == 1) {
                $documentPath    = array_values($documentPath)[0];
            } else {
                return new \WP_Error('tsjippy-file-upload', 'Please supply a string, not an array');
            }
        }

        if (is_numeric($documentPath) && $this->library) {
            $url = wp_get_attachment_url($documentPath);

            if ($url === false) {
                return false;
            } else {
                $libraryId        = $documentPath;
                $documentPath    = $url;
            }
        } elseif (gettype($documentPath) != 'string' || !is_file(TSJIPPY\urlToPath($documentPath))) {
            return false;
        }

        $name    = $this->metaKey;
        if ($multiple) {
            $name    .= '[]';
        }

        $wrapper    = TSJIPPY\addElement('div', $parent, ['class' => 'document']);
        TSJIPPY\addElement('input', $wrapper, ['type' => 'hidden', 'class' => 'no-reset', 'name' => $name, 'value' => $metaValue]);

        //documentpath is already an url
        $url = '';
        if (str_contains($documentPath, SITEURL)) {
            $url = $documentPath;
        } elseif (!empty($documentPath)) {
            $url = SITEURL . '/' . str_replace(ABSPATH, '', $documentPath);
        }
        //Check if file is an image
        $path    = TSJIPPY\urlToPath($url);
        if (file_exists($path) && getimagesize($path) !== false) {
            //Display the image
            $anchor = TSJIPPY\addElement('a', $wrapper, ['href' => $url]);

            TSJIPPY\addElement('img', $anchor, ['src' => $url, 'alt' => 'picture', 'loading' => 'lazy', 'style' => 'height:150px;']);

            //File is not an image
        } else {
            //Display an link to the file
            $fileName = basename($documentPath);

            //remove the username from the filename if it is there
            $userName     = get_userdata($this->userId)->user_login;
            $fileName     = str_replace($userName . '-', '', $fileName);

            //add the hyperlink to the file to the html
            TSJIPPY\addElement('a', $wrapper, ['href' => $url], $fileName);
        }

        //Add an remove button
        $attributes = [
            'class'           =>'remove-document button',
            'data-url'        => $documentPath,
            'data-user-id'    => $this->userId,
            'data-nonce'      => wp_create_nonce('file-delete'),
            "data-updatemeta" => $this->updatemeta,
            "type"            => 'button'
        ];

        if ($index == -1) {
            $attributes['data-metakey'] = $this->metaKey;
        } else {
            $attributes['data-metakey'] = $this->metaKey . '[' . $index . ']';
        }

        if (!empty($libraryId)) {
            $attributes["data-libraryid"] = $libraryId;
        }  

        if ($this->callback != '') {
            $attributes["data-callback"] = $this->callback;
        }

        TSJIPPY\addElement('button', $wrapper, $attributes, 'X');

        return true;
    }
}
