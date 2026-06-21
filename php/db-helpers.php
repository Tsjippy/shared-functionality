<?php

namespace TSJIPPY;

use WP_Error;

if (! defined('ABSPATH')) exit;

/**
 * Search every table and column in the db
 *
 * @param    string    $search                the searchstring
 * @param    array    $excludedTables        the tables to exclude from the search
 * @param    array    $excludedColumns    the columns to exclude from the search
 *
 * @return    array                        An array of results
 */
function searchAllDB($search, $excludedTables = [], $excludedColumns = [])
{
    global $wpdb;

    $out     = [];

    // phpcs:ignore
    $tables    = $wpdb->get_results("show tables", ARRAY_N);
    if (!empty($tables)) {
        foreach ($tables as $table) {
            if (in_array($table[0], $excludedTables)) {
                continue;
            }

            $sqlSearchFields     = [];

            // phpcs:disable
            $columns             = $wpdb->get_results(
                $wpdb->prepare("SHOW COLUMNS FROM %i", $table[0])
            );
            // phpcs:enable

            if (!empty($columns)) {
                foreach ($columns as $column) {
                    if (in_array($column->Field, $excludedColumns)) {
                        continue;
                    }

                    $sqlSearchFields[] = "`" . $column->Field . "` like('%" . $wpdb->_real_escape($search) . "%')";
                }
            }

            // phpcs:disable
            $results        = $wpdb->get_results(
                $wpdb->prepare("select * from %i where %s", $table[0], implode(" OR ", $sqlSearchFields))
            );
            // phpcs:enable
            
            if (!empty($results)) {
                foreach ($results as $result) {
                    foreach ($result as $column => $value) {
                        if (in_array($column, $excludedColumns)) {
                            continue;
                        }
                        if (str_contains($value, $search)) {
                            $out[]     = [
                                'table'        => $table[0],
                                'column'    => $column,
                                'value'        => $value,
                            ];
                        }
                    }
                }
            }
        }
    }

    foreach ($out as $index => &$result) {
        $match    = false;
        $value    = maybe_unserialize($result['value']);
        if (is_array($value)) {
            $found    = arraySearchRecursive($search, $result);
            if (!empty($found)) {
                $match    = true;
                $result    = $found;
            }
        } elseif ($value == $search) {
            $match    = true;
        }

        if (!$match) {
            unset($out[$index]);
        }
    }

    return array_values($out);
}

/**
 * Temporary store a value
 *
 * @param   string                  $key        The identifier
 * @param   string|int|array|object $value      The value
 * @param   int                     $expiration Optional. Time until expiration in seconds. Default 3600.
 */
function storeInTransient($key, $value, $expiration=HOUR_IN_SECONDS)
{
    set_transient($key, sanitize(base64_encode(serialize($value))), $expiration);
}

/**
 * Retrieves a temporary stored value
 *
 * @param   string  $key    The key the values was stored with
 *
 * @return  mixed            The value or false if no value
 */
function getFromTransient($key)
{
    $value      = get_transient($key);

    // Check if valid base64 string
    if(base64_encode(base64_decode($value, true)) === $value){
        $value  = maybe_unserialize(base64_decode($value));
    }
    // phpcs:enable

    return $value;
}

/**
 * Deletes a temporary stored value
 *
 * @param   string  $key    The key the values was stored with
 *
 * @return  string|int|array|object             The value
 */
function deleteFromTransient($key)
{
    delete_transient($key);
}

/**
 * Get a value from the db, or cache
 * @param string      $cacheKey  The key to identify the cache value
 * @param string      $group     Where the cache contents are grouped. Preferably the plugin slug
 * @param string      $query     Query statement with `sprintf()`-like placeholders.
 * @param mixed       ...$args   Variables to substitute into the query's placeholders if being called with individual arguments.
 */
function getFromDb($cacheKey, $group, $query, ...$args)
{
    global $wpdb;

    $query      = strtolower($query);

    $function   = 'get_results';

    $queryParts = explode('from', strtolower($query));
    $select     = $queryParts[0];
    if (
        // We use an averaging function
        (
            (
                // not all colls
                !str_contains($select, '*') &&
                // And we just want one row
                str_ends_with($query, 'limit 1')
            ) ||
            str_contains($select, 'count(') ||
            str_contains($select, 'sum(') ||
            str_contains($select, 'avg(') ||
            str_contains($select, 'max(') ||
            str_contains($select, 'min(')
        ) &&
        
        // And we do not need other columns
        !str_contains($select, ',')
    ) {
        $function = 'get_var';
    } 
    
    // Check if we are selecting more then one column
    else if (!str_contains($query, 'select * from') && !str_contains($queryParts[0], ',')) {
        $function = 'get_col';
    }

    $value = wp_cache_get($cacheKey, $group, false, $found);

    if (!$found) {
        // phpcs:disable
        $value = $wpdb->$function(
            $wpdb->prepare($query, ...$args)
        );
        // phpcs:enable

        if ($wpdb->last_error !== '') {
            return new \WP_Error('db', $wpdb->last_error);
        }

        wp_cache_set($cacheKey, $value, $group);
    }

    return maybe_unserialize($value);
}
