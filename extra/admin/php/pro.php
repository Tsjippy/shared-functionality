<?php

namespace TSJIPPY\ADMIN;

use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Download new plugins or delete them
 */
function mainMenuActions()
{
    if (!empty($_GET['update'])) {
        if ($_GET['update'] == 'all') {
            TSJIPPY\GITHUB\checkForPluginUpdates();

        ?>
            <div class='success'>All plugins updated successfully</div>
        <?php

            return;
        }

        $slug        = TSJIPPY\sanitize($_GET['update']);

        if (updateOrDownloadPlugin($slug)) {
        ?>
            <div class="success">
                Plugin <?php echo esc_attr($slug); ?> succesfully updated.
            </div>
        <?php
        }
    }

    if (!empty($_GET['download'])) {
        $slug        = TSJIPPY\sanitize($_GET['download']);

        if (updateOrDownloadPlugin($slug)) {
        ?>
            <div class="success">
                Plugin <?php echo esc_attr($slug); ?> succesfully downloaded
            </div>
        <?php
        }
    }

    if (!empty($_GET['remove'])) {
        $slug        = TSJIPPY\sanitize($_GET['remove']);

        delete_option("tsjippy_{$slug}_settings");
    }
}

/**
 * Downloads a plugin from github and displays the error messages if any
 *
 * @param    string    $slug    The plugin slug
 *
 * @return    bool            true on succes, false on failure
 */
function updateOrDownloadPlugin($slug)
{
    $slug        = str_replace('tsjippy-', '', $slug);

    $github        = new TSJIPPY\GITHUB\Github();

    $result        = $github->downloadRelease('Tsjippy', $slug, WP_PLUGIN_DIR . '/tsjippy-' . $slug, true);

    if (is_wp_error($result)) {
        echo "<div class='error'>" . esc_attr($result->get_error_message()) . "</div>";

        return false;
    } elseif ($result) {
        // flush the cache so the plugin list updates
        wp_cache_flush();

        return true;
    } else {
        ?>
        <div class="error">
            Plugin <?php echo esc_attr($slug); ?> not found on github.<br><br>
            <?php
            if (!$github->authenticated) {
                $url            = admin_url("admin.php?page=tsjippy_github&main-tab=settings");
            ?> maybe you <a href='<?php echo esc_url($url); ?>'>should supply a github token</a> so I can try again while logged in.
            <?php
            }
            ?>
        </div>
<?php

        return false;
    }
}

add_filter('tsjippy-shared-functionality-menu-links', function($links, $plugin, $data){
    $slug       = basename($plugin, '.php');

    // Update links
    if (($_GET['update'] ?? '') == $slug) {
        // Reset updates cache
        delete_site_transient('update_plugins');
        delete_transient('tsjippy-git-release');

        wp_update_plugins();

        $updates    = get_site_transient('update_plugins');
        if (is_wp_error($updates)) {
            $link = "<div class='error'>" . $updates->get_error_message() . "</div>";
        } elseif (isset($updates->response[$plugin])) {
            $url    = self_admin_url('update.php?action=update-selected&amp;plugin=' . urlencode($plugin));
            $url    = wp_nonce_url($url, 'bulk-update-plugins');
            $link   = "<a href='$url' class='update-link'>Update to " . $updates->response[$plugin]->new_version . "</a>";
        } else {
            $url   = admin_url("plugins.php?update=$slug");
            $link  = "Up to date <a href='$url'>Check again</a>";
        }
    } else {
        $url   = admin_url("plugins.php?update=$slug");
        $link  = "<a href='$url'>Check for update</a>";
    }
    $links['update'] = $link;

    return $links;
}, 10, 3);
