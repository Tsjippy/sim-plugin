<?php
namespace SIM\BANKING;
use SIM;
use SIM\ADMIN;

class DisableBanking extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct('disable', MODULE_SLUG);

        $this->addUser($user);

        $base                   = str_replace('https://', '', site_url());
        $this->defaultSubject   = "Please remove post@$base";

        $this->defaultMessage    = 'Hi finance team,<br><br>';
		$this->defaultMessage   .= "The useraccount of %full_name% is deleted from the website.<br>";
		$this->defaultMessage 	.= "Please please do not send their account statements to post@$base anyymore.<br><br>Thank you";
    }
}