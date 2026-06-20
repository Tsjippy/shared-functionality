<?php

namespace TSJIPPY\FAMILY;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

// Adds family values to the default values of a form
add_filter('tsjippy-forms-load-userdata', __NAMESPACE__ . '\addFamilyData', 10, 2);
function addFamilyData($usermeta, $userId)
{
    $family    = new TSJIPPY\FAMILY\Family();

    // check if this user has family
    if (!$family->hasFamily($userId)) {
        return $usermeta;
    }

    $familyMeta               = [];

    $familyMeta['children']    = $family->getChildren($userId);
    $familyMeta['parents']     = $family->getParents($userId);
    $familyMeta['siblings']    = $family->getSiblings($userId);
    $familyMeta['partner']     = $family->getPartner($userId);
    $familyMeta['weddingdate'] = $family->getWeddingDate($userId);

    foreach ($family->getFamilyMeta($userId) as $meta) {
        $familyMeta[$meta->meta_key] = maybe_unserialize($meta->meta_value);
    }

    return array_merge($usermeta, $familyMeta);
}

/**
 * Gets all the family meta keys
 * 
 * @param array $familyMetaKeys The variable to be filled with the meta keys
 * 
 * @return array family array keys 
 */
function getFamilyMetaKeys(&$familyMetaKeys='')
{
    /**
     * Filters the available family meta keys
     * 
     * @param array $metaKeys  The available keys array. Default ['family_name', 'family_picture']
     */
    $familyMetaKeys = apply_filters('tsjippy-family-meta-keys', ['family_name', 'family_picture']);

    return array_merge(
        $familyMetaKeys,
        ['children', 'parents', 'siblings', 'partner', 'weddingdate']
    );
}

/**
 * Checks if a given meta key should be processed as a family meta key
 *
 * @param   string  $metaKey    The key to check
 *
 * @return  bool                true if it is a family meta key, false otherwise
 */
function isFamilyMetaKey($metaKey, &$familyMetaKeys)
{
    $metaKey    = str_replace('tsjippy_', '', $metaKey);

    // Only run for certain keys
    if (!in_array($metaKey, getFamilyMetaKeys($familyMetaKeys))) {
        return false;
    }

    return true;
}

/**
 * Retrieves values from the family table instead of the user meta table
 */
add_filter("get_user_metadata", __NAMESPACE__ . '\getFamilyMeta', 10, 3);
function getFamilyMeta($value, $userId, $metaKey)
{
    $metaKey    = str_replace('tsjippy_', '', $metaKey);

    // Only run for certain keys, familyMetaKeys is filld by reference
    if (!isFamilyMetaKey($metaKey, $familyMetaKeys)) {
        return $value;
    }

    $family    = new TSJIPPY\FAMILY\Family();

    // check if this user has family
    if (!$family->hasFamily($userId)) {
        return $value;
    }

    // Get the meta keys for the family
    if (in_array($metaKey, (array) $familyMetaKeys)) {
        return $family->getFamilyMeta($userId, $metaKey);
    }

    if ($metaKey == 'children') {
        return $family->getChildren($userId);
    } elseif ($metaKey == 'parents') {
        return $family->getParents($userId);
    } elseif ($metaKey == 'siblings') {
        return $family->getSiblings($userId);
    } elseif ($metaKey == 'partner') {
        return $family->getPartner($userId);
    } elseif ($metaKey == 'weddingdate') {
        return $family->getWeddingDate($userId);
    }

    return $value;
}

/**
 * Stores values in the family table instead of in the user meta table
 */
add_filter("add_user_metadata", __NAMESPACE__ . '\addFamilyMeta', 10, 4);
add_filter("update_user_metadata", __NAMESPACE__ . '\addFamilyMeta', 10, 4);
function addFamilyMeta($value, $userId, $metaKey, $metaValue)
{
    $metaKey    = str_replace('tsjippy_', '', $metaKey);

    // Only run for certain keys, familyMetaKeys is filld by reference
    if (!isFamilyMetaKey($metaKey, $familyMetaKeys)) {
        return $value;
    }

    $family    = new TSJIPPY\FAMILY\Family();

    // check if this user has family
    if (!$family->hasFamily($userId)) {
        return $value;
    }

    if (in_array($metaKey, ['children', 'parents', 'siblings', 'partner'])) {
        switch ($metaKey) {
            case 'children':
                $metaKey    = 'child';
                $oldValue   = $family->getChildren($userId);
                break;
            case 'parents':
                $metaKey    = 'parent';
                $oldValue   = $family->getParents($userId);
                break;
            case 'siblings':
                $metaKey    = 'sibling';
                $oldValue   = $family->getSiblings($userId);
                break;
            default:
                $oldValue   = $family->getPartner($userId);
        }

        if (is_array($metaValue)) {
            // Only add the needed ones
            $removed    = array_diff($oldValue, $metaValue);
            $added      = array_diff($metaValue, $oldValue);

            // Remove old relations
            foreach ($removed as $value) {
                $family->removeRelationShip($userId, $value);
            }

            // Add new relations
            foreach ($added as $value) {
                $family->storeRelationship($userId, $value, $metaKey);
            }
        } else {
            $family->storeRelationship($userId, $metaValue, $metaKey);
        }

        return true;
    }

    if ($metaKey == 'weddingdate') {
        $partner    = $family->getPartner($userId);
        if (empty($partner)) {
            return null;
        }

        $family->storeRelationship($userId, $partner, 'partner', $metaValue);
        return true;
    }

    elseif (in_array($metaKey, (array) $familyMetaKeys)) {
        return $family->updateFamilyMeta($userId, $metaKey, $metaValue);
    }

    return $value;
}

add_filter("delete_user_metadata", function ($value, $userId, $metaKey, $metaValue, $deleteAll) {
    $metaKey    = str_replace('tsjippy_', '', $metaKey);
    
    // Only run for certain keys
    if (!isFamilyMetaKey($metaKey, $familyMetaKeys)) {
        return $value;
    }

    $family    = new TSJIPPY\FAMILY\Family();

    if (in_array($metaKey, (array)$familyMetaKeys)) {
        return $family->removeFamilyMeta($userId, $metaKey);
    }

    // Empty value, remove all
    if (empty($metaValue)) {
        $oldValues  = [];
        
        switch ($metaKey) {
            case 'children':
                $oldValues   = $family->getChildren($userId);
                break;
            case 'parents':
                $oldValues   = $family->getParents($userId);
                break;
            case 'siblings':
                $oldValues   = $family->getSiblings($userId);
                break;
        }

        foreach ($oldValues as $oldValue) {
            $family->removeRelationShip($userId, $oldValue);
        }
    } else {
        $family->removeRelationShip($userId, $metaValue);
    }

    return true;
}, 10, 5);

// Make sure the forms plugin knows it as well
add_filter('tsjippy-forms-user-meta-keys', function ($userMetaKeys) {
    return array_merge($userMetaKeys, getFamilyMetaKeys($familyMetaKeys));
});
