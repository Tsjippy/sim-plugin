<?php
namespace SIM\FAMILY;
use SIM;

// Adds family values to the meta values of a form
add_filter('sim_forms_load_userdata', __NAMESPACE__.'\addFamilyData', 10, 2);
function addFamilyData($usermeta, $userId){
	$family	= new SIM\FAMILY\Family();

    // check if this user has family
    if(!$family->hasFamily($userId)){
        return $usermeta;
    }

    $familyMeta = [];

    $familyMeta['children']	    = $family->getChildren($userId);
    $familyMeta['parents']	    = $family->getParents($userId);
    $familyMeta['siblings']	    = $family->getSiblings($userId);
    $familyMeta['partner']	    = $family->getPartner($userId);
    $familyMeta['weddingdate']	= $family->getWeddingDate($userId);
    
    foreach($family->getFamilyMeta($userId) as $meta){
        $familyMeta[$meta->key] = $meta->value;
    }
	
	return array_merge($usermeta, $familyMeta);
}

/**
 * Checks if a given meta key should be processed as a family meta key
 * 
 * @param   string  $metaKey    The key to check
 */
function isFamilyMetaKey($metaKey, &$familyMetaKeys){
     $familyMetaKeys = apply_filters('sim-family-meta-keys', ['family_name', 'family_picture']);

    // Only run for certain keys
    if(
        !str_contains($metaKey, 'anniversary_event_id') &&                      // anniversaries are usually for the whole family
        !in_array(
            $metaKey, 
            array_merge(
                $familyMetaKeys, 
                ['children', 'parents', 'siblings', 'partner', 'weddingdate']
            )
        )
    ){
        return false;
    }

    return true;
}


/**
 * Retrieves values from the family table instead of the user meta table
 */ 
add_filter( "get_user_metadata", __NAMESPACE__.'\getFamilyMeta', 10, 3);
function getFamilyMeta($value, $userId, $metaKey ){
    // Only run for certain keys
    if(!isFamilyMetaKey($metaKey, $familyMetaKeys)){
        return $value;
    }

    $family	= new SIM\FAMILY\Family();

    // check if this user has family
    if(!$family->hasFamily($userId)){
        return $value;
    }

    if($metaKey == 'child'){
        return $family->getChildren($userId);
    }elseif($metaKey == 'parent'){
        return $family->getParents($userId);
    }elseif($metaKey == 'sibling'){
        return $family->getSiblings($userId);
    }elseif($metaKey == 'partner'){
        return $family->getPartner($userId);
    }elseif($metaKey == 'weddingdate'){
        return $family->getWeddingDate($userId);
    }
    
    // Get the meta keys for the family
    if(in_array($metaKey, $familyMetaKeys)){
        return $family->getFamilyMeta($userId, $metaKey);
    }

    return $value;
}

/**
 * Stores values in the family table instead of in the user meta table
 */ 
add_filter( "add_user_metadata", __NAMESPACE__.'\addFamilyMeta', 10, 4);
add_filter( "update_user_metadata", __NAMESPACE__.'\addFamilyMeta', 10, 4);
function addFamilyMeta($value, $userId, $metaKey, $metaValue){
    // Only run for certain keys
    if(!isFamilyMetaKey($metaKey, $familyMetaKeys)){
        return $value;
    }

    $family	= new SIM\FAMILY\Family();

    // check if this user has family
    if(!$family->hasFamily($userId)){
        return $value;
    }

    if(in_array($metaKey, ['children', 'parents', 'siblings', 'partner'])){
        switch($metaKey){
            case 'children':
                $metaKey    = 'child';
                break;
            case 'parents':
                $metaKey    = 'parent';
                break;
            case 'siblings':
                $metaKey    = 'sibling';
                break;
        }

        if(is_array($metaValue)){
            foreach($metaValue as $value){
                $family->storeRelationship($userId, $value, $metaKey);
            }
        }else{
            $family->storeRelationship($userId, $metaValue, $metaKey);
        }

        return true;
    }

    if($metaKey == 'weddingdate'){
        $partner    = $family->getPartner($userId);
        if(empty($partner)){
            return null;
        }

        $family->storeRelationship($userId, $partner, 'partner', $metaValue);
        return true;
    }
    
    if(in_array($metaKey, $familyMetaKeys) || str_contains($metaKey, 'anniversary_event_id')){
        return $family->getFamilyMeta($userId, $metaKey);
    }

    return $value;
}

add_filter( "delete_user_metadata", function($value, $userId, $metaKey, $metaValue, $deleteAll ){
    // Only run for certain keys
    if(!isFamilyMetaKey($metaKey, $familyMetaKeys)){
        return $value;
    }

    $family	= new SIM\FAMILY\Family();

    $family->removeRelationShip($userId, $metaValue);

    $family->removeFamilyMeta($userId, $metaKey);

    return true;

}, 10, 5);