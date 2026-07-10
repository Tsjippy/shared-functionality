<?php

namespace TSJIPPY\ADMIN;

use TSJIPPY;

use function TSJIPPY\addElement;

if (! defined('ABSPATH')) exit;

abstract class SubAdminMenu
{

    public array $settings;
    public string $name;

    /**
     * AdminMenu constructor.
     *
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name)
    {
        $this->settings    = $settings;
        $this->name        = $name;
    }

    /**
     * @param   object  $node   The DOM Document node to add html to
     *
     * @return  bool            True if something was printed to screen false otherwise
     */
    abstract function settings($node);

    /**
     * @param   object  $node   The DOM Document node to add html to
     *
     * @return  bool    True if something was printed to screen false otherwise
     */
    abstract function emails($node);

    /**
     * @param   object  $node   The DOM Document node to add html to
     *
     * @return  bool    True if something was printed to screen false otherwise
     */
    abstract function data($node);

    /**
     * @param   object  $node   The DOM Document node to add html to
     *
     * @return  bool    True if something was printed to screen false otherwise
     */
    abstract function functions($node);

    public function handlePost()
    {
        $message    = '';

        // phpcs:ignore
        $request    = TSJIPPY\sanitize($_POST);
        $message    = $this->postActions($request);

        // do some checks 
        if (
            !isset($request['plugin']) ||
            !TSJIPPY\verifyNonce('nonce', 'plugin-settings')
        ) {
            return $message;
        }

        if (isset($request['emails'])) {
            $message    .= $this->saveEmails($request);
        } else {
            $message    .= $this->saveSettings($request);
        }

        // Build the message
        $plugin    = TSJIPPY\getFromTransient('plugin');
        if (isset($plugin)) {
            if (isset($plugin['installed'])) {
                $name        = ucfirst($plugin['installed']);
                $message    .= "<br><br>Dependend plugin '$name' succesfully installed and activated";
            } elseif (isset($plugin['activated'])) {
                $name        = ucfirst($plugin['activated']);
                $message    .= "<br><br>Dependend plugin '$name' succesfully activated";
            }
            TSJIPPY\deleteFromTransient('plugin');
        }

        return $message;
    }

    /**
     * Function to do extra actions from $request data. Overwrite if needed
     */
    public function postActions($request)
    {
        return '';
    }

    /**
     * Saves plugins settings from $request
     */
    public function saveSettings($request)
    {
        // phpcs:ignore
        $slug       = TSJIPPY\sanitize($_POST['plugin']);

        unset($request['plugin']);

        $this->settings = $request;

        $extraMessage   = $this->postSettingsSave($request);

        update_option("tsjippy_{$slug}_settings", $this->settings);

        return "<div class='success'>Settings succesfully saved $extraMessage</div>";
    }

    /**
     * Function to do extra actions after settings are saved
     */
    public function postSettingsSave($request)
    {
        return '';
    }

    /**
     * Save email settings
     * 
     * @param   array   $request    The sanitized requests
     */
    public function saveEmails($request)
    {
        $slug            = $request['plugin'] ?? '';

        // Invalid slug
        if(!isset(PLUGINSLUGS[$slug])){
            return;
        }

        $emailSettings   = $request['emails'] ?? [];

        unset($emailSettings['plugin']);

        foreach ($emailSettings as &$emailSetting) {
            $emailSetting = wp_unslash($emailSetting);
        }

        update_option("tsjippy_{$slug}_emails", $emailSettings);

        return "<div class='success'>E-mail settings succesfully saved</div>";
    }

    /**
     * Get html to select an image
     * @param    string         $key            the image key in the plugin settings
     * @param    string        $name            Human readable name of the picture
     * @param    \DOMElement    $parent            The parent node
     * @param    string        $type            The image type you allow
     */
    public function pictureSelector($key, $name, $parent, $type = '')
    {
        wp_enqueue_media();
        wp_enqueue_script('tsjippy_picture_selector_script', TSJIPPY\PLUGINURL . '/js/select_picture.min.js', array(), '7.0.0', true);
        wp_enqueue_style('tsjippy_picture_selector_style', TSJIPPY\PLUGINURL . '/css/picture_select.min.css', array(), '7.0.0');

        if (empty($this->settings['picture-ids'][$key])) {
            $hidden        = 'hidden';
            $src        = '';
            $id            = '';
            $text        = 'Select';
        } else {
            $id            = $this->settings['picture-ids'][$key];
            $src        = wp_get_attachment_image_url($id);
            $hidden        = '';
            $text        = 'Change';
        }

        $wrapper        = TSJIPPY\addElement('div', $parent, ['class' => 'picture-selector-wrapper']);

        $previewWrapper = TSJIPPY\addElement('div', $wrapper, ['class' => "image-preview-wrapper $hidden"]);

        TSJIPPY\addElement('img', $previewWrapper, ['loading' => 'lazy', 'class' => "image-preview", 'src' => $src, 'alt' => '']);

        $attributes     = [
            'type' => "button",
            'value' => "$text picture for $name",
            'class' => "button select-image-button"
        ];

        if (!empty($type)) {
            $attributes['data-type'] = $type;
        }

        TSJIPPY\addElement('input', $wrapper, $attributes);

        $attributes     = [
            'type'  => "hidden",
            'value' => $id,
            'class' => "no-reset image-attachment-id",
            'name'  => "picture-ids[$key]"
        ];

        if (!empty($type)) {
            $attributes['data-type'] = $type;
        }

        TSJIPPY\addElement('input', $wrapper, $attributes);
    }

    /**
     * Creates a dropdown to select a recurrence period
     *
     * @param   string      $name           The selector name
     * @param   string      $selectedValue  The current selected value
     * @param   string      $labelText      Text for the label
     * @param   DOMElement  $parent         The element to append the selector to
     */
    public function recurrenceSelector($name, $selectedValue, $labelText, $parent)
    {
        addElement('label', $parent, [], $labelText);
        addElement('br', $parent);

        $select     = addElement('select', $parent, ['name' => $name]);

        $options    = [
            'daily'         => 'Daily',
            'weekly'        => 'Weekly',
            'monthly'       => 'Monthly',
            'threemonthly'  => 'Every quarter',
            'sixmonthly'    => 'Every half a year',
            'yearly'        => 'Yearly'
        ];

        foreach ($options as $value => $name) {
            $attributes = [
                'value'     => $value
            ];

            if ($value == $selectedValue) {
                $attributes['selected'] = 'selected';
            }

            addElement(
                'option',
                $select,
                $attributes,
                $name
            );
        }
    }
}
