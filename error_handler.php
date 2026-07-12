<?php

namespace TSJIPPY;

if (! defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    // Sub menu for Logs
    add_submenu_page(
        'tsjippy
        ',
        'Logs',
        'Logs',
        "edit_others_posts",
        'tsjippy-logs',
        function () {
            $mainAdminMenu = new ADMIN\MainAdminMenu();
            $mainAdminMenu->buildSubMenu('Logs', 'logs');
        },
        1
    );
}, 20);

add_action('rest_api_init', __NAMESPACE__ . '\restApiInitDev');
function restApiInitDev()
{
    register_rest_route(
        RESTAPIPREFIX,
        '/get_logs',
        array(
            'methods'                 => 'POST',
            'callback'                => __NAMESPACE__ . '\getLogs',
            'permission_callback'     => function(){
                return current_user_can('edit_others_posts');
            },
            'args'                    => array(
                'id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($id) {
                        return is_numeric($id);
                    }
                ),
                'nonce'        => array(
                    'required'    => true,
                    'validate_callback' => function ($nonce) {
                        return wp_verify_nonce($nonce, 'update_logs');
                    }
                ),
                'page' => []
            )
        )
    );

    register_rest_route(
        RESTAPIPREFIX,
        '/clear_logs',
        array(
            'methods'                 => 'POST',
            'callback'                 => __NAMESPACE__ . '\clearLogs',
            'permission_callback'     => function(){
                return current_user_can('edit_others_posts');
            },
            'args'                    => array(
                'nonce'        => array(
                    'required'    => true,
                    'validate_callback' => function ($nonce) {
                        return wp_verify_nonce($nonce, 'delete_logs');
                    }
                )
            )
        )
    );

    register_rest_route(
        RESTAPIPREFIX,
        '/delete_log_entry',
        array(
            'methods'                 => 'POST',
            'callback'                 => __NAMESPACE__ . '\removeEntry',
            'permission_callback'     => function(){
                return current_user_can('edit_others_posts');
            },
            'args'                    => array(
                'id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($id) {
                        return is_numeric($id);
                    }
                ),
                'nonce'        => array(
                    'required'    => true,
                    'validate_callback' => function ($nonce) {
                        return wp_verify_nonce($nonce, 'delete_log_entry');
                    }
                )
            )
        )
    );

    register_rest_route(
        RESTAPIPREFIX,
        '/ignore_log_entry',
        array(
            'methods'                 => 'POST',
            'callback'                 => __NAMESPACE__ . '\storeIgnore',
            'permission_callback'     => function(){
                return current_user_can('edit_others_posts');
            },
            'args'                    => array(
                'id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($id) {
                        return is_numeric($id);
                    }
                ),
                'nonce'        => array(
                    'required'    => true,
                    'validate_callback' => function ($nonce) {
                        return wp_verify_nonce($nonce, 'ignore_log_entry');
                    }
                )
            )
        )
    );
}

function shutdown()
{
    $error = error_get_last();
    if (!empty($error)) {
        printError($error['type'], $error['message'], $error['file'], $error['line']);
    }
}
register_shutdown_function(__NAMESPACE__ . '\shutdown');

/**
 * Prints error messages
 *
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 */
function printError($errno, $errstr, $errfile, $errline)
{
    if (isset([E_ERROR => 1, E_PARSE => 1, E_CORE_ERROR => 1, E_COMPILE_ERROR => 1, E_USER_ERROR => 1][$errno])) {
        $type = 'error';
    } elseif (isset([E_WARNING => 1, E_NOTICE => 1, E_CORE_WARNING => 1, E_COMPILE_WARNING => 1, E_DEPRECATED => 1, E_USER_WARNING => 1, E_USER_DEPRECATED => 1][$errno])) {
        $type = 'warning';
    } else {
        $type = 'info';
    }

    if ( !str_contains($errfile, '/lib/vendor/') ) {
        // Store in db
        $logger = new Logger();
        $logger->insertData(time(), $type, $errstr, str_replace("\n", "<br>", generateStackTrace()));
    }
}
// phpcs:disable
set_error_handler(__NAMESPACE__ . '\printError');
// phpcs:enable

// Function from php.net https://php.net/manual/en/function.debug-backtrace.php#112238
function generateStackTrace()
{

    $e = new \Exception();

    $trace = explode("\n", $e->getTraceAsString());

    // reverse array to make steps line up chronologically
    $trace = array_reverse($trace);

    array_shift($trace); // remove {main}
    array_pop($trace); // remove call to this method

    $length = count($trace);
    $result = array();

    for ($i = 0; $i < $length; $i++) {
        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
    }

    $result = implode("\n", $result);;

    return $result;
}

/**
 * Prints something to the log file and optional to the screen
 * @param     string        $message                 The message to be printed
 * @param    bool        $display                Whether to print the message to the screen or not
 * @param    bool|int    $printFunctionHiearchy    Whether to print the full backtrace, false for not printing, true for all, number for max depth
 */
function printArray($message, $display = false, $printFunctionHiearchy = false, $error = false)
{
    $logger = new Logger();

    // phpcs:disable
    $bt        = debug_backtrace();

    if ($error) {
        $level = 'error';
    } else {
        $level = 'info';
    }

    $path    = '';

    if ($printFunctionHiearchy) {
        error_log("Called from:");
        $caller    = "";
        foreach ($bt as $index => $trace) {
            // stop if we have reached the max depth
            if (is_numeric($printFunctionHiearchy) && $index == $printFunctionHiearchy) {
                break;
            }

            $path    = str_replace(PLUGINPATH, '', $trace['file']);
            $line    = $trace['line'];

            $caller        .= "$index\n";
            $caller        .= "    File: $path\n";
            $caller        .= "    Line $line\n";
            $caller        .= "    Function: {$trace['function']}\n";
            $caller        .= "    Args:\n";
            $caller        .= "    " . print_r($trace['args'], true);
        }

        error_log($caller);
    } else {
        $caller = array_shift($bt);
        $path    = str_replace(PLUGINPATH, '', $caller['file']);
        $line    = $caller['line'];

        $caller    = "Called from file $path line $line\n";
        error_log($caller);
    }

    if (is_object($message)) {
        if (method_exists($message, 'getMessage')) {
            $message            = $message->getMessage();
            $messageWithDate    = gmdate('Y-m-d H:i:s', time()) . ' - ' . $message . "\n";
        } else {
            $messageWithDate     = $message    = print_r($message, true);
        }
    } elseif (is_array($message)) {
        $messageWithDate     = $message    = print_r($message, true);
    } else {
        $messageWithDate    = gmdate('Y-m-d H:i:s', time()) . ' - ' . $message . "\n";
    }

    $logger->insertData(time(), $level, $message, $caller);

    error_log($messageWithDate);

    if ($display) {
?>
        <pre>
            Called from <?php echo esc_html($caller); ?>
            <br>
            <br>
            <?php
            echo wp_kses_post(print_r($message));
            ?>
        </pre>
    <?php
    }
    
    // phpcs:enable
}

/**
 * Deletes a specific log entry
 */
function removeEntry($wpRest)
{
    $logger    = new Logger();
    $result    = $logger->removeEntry($wpRest->get_param('id'));

    return $result;
}

/**
 * Deletes a files  from the content dir
 */
function clearLogs()
{
    $logger    = new Logger();
    $logger->clearLogs();

    return true;
}

function logToHtml($logData)
{
    ob_start();

    foreach ($logData as $value) {
        $date    = gmdate(DATEFORMAT . ' H:i:s', $value->time_stamp);

    ?>
        <div class='log-block' data-level='<?php echo esc_attr($value->level); ?>'>
            <b><?php echo esc_html($date); ?></b>
            <button class="button tsjippy small delete-message" data-id="<?php echo esc_attr($value->id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('delete_log_entry')); ?>">
                Delete
            </button>
            <button class="button tsjippy small delete-similar" data-id="<?php echo esc_attr($value->id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('delete_log_entry')); ?>">
                Delete All Similar
            </button>
            <button class="button tsjippy small ignore" data-id="<?php echo esc_attr($value->id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('ignore_log_entry')); ?>">
                Ignore
            </button>
            <br>

            <i>
                <pre>
                    <?php echo wp_kses_post($value->message); ?>
                </pre>
            </i>
            <br>
            <i class="caller">
                <?php echo wp_kses_post(strip_tags($value->caller, '<br>')); ?>
            </i>
            <br><br>
        </div>
<?php
    }

    return ob_get_clean();
}

function getLogs($wpRest)
{
    $logger        = new Logger();

    $id            = $wpRest->get_param('id');
    $page        = $wpRest->get_param('page');

    $logs        = $logger->getLogs($id, $page);

    if (empty($logs)) {
        $lastId    = $id;
    } else {
        $lastId    = $logs[0]->id;
    }

    return [
        'html'        => logToHtml($logs),
        'last_id'    => $lastId
    ];
}

function storeIgnore($wpRest)
{
    $logger        = new Logger();

    $ignores    = get_option('tsjippy-logs-ignore', []);
    $ignores[$logger->getMessage($wpRest->get_param('id'))] = 1;

    update_option('tsjippy-logs-ignore', $ignores);

    return true;
}
