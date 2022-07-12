<?php
namespace SIM\USERMANAGEMENT;
use SIM;
use SIM\ADMIN;


class WeMissYouMail extends ADMIN\MailSetting{

    public $user;
    public $lastLogin;

    public function __construct($user, $lastLogin='') {
        // call parent constructor
		parent::__construct('miss_you', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%lastlogin%']    = $lastLogin;

        $this->defaultSubject    = "We miss you!";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
        $this->defaultMessage    = "We miss you! We haven't seen you since %lastlogin%<br>";
        $this->defaultMessage 	.= 'Please pay us a visit on <a href="%site_url%">%site_name%</a><br>';
    }
}
