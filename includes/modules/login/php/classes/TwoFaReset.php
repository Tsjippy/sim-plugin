<?php
namespace SIM\LOGIN;
use SIM;
use SIM\ADMIN;

class TwoFaReset extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct('twofa_reset', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%user_login%'] = $user->user_login;

        $this->defaultSubject               = "Your account is unlocked";

        $this->defaultMessage               = 'Hi %first_name%,<br><br>';
		$this->defaultMessage              .= "I have removed all your second login factors so you can login again.<br>";
        $this->defaultMessage              .= "After logging in with your username (%user_login%) and password you have to set it up again.<br>";
        $this->defaultMessage              .= 'Find how to set it up in the <a href="%site_url%/manuals">manuals</a>';
    }
}
