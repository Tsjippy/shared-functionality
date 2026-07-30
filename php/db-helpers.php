<?php

namespace TSJIPPY;

use WP_Error;

if (! defined('ABSPATH')) exit;

/**
 * Search every table and column in the db
 *
 * @param    string   $search             the searchstring
 * @param    array    $excludedTables     the tables to exclude from the search
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
 * Insert in DB
 * 
 * @param string    $table  The table to insert data
 * @param array     $data   An array of key => values to insert
 * @param array     $format THe formats for the data
 * @param string    $group  The group the for caching
 * 
 * @return int|WP_Error     The row id or an wp error object
 */
function insertInDb($table, $data, $format, $group){
    global $wpdb;

    // Serialize
    foreach($data as &$d){
        $d  = maybe_serialize($d);
    }

    // Insert booking in db
    // phpcs:ignore
    $wpdb->insert(
        $table,
        $data,
        $format
    );

    if(!str_contains($group, 'tsjippy_')){
        $group  = 'tsjippy_'.$group;
    }

    /**
     * Flush db cache
     */
    if(wp_cache_supports( 'flush_group' )){
        wp_cache_flush_group($group);
    }else{
        wp_cache_flush();
    }

    if (!empty($wpdb->last_error)) {
        return new \WP_Error($group, $wpdb->last_error);
    }

    return $wpdb->insert_id;
}

/**
    * Upates a value in the db
    * 
    * @param string          $table        Table name.
    * @param array           $data         Data to update (in column => value pairs).
    *                                      Both $data columns and $data values should be "raw" (neither should be SQL escaped).
    *                                      Sending a null value will cause the column to be set to NULL - the corresponding
    *                                      format is ignored in this case.
    * @param array           $where        A named array of WHERE clauses (in column => value pairs).
    *                                      Multiple clauses will be joined with ANDs.
    *                                      Both $where columns and $where values should be "raw".
    *                                      Sending a null value will create an IS NULL comparison - the corresponding
    *                                      format will be ignored in this case.
    * @param string[]|string $format       An array of formats to be mapped to each of the values in $data.
    *                                      If string, that format will be used for all of the values in $data.
    *                                      A format is one of '%d', '%f', '%s' (integer, float, string).
    *                                      If omitted, all values in $data will be treated as strings unless otherwise
    *                                      specified in wpdb::$field_types. Default null.
    * @param string[]|string $whereFormat  An array of formats to be mapped to each of the values in $where.
    *                                      If string, that format will be used for all of the items in $where.
    *                                      A format is one of '%d', '%f', '%s' (integer, float, string).
    *                                      If omitted, all values in $where will be treated as strings unless otherwise
    *                                      specified in wpdb::$field_types. Default null.
    * @param string         $group         The cache key
    * @return int|WP_Error                   The number of rows updated, or WP_Error on error.
 */
function updateDbValue($table, $data, $where, $format, $whereFormat, $group){
    global $wpdb;
    
    // Serialize
    foreach($data as &$d){
        $d  = maybe_serialize($d);
    }
    unset($d);

    // We have named format and data
    if(!is_numeric(array_keys($format)[0])){
        // Make sure we only keep the formats we need if possible
        $format = array_intersect_key($format, $data);

        // Make sure we only keep the data we have a format for
        $data = array_intersect_key($data, $format);
    }

    // phpcs:ignore
    $result = $wpdb->update(
        $table,
        $data,
        $where,
        $format,
        $whereFormat
    );

    /**
     * Maybe we should do an insert not an update
     */
    if($result === 0){

        /**
        * Check if it does exists
        */
        $whereString = '';
        $i = 0;
        foreach($where as $key => $value){
            if($i > 0){
                $whereString .= " AND ";
            }

            $whereString .= "$key = {$whereFormat[$i]}";
            $i++;
        }

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM %i WHERE $whereString",
                $table,
                ...$where
            )
        );

        if(!$exists){
            return insertInDb($table, array_merge($data, $where), $format, $group);
        }
    }
    
    if(!str_contains($group, 'tsjippy_')){
        $group  = 'tsjippy_'.$group;
    }

    /**
     * Flush db cache
     */
    if(wp_cache_supports( 'flush_group' )){
        wp_cache_flush_group($group);
    }else{
        wp_cache_flush();
    }

    if (!empty($wpdb->last_error)) {
        return new WP_Error($group, $wpdb->last_error);
    } 

    return $result;
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
    $value = wp_cache_get($cacheKey, $group, false, $found);

    if ($found) {
        return map_deep( $value, "maybe_unserialize" );
    }

    global $wpdb;

    $query      = strtolower($query);

    $function   = 'get_results';

    $queryParts = explode('from', strtolower($query));
    $select     = $queryParts[0];

    /**
     * get var
     */
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
    
    /**
     * Get column
     */
    else if (!str_contains($query, 'select * from') && !str_contains($queryParts[0], ',')) {
        $function = 'get_col';
    }

    /**
     * Get row
     */
    else if (str_contains($query, 'limit 1')) {
        $function = 'get_row';
    }

    // phpcs:disable
    $value = $wpdb->$function(
        $wpdb->prepare($query, ...$args)
    );
    // phpcs:enable

    if ($wpdb->last_error !== '') {
        return new \WP_Error('db', $wpdb->last_error);
    }
    
    if(!str_contains($group, 'tsjippy_')){
        $group  = 'tsjippy_'.$group;
    }

    wp_cache_set($cacheKey, $value, $group);

    // Unserialize twice as that is sometimes needed
    return map_deep(map_deep( $value, "maybe_unserialize" ), "maybe_unserialize" );
}

/**
 * deletes an value from both the db and the cache
 * 
 * @param string        $tableName The table to delete from
 * @param array         $where     Array containing colname => value pairs for deletion query OR an array containing a query with placeholders and value pairs 
 * @param array         $formats   Variable formats
 * @param string        $cacheKey  The key to identify the cache value
 * @param string        $group     Where the cache contents are grouped. Preferably the plugin slug
 */
function removeFromDb($tableName, $where, $formats, $group, $cacheKey=''){
    global $wpdb;

    if(is_numeric(array_keys($where)[0])){
        $query  = $where[0];

        unset($where[0]);

        // phpcs:ignore
        $wpdb->query($wpdb->prepare($query, $where));
    }else{
        // phpcs:ignore
        $wpdb->delete(
            $tableName,
            $where,
            $formats
        );
    }

    
    if(!str_contains($group, 'tsjippy_')){
        $group  = 'tsjippy_'.$group;
    }

    if(!empty($cacheKey)){
        wp_cache_delete($cacheKey, $group);
    }elseif(wp_cache_supports( 'flush_group' )){
        wp_cache_flush_group($group);
    }else{
        wp_cache_flush();
    }
}