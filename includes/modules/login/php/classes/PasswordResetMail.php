<?php
namespace SIM\LOGIN;
use SIM;
use SIM\ADMIN;

class PasswordResetMail extends ADMIN\MailSetting{

    public $user;
    public $url;

    public function __construct($user, $url='') {
        // call parent constructor
		parent::__construct('password_reset', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%url%']    = $url;

        $this->defaultSubject    = "Password reset requested";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
        $this->defaultMessage    = "Someone requested a password reset for you.<br>";
        $this->defaultMessage	.= "If that was not you, please ignore this e-mail.<br>";
        $this->defaultMessage	.= "Otherwise, follow this <a href='%url%'>link</a> to reset your password.<br>";
    }
}
