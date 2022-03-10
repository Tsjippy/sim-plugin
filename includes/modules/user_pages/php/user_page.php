<?php
namespace SIM\USERPAGE;
use SIM;

function get_user_page_id($user_id){
    return get_user_meta($user_id,"missionary_page_id",true);
}

//Create an missionary (family) page
function create_user_page($user_id){	
	//get the current page
	$page_id    = get_user_page_id($user_id);
	$userdata   = get_userdata($user_id);
	
    //return false when $user_id is not valid
    if(!$userdata) return false;

	//Check if this page exists and is published
	if(get_post_status ($page_id) != 'publish' ) $page_id = null;
	
	$family = SIM\family_flat_array($user_id);
	if (count($family)>0){
		$title = $userdata->last_name." family";
	}else{
		$title = $userdata->last_name.', '.$userdata->first_name;
	}
	
	$update = false;
	
	//Only create a page if the page does not exist
	if ($page_id == null){
		$update = true;

		// Create post object
		$missionary_page = array(
		  'post_title'    => $title,
		  'post_content'  => '',
		  'post_status'   => 'publish',
		  'post_type'	  => 'page',
		  'post_parent'   => SIM\get_module_option('user_pages', 'missionaries_page'),
		);
		 
		// Insert the post into the database
		$page_id = wp_insert_post( $missionary_page );
		
		//Save missionary id as meta
		update_post_meta($page_id,'missionary_id',$user_id);
		
		SIM\print_array("Created missionary page with id $page_id");
	}else{
        $update = update_user_page_title($user_id, $title);
	}
	
	if($update == true and count($family)>0){
		//Check if family has other pages who should be deleted
		foreach($family as $family_member){
			//get the current page
			$member_page_id = get_user_meta($family_member,"missionary_page_id",true);
			
			//Check if this page exists and is already trashed
			if(get_post_status ($member_page_id) == 'trash' ) $member_page_id = null;
			
			//If there a page exists for this family member and its not the same page
			if($member_page_id != null and $member_page_id != $page_id){
				//Remove the current user page
				wp_delete_post($member_page_id);
				
				SIM\print_array("Removed missionary page with id $member_page_id");
			}
		}
	}
	
	//Add the post id to the user profile
	SIM\update_family_meta($user_id,"missionary_page_id",$page_id);
	
	//Return the id
	return $page_id;
}

function get_user_page_url($user_id){
	//Get the missionary page of this user
	$missionary_page_id = get_user_page_id($user_id);
	
	if(!is_numeric($missionary_page_id) or get_post_status($missionary_page_id ) != 'publish'){
        $missionary_page_id = create_user_page($user_id);

        if(!$missionary_page_id) return false;
    }

    $url = get_permalink($missionary_page_id);
    $url_without_https = str_replace('https://','',$url);
    
    //return the url
    return $url_without_https;
}

function get_user_page_link($user){
    if(is_numeric($user)){
        $user   = get_userdata($user);
        if(!$user) return false;
    }
    $url    = get_user_page_url($user->ID);
    if($url){
        $html   = "<a href='$url'>$user->display_name</a>";
    }else{
        $html   = $user->display_name;
    }

    return $html;
}

function update_user_page_title($user_id, $title){
    $page_id    = get_user_page_id($user_id);

    if(is_numeric($page_id)){
        $page = get_post($page_id);

        //update page title if needed
		if($page->post_title != $title){
            wp_update_post(
                array (
                    'ID'         => $page_id,
                    'post_title' => $title
                )
            );

            return true;
        }else{
            return false;
        }
    }else{
        return create_user_page($user_id);  
    } 
}

function remove_user_page($user_id){
    //Check if a page exists for this person
    $missionary_page    = get_user_page_id($user_id);
    if (is_numeric($missionary_page)){
        //page exists, delete it
        wp_delete_post($missionary_page);
        SIM\print_array("Deleted the missionary page $missionary_page");
    }
}

//Display name and mission of missionary
function user_description($user_id){
	global $CompoundsPageID;
	$html = "";

	$userdata	= get_userdata($user_id);
	$user 		= wp_get_current_user();
	
	//get the family details
	$family = get_user_meta( $user_id, 'family', true );
	
	//Find compound or address
	$location = get_user_meta( $user_id, 'location', true );
	if(is_array($location)){
		if (empty($location["compound"])){
			if (empty($location["address"])){
				$address = "No clue, since no address is given";
			}else{
				$address = $location["address"];
			}
		}else{
			$url = "";
			$compounds = get_children( array('post_parent' => $CompoundsPageID,));
			foreach($compounds as $compound){
				if($compound->post_title == $location["compound"]){
					$url = $compound->guid;
					$address = '<a href="'.$url.'">'.$location["compound"].'</a>';
				}
			}
			//Compound is set, but no compound page found
			if ( $url == ""){
				$address = $location["compound"];
			}
		}
	}else{
		$address = "No clue, since no address is given";
	}
	
	//Add a useraccount edit button if the user has the usermanagement role
	if (in_array('usermanagement',$user->roles)){
		$edit_user_url = SITEURL.'/update-personal-info/?userid=';
		
		$html .= "<div class='flex edit_useraccounts'><a href='$edit_user_url$user_id' class='button sim'>Edit useraccount for ".$userdata->first_name."</a>";
		if(is_array($family) and isset($family['partner'])){
			$html .= "<a  href='$edit_user_url".$family['partner']."' class='button sim'>Edit useraccount for ".get_userdata($family['partner'])->first_name."</a>";
		}
		if(is_array($family) and isset($family['children'])){
			foreach($family['children'] as $child){
				$html .= "<a href='$edit_user_url$child' class='button sim'>Edit useraccount for ".get_userdata($child)->first_name."</a>";
			}
		}
		$html .= '</div>';
	}
	
	$privacy_preference = get_user_meta( $user_id, 'privacy_preference', true );
	if(!is_array($privacy_preference)) $privacy_preference = [];

	//Build the html
	//Missionary has a family
	if ($family != ""){
		$html .= "<h1>".$userdata->last_name." family</h1>";
		$html .= "<p>
					Lives in: ";
		$html .= $address.' State';
		$html .= "</p>";
		$sending_office = get_user_meta( $user_id, 'sending_office', true );
		if($sending_office != "")
			$html .= "<p>Sending office: $sending_office</p>";
			
		$arrivaldate 	= get_user_meta( $user_id, 'arrival_date', true );
		if($arrivaldate != "" and !isset($privacy_preference['hide_anniversary'])){
			$arrival_epoch	= strtotime($arrivaldate);
			$arrivaldate	= date('F Y',$arrival_epoch);
			if($arrival_epoch < time()){
				$html .= "<p>In Nigeria since $arrivaldate</p>";
			}else{
				$html .= "<p>Expected arrival in Nigeria: $arrivaldate</p>";
			}
		}
		
		//Partner data
		if (isset($family['partner']) and is_numeric($family['partner'])){
			$html .= show_user_info($family['partner']);
		}
		
		//User data
		$html .= show_user_info($user_id);
			
		//Children
		if (isset($family["children"])){
			$html .= "<p>";
			$html .= " They have the following children:<br>";
			foreach($family["children"] as $child){
				$childdata = get_userdata($child);
				$age = SIM\get_age($child);
				if($age != '') $age = "($age)";
				
				$html .= SIM\USERMANAGEMENT\display_profile_picture($childdata->ID);
			$html .= "<span class='person_work'> {$childdata->first_name} $age</span><br>";
			}
			$html .= "</p>";
		}
	//Single
	}else{
		$userdata = get_userdata($user_id);
	
		if ($userdata != null){
			if(isset($privacy_preference['hide_name'])){ 
				$displayname = '';
			}else{
				$displayname = $userdata->data->display_name;
			}
			
			$html .= "<h1>";
			if(!isset($privacy_preference['hide_profile_picture'])){
				$html .= SIM\USERMANAGEMENT\display_profile_picture($user_id);
			}
			$html .= "  ".$displayname."</h1>";
			$html .= "<br>";
			if(!isset($privacy_preference['hide_location'])){ 
				$html .= "<p>Lives on: $address</p>";
			}
			if(!isset($privacy_preference['hide_ministry'])){ 
				$html .= "<p>Works with: <br>".add_ministry_links($user_id)."</p>";
			}
			$sending_office = get_user_meta( $user_id, 'sending_office', true );
			if($sending_office != "")
				$html .= "<p>Sending office: $sending_office</p>";
				
			$arrivaldate = get_user_meta( $user_id, 'arrival_date', true );
			if($arrivaldate != "" and !isset($privacy_preference['hide_anniversary'])){
				$arrival_epoch	= strtotime($arrivaldate);
				$arrivaldate	= date('F Y',$arrival_epoch);
				if($arrival_epoch < time()){
					$html .= "<p>In Nigeria since $arrivaldate</p>";
				}else{
					$html .= "<p>Expected arrival in Nigeria: $arrivaldate</p>";
				}
			}
			
			$html .= show_phonenumbers($user_id);
		}else{
			$html .= "<p>Nothing to show here due to privacy settings.</p>";
		}
	}
	
	return $html;
}

function show_user_info($user_id){
	$html = ""; 
	
	$userdata = get_userdata($user_id);
	$privacy_preference = (array)get_user_meta( $user_id, 'privacy_preference', true );
	
	//If it is a valid user and privacy allows us to show it
	if ($userdata != null){
		if(isset($privacy_preference['hide_name'])){ 
			$displayname = '';
		}else{
			$displayname = $userdata->first_name;
		}
		
		$html .= "<p>";
		if(empty($privacy_preference['hide_profile_picture'])){
			$html .= SIM\USERMANAGEMENT\display_profile_picture($user_id);
			$style = "";
		}else{
			$style = ' style="margin-left: 55px;"';
		}
		
		if(!isset($privacy_preference['hide_ministry'])){ 
			$html .= "<span class='person_work' $style> $displayname works with: </span><br>";
			$html .= add_ministry_links($user_id);
		}
		$html .= "</p>";
		$html .= show_phonenumbers($user_id);
	}
	
	return $html;
}

function show_phonenumbers($user_id){
	$privacy_preference = (array)get_user_meta( $user_id, 'privacy_preference', true );
	
	$html = "";
	if(!isset($privacy_preference['hide_phone'])){
		$phonenumbers = get_user_meta( $user_id, 'phonenumbers', true );
	
		$html .= "<p><span style='font-size: 15px;'>Contact details below are only for you to use.<br>Do not share with other people.</span><br><br>";
		$email	= get_userdata($user_id)->user_email;
		$html .= "E-mail: <a href='mailto:$email'>$email</a><br>";
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
		$html .= add_vcard_download($user_id).'</p>';
	}
	
	return $html;
}

//Create vcard
function add_vcard_download($user_id){
	global $post;
	//Make vcard
	if (isset($_GET['vcard'])){
		ob_end_clean();
		//ob_start();
		$user_id = $_GET['vcard'];
		header('Content-Type: text/x-vcard');
		header('Content-Disposition: inline; filename= "'.get_userdata($user_id)->data->display_name.'.vcf"');

		$vcard = build_vcard($user_id);
		echo $vcard;
		die();

	//Return vcard hyperlink
	}else{
		$url = add_query_arg( ['vcard' => $user_id], get_permalink( $post->ID ) );
		return '<a href="'.$url.'" class="button sim vcard">Add to your contacts</a>';
	}
}

//Get vcard contents for a person
function build_vcard($user_id){
	//Get the user partner
	$family = (array)get_user_meta( $user_id, 'family', true );
	if (isset($family['partner'])){
		$partner = $family['partner'];
	}else{
		$partner = "";
	}
	
	$privacy_preference = (array)get_user_meta( $user_id, 'privacy_preference', true );
	
	if(empty($privacy_preference['hide_location'])){ 
		//Get the user address
		$location = get_user_meta( $user_id, 'location', true );
		if (!empty($location['address'])){
			$address = $location['address'];
			$lat = $location['latitude'];
			$lon = $location['longitude'];
		}
	}
	$gender 	= get_user_meta( $user_id, 'gender', true );
	$userdata 	= get_userdata($user_id);
		
	$vcard = "BEGIN:VCARD\r\n";
	$vcard .= "VERSION:4.0\r\n";
	if(empty($privacy_preference['hide_name'])){
		$vcard .= "FN:".$userdata->data->display_name."\r\n";
		$vcard .= "N:".$userdata->last_name.";".$userdata->first_name.";;;\r\n";
	}
	$vcard .= "ORG:".SITENAME."\r\n";
	$vcard .= "EMAIL;TYPE=INTERNET;TYPE=WORK:".$userdata->user_email."\r\n";
	
	if(empty($privacy_preference['hide_phone'])){
		$phonenumbers = get_user_meta( $user_id, 'phonenumbers', true );
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
	$vcard .= "BDAY:".str_replace("-","",get_user_meta( $user_id, "birthday", true ))."\r\n";
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
	if (is_numeric(get_user_meta($user_id,'profile_picture',true)) and empty($privacy_preference['hide_profile_picture'])){
		$picture_url 			= str_replace(wp_upload_dir()['url'],wp_upload_dir()['path'], SIM\USERMANAGEMENT\get_profile_picture_url($user_id, "large"));
		$photo               	= file_get_contents($picture_url);
		$b64vcard               = base64_encode($photo);
		$b64mline               = chunk_split($b64vcard,74,"\n");
		$b64final               = preg_replace("/(.+)/", " $1", $b64mline);
		$vcard 					.= "PHOTO;ENCODING=b;TYPE=JPEG:";
		$vcard 					.= $b64final . "\r\n";
	}
	$vcard .= "END:VCARD\r\n";
	return $vcard;
}

//Build hyperlinks for ministries
function add_ministry_links($UserID){
	$user_ministries = (array)get_user_meta( $UserID, "user_ministries", true);

	$html = "";
	foreach($user_ministries as $key=>$user_ministry){
		if(!empty($user_ministry)){
			$ministry = str_replace("_"," ",$key);
			$ministry_page = get_page_by_title( $ministry);
			if ( $ministry_page != ""){
				$page_url = get_post_permalink($ministry_page->ID);
				$page_url =  '<a class="ministry_link" href="'.$page_url.'">'.$ministry.'</a>';
			}else{
				$page_url = $ministry;
			}
			$html .= $page_url.' as '.$user_ministry.'<br>';
		}
	}

	if($html == "") $html = "No clue, since no one told me.";
	return $html;
}

// Add description if the current page is attached to a user id
add_filter( 'the_content', function ( $content ) {
	if (is_user_logged_in()){
		$post_id 	= get_the_ID();
		//missionary page
		$missionary_id = get_post_meta($post_id,'missionary_id',true);
		if(is_numeric($missionary_id)) $content .= user_description($missionary_id);
	}

	return $content;
});