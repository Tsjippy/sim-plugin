<?php
namespace SIM\USERPAGE;
use SIM;

/**
 * Create an user (family) page
 * 
 * @param	int	$userId		The WP_User id
 * 
 * @return	int				WP_Post id
 */
function createUserPage($userId){	
	//get the current page
	$pageId    = SIM\getUserPageId($userId);
	$userdata   = get_userdata($userId);
	
    //return false when $userId is not valid
    if(!$userdata) return false;

	//Check if this page exists and is published
	if(get_post_status ($pageId) != 'publish' ) $pageId = null;
	
	$family = SIM\familyFlatArray($userId);
	if (count($family)>0){
		$title = $userdata->last_name." family";
	}else{
		$title = $userdata->last_name.', '.$userdata->first_name;
	}
	
	$update = false;
	
	//Only create a page if the page does not exist
	if ($pageId == null){
		$update = true;

		// Create post object
		$userPage = array(
		  'post_title'    => $title,
		  'post_content'  => '',
		  'post_status'   => 'publish',
		  'post_type'	  => 'page',
		  'post_parent'   => SIM\getModuleOption('user_pages', 'missionaries_page'),
		);
		 
		// Insert the post into the database
		$pageId = wp_insert_post( $userPage );
		
		//Save user id as meta
		update_post_meta($pageId, 'user_id', $userId);
		
		SIM\printArray("Created user page with id $pageId");
	}else{
        $update = updateUserPageTitle($userId, $title);
	}
	
	if($update == true and count($family)>0){
		//Check if family has other pages who should be deleted
		foreach($family as $familyMember){
			//get the current page
			$memberPageId = get_user_meta($familyMember,"user_page_id",true);
			
			//Check if this page exists and is already trashed
			if(get_post_status ($memberPageId) == 'trash' ) $memberPageId = null;
			
			//If there a page exists for this family member and its not the same page
			if($memberPageId != null and $memberPageId != $pageId){
				//Remove the current user page
				wp_delete_post($memberPageId);
				
				SIM\printArray("Removed user page with id $memberPageId");
			}
		}
	}
	
	//Add the post id to the user profile
	SIM\updateFamilyMeta($userId, "user_page_id", $pageId);
	
	//Return the id
	return $pageId;
}

/**
 * Get the link to a user page
 * 
 * @param	int|object	$user	WP_User or WP_user id
 * 
 * @return	string				Html link
 */
function getUserPageLink($user){
    if(is_numeric($user)){
        $user   = get_userdata($user);
        if(!$user) return false;
    }
    $url    = SIM\getUserPageUrl($user->ID);
    if($url){
        $html   = "<a href='$url'>$user->display_name</a>";
    }else{
        $html   = $user->display_name;
    }

    return $html;
}

/**
 * Update the page title of a user page
 * 
 * @param	int		$userId		The WP_User id
 * @param	string	$title		The new title
 * 
 * @return	bool				True on succces false on failure
 */
function updateUserPageTitle($userId, $title){
    $pageId    = SIM\getUserPageId($userId);

    if(is_numeric($pageId)){
        $page = get_post($pageId);

        //update page title if needed
		if($page->post_title != $title){
            wp_update_post(
                array (
                    'ID'         => $pageId,
                    'post_title' => $title
                )
            );

            return true;
        }else{
            return false;
        }
    }else{
        return createUserPage($userId);  
    } 
}

/**
 * Display name and job of user
 * 
 * @param	int		$userId		The WP_User id
 * 
 * @return	string				The html
 */
function userDescription($userId){
	$html = "";

	$user	= get_userdata($userId);

	do_action('sim_user_description', $user);
	
	//get the family details
	$family = get_user_meta( $userId, 'family', true );
	
	//Find compound or address
	$location = get_user_meta( $userId, 'location', true );
	if(is_array($location)){
		if (empty($location["compound"])){
			if (empty($location["address"])){
				$address = "No clue, since no address is given";
			}else{
				$address = $location["address"];
			}
		}else{
			$address = $location["compound"];
		}
	}else{
		$address = "No clue, since no address is given";
	}
	
	$privacyPreference = get_user_meta( $userId, 'privacy_preference', true );
	if(!is_array($privacyPreference)) $privacyPreference = [];

	$sendingOffice 	= get_user_meta( $userId, 'sending_office', true );
	$officeHtml		= '';
	if($sendingOffice != "")	$officeHtml = "<p>Sending office: $sendingOffice</p>";

	$arrivalDate 	= get_user_meta( $userId, 'arrival_date', true );
	$arrivalHtml	= '';
	if($arrivalDate != "" and !isset($privacyPreference['hide_anniversary'])){
		$arrivalEpoch	= strtotime($arrivalDate);
		$arrivalDate	= date('F Y', $arrivalEpoch);
		if($arrivalEpoch < time()){
			$arrivalHtml = "<p>In Nigeria since $arrivalDate</p>";
		}else{
			$arrivalHtml = "<p>Expected arrival in Nigeria: $arrivalDate</p>";
		}
	}
	//Build the html
	//user has a family
	if (!empty($family)){
		$html .= "<h1>$user->last_name family</h1>";

		$url 	= wp_get_attachment_url($family['picture'][0]);
		if($url){
			$html .= "<a href='$url'><img src=$url width=200 height=200></a>";
		}
		
		$html .= "<p>
					Lives in: ";
		$html .= $address.' State';
		$html .= "</p>";

		$html .= $officeHtml;

		$html .= $arrivalHtml;
		
		//Partner data
		if (isset($family['partner']) and is_numeric($family['partner'])){
			$html .= showUserInfo($family['partner']);
		}
		
		//User data
		$html .= showUserInfo($userId);
			
		//Children
		if (isset($family["children"])){
			$html .= "<p>";
			$html .= " They have the following children:<br>";
			foreach($family["children"] as $child){
				$childdata	= get_userdata($child);
				$age		= SIM\getAge($child);
				if($age !== false){
					$age = "($age)";
				}
				
				$html .= SIM\displayProfilePicture($childdata->ID);
			$html .= "<span class='person_work'> {$childdata->first_name} $age</span><br>";
			}
			$html .= "</p>";
		}
	//Single
	}else{
		$userdata = get_userdata($userId);
	
		if ($userdata != null){
			if(isset($privacyPreference['hide_name'])){ 
				$displayname = '';
			}else{
				$displayname = $userdata->data->display_name;
			}
			
			$html	.= "<h1>";
				if(!isset($privacyPreference['hide_profile_picture'])){
					$html .= SIM\displayProfilePicture($userId);
				}
				$html	.= "  $displayname";
			$html	.= "</h1>";
			$html	.= "<br>";
			if(!isset($privacyPreference['hide_location'])){ 
				$html .= "<p>Lives on: $address</p>";
			}
			if(!isset($privacyPreference['hide_ministry'])){ 
				$html .= "<p>Works with: <br>".addMinistryLinks($userId)."</p>";
			}
			
			$html .= $officeHtml;
				
			$html .= $arrivalHtml;
			
			$html .= showPhonenumbers($userId);
		}else{
			$html .= "<p>Nothing to show here due to privacy settings.</p>";
		}
	}
	
	return $html;
}

/**
 * Shows the user name and details
 * @param	int		$userId		The WP_User id
 * 
 * @return	string				The html
 */
function showUserInfo($userId){
	$html = ""; 
	
	$userdata = get_userdata($userId);
	$privacyPreference = (array)get_user_meta( $userId, 'privacy_preference', true );
	
	//If it is a valid user and privacy allows us to show it
	if ($userdata != null){
		if(isset($privacyPreference['hide_name'])){ 
			$displayname = '';
		}else{
			$displayname = $userdata->first_name;
		}
		
		$html .= "<p>";
		if(empty($privacyPreference['hide_profile_picture'])){
			$html .= SIM\displayProfilePicture($userId);
			$style = "";
		}else{
			$style = ' style="margin-left: 55px;"';
		}
		
		if(!isset($privacyPreference['hide_ministry'])){ 
			$html .= "<span class='person_work' $style> $displayname works with: </span><br>";
			$html .= addMinistryLinks($userId);
		}
		$html .= "</p>";
		$html .= showPhonenumbers($userId);
	}
	
	return $html;
}

/**
 * Show the phone numbers of an user
 * @param	int		$userId		The WP_User id
 * 
 * @return	string				The html
 */
function showPhonenumbers($userId){
	$privacyPreference = (array)get_user_meta( $userId, 'privacy_preference', true );
	
	$html = "";
	if(!isset($privacyPreference['hide_phone'])){
		$phonenumbers	= get_user_meta( $userId, 'phonenumbers', true );
		$email			= get_userdata($userId)->user_email;
	
		$html	.= "<p><span style='font-size: 15px;'>Contact details below are only for you to use.<br>Do not share with other people.</span><br><br>";
		$html	.= "E-mail: <a href='mailto:$email'>$email</a><br>";
		if(empty($phonenumbers)){
			$html .= "Phone number: No phonenumber given<br><br>";
		}elseif(count($phonenumbers) == 1){
			$html .= "Phone number: <a href='https://signal.me/#p/{$phonenumbers[0]}'>{$phonenumbers[0]}</a><br><br>";
		}elseif(count($phonenumbers) > 1){
			$html .= "Phone numbers:<br>";
			foreach($phonenumbers as $key=>$phonenumber){
				$html .= "Phone number ".($key+1)." is: <a href='https://signal.me/#p/{$phonenumber}'>{$phonenumber}</a><br><br>";
			}
		}
		$html .= addVcardDownload($userId).'</p>';
	}
	
	return $html;
}

/**
 * Create vcard
 * @param	int		$userId		The WP_User id
 * 
 * @return	string				The html
 */
function addVcardDownload($userId){
	global $post;
	//Make vcard
	if (isset($_GET['vcard'])){
		ob_end_clean();
		//ob_start();
		$userId = $_GET['vcard'];
		header('Content-Type: text/x-vcard');
		header('Content-Disposition: inline; filename= "'.get_userdata($userId)->data->display_name.'.vcf"');

		$vcard = buildVcard($userId);
		echo $vcard;
		die();

	//Return vcard hyperlink
	}else{
		$url = add_query_arg( ['vcard' => $userId], get_permalink( $post->ID ) );
		return '<a href="'.$url.'" class="button sim vcard">Add to your contacts</a>';
	}
}

/**
 * Get vcard contents for a person
 * @param	int		$userId		The WP_User id
 * 
 * @return	string				The html
 */
function buildVcard($userId){
	//Get the user partner
	$family = (array)get_user_meta( $userId, 'family', true );
	if (isset($family['partner'])){
		$partner = $family['partner'];
	}else{
		$partner = "";
	}
	
	$privacyPreference = (array)get_user_meta( $userId, 'privacy_preference', true );
	
	if(empty($privacyPreference['hide_location'])){ 
		//Get the user address
		$location = get_user_meta( $userId, 'location', true );
		if (!empty($location['address'])){
			$address 	= $location['address'];
			$lat 		= $location['latitude'];
			$lon 		= $location['longitude'];
		}
	}
	$gender 	= get_user_meta( $userId, 'gender', true );
	$userdata 	= get_userdata($userId);
		
	$vcard = "BEGIN:VCARD\r\n";
	$vcard .= "VERSION:4.0\r\n";
	if(empty($privacyPreference['hide_name'])){
		$vcard .= "FN:".$userdata->data->display_name."\r\n";
		$vcard .= "N:".$userdata->last_name.";".$userdata->first_name.";;;\r\n";
	}
	$vcard .= "ORG:".SITENAME."\r\n";
	$vcard .= "EMAIL;TYPE=INTERNET;TYPE=WORK:".$userdata->user_email."\r\n";
	
	if(empty($privacyPreference['hide_phone'])){
		$phonenumbers = get_user_meta( $userId, 'phonenumbers', true );
		if (is_array($phonenumbers)){
			foreach ($phonenumbers as $key=>$phonenumber){
				switch ($key) {
				case 0:
					$type = "cell";
					break;
				case 1:
					$type = "home";
					break;
				case 2:
					$type = "work";
					break;
				default:
					$type = "cell";
				}
				$vcard .= "TEL;TYPE=$type:$phonenumber\r\n";
			}
		}
	}
	if ($address){
		$vcard .= "ADR;TYPE=HOME:;;$address\r\n";
		$vcard .= "GEO:geo:".$lat.",".$lon."\r\n";
	}
	$vcard .= "BDAY:".str_replace("-","",get_user_meta( $userId, "birthday", true ))."\r\n";
	if ($partner != ""){
		$vcard .= "item1.X-ABRELATEDNAMES:".get_userdata($partner)->data->display_name."\r\n";
		$vcard .= 'item1.X-ABLabel:_$!<Spouse>!$_';
		$vcard .= "X-SPOUSE:".get_userdata($partner)->data->display_name."\r\n";
	}
	
	if ($gender != ""){
		if($gender == "female"){
			$vcard .= "GENDER:F\r\n";
		}else{
			$vcard .= "GENDER:M\r\n";
		}
	}
	
	//User has an profile picture add it
	if (is_numeric(get_user_meta($userId,'profile_picture',true)) and empty($privacyPreference['hide_profile_picture'])){
		$pictureUrl 			= str_replace(wp_upload_dir()['url'], wp_upload_dir()['path'], SIM\USERMANAGEMENT\getProfilePictureUrl($userId, "large"));
		$photo               	= file_get_contents($pictureUrl);
		$b64vcard               = base64_encode($photo);
		$b64mline               = chunk_split($b64vcard,74,"\n");
		$b64final               = preg_replace("/(.+)/", " $1", $b64mline);
		$vcard 					.= "PHOTO;ENCODING=b;TYPE=JPEG:";
		$vcard 					.= $b64final . "\r\n";
	}
	$vcard .= "END:VCARD\r\n";
	return $vcard;
}

/**
 * Build hyperlinks for ministries
 * 
 * @param	int		$userId		The WP_User id
 * 
 * @return	string				The html
 */
function addMinistryLinks($UserID){
	$userMinistries = (array)get_user_meta( $UserID, "user_ministries", true);

	$html = "";
	foreach($userMinistries as $key=>$userMinistry){
		if(!empty($userMinistry)){
			$ministry 		= str_replace("_"," ",$key);
			$ministryPage 	= get_page_by_title( $ministry);
			if (!empty($ministryPage)){
				$pageUrl = get_post_permalink($ministryPage->ID);
				$pageUrl = "<a class='ministry_link' href='$pageUrl'>$ministry</a>";
			}else{
				$pageUrl = $ministry;
			}
			$html .= "$pageUrl as $userMinistry<br>";
		}
	}

	if(empty($html)) $html = "No clue, since no one told me.";
	
	return $html;
}

// Add description if the current page is attached to a user id
add_filter( 'the_content', function ( $content ) {
	if (is_user_logged_in()){
		$post_id 	= get_the_ID();
		//user page
		$userId = get_post_meta($post_id,'user_id',true);
		if(is_numeric($userId)) $content .= userDescription($userId);
	}

	return $content;
});