<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

add_action('init', __NAMESPACE__ . '\blockInit');
function blockInit()
{
    register_block_type(
        'tsjippy/show-children',
        array(
            'title'           => __( 'Post Children', 'tsjippy' ),
            'render_callback' => __NAMESPACE__ . '\displayChildren',
            'attributes'      => [
                'title'       => [
                    'label'   => __( 'Show title', 'tsjippy' ),
                    'type'    => 'boolean',
                    'default' => true
                ],
                'listtype'    => [
                    'label'   => __( 'List style', 'tsjippy' ),
                    'type'    => 'string',
                    'enum'    => [
                        "none", 
                        "disc",
                        "circle",
                        "square",
                        "decimal",
                        "decimal-leading-zero",
                        "lower-roman",
                        "upper-roman",
                        "lower-greek",
                        "lower-latin",
                        "upper-latin",
                        "armenian",
                        "georgian",
                        "lower-alpha",
                        "upper-alpha"
                    ],
                    'default' => 'none',
                ],
                'grandchildren' => [
                    'label'   => __( 'Show grandchildren', 'tsjippy' ),
                    'type'    => 'boolean',
                    'default' => false
                ],
                'parents'     => [
                    'label'   => __( 'Show parents', 'tsjippy' ),
                    'type'    => 'boolean',
                    'default' => true
                ],
                'grantparents' => [
                    'label'   => __( 'Show grantparents level', 'tsjippy' ),
                    'type'    => 'integer',
                    'default' => 2
                ],
            ],
            'supports'        => array(
                'autoRegister' => true,
            ),
        )
    );

    register_block_type(
        'tsjippy/displayname',
        array(
            'title'           => __( 'User Display Name', 'tsjippy' ),
            'render_callback' => function(){
                return "<span>".displayName()."</span>";
            },
            'supports'        => array(
                'autoRegister' => true,
            ),
        )
    );

	register_block_type(
        'tsjippy/login-count',
        array(
            'title'           => __( 'User Login Count', 'tsjippy' ),
            'render_callback' => function(){
                return "<span>".loginCount()."</span>";
            },
            'supports'        => array(
                'autoRegister' => true,
            ),
        )
    );

    register_block_type(
        'tsjippy/show-categories',
        array(
            'title'           => __( 'Show Post Categories', 'tsjippy' ),
            'attributes'      => array(
                'title'   => array(
                    'label'   => __("Show categories count", "tsjippy"),
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
            'render_callback' => __NAMESPACE__ . '\displayCategories',
            'supports'        => array(
                'autoRegister' => true,
            ),
        )
    );
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
        'echo'              => 0,
        'taxonomy'          => $taxonomy,
        'current_category'  => get_queried_object()->term_id,
        'show_count'        => $args['count'],
        'title_li'          => '<h4>' . esc_html(__('Categories', '%TEXTDOMAIN%')) . '</h4>'
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
            // phpcs:ignore
            str_contains($_SERVER['HTTP_REFERER'] ?? '', "/wp-admin/widgets.php")
        ) {
            return '<div class="childpost">This page has no children</div>';
        } else {
            return '';
        }
    }

    if (has_post_parent($parentId)) {
        if ($attributes['grantparents']) {
            $ancestors = get_post_ancestors($parentId);
            $level     = min($attributes['grantparents'], count($ancestors)) - 1;
            $parentId  = $ancestors[$level];
        } elseif ($attributes['parents']) {
            $parentId  = wp_get_post_parent_id($parentId);
        }
    }

    $html    = wp_list_pages(array(
        'depth'        => $depth,
        'child_of'     => $parentId,
        'echo'         => false,
        'post_type'    => get_post_type($parentId),
        'title_li'     => null,
        'hierarchical' => true,
    ));

    if (!empty($html)) {
        wp_enqueue_script('tsjippy-child-posts', PLUGINPATH.'blocks/show_children/expand.min.js', array(), PLUGINVERSION, true);

        if (!empty($attributes['listtype'])) {
            $html = str_replace("<li ", "<li style='list-style-type: " . esc_html($attributes['listtype']), $html);
        }

        $html  = str_replace("class='children'", "class='children hidden'", $html);
        $title = '';

        if ($attributes['title']) {
            $url   = esc_url(get_permalink(($parentId)));
            $title = "<h4><a href='$url'>" . esc_html(get_the_title($parentId)) . "</a></h4>";
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

