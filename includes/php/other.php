<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

//Change the timeout on post locks
add_filter('wp_check_post_lock_window', __NAMESPACE__ . '\postLock');
function postLock()
{
    return 70;
}

//Change the extension of all jpg like files to jpe so that they are not directly available for non-logged in users
//add_filter('wp_handle_upload_prefilter', __NAMESPACE__ . '\beforeUpload', 1, 1);
/**
 * Change the extension of all jpg like files to jpe so that they are not directly available for non-logged in users
 * @param array $file An array of data for a single file, including 'name', 'type', 'tmp_name', 'error', and 'size' .
 * @return array The modified file array.
 */
function beforeUpload($file)
{
    $info     = pathinfo($file['name']);
    $ext      = empty($info['extension']) ? '' : ' . ' . $info['extension'];
    $name     = basename($file['name'], $ext);
    $ext     = strtolower($ext);

    //Change the extension to jpe
    if ($ext == " .jpg" || $ext == " .jpeg" || $ext == " .jfif" || $ext == " .exif") {
        $ext = " .jpe";
    }

    $file['name'] = $name . $ext;

    return $file;
}

// Disable auto-update email notifications for plugins.
add_filter('auto_plugin_update_send_email', '__return_false');
// Disable auto-update email notifications for themes.
add_filter('auto_theme_update_send_email', '__return_false');

//Hide adminbar
add_action('after_setup_theme', __NAMESPACE__ . '\showAdminBar');
function showAdminBar()
{
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}

//convert jpeg to webp doesnt seem to work
add_filter('image_editor_output_format', __NAMESPACE__ . '\addWebp');
/**
 * Add WebP format support for image editing
 * @param array $formats The supported image formats.
 * @return array The modified image formats.
 */
function addWebp($formats)
{
    $formats['image/jpg'] = 'image/webp';
    $formats['image/jpe'] = 'image/webp';
    return $formats;
}

//First acions for staging sites
if (get_option("wpstg_is_staging_site") == "true") {
    require_once(ABSPATH . 'wp-admin/includes/user.php');

    add_action('init', __NAMESPACE__ . '\stagingFirstRun');
}

function stagingFirstRun()
{
    global $wp_rewrite;

    if (str_contains($_SERVER['REQUEST_URI'], 'options-permalink.php') && get_option("first_run") == "") {
        flush_rewrite_rules();

        //Indicate that the first run has been done
        update_option("first_run", "first_run");
        //Get all users
        $users = get_users();
        //Only keep admins and editors
        $allowedRoles = array('administrator', 'editor');
        foreach ($users as $user) {
            //If this user is not an admin or editor
            if (!array_intersect($allowedRoles, $user->roles)) {
                printArray("Deleting user with id {$user->ID} as this is an staging site");
                //Delete user and assign its contents to the admin user
                wp_delete_user($user->ID, 1);
            }
        }

        //Set the permalinks
        $wp_rewrite->set_permalink_structure('/%category%/%postname%/');
        $wp_rewrite->flush_rules();
    }
}

//Keep line breaks in excerpts
remove_filter('get_the_excerpt', 'wp_trim_excerpt');
add_filter('get_the_excerpt', __NAMESPACE__ . '\customExcerpt', 10, 2);
add_filter('the_excerpt', __NAMESPACE__ . '\customExcerpt', 10, 2);
/**
 * Custom excerpt function that keeps line breaks
 * @param string $excerpt The excerpt.
 * @param \WP_Post|null $post The post object.
 * @return string The modified excerpt.
 */
function customExcerpt($excerpt, $post = null)
{
    $rawExcerpt = $excerpt;

    if (empty($excerpt)) {
        //Retrieve the post content.
        if (!empty($post)) {
            $excerpt = $post->post_content;
        }

        //Delete all shortcode tags from the content.
        $excerpt             = strip_shortcodes($excerpt);

        $excerpt             = str_replace(["]]>", "<p>", "</p>"], ["]]&gt;", "<br>", ""], $excerpt);
        $allowedTags         = '<br>,<strong>';
        $excerpt             = wp_strip_all_tags($excerpt, $allowedTags);

        while (substr($excerpt, 0, 4) == '<br>') {
            $excerpt    = trim(substr($excerpt, 4));
        }

        $excerptWordCount     = 45;
        $excerptLength         = apply_filters('excerpt_length', $excerptWordCount);

        $excerptMore         = apply_filters('excerpt_more', ' [...]');

        $words = preg_split("/[\n\r\t ]+/", $excerpt, $excerptLength + 1, PREG_SPLIT_NO_EMPTY);
        if (count($words) > $excerptLength) {
            array_pop($words);
            $excerpt = implode(' ', $words);
            $excerpt = "<div class='excerpt'>$excerpt </div>$excerptMore";
        } else {
            $excerpt = implode(' ', $words);
        }
    }

    return apply_filters('wp_trim_excerpt', $excerpt, $rawExcerpt);
}

//Remove the password protect of a page for logged in users
add_filter('post_password_required', __NAMESPACE__ . '\removePostPassword', 10, 2);
/**
 * Remove the password protect of a page for logged in users
 * @param bool $returned Whether the post is password protected. Default true.
 * @param \WP_Post $post The post being checked.
 * @return bool Whether the post is password protected. Default true.
 */
function removePostPassword($returned, $post)
{
    // Override it for logged in users:
    if ($returned && is_user_logged_in())
        $returned = false;

    return $returned;
}

// Make sure only the rest api response is echood and nothing else
add_filter('rest_request_after_callbacks', __NAMESPACE__ . '\cleanOutput');
/**
 * Clean the output after REST API callbacks to ensure only the response is returned.
 * @param mixed $response The response from the REST API callback.
 * @return mixed The cleaned response.
 */
function cleanOutput($response)
{
    clearOutput();
    return $response;
}

// only load needed block assets
add_filter('should_load_separate_core_block_assets', '__return_true');

/**
 * Get the user page ID if the function exists
 * @param int $userId The ID of the user.
 * @return int|false The user page ID or false if not found.
 */
function maybeGetUserPageId($userId)
{
    $userPageId    = false;

    if (function_exists('TSJIPPY\USERPAGES\getUserPageId')) {
        $userPageId = USERPAGES\getUserPageId($userId);
    }

    return $userPageId;
}

/**
 * Get the user page URL if the function exists
 * @param int $userId The ID of the user.
 * @return string|false The user page URL or false if not found.
 */
function maybeGetUserPageUrl($userId)
{
    $url    = apply_filters('tsjippy-user-page-url', false, $userId);

    return $url;
}

add_filter('wp_kses_allowed_html', function ($allowedposttags, $context) {
    $allowedposttags['input'] = [
        // Identification & Data
        'id'                   => true,
        'name'                 => true,
        'value'                => true,
        'type'                 => true,

        // State & Behavior
        'disabled'             => true,
        'readonly'             => true,
        'checked'              => true,
        'autofocus'            => true,
        'required'             => true,

        // Validation & Constraints
        'min'                  => true,
        'max'                  => true,
        'minlength'            => true,
        'maxlength'            => true,
        'pattern'              => true,
        'step'                 => true,

        // UI & Presentation
        'placeholder'          => true,
        'size'                 => true,
        'list'                 => true,
        'autocomplete'         => true,
        'multiple'             => true,
        'accept'               => true,
        'capture'              => true,
        'dirname'              => true,

        // Image Input Specific
        'alt'                  => true,
        'src'                  => true,
        'height'               => true,
        'width'                => true,

        // Form Overrides (Submit/Image)
        'form'                 => true,
        'formaction'           => true,
        'formenctype'          => true,
        'formmethod'           => true,
        'formnovalidate'       => true,
        'formtarget'           => true,

        // Interactive & Popover
        'popovertarget'        => true,
        'popovertargetaction'  => true,

        // Global Attributes
        'class'                => true,
        'style'                => true,
        'title'                => true,
        'accesskey'            => true,
        'autocapitalize'       => true,
        'autocorrect'          => true,
        'contenteditable'      => true,
        'dir'                  => true,
        'draggable'            => true,
        'enterkeyhint'         => true,
        'hidden'               => true,
        'inert'                => true,
        'inputmode'            => true,
        'lang'                 => true,
        'nonce'                => true,
        'part'                 => true,
        'slot'                 => true,
        'spellcheck'           => true,
        'tabindex'             => true,
        'translate'            => true,
        'virtualkeyboardpolicy' => true,
        'writingsuggestions'   => true,

        // Microdata / SEO
        'itemid'               => true,
        'itemprop'             => true,
        'itemref'              => true,
        'itemscope'            => true,
        'itemtype'             => true,
    ];

    $allowedposttags['select'] = [
        // Select Specifieke Attributen
        'autocomplete'         => true,
        'autofocus'            => true,
        'disabled'             => true,
        'form'                 => true,
        'multiple'             => true,
        'name'                 => true,
        'required'             => true,
        'size'                 => true,

        // Globale Attributen
        'id'                   => true,
        'class'                => true,
        'style'                => true,
        'title'                => true,
        'accesskey'            => true,
        'autocapitalize'       => true,
        'autocorrect'          => true,
        'contenteditable'      => true,
        'dir'                  => true,
        'draggable'            => true,
        'enterkeyhint'         => true,
        'hidden'               => true,
        'inert'                => true,
        'inputmode'            => true,
        'lang'                 => true,
        'nonce'                => true,
        'part'                 => true,
        'slot'                 => true,
        'spellcheck'           => true,
        'tabindex'             => true,
        'translate'            => true,
        'virtualkeyboardpolicy' => true,
        'writingsuggestions'   => true,

        // Microdata / SEO
        'itemid'               => true,
        'itemprop'             => true,
        'itemref'              => true,
        'itemscope'            => true,
        'itemtype'             => true,
    ];

    $allowedposttags['option'] = [
        // Standard Element Specific Attributes
        'disabled' => true,
        'label'    => true,
        'selected' => true,
        'value'    => true,

        // Common Global Attributes
        'class'    => true,
        'id'       => true,
        'style'    => true,
        'title'    => true,
        'lang'     => true,
        'dir'      => true,
    ];

    $allowedposttags['datalist'] = array(
        // The most critical attribute for <datalist> to map to an <input list=" ... ">
        'id'             => array(),

        // Core global styling and identification attributes
        'class'          => array(),
        'style'          => array(),
        'title'          => array(),
        'lang'           => array(),
        'dir'            => array(),

        // Accessibility attributes (ARIA)
        'aria-live'      => array(),
        'aria-label'     => array(),
        'aria-labelledby' => array(),

        // Custom data attributes (if you pass dynamic data via JavaScript)
        'data-*'         => array(), // Note: KSES requires explicit names like 'data-id' => array() if not using a global wild card filter
    );

    return $allowedposttags;
}, 10, 2);
