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
			$users = SIM\get_user_accounts(false,true,true,['ID']);
			foreach($users as $user){
				$vcard .= build_vcard($user->ID);
			}
			echo $vcard;
		}elseif($_GET['vcard']=="outlook"){
			$zip = new \ZipArchive;
			
			if ($zip->open('SIMContacts.zip', \ZipArchive::CREATE) === TRUE){
				//Get all user accounts
				$users = SIM\get_user_accounts(false,true,true,['ID','display_name']);
				
				//Loop over the accounts and add their vcards
				foreach($users as $user){
					$zip->addFromString($user->display_name.'.vcf', build_vcard($user->ID));
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
		//echo ob_get_contents();
		die();
	//Return vcard hyperlink
	}else{
		$url 			= add_query_arg( ['vcard' => "all"], get_permalink( $post->ID ) );
		$all_button 	= '<a href="'.$url.'" class="button sim vcard">Gmail and others</a>';
		
		$url 			= add_query_arg( ['vcard' => "outlook"], get_permalink( $post->ID ) );
		$outlook_button	= '<a href="'.$url.'" class="button sim vcard">Outlook</a>';
		
		$html = "<div class='download contacts'>";
		$html .= "<p>If you want to add the contact details of all website users to your addressbook, you can use one of the buttons below.<br>";
		$html .= "For gmail and other programs you can just import the vcf file.	";
		$html .= "For outlook you receive a zip file. Extract it, then click on each .vcf file to add it to your outlook.</p>";
		$html .= "$outlook_button $all_button";
		$html .= "<p>Be patient, preparing the download can take a while. </p>";
		$html .= "</div>";
		
		return $html;
	}
});

add_shortcode("userstatistics",function ($atts){
	wp_enqueue_script('sim_table_script');
	ob_start();
	$users = SIM\get_user_accounts($return_family=false,$adults=true,$local_nigerians=true);
	?>
	<br>
	<div class='form-table-wrapper'>
		<table class='sim-table' style='max-height:500px;'>
			<thead>
				<tr>
					<th>Name</th>
					<th>Login count</th>
					<th>Last login</th>
					<th>Mandatory pages to read</th>
					<th>User roles</th>
					<th>Account validity</th>
				</tr>
			</thead>

			<tbody>
				<?php
				foreach($users as $user){
					$login_count= get_user_meta($user->ID,'login_count',true);
					if(!is_numeric(($login_count))) $login_count = 0;
					$last_login_date	= get_user_meta($user->ID,'last_login_date',true);
					if(empty($last_login_date)){
						$last_login_date	= 'Never';
					}else{
						$time_string 	= strtotime($last_login_date);
						if($time_string ) $last_login_date = date('d F Y', $time_string);
					}

					$picture = SIM\displayProfilePicture($user->ID);

					echo "<tr class='table-row'>";
						echo "<td>$picture {$user->display_name}</td>";
						echo "<td>$login_count</td>";
						echo "<td>$last_login_date</td>";
						echo "<td>".SIM\MANDATORY\get_must_read_documents($user->ID,true)."</td>";
						echo "<td>";
						foreach($user->roles as $role){
							echo $role.'<br>';
						}
						echo "</td>";
						echo "<td>".get_user_meta($user->ID,'account_validity',true)."</td>";
					echo "</tr>";
				}
				?>
			</tbody>
		</table>
	</div>
	<?php
	return ob_get_clean();
});

// Shortcode to display a user in a page or post
add_shortcode('missionary_link',function($atts){
	$html = "";
	$a = shortcode_atts( array(
        'id' => '',
		'picture' => false,
		'phone' => false,
		'email' => false,
		'style' => '',
    ), $atts );
	
	$user_id = $a['id'];
    if(!is_numeric($user_id)) return '';
	
	if(!empty($a['style'])){
		$style = "style='".$a['style']."'";
	}else{
		$style = '';
	}
	
	$html = "<div $style>";
	
	$userdata = get_userdata($user_id);
	$nickname = get_user_meta($user_id,'nickname',true);
	$display_name = "(".$userdata->display_name.")";
	if($userdata->display_name == $nickname) $display_name = '';
	$privacy_preference = get_user_meta( $user_id, 'privacy_preference', true );
	if(!is_array($privacy_preference)) $privacy_preference = [];
	
	$url = SIM\getUserPageUrl($user_id);
	
	if($a['picture'] == true and !isset($privacy_preference['hide_profile_picture'])){
		$profile_picture = SIM\displayProfilePicture($user_id);
	}
	$html .= "<a href='$url'>$profile_picture $nickname $display_name</a><br>";
	
	if($a['email'] == true){
		$html .= '<p style="margin-top:1.5em;">E-mail: <a href="mailto:'.$userdata->user_email.'">'.$userdata->user_email.'</a></p>';
	}
		
	if($a['phone'] == true){
		$html .= show_phonenumbers($user_id);
	}
	return $html."</div>";
});