<?php
namespace SIM\LOGIN;
use SIM;
use SIM\ADMIN;

class EmailVerfEnabled extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct('email_enabled', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%user_login%'] = $user->user_login;

        $this->defaultSubject               = "E-mail verification enabled";

        $this->defaultMessage               = 'Hi %first_name%,<br><br>';
		$this->defaultMessage              .= "This is to confirm that you have enabled e-mail verification for login on %site_name%.";
    }
}
