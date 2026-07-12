<?php

namespace TSJIPPY;

use WP_Error;

if (! defined('ABSPATH')) exit;

/**
 * Adds an element to a DOM Document Node
 *
 * @param    string                $type           The type of html element to add
 * @param    string|\DOMELement    $parent         The parent node to append to, default empty for a new DOM
 * @param    array                 $attributes     The html attributes for the element
 * @param    string                $textContent    The text for the element
 * @param    string                $position       One of beforeBegin, afterBegin, beforeEnd, afterEnd. Default beforeEnd
 * 
 * @return   WP_Error|\DOMELement
 */
function addElement($type, $parent = '', $attributes = [], $textContent = '', $position = 'beforeEnd')
{
    if (empty($parent)) {
        $dom    = new \DOMDocument();
        $parent    = $dom;
    } else {
        $dom    = $parent->ownerDocument ?? $parent;
    }

    try {
        if(!empty($textContent)){
            // Text content should not contain <br> tags, replace them with new line characters
            $textContent = str_replace('<br>', "\n", $textContent);
        }

        $node = $dom->createElement($type, htmlspecialchars($textContent));
    } catch (\DOMException $e) {
        // Catch the specific DOMException
        printArray("Caught DOMException: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");

        return new WP_Error('add-element', "Caught DOMException: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
    } catch (\Exception $e) {
        // Catch any other general exceptions if needed
        printArray("Caught general Exception: " . $e->getMessage());

        return new WP_Error('add-element', "Caught general Exception: " . $e->getMessage());
    }

    // Type should come first
    if (!empty($attributes['type'])) {
        $attributes = ['type' => $attributes['type']] + $attributes;
    }

    foreach ($attributes as $attribute => $value) {
        try {
            $node->setAttribute($attribute, $value);
        } catch (\DOMException $e) {
            // Catch the specific DOMException
            printArray("Caught DOMException for attribute '$attribute' with value '$value' . " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
        } catch (\Exception $e) {
            // Catch any other general exceptions if needed
            printArray("Caught general Exception: " . $e->getMessage());
        }
    }

    try {
        if ($position === 'afterBegin') {
            $node        = $parent->insertBefore($node, $parent->firstChild);
        } elseif ($position === 'beforeBegin') {
            $node        = $parent->parentNode->insertBefore($node, $parent);
        } elseif ($position === 'afterEnd') {
            $node        = $parent->parentNode->insertBefore($node, $parent->nextSibling);
        } else {
            // Default to appending
            $node        = $parent->appendChild($node);
        }
    } catch (\DOMException $e) {
        // Catch the specific DOMException
        printArray("Caught DOMException: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
    } catch (\Exception $e) {
        // Catch any other general exceptions if needed
        printArray("Caught general Exception: " . $e->getMessage());
    }

    return $node;
}

/**
 * Converst a string of HTML into a DOM element and adds it to the parent element
 * @param    string        $html    The HTML string to convert
 * @param    \DOMElement    $parent    The parent element to add the new element to
 * @param    string        $position    The position to add the new element (beforeEnd, afterBegin, beforeBegin, afterEnd)
 *
 * @return    \DOMElement|false    The newly created DOM element or false if the HTML string was empty
 */
function addRawHtml($html, $parent = '', $position = 'beforeEnd')
{
    if (empty(trim($html))) {
        return false;
    }

    if (empty($parent)) {
        $dom    = new \DOMDocument();
        $parent    = $dom;
    } else {
        $dom    = $parent->ownerDocument ?? $parent;
    }

    $html            = trim(force_balance_tags($html));

    // Convert Special chras
    $html            = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

    $tempDom         = new \DOMDocument();

    // set error level
    $internalErrors = libxml_use_internal_errors(true);
    $tempDom->loadHTML($html, LIBXML_HTML_NODEFDTD);
    // Restore error level
    libxml_use_internal_errors($internalErrors);

    $node            = false;

    // Import the node
    foreach ($tempDom->getElementsByTagName('body')->item(0)->childNodes as $node) {
        $node         = $parent->ownerDocument->importNode($node, true);

        if ($position === 'afterBegin') {
            $node        = $parent->insertBefore($node, $parent->firstChild);
        } elseif ($position === 'beforeBegin') {
            $node        = $parent->parentNode->insertBefore($node, $parent);
        } elseif ($position === 'afterEnd') {
            $node        = $parent->parentNode->insertBefore($node, $parent->nextSibling);
        } else {
            // Default to appending if position is not recognized
            $node        = $parent->appendChild($node);
        }
    }

    return $node;
}

/**
 * Creates a submit button with a loader gif
 * @param    string    $elementId        The name or id of the button
 * @param    string    $buttonText        The text of the button
 * @param    string    $extraClass        Any extra class to add to the button
 *
 * @return string                    The html
 */
function addSaveButton($elementId, $buttonText, $extraClass = '', $echo = true)
{
    if (!$echo) {
        ob_start();
    }
?>
    <div class='submit-wrapper'>
        <button type='button' class='button form-submit <?php echo esc_attr($extraClass); ?>' name='<?php echo esc_attr($elementId); ?>'>
            <?php echo wp_kses_post($buttonText); ?>
        </button>
    </div>

    <?php
    if (!$echo) {
        return ob_get_clean();
    }
}

/**
 * Gets the close button
 * 
 * @param \DOMElement $parent The parent to add the button to
 */
function addCloseButtton($parent = '')
{
    $span = addElement('span', $parent, ['class' => 'close mobile-sticky']);

    $svg = addElement(
        'svg',
        $span,
        [
            'width'        => "24",
            'height'       => "24",
            'viewBox'      => "0 0 24 24",
            'fill'         => "none",
            'stroke'       => "currentColor",
            'stroke-width' => "2"
        ]
    );

    addElement('line', $svg, ['x1' => "18", 'y1' => "6", 'x2' => "6", 'y2' => "18"]);
    addElement('line', $svg, ['x1' => "6", 'y1' => "6", 'x2' => "18", 'y2' => "18"]);

    if (empty($parent)) {
        // phpcs:ignore
        echo $span->ownerDocument->saveHTML();
    }
}

/**
 * Get profile picture html
 * @param    int         $userId             WP_user id
 * @param    array       $size               Size (width, height) of the image. Default [50,50]
 * @param    bool        $showDefault        Whether to show a default pictur if no user picture is found. Default true
 * @param    bool        $famillyPicture     Whether or not to use the family picture
 * @param    bool        $wrapInLink         Whether or not to make the picture clickable to the full size picture
 * @param   bool        $echo                Whetther to prin to screen
 *
 * @return    string|false                   The picture html or false if no picture if echo is false
 */
function displayProfilePicture($userId, $size = [50, 50], $showDefault = true, $famillyPicture = false, $wrapInLink = true, $echo = false)
{
    $family            = new FAMILY\Family();

    if ($famillyPicture) {
        $attachmentId    = $family->getFamilyMeta($userId, 'family_picture', true);
    } else {
        $attachmentId     = get_user_meta($userId, 'tsjippy_profile_picture', true);
    }

    $defaultUrl        = plugins_url('pictures/usericon.png', __DIR__);

    if (!$echo) {
        ob_start();
    }

    if (is_numeric($attachmentId)) {
        $url = wp_get_attachment_image_url($attachmentId, 'Full size');

        if ($url && file_exists(urlToPath($url))) {

            if ($wrapInLink) {
                ?>
                <a href='<?php echo esc_url($url); ?>'>
                <?php
            }
            ?>

            <img loading='lazy' width='<?php echo esc_attr($size[0]); ?>' height='<?php echo esc_attr($size[1]); ?>' src='<?php echo esc_url($url); ?>' class='profile-picture attachment-<?php echo esc_attr($size[0]); ?>x<?php echo esc_attr($size[1]); ?> size-<?php echo esc_attr($size[0]); ?>x<?php echo esc_attr($size[1]); ?>' loading='lazy'>
            <?php
            if ($wrapInLink) {
                ?>
                </a>
                <?php
            }

            if (!$echo) {
                return ob_get_clean();
            }
        }
    }

    if ($showDefault) {
        ?>
        <img loading='lazy' width='<?php echo esc_attr($size[0]); ?>' height='<?php echo esc_attr($size[1]); ?>' src='<?php echo esc_url($defaultUrl); ?>' class='profile-picture attachment-<?php echo esc_attr($size[0]); ?>x<?php echo esc_attr($size[1]); ?> size-<?php echo esc_attr($size[0]); ?>x<?php echo esc_attr($size[1]); ?>' loading='lazy'>
        <?php
        if (!$echo) {
            return ob_get_clean();
        }
    }

    return false;
}

/**
 * Create a dropdown with all users
 * @param    string           $title      The title to display above the select
 * @param    bool             $onlyAdults Whether children should be excluded. Default false
 * @param    bool             $families   Whether we should group families in one entry default false
 * @param    string           $class      Any extra class to be added to the dropdown default empty
 * @param    string           $id         The name or id of the dropdown, default 'user-selection'
 * @param    array            $args       Extra query arg to get the users
 * @param    int|string|array $userId     The current selected user id or name or array of multiple user-ids
 * @param    array            $excludeIds An array of user id's to be excluded
 * @param    string           $type       Html input type Either select or list
 * @param    string           $listId     The id of the datalist if type is list, default to $id with -list suffix
 * @param    bool             $multiple   Whether multiple users can be selected, default false
 * @param    bool             $echo       Whether to return the html or directly echo it, default false
 *
 * @return    string                      The html
 */
function userSelect($title = '', $onlyAdults = false, $families = false, $class = '', $id = 'user-selection', $args = [], $userId = '', $excludeIds = [1], $type = 'select', $listId = '', $multiple = false, $echo = false)
{
    wp_enqueue_script('tsjippy_user_select_script');

    if (!$echo) {
        ob_start();
    }

    // phpcs:disable
    if (
        empty($userId) &&
        !empty($_GET["user-id"]) &&
        is_numeric($_GET["user-id"])
    ) {
        $userId = (int) $_GET["user-id"];
    }
    // phpcs:enable

    /**
     * We got a normal array but we need to use isset, swap it
     */
    if(is_array($userId) && isset($userId[0])){
        $userId = array_keys($userId);
    }

    //Get the id and the displayname of all users
    $users             = getUserAccounts($families, $onlyAdults, [], $args, $excludeIds, true);

    ?>
    <div class='option-wrapper'>
        <?php
        if (!empty($title)) {
        ?>
            <h4>
                <?php echo esc_html($title); ?>
            </h4>
        <?php
        }

        $inputClass    = 'wide';
        if ($type == 'select') {
            if ($multiple) {
                if (!str_contains($id, '[]')) {
                    $id    .= '[]';
                }
            }

        ?>
            <select
                name='<?php echo esc_attr($id); ?>'
                id='<?php echo esc_attr($id); ?>'
                class='<?php echo esc_html($class); ?> user-selection'
                value=''
                <?php if ($multiple) echo 'multiple'; ?>>
                <?php
                foreach ($users as $user) {
                    if (empty($user->first_name) || empty($user->last_name) || $families) {
                        $name    = $user->display_name;
                    } else {
                        $name    = "$user->first_name $user->last_name";
                    }

                ?>
                    <option
                        value='<?php echo esc_attr($user->ID); ?>'
                        <?php if ($userId == $user->ID || (is_array($userId) && isset($userId[$user->ID]))) echo 'selected="selected"'; ?>>
                        <?php echo esc_html($name); ?>
                    </option>
                <?php
                }
                ?>
            </select>
            <?php
        } elseif ($type == 'list') {
            if ($multiple) {
                $inputClass    .= ' datalistinput multiple';

            ?>
                <ul class="list-selection-list">
                    <?php
                    // we supplied an array of users
                    if (is_array($userId)) {
                        foreach ($userId as $singleUserId) {
                            ?>
                            <li class='list-selection'>
                                <button type='button' class='small remove-list-selection'>
                                    <span class='remove-list-selection'>×</span>
                                </button>
                                <?php
                                if (is_numeric($singleUserId)) {
                                    $user    = get_userdata($singleUserId);
                                    if ($user) {
                                ?>
                                        <input type='hidden' class='no-reset' name='<?php echo esc_attr($singleUserId); ?>[<?php echo esc_attr($user->ID); ?>]' value='<?php echo esc_attr($user->ID); ?>'>
                                        <span>
                                            <?php echo esc_attr($user->display_name); ?>
                                        </span>
                                    <?php
                                    }
                                } else {
                                    ?>
                                    <span>
                                        <input type='text' name='<?php echo esc_attr($singleUserId); ?>[<?php echo esc_attr($singleUserId); ?>]' value='<?php echo esc_attr($singleUserId); ?>>' readonly=readonly style='width:<?php echo esc_attr(strlen($singleUserId)); ?>ch'>
                                    </span>
                                <?php
                                }
                                ?>
                            </li>
                    <?php
                        }
                    }
                    ?>
                </ul>
            <?php
            }

            $value    = '';

            if (!is_numeric($userId)) {
                $value    = $userId;
            }

            if (empty($listId)) {
                $listId = $id . "-list";
            }

            ?>
            <input type='text' class='<?php echo esc_attr($inputClass); ?>' name='<?php echo esc_attr($id); ?>' id='<?php echo esc_attr($id); ?>' list='<?php echo esc_attr($listId); ?>' value='<?php echo esc_attr($value); ?>'>

            <datalist id='<?php echo esc_attr($listId); ?>' class='<?php echo esc_attr($class); ?> user-selection'>
                <?php
                foreach ($users as $key => $user) {
                    if ($families || empty($user->first_name) || empty($user->last_name)) {
                        $name    = $user->display_name;
                    } else {
                        $name    = "$user->first_name $user->last_name";
                    }

                    if ($userId == $user->ID) {
                        //Make this user the selected user
                        $value    = $user->display_name;
                    }

                ?>
                    <option value='<?php echo esc_attr($name); ?>' data-user-id='<?php echo esc_attr($user->ID); ?>' data-value='<?php echo esc_attr($user->ID); ?>'>
                    <?php
                }
                    ?>
            </datalist>
        <?php
        }
        ?>
    </div>
    <?php

    if (!$echo) {
        return ob_get_clean();
    }
}

/**
 * Creates a dropdown to select a page
 * @param    string      $selectId         The id or name of the dropown
 * @param    bool        $pageId           The current select page id default to empty
 * @param    string      $class            Any extra class to be added to the dropdown default empty
 * @param    array       $postTypes        The posttypes to include archive pages for. Defaults to pages and locations
 * @param    bool        $includeTax       Array with taxonomies to be included
 * @param    bool        $echo             Wetether or not to print to screen
 *
 * @return    string                       The dropdown html
 */
function pageSelect($selectId, $pageId = null, $class = "", $postTypes = ['page', 'location'], $includeTax = true, $echo = false)
{
    $pages = get_posts(
        array(
            'orderby'        => 'post_title',
            'order'          => 'asc',
            'post_status'    => 'publish',
            'post_type'      => $postTypes,
            'posts_per_page' => -1
        )
    );

    $options    = [];
    foreach ($pages as $page) {
        // skip the current page
        if ($page->ID == get_the_ID()) {
            continue;
        }

        $options[$page->ID]    = $page->post_title;
    }

    if ($includeTax) {
        $taxonomies = get_taxonomies(
            array(
                'public'   => true,
                '_builtin' => false
            )
        );
        foreach ($taxonomies as $taxonomy) {
            $options[$taxonomy]    = ucfirst($taxonomy);
        }

        $terms        = get_terms(['hide_empty' => false]);
        foreach ($terms as $term) {
            $options[$term->taxonomy . '/' . $term->slug]    = $term->name;
        }
    }

    asort($options);

    if (!$echo) {
        ob_start();
    }

    ?>
    <select name='<?php echo esc_attr($selectId); ?>' id='<?php echo esc_attr($selectId); ?>' class='selectpage <?php echo esc_attr($class); ?>'>
        <option value=''>
            ---
        </option>
        <?php
        foreach ($options as $id => $name) {
        ?>
            <option value='<?php echo esc_attr($id); ?>' <?php if ($pageId == $id) echo 'selected=selected'; ?>>
                <?php echo esc_html($name); ?>
            </option>
        <?php
        }
        ?>
    </select>

    <?php
    if (!$echo) {
        return ob_get_clean();
    }
}


/**
 * Adds and counter indicator to a menu item and all of its parents
 * 
 * @param   int     $nr     The indicator number
 * @param   int     $postId The post id of the menu item target to append the indicator to
 * @param   array   $items  The menu items changed by reference
 */
function addMenuIcon($nr, $postId, &$items){
    $indicatorHtml  = " <span class='number-circle'>%d</span>";

    $posts      = [];
    foreach($items as &$item){
        $posts[$item->ID]   = $item;

        // Get the post this menu item is linking to
        $targetId   = get_post_meta( $item->ID, '_menu_item_object_id', true );

         if($targetId == $postId){
            $parent = $item;
            $parent->title .= sprintf($indicatorHtml, $nr);

            while($parent->menu_item_parent != 0){
                $parent = $posts[$parent->menu_item_parent];

                // This parent has no other indicators yet
                if(!isset($parent->indicator)){
                    $parent->indicator  = $nr;
                    $parent->title      .= sprintf($indicatorHtml, $nr);

                    continue;
                }

                // Replace the existing one with the new one
                $parent->title  = str_replace(sprintf($indicatorHtml, $parent->indicator), sprintf($indicatorHtml, $parent->indicator + $nr), $parent->title);
                $parent->indicator += $nr;
            }

            // We found our page, no need to continue;
            break;
        }
    }
}