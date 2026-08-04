<?php

namespace TSJIPPY;

use WP_Error;

if (! defined('ABSPATH')) exit;


/**
 * Updated nested array based on array of keys
 * @param    array        $keys              The keys
 * @param    array        $array            Reference to an array
 * @param    string        $value            The value to set
 */
function addToNestedArray($keys, &$array = array(), $value = null)
{
    //$temp point to the same content as $array
    $temp = &$array;
    if (!is_array($temp)) {
        $temp = [];
    }

    //loop over all the keys
    foreach ($keys as $key) {
        if (!isset($temp[$key])) {
            $temp[$key]    = [];
        }
        //$temp points now to $array[$key]
        $temp = &$temp[$key];
    }

    //We update $temp resulting in updating $array[X][y][z] as well
    $temp[] = $value;
}

/**
 * Removes a key from a nested array based on array of keys
 * @param    array        $array            Reference to an array
 * @param    array        $arrayKeys        Array of keys
 *
 * @return array                        The array
 */
function removeFromNestedArray(&$array, $arrayKeys)
{
    if (!is_array($array)) {
        return $array;
    }

    $last         = array_key_last($arrayKeys);
    $current     = &$array;
    foreach ($arrayKeys as $index => $key) {
        if ($index == $last) {
            unset($current[$key]);
        } else {
            $current = &$current[$key];
        }
    }

    return $current;
}

/**
 * Removes all empty values from array, if the emty value is an array keep it by default
 * @param    array        $array            Reference to an array
 */
function cleanUpNestedArray($array)
{
    if (!is_array($array)) {
        return $array;
    }

    // This is an array containing arrays
    if (count($array) != count($array, COUNT_RECURSIVE)){
        foreach($array as $index => &$value){
            if(is_array($value)){
                $value  = cleanUpNestedArray($value);

                if(empty($value)){
                    unset($array[$index]);
                }
            }
        }

        unset($value);
    }

    return array_filter(
        $array,
        function ($value) {
            return ($value !== false && $value !== null && $value !== "");
        }
    );
}

/**
 * Checks if a given array is associative
 * 
 * @param   array   $array  The array to check
 * 
 * 
 * @return  bool            True if associative false otherwise
 */
function isAssociative($array)
{
    return $array !== array_values($array);
}

/**
 * Get the value of a given meta key
 * @param    int    $userId         WP_User id
 * @param    string $metaKey        The meta key we should get the value for
 * @param    array  $values         The optional values of a metakey
 *
 * @return string                   The value
 */
function getMetaArrayValue($userId, $metaKey, $values = null)
{
    if (empty($metaKey)) {
        return $values;
    }

    if ($values === null && !empty($metaKey)) {
        //get the basemetakey in case of an indexed one
        if (preg_match('/(.*?)\[/', $metaKey, $match)) {
            $baseMetaKey    = $match[1];
        } else {
            //just use the whole, it is not indexed
            $baseMetaKey    = $metaKey;
        }
        $values    = (array)get_user_meta($userId, $baseMetaKey, true);
    }

    $value    = $values;

    //Return the value of the variable whos name is in the keystringvariable
    preg_match_all('/\[(.*?)\]/', $metaKey, $matches);
    if (!empty($matches[1]) && is_array($matches[1])) {
        foreach ($matches[1] as $key) {
            if (!is_array($value)) {
                break;
            }

            if (empty($key)) {
                $value = array_values($value)[0];
            } else {
                if (!isset($value[$key])) {
                    $key    = str_replace('-files', '', $key);
                }

                if (isset($value[$key])) {
                    $value    = $value[$key];
                } else {
                    $value    = '';
                }
            }
        }
    }

    return $value;
}

/**
 * Finds a value in an nested array
 * @param  mixed  $needle      The value to search for
 * @param  array  $haystack    The array to search in
 * @param  bool   $strict      Whether to use strict comparison
 * @param  array  $stack       Used internally to keep track of the current stack of keys
 * @return array               An array of key paths where the value was found
 */
function arraySearchRecursive($needle, $haystack, $strict = true, $stack = array())
{
    $results = array();
    foreach ($haystack as $key => $value) {
        if (($strict && $needle == $value) || (is_string($value) && !$strict && str_contains($value, $needle))) {
            $value    = maybe_unserialize($value);

            if (!is_array($value)) {
                $results[] = array_merge($stack, array($key));
            }
        }

        if (is_array($value) && count($value) != 0) {
            $results = array_merge($results, arraySearchRecursive($needle, $value, $strict, array_merge($stack, array($key))));
        }
    }
    return ($results);
}

/**
 * Compares nested arrays to find whats changed
 * 
 * @param array $array1 First array to compare
 * @param array $array2 Second array to compare
 * 
 * @return array        Array containing the diffrences found
 */
function arrayDiffAssocRecursive($array1, $array2)
{
    $difference = [];

    $array1  = cleanUpNestedArray($array1);
    $array2  = cleanUpNestedArray($array2);

    $assoc   = true;

    /**
     * Remove duplicates
     */
    if(!isAssociative($array1) && !isAssociative($array2)){
        $array1 = array_unique($array1);
        $array2 = array_unique($array2);

        $assoc   = false;
    }

    foreach ($array1 as $key => $value) {
        // 1. Check if the key exists in the second array
        if ($assoc && !isset($array2[$key])) {
            $difference[$key] = $value;
        }

        // 2. If both are arrays, recursively check their differences
        elseif (is_array($value) && is_array($array2[$key])) {
            $subDiff = arrayDiffAssocRecursive($value, $array2[$key]);
            if (!empty($subDiff)) {
                $difference[$key] = $subDiff;
            }
        }

        // 3. Check if the key exists in the second array if it is an associative array
        elseif ($assoc && $value != $array2[$key]) {
            $difference[$key] = $value;
        }
        
        // 4. If it is not associative just check for the value
        elseif(!$assoc && array_search($value, $array2) === false){
            $difference[$key] = $value;
        }
    }

    return $difference;
}