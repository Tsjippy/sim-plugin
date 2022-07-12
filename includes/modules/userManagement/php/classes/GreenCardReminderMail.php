<?php
namespace SIM\USERMANAGEMENT;
use SIM;
use SIM\ADMIN;

class GreenCardReminderMail extends ADMIN\MailSetting{

    public $user;
    public $reminder;

    public function __construct($user, $reminder='') {
        // call parent constructor
		parent::__construct('greencard_warning', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%reminder%']    = $reminder;

        $this->defaultSubject    = "Please renew your greencard";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
        $this->defaultMessage    = "%reminder%<br>";
        $this->defaultMessage   .= 'Please renew it as soon as possible.<br>';
        $this->defaultMessage   .= 'If you have any questions, just reply to this e-mail';
    }
}
