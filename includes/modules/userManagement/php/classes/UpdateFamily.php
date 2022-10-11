<?php
namespace SIM\USERMANAGEMENT;
use SIM;

class UpdateFamily{
    public function __construct($userId, $family, $oldFamily){
        $this->userId           = $userId;
        $this->family           = $family;
        $this->oldFamily        = $oldFamily;
        $this->partnerFamily    = (array)get_user_meta( $this->family['partner'], 'family', true );
        $this->userGender       = get_user_meta( $userId, 'gender', true );

        if(empty($this->userGender)){
            $this->userGender = 'male';
        }

        if (isset($this->oldFamily['partner'])){
            $this->oldPartner = $this->oldFamily['partner'];
        }else{
            $this->oldPartner = null;
        }

        if ($this->family['weddingdate'] != $this->oldFamily['weddingdate']) {
            $this->updateWeddingDate();
        }

        //Update the partner if needed
        if (isset($this->family['partner'])){
            $this->updatePartner();
        }

        //Update the previous partner if needed
        if (
            $this->oldPartner != null                           &&  // We had a spouse before
            (
                !isset($this->family['partner'])                ||  // There is no spouse anymore
                $this->family['partner'] != $this->oldPartner       // The spouse has changed
            )
        ){
            $this->updateOldPartner();
        }

        //Remove the parents from the old children if needed
        if(
            isset($this->oldFamily["children"])                             &&  // There were children previously
            (
                !isset($this->family["children"])                           ||  // There are no children anymore
                $this->oldFamily["children"] != $this->family["children"]       // Or the children are not the same
            )
        ){
            $this->removeChildren();
        }

        //If there are currently kids
        if (isset($this->family["children"])){
            $this->addChildren();
        //No children anymore, update the children and partner
        }elseif(isset($this->oldFamily["children"]) && isset($this->family['partner'])){
            //Remove children - for the partner as well
            unset($this->partnerFamily["children"]);
        }

        // Update family picture
        if (
            !empty($this->family['picture'])                            &&  // There is a family picture
            (
                !isset($this->oldFamily['picture'])                     ||  // There was no familhy picture
                $this->family['picture'] != $this->oldFamily['picture']     // The family picture has changed
            )
        ){
            $this->changeFamilyPicture();
        }

        $this->save();
    }

    public function updateWeddingDate(){
        if(!class_exists('SIM\EVENTS\CreateEvents')){
            return;
        }

        $events		= new SIM\EVENTS\CreateEvents();

        //save wedding date to partner as well
        if (isset($this->family['partner'])){
            //Get the partners family
            $this->partnerFamily['weddingdate']	= $this->family['weddingdate'];
            update_user_meta($this->family['partner'], 'family', $this->partnerFamily);
        }

        $events->createCelebrationEvent('Wedding anniversary', $this->userId, 'family[weddingdate]', $this->family['weddingdate']);
    }

    public function updatePartner(){
        if($this->family['partner'] != $this->oldPartner){
            //Store curent user as partner of the partner
            $this->partnerFamily['partner'] = $this->userId;

            //If I am updating this user to have a partner and that partner has children adds them to the current user as well
            if (isset($this->partnerFamily['children']) && !isset($this->family['children']) && !isset($this->oldFamily['children'])){
                //Add the children of the partner to this user as well.
                $this->family['children'] = $this->partnerFamily['children'];
            }
        }
    }

    public function updateOldPartner(){
        $oldPartnerFamily = get_user_meta( $this->oldPartner, 'family', true );
        if (is_array($oldPartnerFamily)){
            unset($oldPartnerFamily["partner"]);
            $this->savefamilyIndb($this->oldPartner, $oldPartnerFamily);
        }
    }

    public function removeChildren(){
        if(!is_array($this->family["children"])){
            $this->family["children"] = [];
        }

        //get the removed children
        $childDiff	= array_diff($this->oldFamily["children"], $this->family["children"]);

        //Loop over the removed children
        foreach($childDiff as $child){
            //Get the childs family array
            $childFamily = get_user_meta( $child, 'family', true );

            //Remove the parents for this child
            if (is_array($childFamily)){
                unset($childFamily["father"]);
                unset($childFamily["mother"]);

                //Save in DB
                if(empty($childFamily)){
                    //delete the family entry if its empty
                    delete_user_meta( $child, 'family');
                }else{
                    update_user_meta( $child, 'family', $childFamily);
                }
            }
        }
    }

    public function addChildren(){
        //get the added children
        $childDiff	= array_diff((array)$this->family["children"], (array)$this->oldFamily["children"]);

        //Loop over the added children
        foreach($childDiff as $child){
            //Get the childs family array
            $childFamily = (array)get_user_meta( $child, 'family', true );

            //Store current user as parent of the child
            if($this->userGender == 'male'){
                $childFamily["father"]  = $this->userId;
                $otherParent            = 'mother';
            }else{
                $childFamily["mother"]  = $this->userId;
                $otherParent            = 'father';

            }

            if (isset($this->family['partner'])){
                //store current partner as parent of the child
                $childFamily[$otherParent] = $this->family['partner'];
            }

            //Save in DB
            update_user_meta( $child, 'family', $childFamily);
        }

        //Store child - for current users partner as well
        if (isset($this->family['partner'])){
            $this->partnerFamily["children"] = $this->family["children"];
        }
    }

    public function changeFamilyPicture(){
        // Hide profile picture by default from media galery
        $pictureId	=  $this->family['picture'][0];
        if(is_numeric($pictureId)){
            update_post_meta($pictureId, 'gallery_visibility', 'hide' );
        }

        do_action('sim_update_family_picture', $this->userId, $this->family['picture'][0]);

        if (isset($this->family['partner'])){
            $this->partnerFamily['picture']	= $this->family['picture'];
        }
    }

    public function save(){
        //Save the family array
        $this->savefamilyIndb($this->userId, $this->family);
        if (isset($this->family['partner'])){
            //Save the partners family array
            update_user_meta( $this->family['partner'], 'family', $this->partnerFamily);
        }

        //update user page if needed
        if(function_exists('SIM\USERPAGE\createUserPage')){
            SIM\USERPAGE\createUserPage($this->userId);
        }
    }

    //Save in db
    public function savefamilyIndb(){
        if(is_array($this->family)){
            SIM\cleanUpNestedArray($this->family);
        }

        if (empty($this->family)){
            //remove from db, there is no family anymore
            delete_user_meta($this->userId, "family");
        }else{
            //Store in db
            update_user_meta($this->userId, "family", $this->family);
        }

        do_action('sim_family_safe', $this->userId);
    }
}
