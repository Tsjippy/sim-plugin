<?php
namespace SIM\USERMANAGEMENT;
use SIM;
use SIM\ADMIN;

class AccountApproveddMail extends ADMIN\MailSetting{

    public $user;
    public $loginUrl;

    public function __construct($user, $loginUrl='') {
        // call parent constructor
		parent::__construct('account_approved', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%login_url%']    = $loginUrl;
        $this->replaceArray['%user_name%']    = $user->user_login;

        $this->defaultSubject    = 'We have approved your account on %site_name%';

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage 	.= "We have approved your account on  %site_name%.<br>";
        $this->defaultMessage 	.= "You can now login on %site_url%.<br>";
        $this->defaultMessage 	.= 'Your username is: %user_name%.<br>';
		$this->defaultMessage 	.= "If you have not yet setup a password you can do so using this <a href='%login_url%'>link</a>.<br>";
        $this->defaultMessage 	.= 'If you have any problems, please contact us by replying to this e-mail.';
    }
}