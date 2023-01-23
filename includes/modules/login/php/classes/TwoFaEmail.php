<?php
namespace SIM\LOGIN;
use SIM;
use SIM\ADMIN;

class TwoFaEmail extends ADMIN\MailSetting{

    public $user;
    public $emailCode;

    public function __construct($user, $emailCode='') {
        // call parent constructor
		parent::__construct( 'email_code', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%email_code%']    = $emailCode;

        $this->defaultSubject    = "Verification code for %site_name% login";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "Your requested a verification code for login on %site_name%.<br>";
		$this->defaultMessage   .= "Please use this code: <code>%email_code%</code>.";
    }
}
