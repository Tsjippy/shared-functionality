<?php

namespace TSJIPPY\FRONTPAGE;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

add_action('init', __NAMESPACE__ . '\blockInit');
function blockInit()
{
    // Register all js blocks
    $manifestPath   = __DIR__ . '/build/blocks-manifest.php';
    $buildPath      = __DIR__ . '/build';

    wp_register_block_types_from_metadata_collection( $buildPath, $manifestPath );

    /**
     * PHP Only blocks
     */
    register_block_type(
        'tsjippy/displayname',
        array(
            'title'           => __( 'Display Name', 'tsjippy' ),
            'apiVersion' => 3,
            'attributes'      => array(
                'size'    => array(
                    'label'   => __( 'Size', 'tsjippy' ),
                    'type'    => 'string',
                    'enum'    => array( 'small', 'medium', 'large' ),
                    'default' => 'medium',
                ),
            ),
            'render_callback' => __NAMESPACE__ . '\displayName',
            'supports'        => array(
                'autoRegister' => true,
            ),
            "category" => "form-blocks",
            "icon"     => "caution",
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
        'echo'                => 0,
        'taxonomy'             => $taxonomy,
        'current_category'    => get_queried_object()->term_id,
        'show_count'        => $args['count'],
        'title_li'             => '<h4>' . __('Categories', 'tsjippy-theme') . '</h4>'
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
        return;
    }

    $html    = '';
    $depth    = 1;
    if ($attributes['grandchildren']) {
        $depth    = 0;
    }
    $parentId    = get_the_ID();
    if (!$parentId) {
        if (isset($attributes['postid']) && is_numeric($attributes['postid'])) {
            $parentId    = $attributes['postid'];
        } elseif ( TSJIPPY\onBlockEditPage()) {
            ob_start();
            ?>
            <div class="childpost">
                This page has no children, so here is an example of what the block will look like when it is used on a page with children.
                <ul>
                    <li><a href="#">Child Page 1</a></li>
                    <li><a href="#">Child Page 2</a></li>
                    <li><a href="#">Child Page 3</a></li>
                </ul>
            </div>
            <?php
            $html = ob_get_clean();
        } else {
            return;
        }
    }

    if(empty($html)){
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
    }

    if (!empty($html)) {
        wp_enqueue_script_module('@tsjippy/child-posts');

        if (!empty($attributes['listtype'])) {
            $html    = str_replace("<li", "<li style='list-style-type: {$attributes['listtype']}'", $html);
        }

        $html    = str_replace("class='children'", "class='children hidden'", $html);

        ?>
        <div class='childpost'>
            <?php
            if ($attributes['title']) {
                ?>
                <h4>
                    <a href='<?php echo esc_url(get_permalink(($parentId)));?>'>
                        <?php echo esc_html(get_the_title($parentId)); ?>
                    </a>
                </h4>
            <?php } ?>
            <ul><?php echo wp_kses_post($html); ?></ul>
        </div>
        <?php
        return;
    }

    return;
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

