<?php
namespace SIM\BANKING;
use SIM;
use SIM\ADMIN;

class EnableBanking extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct('enable', MODULE_SLUG);

        $this->addUser($user);

        $base                   = str_replace('https://', '', site_url());
        $this->defaultSubject   = "Please add post@$base";

        $this->defaultMessage    = 'Hi finance team,<br><br>';
		$this->defaultMessage   .= "%full_name% wants the account statements to be available on the website.<br>";
		$this->defaultMessage 	.= "Please send the account statements to post@$base from now on.<br><br>Thank you";
    }
}