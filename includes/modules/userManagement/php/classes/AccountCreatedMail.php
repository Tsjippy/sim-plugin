<?php
namespace SIM\USERMANAGEMENT;
use SIM;
use SIM\ADMIN;

class AccountCreatedMail extends ADMIN\MailSetting{

    public $user;
    public $loginUrl;

    public function __construct($user, $loginUrl='') {
        // call parent constructor
		parent::__construct('account_created', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%login_url%']    = $loginUrl;
        $this->replaceArray['%user_name%']    = $user->user_login;

        $this->defaultSubject    = 'We have created an account for you on %site_name%';

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage 	.= "We have created an account for you on  %site_name%.<br>";
		$this->defaultMessage 	.= "Please set a password using this <a href='%login_url%'>link</a>.<br>";
        $this->defaultMessage 	.= 'Your username is: %user_name%.<br>';
        $this->defaultMessage 	.= 'If you have any problems, please contact us by replying to this e-mail.';
    }
}
