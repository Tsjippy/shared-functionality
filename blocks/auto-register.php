<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

add_action('enqueue_block_editor_assets', __NAMESPACE__ . '\enqueueAutoRegisterBlocks', 5);
/**
 * Registers PHP-only blocks in the block editor.
 */
function enqueueAutoRegisterBlocks()
{
    $blocks = getAutoRegisterBlockDefinitions();

    if (empty($blocks)) {
        return;
    }

    wp_enqueue_script(
        'tsjippy-auto-register-blocks',
        pathToUrl(PLUGINPATH . 'blocks/auto-register/register.js'),
        [
            'wp-blocks',
            'wp-element',
            'wp-block-editor',
            'wp-components',
            'wp-server-side-render',
            'wp-i18n',
        ],
        STYLEVERSION,
        true
    );

    wp_add_inline_script(
        'tsjippy-auto-register-blocks',
        'window.tsjippyAutoRegisterBlocks = ' . wp_json_encode($blocks) . ';',
        'before'
    );

    // #region agent log
    debugLogBlocks(
        'auto-register.php:enqueueAutoRegisterBlocks',
        'Enqueued auto-register block editor script',
        [
            'blockCount' => count($blocks),
            'blockNames' => array_column($blocks, 'name'),
        ],
        'D'
    );
    // #endregion
}

/**
 * Collect block definitions that need client-side editor registration.
 *
 * @return array<int, array<string, mixed>>
 */
function getAutoRegisterBlockDefinitions()
{
    $registry = \WP_Block_Type_Registry::get_instance();
    $blocks   = [];

    foreach ($registry->get_all_registered() as $name => $block) {
        if (strpos($name, 'tsjippy') === false) {
            continue;
        }

        if (empty($block->supports['autoRegister'])) {
            continue;
        }

        if (!empty($block->editor_script) || !empty($block->editor_script_handles)) {
            continue;
        }

        $attributes = [];
        foreach ($block->attributes ?? [] as $attrName => $attrConfig) {
            $attributes[$attrName] = [
                'type'    => $attrConfig['type'] ?? 'string',
                'default' => $attrConfig['default'] ?? '',
                'label'   => $attrConfig['label'] ?? $attrName,
            ];

            if (!empty($attrConfig['enum'])) {
                $attributes[$attrName]['enum'] = array_values($attrConfig['enum']);
            }
        }

        $blocks[] = [
            'name'       => $name,
            'title'      => $block->title,
            'icon'       => is_string($block->icon) ? $block->icon : 'admin-generic',
            'category'   => is_string($block->category) ? $block->category : 'widgets',
            'attributes' => $attributes,
        ];
    }

    return $blocks;
}
