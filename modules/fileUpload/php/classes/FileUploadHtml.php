<?php

namespace TSJIPPY\FILEUPLOAD;

use DOMDocument;
use TSJIPPY;

if (! defined('ABSPATH')) exit;

class FileUploadHtml
{
    public int            $userId;
    public string         $metaKey;
    public string|array   $value;
    public string|int     $metaKeyIndex;
    public bool           $library;  // store in media library
    public string         $callback; // Gets called before an file is processed after upload

    /**
     * Constructs the fileupload object
     *
     * @param    int    $userId     The wp WP_User id
     * @param    bool   $library    Whether to attach the upload to the wp library. Default false
     * @param    string $callback   The callback function to call after upload. Default empty
     */
    public function __construct($userId=0, $library = false, $callback = '')
    {
        $this->userId       = $userId;
        $this->library      = $library;
        $this->callback     = $callback;
    }

    /**
     * Finds the value in the user meta or options table of a given metaKey
     */
    public function processMetaKey()
    {
        if (!empty($this->value)) {
            return;
        }

        if (empty($this->metaKey)) {
            return ;
        }

        //get the db value
        if (!is_numeric($this->userId)) {
            return new \WP_Error('file upload', 'You need to be logged in to do this');
        }

        $this->value = get_user_meta($this->userId, $this->metaKey, true);

        //get subvalue if needed
        if(!empty($this->metaKeyIndex)){
            $this->value = $this->value[$this->metaKeyIndex] ?? '';
        }
    }

    /**
     * Renders the upload button
     * @param    string       $inputName           The name to use for the files input and storage in db
     * @param    string       $targetDir           The subfolder of the uploads folder. Default empty
     * @param    bool         $multiple            Whether to allow multiple files to be uploaded. Default false
     * @param    array        $options             Extra options to add to the files input element
     * @param    bool         $editBeforeUpload    Whether or not people can edit a picture before uploading it, default false
     * @param    array|string $value               The current value or array of values. Default empty for auto retrieval from meta db
     * @param    string       $metaKey             The key for storage in the user meta or options table. Default empty
     * @param    bool         $auto                Auto upload when a file or image is selected. Default true
     *
     * @return    string                           The input html
     */
    public function getUploadHtml($inputName, $targetDir = '', $multiple = false, $options = [], $editBeforeUpload = false, $value='', $metaKey = '', $auto = true, $echo = false)
    {
        //Load js
        wp_enqueue_script('tsjippy_fileupload_script');

        // Will only work if vimeo plugin is enabled
        // Exposes the vimeoUploader variable
        wp_enqueue_script('tsjippy_vimeo_uploader_script');

        wp_enqueue_style('tsjippy_image-edit');

        $this->metaKey      = $metaKey;
        if(!empty($this->metaKey) && !str_contains($this->metaKey, 'tsjippy_')){
            $this->metaKey    = 'tsjippy_' . $this->metaKey;
        }

        $this->metaKeyIndex = '';
        if(preg_match('/(.*?)\[(.*)\]/', $this->metaKey, $match)){
            $this->metaKeyIndex = $match[2];
            $this->metaKey      = $match[1];
        }

        $this->value    = $value;
        if(empty($this->value)){
            $this->processMetaKey();
        }

        $fileClass      = '';

        $dom            = new DOMDocument();
        if ($editBeforeUpload) {
            TSJIPPY\addElement('div', $dom, ['class' => 'image-edit-modal-trigger']);
            $fileClass    = 'should-edit';
        }

        $wrapper    = TSJIPPY\addElement('div', $dom, ['class' => 'file-upload-wrap']);
        $preview    = TSJIPPY\addElement('div', $wrapper, ['class' => 'document-preview']);
        $class      = '';

        if(!empty($this->value)) {
            $class = "hidden";

            if (is_array($this->value)) {
                foreach ($this->value as $documentKey => $document) {
                    if (!$this->documentPreview($document, $documentKey, $preview)) {
                        // remove from document array if the file is not valid
                        unset($this->value[$documentKey]);
                    }
                }
            } elseif (!$this->documentPreview($this->value, -1, $preview)) {
                $this->value    = '';
                $class          = '';
            }
        }

        $inputName      = "{$inputName}-files";
        if ($multiple) {
            $inputName .= '[]';
        }

        $uploadWrapper  = TSJIPPY\addElement('div', $wrapper, ['class' => "upload-div $class"]);
        $attributes     = [
            'class'          => "file-upload $fileClass", 
            'type'           => 'file', 
            'name'           => $inputName
        ];

        if (!$auto) {
            $attributes['class'] .= ' defer-upload';
        }
        
        if ($multiple) {
            $attributes['multiple'] = 'multiple';
        }

        /**
         * Add the options to the attributes
         */
        foreach($options as $key => $option){
            // This option type does not exist yet
            if(empty($attributes[$key])){
                $attributes[$key] = $option;
            }
            
            // Append to the existing option
            else{
                $attributes[$key] .= " $option";
            }
        }

        TSJIPPY\addElement('input', $uploadWrapper, $attributes);

        /**
         * Target Dir
         */
        if (!empty($targetDir)) {
            $targetDir    = wp_normalize_path($targetDir);
            TSJIPPY\addElement(
                'input', 
                $uploadWrapper, 
                [
                    'type'  => 'hidden', 
                    'class' => 'no-reset',
                    'name'  => 'file-upload-target-dir', 
                    'value' => $targetDir
                ]
            );
        }

        /**
         * Only add these elements if uploading straigt away
         */
        if($auto){
            /**
             * Nonce
             */
            TSJIPPY\addElement(
                'input', 
                $uploadWrapper, 
                [
                    'type'  => 'hidden', 
                    'class' => 'no-reset', 
                    'name'  => 'nonce', 
                    'value' => wp_create_nonce('file-upload')
                ]
            );

            /** 
             * User ID
             */
            if (is_numeric($this->userId)) {
                TSJIPPY\addElement(
                    'input', 
                    $uploadWrapper, 
                    [
                        'type'  => 'hidden', 
                        'class' => 'no-reset', 
                        'name'  => 'fileupload[user-id]', 
                        'value' => $this->userId
                    ]
                );
            }

            /**
             * Library
             */
            if (!empty($this->library)) {
                TSJIPPY\addElement(
                    'input', 
                    $uploadWrapper, 
                    [
                        'type'  => 'hidden', 
                        'class' => 'no-reset',
                        'name'  => 'fileupload[library]', 
                        'value' => $this->library
                    ]
                );
            }

            /**
             * Callback
             */
            if (!empty($this->callback)) {
                TSJIPPY\addElement(
                    'input', 
                    $uploadWrapper, 
                    [
                        'type'  => 'hidden', 
                        'class' => 'no-reset',
                        'name'  => 'fileupload[callback]', 
                        'value' => $this->callback
                    ]
                );
            }

            /**
             * Options
             */
            if (!empty($options)) {
                TSJIPPY\addElement(
                    'input', 
                    $uploadWrapper, 
                    [
                        'type'  => 'hidden', 
                        'class' => 'no-reset',
                        'name'  => 'fileupload[options]', 
                        'value' => json_encode($options)
                    ]
                );
            }

            /**
             * Allow edit
             */
            if ($editBeforeUpload) {
                TSJIPPY\addElement(
                    'input', 
                    $uploadWrapper, 
                    [
                        'type'  => 'hidden', 
                        'class' => 'no-reset',
                        'name'  => 'fileupload[edit]', 
                        'value' => $editBeforeUpload
                    ]
                );
            }

            /**
             * Meta Key
             */
            if (!empty($this->metaKey)) {
                TSJIPPY\addElement(
                    'input', 
                    $uploadWrapper, 
                    [
                        'type'  => 'hidden', 
                        'class' => 'no-reset',
                        'name'  => 'fileupload[metakey]', 
                        'value' => $this->metaKey
                    ]
                );

                if(!empty($this->metaKeyIndex)){
                    TSJIPPY\addElement(
                        'input', 
                        $uploadWrapper, 
                        [
                            'type'  => 'hidden', 
                            'class' => 'no-reset',
                            'name'  => 'fileupload[metakey-index]', 
                            'value' => $this->metaKeyIndex
                        ]
                    );
                }
            }
        }

        if(!$echo){
            return $dom->saveHTML();
        }
        
        echo $dom->saveHTML();
    }

    /**
     * Renders the already uploaded images or show the link to a file
     *
     * @param    string|int  $path    The url, filepath or WP attachment id of a file
     * @param    int         $index           The metakey sub key
     * @param    \DOMElement $parent          Parent DOMElement to append to
     * 
     * @return   \WP_Error|false              False or error on failure, true on succes
     */
    public function documentPreview($path, $index, $parent)
    {
        if (is_array($path)) {
            if (count($path) == 1) {
                $path    = array_values($path)[0];
            } else {
                return new \WP_Error('tsjippy-file-upload', 'Please supply a string, not an array');
            }
        }

        if (is_numeric($path) && $this->library) {
            $url = wp_get_attachment_url($path);

            if ($url === false) {
                return false;
            } else {
                $libraryId       = $path;
                $path    = $url;
            }
        } elseif (gettype($path) != 'string' || !is_file(TSJIPPY\urlToPath($path))) {
            return false;
        }

        $wrapper    = TSJIPPY\addElement('div', $parent, ['class' => 'document']);
        TSJIPPY\addElement('input', $wrapper, ['type' => 'hidden', 'class' => 'no-reset', 'name' => 'url',   'value' => $path]);
        TSJIPPY\addElement('input', $wrapper, ['type' => 'hidden', 'class' => 'no-reset', 'name' => 'nonce', 'value' => wp_create_nonce("file-delete-$path")]);
        
        TSJIPPY\addElement('input', $wrapper, ['type' => 'hidden', 'class' => 'no-reset', 'name' => 'user-id', 'value' => $this->userId]);

        if ($index == -1 ) {
            $this->value = $this->metaKey;
        } else {
            $this->value = $this->metaKey . '[' . $index . ']';
        }
        TSJIPPY\addElement('input', $wrapper, ['type' => 'hidden', 'class' => 'no-reset', 'name' => 'metakey', 'value' => $this->value]);

        if (!empty($libraryId)) {
            TSJIPPY\addElement('input', $wrapper, ['type' => 'hidden', 'class' => 'no-reset', 'name' => 'libraryid', 'value' => $libraryId]);
        }  

        if ($this->callback != '') {
            TSJIPPY\addElement('input', $wrapper, ['type' => 'hidden', 'class' => 'no-reset', 'name' => 'callback', 'value' => $this->callback]);
        }

        //path is already an url
        $url = '';
        if (str_contains($path, TSJIPPY\SITEURL)) {
            $url = $path;
        } elseif (!empty($path)) {
            $url = content_url($path);
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
            $fileName       = basename($path);

            //remove the username from the filename if it is there
            $userName     = get_userdata($this->userId)->user_login;
            $fileName     = str_replace($userName . '-', '', $fileName);

            //add the hyperlink to the file to the html
            TSJIPPY\addElement('a', $wrapper, ['href' => $url], $fileName);
        }

        //Add an remove button
        TSJIPPY\addElement('button', $wrapper, [
            'class'           =>'remove-document button',
            "type"            => 'button'
        ], 'X');

        return true;
    }
}
