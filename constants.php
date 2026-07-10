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
define(__NAMESPACE__.'\PLUGINSLUGS', [
    'bookings' => 1,
    'captcha' => 1,
    'comments' => 1,
    'content-filter' => 1,
    'default-pictures' => 1,
    'embed-page' => 1,
    'events' => 1,
    'html-email' => 1,
    'forms' => 1,
    'frontend-posting' => 1,
    'heic-to-jpeg' => 1,
    'library' => 1,
    'locations' => 1,
    'login' => 1,
    'mailchimp' => 1,
    'maintenance' => 1,
    'mandatory' => 1,
    'media-gallery' => 1,
    'page-gallery' => 1,
    'pdf' => 1,
    'prayer' => 1,
    'projects' => 1,
    'positional-accounts' => 1,
    'querier' => 1,
    'statistics' => 1,
    'schedules' => 1,
    'user-management' => 1,
    'user-pages' => 1,
    'welcome-message' => 1,
    'signal' => 1,
    'vimeo' => 1,
]);