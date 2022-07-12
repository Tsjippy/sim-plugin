<?php
namespace SIM\USERMANAGEMENT;
use SIM;

class DashboardWarnings{
    function __construct($userId){
        $this->reminderCount    = 0;
        $this->reminderHtml     = '';
        $this->userId           = $userId;
    }

    /**
     * Check for expired greencard
     */
    function greenCardReminder(){
        $visaInfo = get_user_meta( $this->userId, "visa_info", true);

        if (is_array($visaInfo) && isset($visaInfo['greencard_expiry'])){
            $this->reminderHtml .= checkExpiryDate($visaInfo['greencard_expiry'], 'greencard');
            
            if(!empty($this->reminderHtml)){
                $this->reminderCount = 1;
                $this->reminderHtml .= '<br>';
            }
        }
    }
	
	/**
     * Checks for vaccination reminders of the current user
     * and his or her children
     */
    function vaccinationReminders(){
        $vaccinationReminderHtml = vaccinationReminders($this->userId);
	
        if (!empty($vaccinationReminderHtml)){
            $this->reminderCount += 1;
            $this->reminderHtml .= $vaccinationReminderHtml;
        }
        
        //Check for children
        $family = get_user_meta($this->userId, "family", true);

        //User has children
        if (isset($family["children"])){
            foreach($family["children"] as $child){
                $result = vaccinationReminders($child);
                if (!empty($result)){
                    $this->reminderCount++;
                    $userdata 		        = get_userdata($child);
                    $this->reminderHtml	    .= str_replace("Your", $userdata->first_name."'s", $result);
                }
            }
        }
    }

    /**
     * Checks for upcoming reviews
     */
    function reviewReminder(){
        $personnelCoordinatorEmail	= SIM\getModuleOption(MODULE_SLUG, 'personnel_email');

        //Check for upcoming reviews, but only if not set to be hidden for this year
        if(get_user_meta($this->userId, 'hide_annual_review', true) == date('Y')){
            return;
        }

        $personnelInfo 				= get_user_meta($this->userId, "personnel", true);
        if(!is_array($personnelInfo) || empty($personnelInfo['review_date'])){
            return;
        }

        //Hide annual review warning
        if(isset($_GET['hide_annual_review']) && $_GET['hide_annual_review'] == date('Y')){
            //Save in the db
            update_user_meta($this->userId, 'hide_annual_review', date('Y'));
            
            //Get the current url withouth the get params
            $url = str_replace('hide_annual_review='.date('Y'),'', SIM\currentUrl());

            //redirect to same page without params
            header ("Location: $url");
        }
        
        $reviewDate	= date('F', strtotime($personnelInfo['review_date']));
        //If this month is the review month or the month before the review month
        if($reviewDate == date('F') || date('F', strtotime('-1 month', strtotime($reviewDate))) == date('F')){			
            $genericDocuments = get_option('personnel_documents');

            if(is_array($genericDocuments) && !empty($genericDocuments['Annual review form'])){
                $this->reminderHtml .= "Please fill in the annual review questionary.<br>";
                $this->reminderHtml .= 'Find it <a href="'.SITEURL.'/'.$genericDocuments['Annual review form'].'">here</a>.<br>';
                $this->reminderHtml .= "Then send it to the <a href='mailto:$personnelCoordinatorEmail?subject=Annual review questionary'>Personnel coordinator</a><br>";
                $url			    = add_query_arg( 'hide_annual_review', date('Y'), SIM\currentUrl() );
                $this->reminderHtml .= "<a class='button sim' href='$url' style='margin-top:10px;'>I already send it!</a><br>";

                $this->reminderCount++;
            }
        }
    }
}