<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

/**
 * Prints html properly outlined for easy debugging
 */
function printHtml($html)
{
    $tabs    = 0;

    // Split on the < symbol to get a list of opening and closing tags
    $html        = explode('>', $html);
    $newHtml    = '';

    // loop over the elements
    foreach ($html as $index => $el) {
        $el = trim($el);

        if (empty($el)) {
            continue;
        }

        // Split the line on a closing character </
        $lines    = explode('</', $el);

        if (!empty($lines[0])) {
            $newHtml    .= "\n";

            // write as many tabs as need
            for ($x = 0; $x <= $tabs; $x++) {
                $newHtml    .= "\t";
            }

            // then write the first element
            $newHtml    .= $lines[0];
        }

        if (
            substr($el, 0, 1) == '<' &&                         // Element start with an opening symbol
            substr($el, 0, 2) != '</' &&                         // It does not start with a closing symbol
            substr($el, 0, 6) != '<input' &&                     // It does not start with <input (as that one does not have a closing />)
            (
                substr($el, 0, 7) != '<option' ||                 // It does not start with <option (as that one does not have a closing />)
                str_contains($html[$index + 1], '</option')         // or the next element contains a closing option
            ) &&
            $el != '<br'
        ) {
            $tabs++;
        }

        if (isset($lines[1])) {
            $tabs--;

            $newHtml    .= "\n";

            for ($x = 0; $x <= $tabs; $x++) {
                $newHtml    .= "\t";
            }
            $newHtml    .= '</' . $lines[1] . '>';
        } else {
            $newHtml    .= '>';
        }
    }

    printArray($newHtml);
}

// disable auto updates for this plugin on localhost
add_filter('auto_update_plugin', __NAMESPACE__ . '\disableAutoUpdate', 10, 2);
/** 
 * Disable auto-updates for the specified plugin on localhost.
 * 
 * @param bool $value The current auto-update status.
 * @param object $item The plugin item object.
 * 
 * @return bool The modified auto-update status.
*/
function disableAutoUpdate($value, $item)
{
    if ('tsjippy-shared-functionality' === $item->slug && (wp_get_environment_type() === 'local')) {
        return false; // disable auto-updates for the specified plugin
    }

    return $value; // Preserve auto-update status for other plugins
}

//Shortcode for testing
add_shortcode("tsjippy_test", function ($atts) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    global $wpdb;

    $removedShortCodes  = [
        'tsjippy_your_posts' => 'tsjippy-frontend-posting/your-posts',
        'tsjippy_pending-pages' => 'tsjippy-frontend-posting/pending-posts',
        'tsjippy_front_end_post'=> 'tsjippy-frontend-posting/front-end-posting',
        'tsjippy_old-pages' => 'tsjippy-frontend-posting/old-posts',
        'tsjippy_ministry_description' => 'tsjippy-locations/description',
        'tsjippy_mailchimp' => 'tsjippy-mailchimp/show-campaign',
        'tsjippy_mediagallery' => "tsjippy-media-gallery/show",
        'tsjippy_formselector' => 'tsjippy-forms/form-selector',
        'tsjippy_formbuilder' => 'tsjippy-forms/form-builder',
        'tsjippy_formresults' => 'tsjippy-forms/forms-results',
        "tsjippy_missing_form_fields" => 'tsjippy-forms/missing-form-inputs',
        'tsjippy_twofa_setup' => 'tsjippy-login/twofa-setup',
        "tsjippy_change_password" => 'tsjippy-login/change-password',
        'tsjippy_request_account' => 'tsjippy-login/request-user-account',
        "tsjippy_schedules" => 'tsjippy-schedules/show-schedules',
        'tsjippy_pending_user' => 'tsjippy-user-management/pending-user-accounts',
        "tsjippy_userstatistics" => 'tsjippy-user-management/user-statistics',
        '{"onlyOn":[],"phpFilters":[]}' => '',
        'tsjippy_user_link' => "tsjippy-user-pages/description",
        "tsjippy_vimeo_video" => 'tsjippy-vimeo/show-video',
        "tsjippy_welcome" => 'tsjippy-welcome-message/show',
        'tsjippy/locationmeta' => "tsjippy-locations/meta",
        "tsjippy/media-gallery" => "tsjippy-media-gallery/show",
        "tsjippy-user-pages/user_description" => "tsjippy-user-pages/description",
        'tsjippy-welcome-message/show_message' => 'tsjippy-welcome-message/show',
        "tsjippy/embed-page" => "tsjippy-embed-page/show",
        'tsjippy-schedules/show-schedules' => 'tsjippy-schedules/show'
    ];

    foreach($removedShortCodes as $shortcode => $block){
        $posts  = $wpdb->get_results("select * from $wpdb->posts where post_content like '%$shortcode%'");

        foreach($posts as $post){
            echo "Processing post <a href='".get_permalink($post)."' target='_blank'>$post->post_title</a><br>";
            if(preg_match_all( '/(<!-- wp:paragraph .*?-->)?\s*\R?(<!-- wp:shortcode.*?-->)?\s*\R?(<p>)?\s*\R?' . get_shortcode_regex([$shortcode]) . '(<\/p>)?\s*\R?(<!-- \/wp:shortcode -->)?\s*\R?(<!-- \/wp:paragraph -->)?\s*\R?/', $post->post_content, $matches, PREG_SET_ORDER )){
                foreach($matches as $data){
                    $replacement    = "<!-- wp:$block ";

                    if(!empty($data[6])){
                        $params     = [];
                        $exploded1   = explode(' ', trim($data[6]));

                        foreach($exploded1 as $explode1){
                            $exploded   = explode('=', $explode1);

                            foreach($exploded as &$exp){
                                $exp = trim(str_replace("'", '"', $exp));

                                if(!is_numeric($exp) && $exp != 'true' && $exp != 'false' && !str_contains($exp, '"')){
                                    $exp    = '"'.$exp.'"';
                                }
                            }

                            $params[]   = implode(':', $exploded);
                        }

                        $replacement    .= '{'.implode(',', $params).'} ';
                    }

                    $replacement    .= "/-->";

                    $post->post_content = str_replace($data[0], $replacement, $post->post_content);
                }
            }else{
                $post->post_content = str_replace($shortcode, $block, $post->post_content);
            }

            $wpdb->update(
                $wpdb->posts,
                ['post_content'  => $post->post_content],
                ['ID' => $post->ID]
            );
        }
    }

    $results = $wpdb->get_results("select * from {$wpdb->prefix}tsjippy_form_elements where conditions is not null");
    foreach($results as $result){
        $conditions = $result->conditions;
        while(!is_array($conditions)){
            $conditions = unserialize($conditions);

            if(!$conditions){
                break;
            }
        }

        $wpdb->update(
            "{$wpdb->prefix}tsjippy_form_elements",
            ['conditions'  => maybe_serialize($conditions)],
            ['id' => $result->id]
        );
    }

    $wpdb->query("update `{$wpdb->prefix}term_taxonomy` set taxonomy = 'tsjippy_visibility' where taxonomy = 'visibility'" );
});

// turn off incorrect error on localhost
add_filter('wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false');
