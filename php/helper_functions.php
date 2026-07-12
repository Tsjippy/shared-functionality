<?php

namespace TSJIPPY;

use WP_Error;

if (! defined('ABSPATH')) exit;

/**
 * Create a dropdown with all users
 * @param    bool        $returnFamily      Whether we should group families in one entry default false
 * @param    bool        $adults            Whether we should only get adults
 * @param    array        $fields            Extra fields to return
 * @param    array        $extraArgs        An array of extra query arguments
 * @param    array        $excludeIds        An array of user id's to be excluded
 *
 * @return    array                        An array of WP_Users
 */
function getUserAccounts($returnFamily = false, $adults = true, $fields = [], $extraArgs = [], $excludeIds = [1], $uniqueDisplayName = false)
{
    $doNotProcess         = $excludeIds;
    $cleanedUserArray     = [];
    $family                = new FAMILY\Family();
    $arg                 = [];

    if (!empty($fields)) {
        $arg['fields'] = $fields;
    }

    $arg     = array_merge_recursive($arg, $extraArgs);

    $users  = get_users($arg);

    //Loop over the users and remove any user who should not be in the dropdown
    foreach ($users as $user) {
        // If ‘fields‘ is set to any individual wp_users table field, an array of IDs will be returned.
        // In that case the user will not be an object
        if (is_object($user)) {
            $userId    = $user->ID;
        } else {
            $userId    = $user;
        }
        //If we should only return families
        if ($returnFamily) {
            //Current user is a child, exclude it
            if ($family->isChild($userId)) {
                $doNotProcess[$userId] = 1;
            }

            //Check if this adult is not already in the list
            elseif (!isset($doNotProcess[$userId])) {
                $partnerId = null;
                //Change the display name
                $user->display_name = $family->getFamilyName($user, false, $partnerId);

                if ($partnerId) {
                    $doNotProcess[$partnerId] = 1;
                }
            }
            //Only returning adults, but this is a child
        } elseif ($adults && $family->isChild($userId)) {
            $doNotProcess[$userId] = 1;
        }
    }

    // Return the ids we need
    if (is_numeric($user)) {
        sort($users);

        return array_diff($users, array_keys($doNotProcess));
    }

    $existsArray     = array();

    //Loop over all users again to make sure we do not have duplicate names
    foreach ($users as $key => $user) {
        if (isset($doNotProcess[$user->ID])) {
            continue;
        }

        if ($uniqueDisplayName) {
            //Get the full name
            $fullName = strtolower("$user->first_name $user->last_name");

            //If the full name is already found
            if (isset($existsArray[$fullName])) {
                // Change current users last name
                $user->last_name = "$user->last_name ($user->user_email)";

                // Change current users display name
                if ($user->display_name == $user->nickname) {
                    $user->display_name = "$user->first_name $user->last_name";
                } else {
                    $user->display_name = $user->nickname;
                }

                // Change previous found users last name
                $prevUser = $users[$existsArray[$fullName]];

                // But only if not already done
                if (!str_contains($prevUser->last_name, $prevUser->user_email)) {
                    $prevUser->last_name = "$prevUser->last_name ($prevUser->user_email)";
                }

                // Change current users display name
                if ($prevUser->display_name == $prevUser->nickname) {
                    $prevUser->display_name = "$prevUser->first_name $prevUser->last_name";
                } else {
                    $prevUser->display_name = $prevUser->nickname;
                }

                $cleanedUserArray[$prevUser->ID] = $prevUser;
            } else {
                //User has a so far unique displayname, add to array
                $existsArray[$fullName] = $key;
            }
        }

        //Add the user to the cleaned array if not in the donotprocess array
        $cleanedUserArray[$user->ID] = $user;
    }

    usort($cleanedUserArray, function ($a, $b) {
        return strcmp($a->last_name, $b->last_name);
    });

    return $cleanedUserArray;
}

/**
 * Returns the current url
 *
 * @param    bool    $trim        Remove request params
 *
 * @return    string                The url
 */
function currentUrl($trim = false)
{
    // phpcs:ignore
    if (defined('REST_REQUEST') && !empty($_SERVER['HTTP_REFERER'])) {
        // phpcs:ignore
        $url        = sanitize($_SERVER['HTTP_REFERER'], 'url');
    } else {
        $protocol = 'https';

        // phpcs:disable
        if (!empty($_SERVER['REQUEST_SCHEME'])) {
            $protocol    = sanitize($_SERVER['REQUEST_SCHEME'], 'url');
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protocol    = sanitize($_SERVER['HTTP_X_FORWARDED_PROTO'], 'url');
        }

        $url  = "$protocol://";
        $url .= sanitize(($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''), 'url');
        // phpcs:enable
    }

    if ($trim) {
        $url     = trim(explode('?', $url)[0], "/");
    }

    return $url;
}

/**
 * Returns the current url
 *
 * @return    string                        The url
 */
function getCurrentUrl()
{
    return currentUrl();
}

/**
 * Transforms an url to a path
 * @param     string        $url             The url to be transformed
 *
 * @return    string                        The path
 */
function urlToPath($url)
{
    if (gettype($url) != 'string') {
        printArray("Invalid url:");
        printArray($url);
        return '';
    }

    if (file_exists($url)) {
        return $url;
    }

    $siteUrl    = str_replace(['https://', 'http://'], '', SITEURL);
    $url        = str_replace(['https://', 'http://'], '', urldecode($url));
    $url        = explode('?', $url)[0];

    return str_replace(trailingslashit($siteUrl), str_replace('\\', '/', ABSPATH), $url);
}

/**
 * Transforms a path to an url
 * @param     string        $path             The path to be transformed
 *
 * @return    string|false                The url or false on failure
 */
function pathToUrl($path)
{
    if (empty($path)) {
        return false;
    }

    // Check if already an url
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }

    if (is_string($path)) {
        require_once(ABSPATH . '/wp-admin/includes/file.php');
        
        $base    = get_home_path();
        $path    = wp_normalize_path($path);

        // We should only process files in the content dir, so only keep that part


        //Replace any query params
        $exploded    = explode('?', $path);
        $path        = $exploded[0];
        $query       = '';
        if (!empty($exploded[1])) {
            $query    = '?' . $exploded[1];
        }

        if (!str_contains($path, $base)) {
            $path    = $base . $path;
        }

        if (!file_exists($path)) {
            return false;
        }
        $url    = str_replace($base, SITEURL . '/', $path) . $query;

        // fix any spaces
        $url    = str_replace(' ', '%20', $url);

        // not a valid url
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            printArray($url);
            return false;
        }
    } else {
        $url    = $path;
    }

    return $url;
}

/**
 * Checks if a child is a son or daughter
 * @param     int        $userId         The User_ID of the child
 *
 * @return    string                Either "son", "daughter" or 'child'
 */
function getChildTitle($userId)
{
    $gender = get_user_meta($userId, 'tsjippy_gender', true);
    if ($gender == 'male') {
        $title = "son";
    } elseif ($gender == 'female') {
        $title = "daughter";
    } else {
        $title = "child";
    }

    return $title;
}

/**
 * Get an users age
 * @param     int        $userId         WP User_ID
 * @param    bool    $numeric    Whether to return the age as a number or a word. Default false
 *
 * @return    int                    Age in years
 */
function getAge($userId, $numeric = false)
{
    if (is_numeric($userId)) {
        $birthday = get_user_meta($userId, 'tsjippy_birthday', true);

        if (empty($birthday)) {
            return false;
        }
    } else {
        $birthday = $userId;
    }

    if (is_array($birthday)) {
        $birthday    = array_values($birthday)[0];
    }

    if (empty($birthday)) {
        return;
    }

    $birthDate = explode("-", $birthday);

    if (gmdate("md", gmdate("U", mktime(0, 0, 0, $birthDate[1], $birthDate[2], $birthDate[0]))) > gmdate("md")) {
        $age = (gmdate("Y") - $birthDate[0]) - 1;
    } else {
        $age = (gmdate("Y") - $birthDate[0]);
    }

    if ($numeric) {
        return $age;
    }
    return numberToWords($age);
}

/**
 * Converts an number to words
 * @param     string|int|float    $number    the number to be converted
 *
 * @return    string                        the number in words
 */
function numberToWords($number)
{
    $hyphen         = '-';
    $conjunction     = ' and ';
    $separator         = ', ';
    $negative         = 'negative ';
    $decimal         = ' Thai Baht And ';

    $firstDic        = [
        1 => 'first',
        2 => 'second',
        3 => 'third',
        4 => 'fourth',
        5 => 'fifth',
        6 => 'sixth',
        7 => 'seventh',
        8 => 'eight',
        9 => 'nineth',
        10 => 'tenth',
        11 => 'eleventh',
        12 => 'twelfth',
        13 => 'thirteenth',
        14 => 'fourteenth',
        15 => 'fifteenth',
        16 => 'sixteenth',
        17 => 'seventeenth',
        18 => 'eighteenth',
        19 => 'nineteenth',
        20 => 'twentieth',
        30 => 'thirtieth',
        40 => 'fortieth',
        50 => 'fiftieth',
        60 => 'sixtieth',
        70 => 'seventieth',
        80 => 'eightieth',
        90 => 'ninetieth'
    ];
    $dictionary     = array(
        0 => 'zero',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nin',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
        20 => 'twenty',
        30 => 'thirty',
        40 => 'fourty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety',
        100 => 'hundred',
        1000 => 'thousand',
        1000000 => 'million',
        1000000000 => 'billion',
        1000000000000 => 'trillion',
        1000000000000000 => 'quadrillion',
        1000000000000000000 => 'quintillion'
    );

    // If not numeric return an number from a word
    if (!is_numeric($number)) {
        return array_search(strtolower($number), $dictionary);
    }

    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        return false;
    }

    if ($number < 0) {
        return $negative . numberToWords(abs($number));
    }

    $string = $fraction = null;

    if (str_contains($number, ' . ')) {
        list($number, $fraction) = explode(' . ', $number);
    }

    switch (true) {
        case isset($firstDic[$number]):
            $string = $firstDic[$number];
            break;
        case $number < 100:
            $tens = ((int) ($number / 10)) * 10;
            $units = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $firstDic[$units];
            }
            break;
        case $number < 1000:
            $hundreds = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . numberToWords($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= numberToWords($remainder);
            }
            break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $nr) {
            $words[] = $dictionary[$nr];
        }
        $string .= implode(' ', $words);
    }

    return $string;
}

/**
 * Creates a submit button with a loader gif
 * @param    string    $targetFile        The path to a file
 * @param    string    $title            The title for the file
 * @param    string    $description    The default description of the file
 *
 * @return     int|WP_Error            The post id of the created attachment, WP_Error on error
 */
function addToLibrary($targetFile, $title = '', $description = '')
{
    try {
        // Check the type of file. We'll use this as the 'post_mime_type' .
        $filetype = wp_check_filetype(basename($targetFile), null);

        if (empty($title)) {
            $title = preg_replace('/\.[^.]+$/', '', basename($targetFile));
        }

        // Prepare an array of post data for the attachment.
        $attachment = array(
            'guid'           =>    pathToUrl($targetFile),
            'post_mime_type' => $filetype['type'],
            'post_title'     => $title,
            'post_content'   => $description,
            'post_status'    => 'publish'
        );

        // Insert the attachment.
        $postId = wp_insert_attachment($attachment, $targetFile);

        //Schedule the creation of subsizes as it can take some time.
        // By doing it this way its asynchronous
        wp_schedule_single_event(time(), 'tsjippy-process-images', [$postId]);

        return $postId;
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $result = json_decode($e->getResponse()->getBody()->getContents());
        // phpcs:ignore
        $errorResult = $result->detail . "<pre>" . print_r($result->errors, true) . "</pre>";
        printArray($errorResult);
        if (isset($postId)) {
            return $postId;
        }

        return new WP_Error('library', $errorResult);
    } catch (\Exception $e) {
        $errorResult = $e->getMessage();
        printArray($errorResult);
        if (isset($postId)) {
            return $postId;
        }
        return new WP_Error('library', $errorResult);
    }
}

/**
 * Creates sub images using wp_maybe_generate_attachment_metadata
 * @param    int|\WP_Post    $post        WP_Post or attachment id
 */
function processImages($post)
{
    include_once(ABSPATH . 'wp-admin/includes/image.php');

    if (is_numeric($post)) {
        $post    = get_post($post);
    }
    wp_maybe_generate_attachment_metadata($post);
}

/**
 * Remove a single file or a folder including all the files
 * @param    string         $target            The path to delete
 */
function removeFiles($target)
{
    if (is_dir($target)) {
        $wpFileSystem   = loadWpFileSystem();

        $files = glob($target . '*', GLOB_MARK);

        foreach ($files as $file) {
            removeFiles($file);
        }

        $wpFileSystem->rmdir($target);
    } elseif (is_file($target)) {
        wp_delete_file($target);
    }
}

/**
 * Checks if a string is a date
 * @param    string         $date            the date to check
 *
 * @return    bool                        Whether a date or not
 */
function isDate($date)
{
    if (is_array($date)) {
        $date    = array_values($date)[0];
    }

    if (preg_match("/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])$/", $date)) {
        return true;
    }

    return false;
}

/**
 * Checks if a string is a time
 * @param    string         $time            the time to check
 *
 * @return    bool                        Whether a time or not
 */
function isTime($time)
{
    if (preg_match("/^\d{2}:\d{2}$/", $time)) {
        return true;
    }
    return false;
}

/**
 * Get profile picture html
 * @param    int         $postId                WP_post id
 *
 * @return    string|false                    The url or false if no valid page
 */
function getValidPageLink($postId)
{
    if (is_array($postId)) {
        foreach ($postId as $id) {
            $url    = getValidPageLink($id);
            if ($url) {
                return $url;
            }
        }
    }

    if (!is_numeric($postId)) {
        return false;
    }

    if (get_post_status($postId) != 'publish') {
        return false;
    }

    $link      = get_page_link($postId);

    //Only redirect if we are not currently on the page already
    if (str_contains(currentUrl(), $link)) {
        return false;
    }

    return $link;
}

/**
 * Remove duplicate tags from a string
 * @param    string        $matches    The matches from the regex
 *
 * @return    string                The cleaned string
 */
function removeDuplicateTags($matches)
{
    //If the opening tag is exactly like the next opening tag, remove the the duplicate
    if ($matches[1] == $matches[4] && ($matches[3] == 'span' || $matches[3] == 'strong' || $matches[3] == 'b')) {
        return '<' . $matches[1] . '>' . $matches[2];
    } else {
        return $matches[0];
    }
}

/**
 * Checks if the current request is a REST API request
 *
 * @return bool Whether the current request is a REST API request
 */
function isRestApiRequest()
{
    // phpcs:ignore
    if (empty($_SERVER['REQUEST_URI'])) {
        // Probably a CLI request
        return false;
    }

    $restPrefix         = trailingslashit(rest_get_url_prefix());
    // phpcs:ignore
    return str_contains($_SERVER['REQUEST_URI'], $restPrefix);
}

/**
 * Clears the output queue
 */
function clearOutput($write = false)
{
    while (true) {
        //ob_get_clean only returns false when there is absolutely nothing anymore
        $result    = ob_get_clean();
        if ($result === false) {
            break;
        }
        if ($write) {
            echo wp_kses_post($result);
        }
    }
}

/**
 * Find all depency urls of a given js handle
 *
 * @param    array    $scripts    the current urls array
 * @param    string    $handle            the handle of the js to find all urls for
 *
 * @return    array                    array containing all urls to the js files
 */
function getJsDependicies(&$scripts, $handle, $extras = [])
{
    global $wp_scripts;

    $url    = $wp_scripts->registered[$handle]->src;
    if (!$url) {
        return $extras;
    }

    if (!str_contains($url, '//')) {
        $url    = $wp_scripts->base_url . $url;
    }
    $scripts[$handle]    = [
        'src'    => $url,
        'deps'    => []
    ];


    $extra    = $wp_scripts->registered[$handle]->extra;
    if (!empty($extra)) {
        $extras[]    = $extra;
    }

    foreach ($wp_scripts->registered[$handle]->deps as $dep) {
        $extras    = getJsDependicies($scripts[$handle]['deps'], $dep, $extras);
    }

    return $extras;
}

/**
 * update url in posts
 *
 * @param    string        $oldPath        The path to be replaced
 * @param    string        $newPath        The path to replace with
 */
function urlUpdate($oldPath, $newPath)
{
    //replace any url with new urls for this attachment
    $oldUrl    = pathToUrl($oldPath);
    $newUrl    = pathToUrl($newPath);

    // Search for any post with the old url
    $query = new \WP_Query(array('s' => basename($oldUrl)));

    foreach ($query->posts as $post) {
        $updated    = false;
        //if old url is found in the content of this post
        if (str_contains($post->post_content, $oldUrl)) {
            //replace with new url
            $post->post_content = str_replace($oldUrl, $newUrl, $post->post_content);

            $updated    = true;
        }

        if ($updated) {
            $args = array(
                'ID'           => $post->ID,
                'post_content' => $post->post_content,
            );

            // Update the post into the database
            wp_update_post($args, false, false);
        }
    }
}

/**
 * Initializes the image processing action
 */
add_action('init', __NAMESPACE__ . '\processImagesAction');
function processImagesAction()
{
    add_action('tsjippy-process-images', __NAMESPACE__ . '\processImages');
}

/**
 * Loads the WordPress Filesystem API
 *
 * @return \WP_Filesystem_Base The WordPress Filesystem object
 */
function loadWpFileSystem()
{
    // Ensure the WordPress Filesystem API is loaded
    require_once(ABSPATH . 'wp-admin/includes/file.php');

    // Initialize the filesystem object
    WP_Filesystem();

    global $wp_filesystem;

    return $wp_filesystem;
}

/**
 * Sanitizes a value based on its type
 *
 * @param mixed     $value  The value to sanitize
 * @param string    $type   The type of sanitization to apply
 * @return mixed            The sanitized value
 */
function sanitize($value, $type='text_field'){
    // Always unslash posted data first to avoid double slashes
    $value = wp_unslash( $value );

    if(is_array($value)){
        if( isset($value['_wpnonce']) ){
            unset($value['_wpnonce']);
        }

        if( isset($value['nonce']) ){
            unset($value['nonce']);
        }
    }

    $function   = $type;
    if($type != 'wp_kses_post' && !str_contains($type, 'sanitize')){
        $function   =  "sanitize_$type";
    }

    // Recursively sanitize all text fields in the array
    $value = map_deep( $value, $function );

    return $value;
}

/**
 * Verifies that a correct security nonce was used with time limit.
 *
 * A nonce is valid for between 12 and 24 hours (by default).
 *
 * @since 2.0.3
 *
 * @param string     $key  The key for the nonce value in $_POST. Will be sanitized and unslashed before validating it
 * @param string|int $action Should give context to what is taking place and be the same when nonce was created.
 * @return int|false 1 if the nonce is valid and generated between 0-12 hours ago,
 *                   2 if the nonce is valid and generated between 12-24 hours ago.
 *                   False if the nonce is invalid.
 */
function verifyNonce($key, $action = -1)
{
    if(empty($_REQUEST[$key])){
        return false;
    }
    
    // phpcs:ignore
    return wp_verify_nonce(sanitize($_REQUEST[$key]), $action);
}