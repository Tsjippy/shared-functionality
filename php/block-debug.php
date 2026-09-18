<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

// #region agent log
function debugLogBlocks($location, $message, $data = [], $hypothesisId = 'ALL')
{
    $payload = [
        'sessionId'  => '0a5746',
        'runId'      => 'pre-fix',
        'hypothesisId' => $hypothesisId,
        'location'   => $location,
        'message'    => $message,
        'data'       => $data,
        'timestamp'  => round(microtime(true) * 1000),
    ];

    $logPath = WP_CONTENT_DIR . '/plugins/debug-0a5746.log';
    file_put_contents($logPath, json_encode($payload) . "\n", FILE_APPEND | LOCK_EX);
}
// #endregion

add_action('plugins_loaded', function () {
    // #region agent log
    $registry = \WP_Block_Type_Registry::get_instance();
    $autoRegister = [];
    $withoutEditorScript = [];
    $withEditorScript = [];

    foreach ($registry->get_all_registered() as $name => $block) {
        if (strpos($name, 'tsjippy') === false) {
            continue;
        }

        $supports = $block->supports ?? [];
        if (!empty($supports['autoRegister'])) {
            $autoRegister[] = $name;
        }

        if (!empty($block->editor_script) || !empty($block->editor_script_handles)) {
            $withEditorScript[] = $name;
        } else {
            $withoutEditorScript[] = $name;
        }
    }

    debugLogBlocks(
        'block-debug.php:plugins_loaded',
        'Tsjippy blocks after plugin PHP loaded (before init)',
        [
            'autoRegisterBlocks'      => $autoRegister,
            'withoutEditorScript'     => $withoutEditorScript,
            'withEditorScriptCount'   => count($withEditorScript),
            'autoRegisterCount'       => count($autoRegister),
        ],
        'D'
    );
    // #endregion
}, 9999);

add_action('init', function () {
    // #region agent log
    $registry = \WP_Block_Type_Registry::get_instance();
    $tsjippyBlocks = [];
    $autoRegisterNoEditor = [];

    foreach ($registry->get_all_registered() as $name => $block) {
        if (strpos($name, 'tsjippy') === false) {
            continue;
        }

        $tsjippyBlocks[] = $name;
        $supports = $block->supports ?? [];
        $hasEditor = !empty($block->editor_script) || !empty($block->editor_script_handles);

        if (!empty($supports['autoRegister']) && !$hasEditor) {
            $autoRegisterNoEditor[] = $name;
        }
    }

    debugLogBlocks(
        'block-debug.php:init',
        'Tsjippy blocks after init',
        [
            'totalTsjippyBlocks'         => count($tsjippyBlocks),
            'sampleBlocks'               => array_slice($tsjippyBlocks, 0, 15),
            'autoRegisterWithoutEditor'  => $autoRegisterNoEditor,
            'formsFormbuilderRegistered' => $registry->is_registered('tsjippy-forms/formbuilder'),
            'formsFormbuilderHasEditor'  => !empty($registry->get_registered('tsjippy-forms/formbuilder')?->editor_script_handles),
        ],
        'C'
    );
    // #endregion
}, 9999);

add_action('enqueue_block_editor_assets', function () {
    // #region agent log
    $registry = \WP_Block_Type_Registry::get_instance();
    $autoRegisterNoEditor = [];

    foreach ($registry->get_all_registered() as $name => $block) {
        if (strpos($name, 'tsjippy') === false) {
            continue;
        }

        $supports = $block->supports ?? [];
        $hasEditor = !empty($block->editor_script) || !empty($block->editor_script_handles);

        if (!empty($supports['autoRegister']) && !$hasEditor) {
            $autoRegisterNoEditor[] = $name;
        }
    }

    debugLogBlocks(
        'block-debug.php:enqueue_block_editor_assets',
        'Block editor assets enqueue',
        [
            'isAdmin'                   => is_admin(),
            'autoRegisterWithoutEditor' => $autoRegisterNoEditor,
            'wpScriptModulesRegistered' => function_exists('wp_script_modules') ? array_keys((array) (wp_script_modules()->registered ?? [])) : [],
        ],
        'E'
    );

    wp_add_inline_script(
        'wp-blocks',
        "(function(){fetch('http://127.0.0.1:7606/ingest/65213ea6-d059-40d4-b7e9-410ca2f639ca',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'0a5746'},body:JSON.stringify({sessionId:'0a5746',runId:'pre-fix',hypothesisId:'E',location:'block-editor:inline',message:'Block editor JS boot',data:{registeredBlockCount:(window.wp&&wp.blocks&&wp.blocks.getBlockTypes)?wp.blocks.getBlockTypes().filter(function(b){return b.name.indexOf('tsjippy')===0;}).length:0,tsjippyBlockNames:(window.wp&&wp.blocks&&wp.blocks.getBlockTypes)?wp.blocks.getBlockTypes().filter(function(b){return b.name.indexOf('tsjippy')===0;}).map(function(b){return b.name;}).slice(0,20):[]},timestamp:Date.now()})}).catch(function(){});})();",
        'after'
    );
    // #endregion
}, 9999);
