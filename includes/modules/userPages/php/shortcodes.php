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
			
			if ($zip->open('SIMContacts.zip', \ZipArchive::CREATE) === true){
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
		}elseif($_GET['vcard'] == "pdf"){
			//Create a pdf and add it to the mail
			buildUserDetailPdf();
		}

		die();
	//Return vcard hyperlink
	}else{
		$url 			= add_query_arg( ['vcard' => "all"], get_permalink( $post->ID ) );
		$allButton 		= "<a href='$url' class='button sim vcard'>Gmail and Others</a>";
		
		$url 			= add_query_arg( ['vcard' => "outlook"], get_permalink( $post->ID ) );
		$outlookButton	= "<a href='$url' class='button sim vcard'>Outlook</a>";

		$url 			= add_query_arg( ['vcard' => "pdf"], get_permalink( $post->ID ) );
		$pdfButton		= "<a href='$url' class='button sim vcard'>Download a printable list</a>";
		
		ob_start();
		?>
		<div class='download contacts' style='margin-top:10px;'>
			<h4>Add Contacts to Your Address Book</h4>
			<p>
				For your convenience, you can add contact details for SIM Nigeria’s team members to your phone or email address book.<br>
				<br>
				For Gmail and other email clients, simply import the .vcf file after selecting the button below.
				For Outlook, you will download a compressed .zip file. Extract this, then click on each .vcf file to add it to your Outlook contacts list.
			</p>
			<?php echo "$outlookButton  $allButton  $pdfButton";?>
			<p>Be patient, preparing the download can take a while. </p>
			<?php
			do_action('sim-after-download-contacts');
			?>
		</div>
		
		<?php
		return ob_get_clean();
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


/**
 * Export data in an PDF
 *
 * @param	array	$header 	The header text
 * @param	array	$data		The data
 * @param	bool	$download	Serve as downloadable filedefault false
 *
 * @return string the pdf path or none
 */
function createContactlistPdf($header, $data, $download=false) {
	// Column headings
	$widths = array(30, 45, 28, 47,45);
	
	//Built frontpage
	$pdf = new SIM\PDF\PdfHtml();
	$pdf->frontpage(SITENAME.' Contact List',date('F'));
	
	//Write the table headers
	$pdf->tableHeaders($header, $widths);
	
    // Data
    $fill = false;
	//Loop over all the rows
    foreach($data as $row){
		$pdf->writeTableRow($widths, $row, $fill,$header);
        $fill = !$fill;
    }
    // Closing line
    $pdf->Cell(array_sum($widths),0,'','T');
	
	$contactList = "Contactlist - ".date('F').".pdf";

	$output		= 'F';
	if($download){
		// CLear the complete queue
		SIM\clearOutput();
		$output		= 'D';
	}else{
		$contactList = get_temp_dir().SITENAME." $contactList";
	}

	$pdf->Output( $output, $contactList);
	
    return $contactList;
}


/**
 * Builds the PDF of contact info of all users
 *
 * @return string pdf path
 */
function buildUserDetailPdf(){
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
		
	//Sort users on last name, then on first name
	$args = array(
		'meta_query' => array(
			'relation'	=> 'AND',
			'query_one' => array(
				'key' => 'last_name'
			),
			'query_two'	=> array(
				'key' => 'first_name'
			),
		),
		'orderby'	=> array(
			'query_one' => 'ASC',
			'query_two' => 'ASC',
		),
	);
	
	$users = SIM\getUserAccounts(false, true, [], $args);

	//Loop over all users to add a row with their data to the table
	$userDetails 	= [];

	foreach($users as $user){
		//skip admin
		if ($user->ID != 1 && $user->display_name != 'Signal Bot'){
			$privacyPreference = get_user_meta( $user->ID, 'privacy_preference', true );
			if(!is_array($privacyPreference)){
				$privacyPreference = [];
			}
			
			$name		= $user->display_name; //Real name
			$nickname	= get_user_meta($user->ID, 'nickname', true); //persons name in case of a office account
			if($name != $nickname && $nickname != ''){
				$name .= "\n ($nickname)";
			}
			
			$email	= $user->user_email;
			
			//Add to recipients
			if (strpos($user->user_email,'.empty') !== false){
				$email	= '';
			}
			
			$phonenumbers = "";
			if(empty($privacyPreference['hide_phone'])){
				$userPhonenumbers = (array)get_user_meta ( $user->ID,"phonenumbers",true);
				foreach($userPhonenumbers as $key=>$phonenumber){
					if ($key > 0){
						$phonenumbers .= "\n";
					}
					$phonenumbers .= $phonenumber;
				}
			}
			
			$ministries = "";
			if(empty($privacyPreference['hide_ministry'])){
				$userMinistries = (array)get_user_meta( $user->ID, "jobs", true);
				$i = 0;
				foreach ($userMinistries as $key=>$userMinistry) {
					if ($i > 0){
						$ministries .= "\n";
					}
					$ministries  .= get_the_title($key);
					$i++;
				}
			}
			
			$compound = "";
			if(empty($privacyPreference['hide_location'])){
				$location = (array)get_user_meta( $user->ID, 'location', true );
				if(isset($location['compound'])){
					$compound = $location['compound'];
				}
			}
			$userDetails[] 	= [$name, $email, $phonenumbers, $ministries, $compound];
		}
	}

	//Headers of the table
	$tableHeaders = ["Name"," E-mail"," Phone"," Ministries"," State"];

	//Create a pdf and add it to the mail
	return createContactlistPdf($tableHeaders, $userDetails, true);
}