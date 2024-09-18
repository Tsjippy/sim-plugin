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
	$pageId		= getUserPageId($userId);
	$userdata   = get_userdata($userId);

    //return false when $userId is not valid
    if(!$userdata){
		return false;
	}

	//Check if this page exists and is published
	if(get_post_status ($pageId) != 'publish' ){
		$pageId = null;
	}

	$family = SIM\familyFlatArray($userId);
	$title 	= SIM\getFamilyName($userdata, true);

	//Only create a page if the page does not exist
	if ($pageId == null){
		// Create post object
		$userPage = array(
		  'post_title'    => $title,
		  'post_content'  => '',
		  'post_status'   => 'publish',
		  'post_type'	  => 'page',
		  'post_parent'   => SIM\getModuleOption(MODULE_SLUG, 'allcontacts_pages')[0],
		);
		
		// Insert the post into the database
		$pageId = wp_insert_post( $userPage );

		//Save user id as meta
		update_post_meta($pageId, 'user_id', $userId);

		// make static
		update_post_meta($pageId, 'static_content', true);

		SIM\printArray("Created user page with id $pageId");
	}else{
        updateUserPageTitle($userId, $title, $pageId);
	}

	if(!empty($family)){
		//Check if family has other pages who should be deleted
		foreach($family as $familyMember){
			//get the current page
			$memberPageId = get_user_meta($familyMember, "user_page_id", true);

			//Check if this page exists and is already trashed
			if(get_post_status ($memberPageId) == 'trash' ){
				$memberPageId = null;
			}

			//If there a page exists for this family member and its not the same page
			if(is_numeric($memberPageId) && $memberPageId != $pageId){
				//Remove the current user page
				wp_delete_post($memberPageId, true);

				SIM\printArray("Removed user page with id $memberPageId because we only need one per family");
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
        if(!$user){
			return false;
		}
    }
	
    $url    = getUserPageUrl($user->ID);
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
function updateUserPageTitle($userId, $title, $pageId=null){

	if(!is_numeric($pageId)){
		$pageId    = getUserPageId($userId);
	}

    if(!is_numeric($pageId)){
		return createUserPage($userId);
	}

	$page = get_post($pageId);

	//update page title if needed
	if($page->post_title != $title){
		wp_update_post(
			array (
				'ID'         	=> $pageId,
				'post_title' 	=> $title,
				'post_author'	=> $userId
			)
		);

		return true;
	}

	return false;
}

/**
 * Display name and job of user
 *
 * @param	int		$userId		The WP_User id
 *
 * @return	string				The html
 */
function userDescription($userId){
	$html 	= "";

	$user	= get_userdata($userId);

	do_action('sim_user_description', $user);

	//get the family details
	$family	= SIM\getUserFamily($userId);
	SIM\cleanUpNestedArray($family);

	$privacyPreference = get_user_meta( $userId, 'privacy_preference', true );
	if(!is_array($privacyPreference)){
		$privacyPreference = [];
	}

	$sendingOffice 	= get_user_meta( $userId, 'sending_office', true );
	$officeHtml		= '';
	if(!empty($sendingOffice)){
		$officeHtml = "<p>Sending office: $sendingOffice</p>";
	}

	$arrivalDate 	= get_user_meta( $userId, 'arrival_date', true );
	$arrivalHtml	= '';
	$arrived		= true;
	if(!empty($arrivalDate) && !isset($privacyPreference['hide_anniversary'])){
		$arrivalEpoch	= strtotime($arrivalDate);
		$arrivalDate	= date('F Y', $arrivalEpoch);
		if($arrivalEpoch < time()){
			$arrivalHtml 	= "<p>In the country since $arrivalDate</p>";
		}else{
			$arrivalHtml 	= "<p>Expected arrival date: $arrivalDate</p>";
			$arrived		= false;
		}
	}

	//Find location or address
	$location	= get_user_meta( $userId, 'location', true );
	$address	= "No address provided.";
	if(get_current_user_id() == $userId){
		$url	= SIM\ADMIN\getDefaultPageLink('usermanagement', 'account_page');
		$address	.= "Please update on the <a href='$url/?main_tab=generic_info#ministries'>Generic Info page</a>";
	}
	if(is_array($location)){
		if (empty($location["location"])){
			if (!empty($location["address"])){
				$address = $location["address"].' State';
			}
		}else{
			$address = $location["location"].' State';
		}
	}

	//Build the html
	//user has a family
	$siblings	= [];
	if(isset($family["siblings"])){
		$siblings	= $family["siblings"];
		unset($family["siblings"]);
	}
	unset($family["name"]);
	if (!empty($family)){
		$html .= "<h1>$user->last_name family</h1>";

		$url 	= wp_get_attachment_url($family['picture'][0]);
		if($url){
			$html .= "<a href='$url'><img src='$url' loading='lazy' width=200 height=200></a>";
		}

		if($arrived){
			$html .= "<p>";
				$html	.= " Lives in: ";
				$html .= $address;
			$html .= "</p>";
		}

		$html .= $officeHtml;

		$html .= $arrivalHtml;

		//Partner data
		if (isset($family['partner']) && is_numeric($family['partner'])){
			$html .= showUserInfo($family['partner'], $arrived);
		}

		//User data
		$html .= showUserInfo($userId, $arrived);

		//Children
		if (!empty($family["children"])){
			$html .= "<p>";
			$html .= " They have the following children:<br>";
			foreach($family["children"] as $child){
				$childdata	= get_userdata($child);
				$age		= SIM\getAge($child, true);
				if($age !== false){
					$age = "($age)";
				}

				$html .= SIM\displayProfilePicture($childdata->ID);
			$html .= "<span class='person_work'> {$childdata->first_name} $age</span><br>";
			}
			$html .= "</p>";
		}

		if (!empty($siblings)){
			$html .= "<p>";
				$html .= " They are related to:<br>";
				foreach($siblings as $sibling){
					$siblingsData	= get_userdata($sibling);

					$html .= SIM\displayProfilePicture($siblingsData->ID);
				$html .= "<span class='person_work'> {$siblingsData->first_name}</span><br>";
				}
			$html .= "</p>";
		}

		$html .= showContent($userId);

		if (isset($family['partner']) && is_numeric($family['partner'])){
			$html .= showContent($family['partner']);
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
			if(!isset($privacyPreference['hide_location']) && $arrived){
				$html .= "<p>Lives in: $address</p>";
			}
			if(!isset($privacyPreference['hide_ministry']) && $arrived){
				$html .= "<p>Works with: <br>".addMinistryLinks($userId)."</p>";
			}

			$html .= $officeHtml;

			$html .= $arrivalHtml;

			$html .= showPhonenumbers($userId);

			if (!empty($siblings)){
				$gender 	= get_user_meta( $userId, 'gender', true );
				if(is_array($gender)){
					if(isset($gender[0])){
						$gender	= $gender[0];
					}else{
						$gender	= '';
					}
				}
				if ($gender == "" || strtolower($gender) == "female" ){
					$gender	= 'She';
				}else{
					$gender	= 'He';
				}

				$html .= "<p>";
					$html .= " $gender is related to:<br>";
					foreach($siblings as $sibling){
						$siblingsData	= get_userdata($sibling);
	
						$html	.= SIM\displayProfilePicture($siblingsData->ID);
						$link	= getUserPageLink($siblingsData->ID);
						$html	.= "<span class='person_work'> $link</span><br>";
					}
				$html .= "</p>";
			}
		}else{
			$html .= "<p>No user found with id $userId.</p>";
		}

		$html .= showContent($userId);
	}

	return $html;
}

/**
 * Shows the user name and details
 * @param	int		$userId		The WP_User id
 *
 * @return	string				The html
 */
function showUserInfo($userId, $arrived){
	$html				= "";
	$userdata			= get_userdata($userId);
	$privacyPreference 	= (array)get_user_meta( $userId, 'privacy_preference', true );

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

			if(!isset($privacyPreference['hide_ministry']) & $arrived){
				$html .= "<span class='person_work' $style> $displayname works with: </span><br>";
				$html .= addMinistryLinks($userId);
			}else{
				$html .= "<span $style> $displayname</span><br>";
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
		$signalNumber	= get_user_meta( $userId, 'signal_number', true );
		$email			= get_userdata($userId)->user_email;

		$icon   = '<svg width="20px" height="20px" style="margin-left: 10px;">';
			$icon   .= '<g transform="matrix(0.15625 0 0 0.15625 0 0)">';
				$icon   .= '<title>Connected to Signal Messenger</title>';
				$icon   .= '<path d="M48.64,1.87L50.079998 7.69C 44.415306 9.083115 38.994007 11.325134 34 14.34L34 14.34L30.92 9.2C 36.423363 5.877327 42.39759 3.4060478 48.64 1.87zM79.36 1.87L77.92 7.69C 83.58467 9.083107 89.00602 11.325146 94 14.34L94 14.34L97.1 9.200001C 91.59042 5.875473 85.60933 3.4041443 79.36 1.87zM9.2 30.92C 5.877327 36.423363 3.4060478 42.39759 1.87 48.64L1.87 48.64L7.69 50.079998C 9.083115 44.415306 11.325134 38.994007 14.34 34zM6 64C 5.998066 61.09113 6.215351 58.18621 6.65 55.31L6.65 55.31L0.72000027 54.41C -0.23995209 60.7673 -0.23995209 67.232704 0.72000027 73.59L0.72000027 73.59L6.65 72.689995C 6.2153587 69.81381 5.99807 66.908844 6 64zM97.08 118.8L94 113.66C 89.01223 116.673 83.59773 118.91499 77.94 120.310005L77.94 120.310005L79.380005 126.130005C 85.61551 124.59205 91.58283 122.120834 97.08 118.8zM122 64C 122.00194 66.90884 121.784645 69.81382 121.35 72.69L121.35 72.69L127.28 73.590004C 128.23996 67.232704 128.23996 60.767296 127.28 54.410004L127.28 54.410004L121.35 55.310005C 121.784645 58.186188 122.00194 61.091164 122 64zM126.13 79.36L120.31 77.92C 118.9169 83.58469 116.674866 89.006004 113.66 94L113.66 94L118.8 97.1C 122.12453 91.59043 124.595856 85.60934 126.13 79.36zM72.69 121.36C 66.92908 122.22673 61.070923 122.22673 55.310005 121.36L55.310005 121.36L54.410004 127.29C 60.767296 128.24995 67.232704 128.24995 73.590004 127.29zM110.69 98.41C 107.2307 103.09508 103.08789 107.23451 98.4 110.69L98.4 110.69L101.96 115.520004C 107.131454 111.71768 111.70241 107.1602 115.52 102zM98.4 17.31C 103.08865 20.76854 107.23146 24.91135 110.69 29.6L110.69 29.6L115.52 26C 111.7146 20.84277 107.15723 16.2854 102 12.48zM17.31 29.6C 20.76854 24.91135 24.91135 20.76854 29.6 17.31L29.6 17.31L26 12.48C 20.842766 16.285397 16.285397 20.842766 12.48 26zM118.8 30.92L113.66 34C 116.673 38.98777 118.91499 44.402264 120.310005 50.059998L120.310005 50.059998L126.130005 48.62C 124.59204 42.38449 122.120834 36.417175 118.8 30.92zM55.31 6.65C 61.07092 5.783268 66.92908 5.783268 72.69 6.65L72.69 6.65L73.590004 0.72000027C 67.232704 -0.23995209 60.767296 -0.23995209 54.410004 0.72000027zM20.39 117.11L8 120L10.89 107.61L5.05 106.24L2.16 118.63C 1.6867399 120.65097 2.2916193 122.77299 3.7593164 124.240685C 5.227014 125.70838 7.3490367 126.31326 9.37 125.84L9.37 125.84L21.75 123zM6.3 100.89L12.14 102.25L14.14 93.66C 11.2248535 88.76049 9.051266 83.45627 7.69 77.92L7.69 77.92L1.87 79.36C 3.1747017 84.66252 5.1577263 89.77468 7.77 94.57zM34.3 113.89L25.71 115.89L27.07 121.729996L33.39 120.259995C 38.185314 122.87227 43.297478 124.855286 48.6 126.159996L48.6 126.159996L50.039997 120.34C 44.515343 118.962456 39.224777 116.77547 34.34 113.85zM64 12C 45.07826 12.009804 27.655266 22.297073 18.507568 38.860645C 9.359871 55.42422 9.93145 75.64949 20 91.67L20 91.67L15 113L36.33 108C 55.03896 119.78357 79.1536 118.447586 96.446396 104.669464C 113.7392 90.89134 120.42809 67.684456 113.12143 46.816055C 105.81476 25.947659 86.110565 11.981804 64 12z" stroke="none" fill="#3A76F0" fill-rule="nonzero" />';
			$icon   .= '</g>';
		$icon   .= '</svg>';


		$html	.= "<p style='margin-bottom:0px'>";
		$html	.= "<span style='font-size: 15px;'>Contact details below are only for you to use.<br>Do not share with other people.</span><br><br>";
		$html	.= "E-mail: <a href='mailto:$email'>$email</a><br>";
		if(empty($phonenumbers)){
			$html .= "Phone number: No phonenumber given<br><br>";
		}elseif(count($phonenumbers) == 1){
			if($phonenumbers[0] == $signalNumber){
				$html .= "Phone number: <a href='https://signal.me/#p/{$phonenumbers[0]}'>{$phonenumbers[0]}$icon</a></p>";
			}else{
				$html .= "Phone number: {$phonenumbers[0]}</p>";
			}
		}elseif(count($phonenumbers) > 1){
			$html .= "Phone numbers</p><ul style='list-style:square;margin-left: 25px;'>";
			foreach($phonenumbers as $key=>$phonenumber){
				if($phonenumber == $signalNumber){
					$html .= "<li><a href='https://signal.me/#p/{$phonenumber}'>{$phonenumber}$icon</a></li>";
				}else{
					$html .= "<li>$phonenumber</li>";
				}
			}
			$html .= "</ul>";
		}
		$html .= addVcardDownload($userId).'<br><br>';
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
	if (is_numeric(get_user_meta($userId, 'profile_picture', true)) && empty($privacyPreference['hide_profile_picture'])){
		$pictureUrl				= SIM\USERMANAGEMENT\getProfilePictureUrl($userId, "large");
		if($pictureUrl){
			$pictureUrl 			= str_replace(wp_upload_dir()['url'], wp_upload_dir()['basedir'], $pictureUrl);
			$photo               	= file_get_contents($pictureUrl);
			$b64vcard               = base64_encode($photo);
			$b64mline               = chunk_split($b64vcard,74,"\n");
			$b64final               = preg_replace("/(.+)/", " $1", $b64mline);
			$vcard 					.= "PHOTO;ENCODING=b;TYPE=JPEG:";
			$vcard 					.= $b64final . "\r\n";
		}
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
function addMinistryLinks($userId){
	$userMinistries = (array)get_user_meta( $userId, "jobs", true);

	$html = "";
	foreach($userMinistries as $key=>$userMinistry){
		if(!empty($userMinistry)){
			$page	= get_post($key);
			if (!empty($page)){
				$pageUrl = get_post_permalink($page->ID);
				$pageUrl = "<a class='ministry_link' href='$pageUrl'>$page->post_title</a>";
			}elseif($key == -1){
				$pageUrl = 'Also';
			}else{
				continue;
			}
			$html .= "$pageUrl as $userMinistry<br>";
		}
	}

	if(empty($html)){
		$html	= "Ministry location(s) missing.";
		if(get_current_user_id() == $userId){
			$url	= SIM\ADMIN\getDefaultPageLink('usermanagement', 'account_page');
			$html	.= "Please update on the <a href='$url/?main_tab=generic_info#ministries'>Generic Info page</a>";
		}
	}

	return $html;
}

// Add description if the current page is attached to a user id
add_filter( 'the_content', function ( $content ) {
	if (is_user_logged_in()){
		$postId 	= get_the_ID();

		//user page
		$userId = get_post_meta($postId, 'user_id', true);
		if(is_numeric($userId)){
			$content .= userDescription($userId);
		}
	}

	return $content;
});

/**
 * Gets the page id describing an user
 * @param	int 		$userId		WP_user id
 *
 * @return	int|WP_Error			The page id
*/
function getUserPageId($userId){
    return get_user_meta($userId, "user_page_id", true);
}

/**
 * Get the users description page
 * @param	int 		$userId		WP_user id
 *
 * @return	string					user page url
*/
function getUserPageUrl($userId){
	//Get the user page of this user
	$userPageId = getUserPageId($userId);

	if(!is_numeric($userPageId) || get_post_status($userPageId ) != 'publish'){
        $userPageId = createUserPage($userId);

        if(!$userPageId){
			return false;
		}
    }

   	return get_permalink($userPageId);
}

/**
 * Show a gallery of the users published content
 *
 * @param	int			$userId		WP_user id
 */
function showContent($userId){
	wp_enqueue_style( 'sim_show_user_content_style', plugins_url('css/usercontent.min.css', __DIR__), array(), MODULE_VERSION);

	$posts = get_posts(
		array(
			'post_type'		=> 'post',
			'post_status'	=> 'publish',
			'author'		=> $userId,
			'orderby'		=> 'post_date',
			'order'			=> 'ASC',
			'numberposts'	=> -1,
		)
	);

	$allowedHtml = array(
        'br'     => array(),
        'em'     => array(),
        'strong' => array(),
        'i'      => array(),
        'span'   => array(
			"class" => array(),
		),
		'div'   => array(
			"class" => array(),
		),
        'class' => array(),
		'a'		=> array(
			"class" => array(),
		)
    );

	ob_start();

	$name	= get_userdata($userId)->first_name;

	?>
	<div class='content-wrapper'>
		<h4>Content published by <?php echo $name;?></h4>
		<div class='author-content-wrapper'>
			<?php
			if(empty($posts)){
				echo "No content found";
			}
			foreach($posts as $post){
				$url	= get_permalink($post);
				?>
				<article id="post-<?php echo $post->ID; ?>" class='author-content'>
					<div class='picture'>
						<a href='<?php echo $url;?>'>
							<?php
							echo get_the_post_thumbnail( $post, [250,200] );
							?>
						</a>
					</div>

					<div class='title'>
						<a href='<?php echo $url;?>'>
							<h4><?php echo wp_kses( force_balance_tags($post->post_title), $allowedHtml );?></h4>
						</a>
					</div>

					<div class='content'>
						<?php echo wp_kses( force_balance_tags(get_the_excerpt($post)), $allowedHtml );?>
					</div>
				</article>
				
				<?php
			}
			?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}