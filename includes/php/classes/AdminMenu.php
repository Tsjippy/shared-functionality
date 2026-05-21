<?php
namespace TSJIPPY;
use TSJIPPY\ADMIN;

use function TSJIPPY\addRawHtml;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu extends ADMIN\SubAdminMenu{

    /**
     * AdminMenu constructor.
     * 
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name){
        parent::__construct($settings, $name);
    }

    public function settings($parent){
	    global $wp_roles;

        ob_start();
	
        ?>
        <label>
            Roles with access to the logs page<br>
            <br>
            <?php
            foreach($wp_roles->role_names as $slug => $name){
                ?>
                <label>
                    <input type='checkbox' name='roles[<?php echo esc_attr($slug);?>]' value='<?php echo esc_attr($slug);?>' <?php if(in_array($slug, $this->settings['roles'])){echo 'checked';}?>>
                    <?php
                    echo esc_attr($name);
                    ?>
                </label>
                <br>
                <?php
            }
            ?>
        </label>

        <?php

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function emails($parent){
        return false;
    }

    public function data($parent){
        return false;
    }

    public function functions($parent){
        return false;
    }

    /**
     * Schedules the tasks for this plugin
     *
    */
    public function postSettingsSave(){
        return true;
    }
}