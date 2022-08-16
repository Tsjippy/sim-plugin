<?php
namespace SIM\USERPAGE;
use SIM;

//Shortcode to download all contact info
add_shortcode("all_contacts",function (){
	global $post;

	//Make vcard
	if (isset($_GET['vcard'])){
		if($_GET['vcard']=="all"){
			ob_end_clean();
			header('Content-Type: text/x-vcard');
			header('Content-Disposition: inline; filename= "SIMContacts.vcf"');
			
			$vcard = "";
			$users = SIM\getUserAccounts(false,true,true,['ID']);
			foreach($users as $user){
				$vcard .= buildVcard($user->ID);
			}
			echo $vcard;
		}elseif($_GET['vcard']=="outlook"){
			$zip = new \ZipArchive;
			
			if ($zip->open('SIMContacts.zip', \ZipArchive::CREATE) === TRUE){
				//Get all user accounts
				$users = SIM\getUserAccounts(false,true,true,['ID','display_name']);
				
				//Loop over the accounts and add their vcards
				foreach($users as $user){
					$zip->addFromString($user->display_name.'.vcf', buildVcard($user->ID));
				}	
			 
				// All files are added, so close the zip file.
				$zip->close();
			}
	
			ob_end_clean();
			
			header('Content-Type: application/zip');
			header('Content-Disposition: inline; filename= "SIMContacts.zip"');
			readfile('SIMContacts.zip');
			
			//remove the zip from the server
			unlink('SIMContacts.zip');
		}

		die();
	//Return vcard hyperlink
	}else{
		$url 			= add_query_arg( ['vcard' => "all"], get_permalink( $post->ID ) );
		$allButton 		= '<a href="'.$url.'" class="button sim vcard">Gmail and others</a>';
		
		$url 			= add_query_arg( ['vcard' => "outlook"], get_permalink( $post->ID ) );
		$outlookButton	= '<a href="'.$url.'" class="button sim vcard">Outlook</a>';
		
		$html = "<div class='download contacts'>";
			$html .= "<p>";
				$html .= "If you want to add the contact details of all website users to your addressbook, you can use one of the buttons below.<br>";
				$html .= "For gmail and other programs you can just import the vcf file.	";
				$html .= "For outlook you receive a zip file. Extract it, then click on each .vcf file to add it to your outlook.";
			$html .= "</p>";
			$html .= "$outlookButton $allButton";
			$html .= "<p>Be patient, preparing the download can take a while. </p>";
		$html .= "</div>";
		
		return $html;
	}
});

// Shortcode to display a user in a page or post
add_shortcode('user_link', __NAMESPACE__.'\linkedUserDescription');

function linkedUserDescription($atts){
	$html 	= "";
	$a 		= shortcode_atts( array(
        'id' 		=> '',
		'picture' 	=> false,
		'phone' 	=> false,
		'email' 	=> false,
		'style' 	=> '',
    ), $atts );

	$a['picture']	= filter_var($a['picture'], FILTER_VALIDATE_BOOLEAN);
	$a['phone']	= filter_var($a['phone'], FILTER_VALIDATE_BOOLEAN);
	$a['email']	= filter_var($a['email'], FILTER_VALIDATE_BOOLEAN);
	
	$userId = $a['id'];
    if(!is_numeric($userId)){
		return 'Please enter an user to show the details of';
	}
	
	if(!empty($a['style'])){
		$style = "style='".$a['style']."'";
	}else{
		$style = '';
	}
	
	$html = "<div $style>";
	
	$userdata		= get_userdata($userId);
	$nickname 		= get_user_meta($userId, 'nickname', true);
	$displayName 	= "(".$userdata->display_name.")";
	if($userdata->display_name == $nickname){
		$displayName = '';
	}
	$privacyPreference = (array)get_user_meta( $userId, 'privacy_preference', true );
	
	$url = SIM\maybeGetUserPageUrl($userId);
	
	$profilePicture	= '';
	if($a['picture'] && !isset($privacyPreference['hide_profile_picture'])){
		$profilePicture = SIM\displayProfilePicture($userId);
	}
	$html .= "<a href='$url'>$profilePicture $nickname $displayName</a><br>";
	
	if($a['email']){
		$html .= '<p style="margin-top:1.5em;">E-mail: <a href="mailto:'.$userdata->user_email.'">'.$userdata->user_email.'</a></p>';
	}
		
	if($a['phone']){
		$html .= showPhonenumbers($userId);
	}
	return $html."</div>";
}