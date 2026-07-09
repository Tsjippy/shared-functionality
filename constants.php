<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

// Define constants
define(__NAMESPACE__ . '\PLUGINPATH', __DIR__ . '/');
define(__NAMESPACE__ . '\SITEURL', site_url('', 'https'));
define(__NAMESPACE__ . '\SITEURLWITHOUTSCHEME', str_replace(['https://', 'http://'], '', SITEURL));
define(__NAMESPACE__ . '\SITENAME', get_bloginfo());
define(__NAMESPACE__ . '\PLUGINURL', plugins_url('', __FILE__));
define(__NAMESPACE__ . '\PICTURESURL', PLUGINURL . '/pictures');
define(__NAMESPACE__ . '\PLUGINFOLDER', plugin_dir_path(__FILE__));
define(__NAMESPACE__ . '\PICTURESPATH', PLUGINFOLDER . 'pictures/');
define(__NAMESPACE__ . '\RESTAPIPREFIX', 'tsjippy/v2');
define(__NAMESPACE__ . '\DATEFORMAT', get_option('date_format'));
define(__NAMESPACE__ . '\TIMEFORMAT', get_option('time_format'));
define(__NAMESPACE__ . '\STYLEVERSION', '11.1');
