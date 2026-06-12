<?php

namespace TSJIPPY\FILEUPLOAD;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

class FileUploader extends FileUploadHtml
{
    public int        $maxSize;
    public string     $username;
    public string     $targetDir;
    public array      $files;
    public array      $filesArr;
    public string     $key;
    public string     $fileName;
    public string     $targetFile;

    public function __construct($files, $userId=0, $library = false, $callback = '', $targetDir = '', $metaKey = '', $metaKeyIndex = '')
    {
        parent::__construct($userId, $library, $callback);

        $this->maxSize      = wp_max_upload_size();
        $this->username     = '';
        $this->key          = '';
        $this->fileName     = '';
        $this->targetFile   = '';
        $this->filesArr     = [];
        $this->files        = $files;

        if (!empty($targetDir)) {
            $this->targetDir  = wp_upload_dir()['basedir'] . '/' . $targetDir . '/';
        } else {
            $this->targetDir  = wp_upload_dir()['basedir'] . '/';
        }

        //create folder if it does not exist
        if (!is_dir($this->targetDir)) {
            wp_mkdir_p($this->targetDir);
        }

        if (!empty($userId)) {
            $this->username   = get_userdata($this->userId)->user_login;
        }

        $this->metaKey        = TSJIPPY\sanitize($metaKey);

        if(!empty($this->metaKey) &&!str_contains($this->metaKey, 'tsjippy_')){
            $this->metaKey    = 'tsjippy_' . $this->metaKey;
        }

        $this->metaKeyIndex   = TSJIPPY\sanitize($metaKeyIndex);

        $this->processFiles();

        if (!empty($callback)) {
            call_user_func($callback, $this->userId);
        }
    }

    public function processFiles()
    {
        foreach ($this->files['name'] as $this->key => $this->fileName) {
            //check file size
            if ($this->files['size'][$this->key] > $this->maxSize) {
                wp_die(esc_html('File to big, max file size is ' . $this->maxSize / 1024 / 1024 . 'MB'));
            }

            $this->findFileName();

            $this->moveFile();

            if (!empty($this->metaKey)) {
                $this->addToDb();
            }
        }
    }

    /**
     * Finds the first available filename
     */
    public function findFileName()
    {
        $this->fileName     = sanitize_file_name(wp_unslash($this->fileName));

        //Create the filename
        $i = 0;
        if (strtolower(substr($this->fileName, 0, strlen($this->username))) == strtolower($this->username)) {
            $this->targetFile = $this->targetDir . $this->fileName;
        } else {
            $this->targetFile = $this->targetDir . $this->username . '-' . $this->fileName;
        }

        while (file_exists($this->targetFile)) {
            $i++;

            // check if the file already exists
            if (md5_file($this->files['tmp_name'][$this->key]) == md5_file($this->targetFile)) {
                return false;
            }

            if (strtolower(substr($this->fileName, 0, strlen($this->username))) == strtolower($this->username)) {
                $this->targetFile = $this->targetDir . $i . '-' . $this->fileName;
            } else {
                $this->targetFile = $this->targetDir . $this->username . '-' . $i . '-' . $this->fileName;
            }
        }
    }

    public function moveFile()
    {
        //Move the file if it does not already exist
        if (!file_exists($this->targetFile)) {
            $wpFileSystem   = TSJIPPY\loadWpFileSystem();

            $moved = $wpFileSystem->move($this->files['tmp_name'][$this->key], $this->targetFile);

            if (!$moved) {
                header('HTTP/1.1 500 Internal Server Booboo');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(array('error' => "File is not uploaded")));
            }
        }

        /**
         * Filters the destination path for a file upload
         * 
         * @param string $destination   The targetfile path
         */
        $path    = apply_filters('tsjippy-file-upload-path', $this->targetFile);

        array_push($this->filesArr, ['url' => TSJIPPY\pathToUrl($path)]);
    }

    public function addToDb()
    {
        //get the basemetakey in case of an indexed one
        if (!empty($this->metaKeyIndex) && preg_match_all('/(.*?)\[(.*?)\]/i', $this->metaKey, $matches)) {
            $baseMetaKey    = $matches[1][0];
            $keys           = $matches[2];
        } else {
            //just use the whole, it is not indexed
            $baseMetaKey    = $this->metaKey;
        }

        if(!str_contains($baseMetaKey, 'tsjippy_')){
            $baseMetaKey = 'tsjippy_'.$baseMetaKey;
        }

        $newValue    = $this->targetFile;

        //Add to library if needed
        if ($this->library) {
            $attachId    = TSJIPPY\addToLibrary($this->targetFile);

            $newValue    = $attachId;

            //store the id in the array
            $this->filesArr[count($this->filesArr) - 1]['id'] = $attachId;
        }

        if (!empty($this->metaKeyIndex) || !empty($keys)) {
            if (!is_numeric($this->userId)) {
                //generic documents
                $metaValue = get_option($baseMetaKey);
            } else {
                $metaValue = get_user_meta($this->userId, $baseMetaKey, true);
            }

            if (!empty($keys)) {
                TSJIPPY\addToNestedArray($keys, $metaValue, $newValue);
            }

            if (!is_array($metaValue)) {
                $metaValue  = [];
            }
            $newValue[$this->metaKeyIndex] = $metaValue;
        }

        if (!is_numeric($this->userId)) {
            //generic documents
            update_option($baseMetaKey, $newValue);
        } elseif (!empty($this->metaKey)) {
            update_user_meta($this->userId, $baseMetaKey, $newValue);
        }
    }
}
