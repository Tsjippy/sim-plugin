<?php

namespace TSJIPPY\FAMILY;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

/**
 * Gets all the family meta keys
 * 
 * @return array family array keys 
 */
function getFamilyMetaKeys()
{
    $metaKeys = wp_cache_get( 'meta-keys', 'tsjippy_family' );

    if ( false !== $metaKeys ) {
        return $metaKeys;
    }

    /**
     * Filters the available family meta keys
     * Expects the metakeys to be the index of the array to allow isset
     * 
     * @param array $metaKeys  The available keys array. Default ['family_name' => 1, 'family_picture' => 1]
     */
    $metaKeys = apply_filters('tsjippy-family-meta-keys', ['family_name' => 1, 'family_picture' => 1, 'children' => 1, 'parents' => 1, 'siblings' => 1, 'partner' => 1, 'weddingdate' => 1]);

    wp_cache_set( 'meta-keys', $metaKeys, 'tsjippy_family' );

    return $metaKeys;
}

/**
 * Checks if a given meta key should be processed as a family meta key
 *
 * @param   string  $metaKey        The key to check
 *
 * @return  bool                    True if it is a family meta key, false otherwise
 */
function isFamilyMetaKey($metaKey)
{
    $metaKey    = str_replace('tsjippy_', '', $metaKey);

    // Only run for certain keys
    if (!isset(getFamilyMetaKeys()[$metaKey])) {
        return false;
    }

    return true;
}

add_filter("get_user_metadata", __NAMESPACE__ . '\getFamilyMeta', 10, 3);
/**
 * Retrieves values from the family table instead of the user meta table
 * 
 * @param mixed  $value     The value to return, either a single metadata value or an array of values depending on the value of `$single`.
 * @param int    $userId    ID of the user metadata is for.
 * @param string $metaKey  Metadata key.
 */
function getFamilyMeta($value, $userId, $metaKey)
{
    $metaKey    = str_replace('tsjippy_', '', $metaKey);

    if (!empty($metaKey) && !isFamilyMetaKey($metaKey)) {
        return $value;
    }

    $family    = new TSJIPPY\FAMILY\Family();

    // check if this user has family
    if (!$family->hasFamily($userId)) {
        return $value;
    }

    $familyMetaKeys = getFamilyMetaKeys();

    // Get the meta keys for the family
    if (empty($metaKey) || isset($familyMetaKeys[$metaKey])) {
        $familyMetas = $family->getFamilyMeta($userId, $metaKey);

        // We are requesting all meta's but this filter cancels the rest so we add them to the result
        if(empty($metaKey)){
            // remove the filter to prevent a loop 
            remove_filter("get_user_metadata", __NAMESPACE__ . '\getFamilyMeta', 10);
            $familyMetas = array_merge($familyMetas, get_user_meta($userId));

            // Re-add the filter
            add_filter("get_user_metadata", __NAMESPACE__ . '\getFamilyMeta', 10, 3);
        }

        return $familyMetas;
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
 * Adds the relation ships keys to indicate they can have multiple values
 */
add_filter('tsjippy-forms-user-meta-multi-keys', function($multiKeys){
    $multiKeys['children'] = 1;
    $multiKeys['siblings'] = 1;
    $multiKeys['parents']  = 1;

    return $multiKeys;
});

/**
 * Stores values in the family table instead of in the user meta table
 */
add_filter("add_user_metadata", __NAMESPACE__ . '\addFamilyMeta', 10, 4);
add_filter("update_user_metadata", __NAMESPACE__ . '\addFamilyMeta', 10, 4);
function addFamilyMeta($value, $userId, $metaKey, $metaValue)
{
    $metaKey    = str_replace('tsjippy_', '', $metaKey);

    if (!isFamilyMetaKey($metaKey)) {
        return $value;
    }

    $family    = new TSJIPPY\FAMILY\Family();

    // check if this user has family
    if (!$family->hasFamily($userId)) {
        return $value;
    }

    if (isset(['children' => 1, 'parents' => 1, 'siblings' => 1, 'partner' => 1][$metaKey])) {
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

    $familyMetaKeys = getFamilyMetaKeys();

    if ($metaKey == 'weddingdate') {
        $partner    = $family->getPartner($userId);
        if (empty($partner)) {
            return null;
        }

        $family->storeRelationship($userId, $partner, 'partner', $metaValue);
        return true;
    }

    elseif (isset($familyMetaKeys[$metaKey])) {
        return $family->updateFamilyMeta($userId, $metaKey, $metaValue);
    }

    return $value;
}

add_filter("delete_user_metadata", function ($value, $userId, $metaKey, $metaValue, $deleteAll) {
    $metaKey    = str_replace('tsjippy_', '', $metaKey);
    
    // Only run for certain keys
    if (!isFamilyMetaKey($metaKey)) {
        return $value;
    }

    $family         = new TSJIPPY\FAMILY\Family();

    $familyMetaKeys = getFamilyMetaKeys();

    if (isset($familyMetaKeys[$metaKey])) {
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

