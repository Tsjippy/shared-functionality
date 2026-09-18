<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

add_shortcode('tsjippy_debug', function ($atts) {
    wp_enqueue_script_module('@tsjippy/debug_script');

    return "<button type='button' id='exportLogsButton'>Export Debug Log</button>";
});
