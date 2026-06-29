<?php

namespace TSJIPPY;

if (!defined(__NAMESPACE__ . '\PLUGINPATH')) {
    if (! defined('ABSPATH')) {
        exit;
    }

    if (!isset(['document' => 1, 'empty' => 1, 'iframe' => 1][$_SERVER['HTTP_SEC_FETCH_DEST'] ?? 'document'])) {
        // Do not run plugin when requesting an image
        exit;
    }

    $TsjippyClassFiles = [];

    // Make sure logging works
    require_once(__DIR__.'/php/db-helpers.php');

    /**
     * Finds all classfiles for tsjippy- plugins
     */
    function getClassFiles()
    {
        global $TsjippyClassFiles;

        if (!empty($TsjippyClassFiles)) {
            return $TsjippyClassFiles;
        }

        // Find all class files in all tsjippy- plugins
        $classPaths = glob("{," . WP_PLUGIN_DIR . "/tsjippy-*,". __DIR__ . ",". __DIR__ . "/modules/*}/php/{classes,traits}/*.php", GLOB_BRACE);

        foreach ($classPaths as $file) {
            $className  = basename($file, '.php');

            $nameSpace  = strtoupper(str_replace(['tsjippy-', '-'], '', basename(dirname(dirname(dirname($file))))));

            if ($nameSpace == "SHAREDFUNCTIONALITY") {
                $nameSpace = 'TSJIPPY';
            }

            // Store the file path for the class name in an array in case there are multiple classes with the same name in different namespaces
            if (!isset($TsjippyClassFiles[$nameSpace])) {
                $TsjippyClassFiles[$nameSpace] = [];
            }

            $TsjippyClassFiles[$nameSpace][$className] = str_replace('\tsjippy-shared-functionality/..', '', $file);
        }

        return $TsjippyClassFiles;
    }

    // Class loader function
    spl_autoload_register(function ($classname) {
        $TsjippyClassFiles = getClassFiles();

        $path       = explode('\\', $classname);

        if ($path[0] != 'TSJIPPY' || count($path) < 1) {
            return;
        }

        $className  = array_pop($path);

        if (count($path) > 1) {
            $nameSpace  = array_pop($path);
        } else {
            $nameSpace = 'TSJIPPY';
        }

        $classFile    = $TsjippyClassFiles[$nameSpace][$className] ?? '';
        if (!empty($classFile) && file_exists($classFile)) {
            require_once($classFile);
            return;
        } else {
            // If the class file does not exist, throw an error
            //trigger_error(esc_html("Class $classname not found in file $classFile"), E_USER_ERROR);

            return false;
        }
    });

    
    // Load all shared-functionality files
    // phpcs:ignore
    foreach (glob( "{".__DIR__ .",".__DIR__ ."/blocks,".__DIR__ . "/php,".__DIR__ . "/modules/*/php}/*.php", GLOB_BRACE) as $file) {
        require_once($file);
    }
    unset($file);

    add_action("plugins_loaded", __NAMESPACE__ . '\loadPHPFiles');
    function loadPHPFiles()
    {
        /**
         * Get active tsjippy plugins so we only load the files of active plugins
         */
        $plugins        = wp_get_active_and_valid_plugins();
        $tsjippyPlugins = [];
        $libraryLoaders = [];
        foreach ($plugins as $plugin) {
            if (strpos($plugin, 'tsjippy-') !== false) {
                $tsjippyPlugins[]   = basename($plugin, '.php');

                $libLoader  = pathinfo($plugin, PATHINFO_DIRNAME) . "/lib/vendor/autoload.php";
                if (file_exists($libLoader)) {
                    $libraryLoaders[]  = $libLoader;
                }
            }
        }

        $globPattern   = "{" . implode(",", $tsjippyPlugins) . "}";

        //Load all plugin files
        $files = array_merge($libraryLoaders, glob(WP_PLUGIN_DIR . "/$globPattern/{php,blocks}/*.php", GLOB_BRACE));

        foreach ($files as $file) {
            $result = require_once($file);

            if (is_wp_error($result)) {
                ?>
                <div class='error' style='background-color:white;'>
                    <?php echo esc_html($result->get_error_message()); ?>
                </div>
                <?php
            }
        }
    }
}
