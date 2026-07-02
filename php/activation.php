<?php

namespace TSJIPPY;

if (!defined('ABSPATH')) {
    exit;
}

// run before activation
function activate() {
    $logger = new Logger();

    $logger->createDbTable();
    
    // Create private upload folder
    $path   = wp_upload_dir()['basedir'] . '/private';
    if (!is_dir($path)) {
        wp_mkdir_p($path);
    }

    $family = new FAMILY\Family();
    $family->createDbTables();
}

// Run after activation
add_action('activated_plugin', function ($plugin) {
    /**
     * Redirect to settings page after plugin activation
     * If it is activated from the plugins page and not in bulk
     */
    if (
        str_contains($plugin, 'tsjippy') &&             // Its a tsjippy plugin
        // phpcs:ignore
        ($_REQUEST['bulk_action'] ?? '') != 'Apply' &&  // Not in bulk
        // phpcs:ignore
        ($_REQUEST['action'] ?? '') == 'activate'       // Activating
    ) {
        $page   = basename($plugin, '.php');

        exit(esc_url(wp_safe_redirect(admin_url("admin.php?page=$page"))));
    }
});