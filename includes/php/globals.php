<?php
namespace SIM;
define('SiteURL',get_site_url());
define('TwoFA_page', SiteURL."/account/?redirected=true#Two%20factor");
define('PicturesUrl',plugins_url('',__DIR__).'/pictures');
define('LoaderImageURL', PicturesUrl.'/loading.gif');

//ALl global available variables
$CustomSimSettings 		= get_option("customsimsettings");

if($CustomSimSettings == false){
	echo "Please set the options in /wp-admin/options-general.php?page=custom_simnigeria";
}else{
	define('WebmasterName', $CustomSimSettings["webmastername"]);

	//Global variables
	$NigeriaStates	=[
		'Abia' => [
			'lat'=>'5.532003041',
			'lon'=>'7.486002487'
		],
		'Abuja'=>[
			'lat'=>'9.083333333',
			'lon'=>'7.533333'
		],
		'Adamawa'=>[
			'lat'=>'10.2703408',
			'lon'=>'13.2700321'
		],
		'Akwa Ibom'=>[
			'lat'=>'5.007996056',
			'lon'=>'7.849998524'
		],
		'Anambra'=>[
			'lat'=>'6.210433572',
			'lon'=>'7.06999711'
		],
		'Bauchi'=>[
			'lat'=>'11.68040977',
			'lon'=>'10.190013'
		],
		'Benue'=>[
			'lat'=>'7.190399596',
			'lon'=>'8.129984089'
		],
		'Borno'=>[
			'lat'=>'10.62042279',
			'lon'=>'12.18999467'
		],
		'Cross_River'=>[
			'lat'=>'4.960406513',
			'lon'=>'8.330023558'
		],
		'Delta'=>[
			'lat'=>'5.890427265',
			'lon'=>'5.680004434'
		],
		'Edo'=>[
			'lat'=>'6.340477314',
			'lon'=>'5.620008096'
		],
		'Ekiti'=>[
			'lat'=>'7.630372741',
			'lon'=>'5.219980834'
		],
		'Enugu'=>[
			'lat'=>'6.867034321',
			'lon'=>'7.383362995'
		],
		'Gombe'=>[
			'lat'=>'10.29044293',
			'lon'=>'11.16995357'
		],
		'Imo'=>[
			'lat'=>'5.492997053',
			'lon'=>'7.026003588'
		],
		'Jigawa'=>[
			'lat'=>'11.7991891',
			'lon'=>'9.350334607'
		],
		'Kaduna'=>[
			'lat'=>'11.0799813',
			'lon'=>'7.710009724'
		],
		'Kano'=>[
			'lat'=>'11.99997683',
			'lon'=>'8.5200378'
		],
		'Katsina'=>[
			'lat'=>'11.5203937',
			'lon'=>'7.320007689'
		],
		'Kebbi'=>[
			'lat'=>'12.45041445',
			'lon'=>'4.199939737'
		],
		'Kogi'=>[
			'lat'=>'7.800388203',
			'lon'=>'6.739939737'
		],
		'Kwara'=>[
			'lat'=>'8.490010192',
			'lon'=>'4.549995889'
		],
		'Lagos'=>[
			'lat'=>'6.443261653',
			'lon'=>'3.391531071'
		],
		'Nassarawa'=>[
			'lat'=>'8.490423603',
			'lon'=>'8.5200378'
		],
		'Niger'=>[
			'lat'=>'10.4003587',
			'lon'=>'5.469939737'
		],
		'Ogun'=>[
			'lat'=>'7.160427265',
			'lon'=>'3.350017455'
		],
		'Ondo'=>[
			'lat'=>'7.250395934',
			'lon'=>'5.199982054'
		],
		'Osun'=>[
			'lat'=>'7.629959329',
			'lon'=>'4.179992634'
		],
		'Oyo'=>[
			'lat'=>'7.970016092',
			'lon'=>'3.590002806'
		],
		'Plateau'=>[
			'lat'=>'9.929973978',
			'lon'=>'8.890041055'
		],
		'Rivers'=>[
			'lat'=>'4.810002257',
			'lon'=>'7.010000772'
		],
		'Sokoto'=>[
			'lat'=>'13.06001548',
			'lon'=>'5.240031289'
		],
		'Taraba'=>[
			'lat'=>'7.870409769',
			'lon'=>'9.780012572'
		],
		'Yobe'=>[
			'lat'=>'11.74899608',
			'lon'=>'11.96600457'
		],
		'Zamfara'=>[
			'lat'=>'12.1704057',
			'lon'=>'6.659996296'
		],
	];
	$num_word_list = ['', 
		'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eight', 'nineth', 'tenth', 
		'eleventh','twelfth', 'thirteenth', 'fourteenth', 'fifteenth', 'sixteenth', 'seventeenth', 'eighteenth', 'nineteenth','twentieth',
		'teenth','twenty','thirty','fourty','fifty'
	];
	$QuotaNames				= [
		'Administrative Workers',
		'Allied Health Professionals',
		'Allied Health Workers',
		'Community Workers',
		'Dental Personnel',
		'Dental Surgein',
		'Evangelical Personnel',
		'Evangelism Consultants',
		'Family Practice',
		'Internal Medicine',
		'Medical Consultants',
		'Missionaries',
		'Missionaries Managers',
		'Missionary Administrative Workers',
		'Missionary Support Services',
		'Nurses Anaestherists',
		'Nurses Educators',
		'Palliative Care Specialist',
		'Rural Development Workers',
		'Special Teachers',
		'Technical Personnel',
		'Teachers',
		'Theological Educators',
		'Theological Teachers',
		'Theological Translator'
	];
	$ScheduledFunctions = [
		['monthly','personal_info_reminder'],
		['monthly','vaccination_reminder'],
		['daily','birthday_check'],
		['daily','account_expiry_check'],
		['threemonthly','send_missonary_detail'],
		['monthly','greencard_reminder'],
		['monthly','send_reimbursement_requests'],
		['daily','archive_old_requests'],
		['daily','anniversary_check'],
		//['weekly','simnigeria_review_reminders'],
		['monthly','check_last_login_date'],
		['daily','expired_posts_check'],
		['daily','process_images'],
		['weekly','read_reminder'],
		['yearly','check_details_mail'],
		['yearly','remove_old_events'],
		['monthly','page_age_warning']
	];
	$picturesurl				= plugins_url('',__DIR__).'/pictures';
	$LoaderImageURL 			= $picturesurl.'/loading.gif';
	$GenericUserInfoFields		= ["birthday","gender","privacy_preference","phonenumbers","user_ministries","arrival_date"];
	$ChildrenCopyFields			= ["sending_office","local_nigerian","financial_account_id","account_statements","online_statements"]; //Fields of children who get their value from their parents
	$FinanceDefaultImageID 		= get_theme_mod('financedefaultimage','');				//Other.php									
	$NewsDefaultImageID 		= get_theme_mod('newsdefaultimage','');					//Other.php			
	$PrayerDefaultImageID 		= get_theme_mod('prayerdefaultimage','');				//Other.php	
	$EventDefaultImageID 		= get_theme_mod('eventdefaultimage','');				//Other.php
	$TravelCoordinatorSignature	= get_attached_file( get_theme_mod('travelsignature',''));					
	$PDF_Logo_path				= get_attached_file( get_theme_mod('pdflogo','') );
	$PlacesMapId				= $CustomSimSettings["placesmapid"];
	$MissionariesMapId			= $CustomSimSettings["missionariesmapid"];
	$PublicCategoryID 			= $CustomSimSettings["publiccategory"];					//Other.php
	$ConfCategoryID 			= $CustomSimSettings["confcategory"];					//Other.php
	$PrayerCategoryID 			= $CustomSimSettings["prayercategory"];
	$FinanceCategoryID 			= $CustomSimSettings["financecategory"];
	$MinistryCategoryID			= $CustomSimSettings["ministrycategory"];
	$CompoundCategoryID			= $CustomSimSettings["compoundcategory"];
	$LoggedInHomePage			= $CustomSimSettings["logged_in_home_page"];
	$MissionariesPageID			= $CustomSimSettings["missionaries_page"];
	$WelcomeMessagePageID		= $CustomSimSettings["welcome_page"];
	$PublishPostPage			= $CustomSimSettings["publish_post_page"];
	$ProfilePage				= $CustomSimSettings["profile_page"];
	$PostOutOfDataWarning		= $CustomSimSettings["postoutofdatawarning"]; 	//Age in months on when a warning should be showed or send
	$VaccinationExpiryWarning	= $CustomSimSettings["vaccinationoutofdatawarning"]; 	//Age in months on when a warning should be showed or send
	$WebmasterName				= $CustomSimSettings["webmastername"]; 	//Used in registration_fields.php to sign the welcome e-mail
	$FinanceEmail				= $CustomSimSettings["finance_email"];  //Used in postie.php
	$HealthCoordinatorEmail		= $CustomSimSettings["medical_email"];  //Used for medical warning emails
	$PersonnelCoordinatorEmail	= $CustomSimSettings["personnel_email"];
	$STAEmail					= $CustomSimSettings["sta_email"];
	$MailchimpAudienceIDs 		= $CustomSimSettings["mailchimp_audienceids"];
	$MailchimpUserTAGs 			= $CustomSimSettings["mailchimp_user_tags"];
	$MailchimpMissionaryTAGs	= $CustomSimSettings["mailchimp_missionary_tags"];
	$MailchimpOfficeStaffTAGs	= $CustomSimSettings["mailchimp_office_staff_tags"];
	$MailchimpApi				= $CustomSimSettings["mailchimpapi"];
	if(!empty($CustomSimSettings["mailchimptemplateid"])){
		$MailchimpTemplateID		= (int)$CustomSimSettings["mailchimptemplateid"];
	}else{
		$MailchimpTemplateID=0;
	}
	$TrelloApiKey				= $CustomSimSettings["trellopapikey"];
	$TrelloApiToken				= $CustomSimSettings["trellopapitoken"];
	$TrelloBoard				= $CustomSimSettings["trello_board"];
	$TrelloList					= $CustomSimSettings["trello_list"];
	$TrelloDestinationList		= $CustomSimSettings["trello_destination_list"];
	//Get the current health coordinator
	$HealtCoordinators 			= get_users( array( 'fields' => array( 'ID','display_name' ),'role' => 'medicalinfo' ));
	if($HealtCoordinators != null){
		$HealtCoordinator = (object)$HealtCoordinators[0];
	}else{
		$HealtCoordinator = new \stdClass();
		$HealtCoordinator->display_name = $WebmasterName;
		error_log("Please assign someone the health coorodinator role!");
	}
	
	//Create a html hyperlink for the page of the current health coordinator
	add_action('init', function() {
		global $HealtCoordinator;
		global $Healtcoordinator_Page_Url;
		
		$Healtcoordinator_Page_Id = get_user_meta($HealtCoordinator->ID,'missionary_page_id',true);
		if($Healtcoordinator_Page_Id != ""){
			$Healtcoordinator_Page_Url = get_permalink($Healtcoordinator_Page_Id);
			$Healtcoordinator_Page_Url = '<a href="'.$Healtcoordinator_Page_Url.'">'.$HealtCoordinator->display_name.'</a>';
		}else{
			$Healtcoordinator_Page_Url = $HealtCoordinator->display_name;
		}
	});
	
	//Get the current travel coordinator
	$TravelCoordinator 			= get_users( array( 'role' => 'visainfo' ));
	if($TravelCoordinator != null){
		$TravelCoordinator = $TravelCoordinator[0];
	}else{
		$TravelCoordinator = new \stdClass();
		$TravelCoordinator->display_name = $WebmasterName;
		error_log("Please assign someone the travelcoorodinator role!");
	}
}