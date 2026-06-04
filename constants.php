<?php
namespace TSJIPPY;

if ( ! defined('ABSPATH')) exit;

$pluginData = get_plugin_data(WP_PLUGIN_DIR. '/' .PLUGIN, false, false);

// Define constants
define(__NAMESPACE__ . '\PLUGINNAME', 'tsjippy-shared-functionality');
define(__NAMESPACE__ . '\PLUGINVERSION', get_plugin_data(__FILE__, false, false)['Version']);
define('SITEURL', site_url('', 'https'));
define('SITEURLWITHOUTSCHEME', str_replace(['https://', 'http://'], '', SITEURL));
define('SITENAME', get_bloginfo());
define(__NAMESPACE__ . '\INCLUDESURL', plugins_url('includes', __FILE__));
define(__NAMESPACE__ . '\PICTURESURL', INCLUDESURL. '/pictures');
define(__NAMESPACE__ . '\PLUGINFOLDER', plugin_dir_path(__FILE__));
define(__NAMESPACE__ . '\INCLUDESPATH', PLUGINFOLDER. 'includes/');
define(__NAMESPACE__ . '\PICTURESPATH', INCLUDESPATH. 'pictures/');
define('RESTAPIPREFIX', 'tsjippy/v2');
define('DATEFORMAT', get_option('date_format'));
define('TIMEFORMAT', get_option('time_format'));