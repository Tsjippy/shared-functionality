<?php

namespace TSJIPPY\GITHUB;

use TSJIPPY;
use TSJIPPY\ADMIN;

use function TSJIPPY\addRawHtml;

if (! defined('ABSPATH')) {
    exit;
}

class AdminMenu extends ADMIN\SubAdminMenu
{

    /**
     * AdminMenu constructor.
     *
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name)
    {
        parent::__construct($settings, $name);
    }

    /**
     * Add the settings page to the admin menu
     *
     * @param \DOMElement $parent The parent menu slug
     * 
     * @return bool True if the settings page was added, false otherwise
     */
    public function settings($parent)
    {
        ob_start();

        ?>
        <label>
            Github access token. Needed to access private repositories.<br>
            Create one <a href='https://github.com/settings/tokens/new'>here</a>.<br>
            <input type='text' name='token' value='<?php echo esc_attr($this->settings['token'] ?? ''); ?>' style='min-width:300px'>
        </label>
        <?php   

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    /**
     * Function to display the emails page
     *
     * @param   string  $parent The parent menu slug
     * 
     * @return  bool            True if the emails page was displayed, false otherwise
     */
    public function emails($parent)
    {
        return false;
    }

    /**
     * Add the data page to the admin menu
     *
     * @param string $parent The parent menu slug
     * 
     * @return bool True if the data page was added, false otherwise
     */
    public function data($parent)
    {
        return false;
    }

    /**
     * Add the functions page to the admin menu
     *
     * @param string $parent The parent menu slug
     * 
     * @return bool True if the functions page was added, false otherwise
     */
    public function functions($parent)
    {
        return false;
    }
}
