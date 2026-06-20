<?php

namespace TSJIPPY\FRONTPAGE;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

add_action('init', __NAMESPACE__ . '\blockInit');
function blockInit()
{
    register_block_type(
        __DIR__ . '/show_categories/build',
        array(
            'render_callback' => __NAMESPACE__ . '\displayCategories',
        )
    );

    register_block_type(
        __DIR__ . '/show_children/build',
        array(
            'render_callback' => __NAMESPACE__ . '\displayChildren',
            'attributes'        => [
                'title'            => [
                    'type'         => 'boolean',
                    'default'    => true
                ],
                'listtype'            => [
                    'type'         => 'string',
                    'default'    => 'none'
                ],
                'grandchildren' => [
                    'type'         => 'boolean',
                    'default'    => false
                ],
                'parents' => [
                    'type'         => 'boolean',
                    'default'    => true
                ],
                'grantparents' => [
                    'type'         => 'integer',
                    'default'    => 2
                ],
            ]
        )
    );

	register_block_type(
		__DIR__ . '/displayname/build',
		array(
			'render_callback' => __NAMESPACE__ . '\displayName',
		)
	);

	register_block_type(
		__DIR__ . '/login_count/build',
		array(
			'render_callback' => __NAMESPACE__ . '\loginCount',
		)
	);

	register_block_type(
		__DIR__ . '/welcome/build',
		array(
			'render_callback' => __NAMESPACE__ . '\welcomeMessage',
		)
	);
}

// Load the js file to filter all blocks
add_action('enqueue_block_editor_assets', __NAMESPACE__ . '\addBlockJs');
function addBlockJs()
{
	wp_enqueue_script('sim_home_script');

    wp_enqueue_script(
        'tsjippy-block-filter',
        get_stylesheet_directory_uri().'/blocks/block_filters/build/index.js',
        ['wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post'],
        wp_get_theme()->get('Version'),
        true
    );
}

// Filter block visibility
add_filter('render_block', __NAMESPACE__ . '\renderBlock', 10, 2);

/**
 * Filters the block content based on the block attributes
 *
 * @param    string    $blockContent    The content of the block
 * @param    array    $block            The block attributes
 */
function renderBlock($blockContent, $block)
{
    // make sure only published pages are included
    if (!empty($block['attrs']['onlyOn'])) {
        foreach ($block['attrs']['onlyOn'] as $index => $pageId) {
            if (get_post_status($pageId) != "publish") {
                unset($block['attrs']['onlyOn'][$index]);
            }
        }
    }

    if (
        // not on a specific page
        (!empty($block['attrs']['onlyOn']) &&     !in_array(get_the_ID(), $block['attrs']['onlyOn']))    ||
        // or not logged in
        (isset($block['attrs']['onlyLoggedIn']) && !is_user_logged_in())    ||
        // or logged in
        (isset($block['attrs']['onlyNotLoggedIn']) && is_user_logged_in())
    ) {
        return '';
    }

    // Run php filters
    if (!empty($block['attrs']['phpFilters'])) {
        $show    = false;
        foreach ($block['attrs']['phpFilters'] as $filter) {
            if (function_exists($filter) && $filter(get_the_ID())) {
                $show    = true;
                break;
            }
        }

        if (!$show) {
            return '';
        }
    }

    // add hide on mobile class
    if (isset($block['attrs']['hideOnMobile']) && $block['attrs']['hideOnMobile']) {
        return "<span class='hide-on-mobile'>$blockContent</span>";
    }

    return $blockContent;
}

/**
 * Displays the categories of the current page
 *
 * @param    array    $attributes    The block attributes
 */
function displayCategories($attributes)
{

    $args = wp_parse_args($attributes, array(
        'count'         => false
    ));

    if (is_home()) {
        $taxonomy    = 'category';
    } elseif (is_archive()) {

        if (isset(get_queried_object()->taxonomy)) {
            $taxonomy    = get_queried_object()->taxonomy;
        } else {
            $taxonomy    = get_queried_object()->taxonomies[0];
        }
    } elseif (is_tax()) {
        $taxonomy    = '';
    } else {
        // We are on place without categories
        return '';
    }

    return wp_list_categories(array(
        'echo'                => 0,
        'taxonomy'             => $taxonomy,
        'current_category'    => get_queried_object()->term_id,
        'show_count'        => $args['count'],
        'title_li'             => '<h4>' . __('Categories', '%TEXTDOMAIN%') . '</h4>'
    ));
}

/**
 * Displays the children of the current page
 *
 * @param    array    $attributes    The block attributes
 */
function displayChildren($attributes)
{
    if (is_archive()) {
        return '';
    }

    $depth    = 1;
    if ($attributes['grandchildren']) {
        $depth    = 0;
    }

    $parentId    = get_the_ID();
    if (!$parentId) {
        if (isset($attributes['postid']) && is_numeric($attributes['postid'])) {
            $parentId    = $attributes['postid'];
        } elseif (
            (
                function_exists('get_current_screen') &&
                get_current_screen() != null &&
                get_current_screen()->is_block_editor()
            ) ||
            str_contains($_SERVER['HTTP_REFERER'] ?? '', "/wp-admin/widgets.php")
        ) {
            return '<div class="childpost">This page has no children</div>';
        } else {
            return '';
        }
    }

    if (has_post_parent($parentId)) {
        if ($attributes['grantparents']) {
            $ancestors    = get_post_ancestors($parentId);
            $level        = min($attributes['grantparents'], count($ancestors)) - 1;
            $parentId    = $ancestors[$level];
        } elseif ($attributes['parents']) {
            $parentId    = wp_get_post_parent_id($parentId);
        }
    }

    $html    = wp_list_pages(array(
        'depth'            => $depth,
        'child_of'         => $parentId,
        'echo'            => false,
        'post_type'        => get_post_type($parentId),
        'title_li'        => null,
        'hierarchical'     => true,
    ));

    if (!empty($html)) {
        wp_enqueue_script('tsjippy-child-posts', get_stylesheet_directory_uri().'/blocks/show_children/expand.min.js', array(), wp_get_theme()->get('Version'), true);

        if (!empty($attributes['listtype'])) {
            $html    = str_replace("<li ", "<li style='list-style-type: {$attributes['listtype']}'", $html);
        }

        $html    = str_replace("class='children'", "class='children hidden'", $html);
        $title    = '';

        if ($attributes['title']) {
            $url    = esc_url(get_permalink(($parentId)));
            $title    = "<h4><a href='$url'>" . esc_html(get_the_title($parentId)) . "</a></h4>";
        }
        return "<div class='childpost'>$title<ul>$html</ul></div>";
    }

    if (function_exists('get_current_screen') && !empty(get_current_screen()) && get_current_screen()->is_block_editor()) {
        return "This page has no children";
    }

    return '';
}

/**
 * Creates children html
 *
 * @param    int        $postId        The postId of the post to get children for
 * @param    boolean    $recursive    Whether or not to add children of children
 */
function getGrantChildren($postId, $recursive, $level = 1)
{
    $html        = '';
    $children    = get_children($postId);
    if (empty($children)) {
        return '';
    }

    $html    .= "<ul>";
    foreach ($children as $child) {
        $url    = esc_url(get_permalink($child->ID));
        $title     = esc_html($child->post_title);
        $html    .= "<li>";
        $html    .= "<a href='$url'>$title</a>";
        $html    .= "</li>";

        if ($recursive) {
            $html    .= getGrantChildren($child->ID, $level + 1);
        }
    }
    $html    .= "</ul>";

    return $html;
}

