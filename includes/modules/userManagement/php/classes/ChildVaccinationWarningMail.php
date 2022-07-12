<?php
namespace SIM\USERMANAGEMENT;
use SIM;
use SIM\ADMIN;

class ChildVaccinationWarningMail extends ADMIN\MailSetting{

    public $user;
    public $reminderHtml;

    public function __construct($user, $reminderHtml='') {
        // call parent constructor
		parent::__construct('child_vacc_warning', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%reminder_html%']    = $reminderHtml;

        $this->defaultSubject    = "Please renew the vaccinations of %first_name%";

        $this->defaultMessage    = 'Dear %last_name% family,<br><br>';
        $this->defaultMessage   .= '%reminder_html%<br>';
        $this->defaultMessage   .= 'Please renew them as soon as possible.<br>';
        $this->defaultMessage   .= 'If you have any questions, just reply to this e-mail';
    }
}
