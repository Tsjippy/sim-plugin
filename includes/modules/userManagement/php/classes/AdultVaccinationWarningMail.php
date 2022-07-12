<?php
namespace SIM\USERMANAGEMENT;
use SIM;
use SIM\ADMIN;

class AdultVaccinationWarningMail extends ADMIN\MailSetting{

    public $user;
    public $reminderHtml;

    public function __construct($user, $reminderHtml='') {
        // call parent constructor
		parent::__construct('adult_vacc_warning', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%reminder_html%']    = $reminderHtml;

        $this->defaultSubject    = "Please renew your vaccinations";

        $this->defaultMessage    = 'Dear %first_name%,<br><br>';
        $this->defaultMessage   .= '%reminder_html%<br>';
        $this->defaultMessage   .= 'Please renew them as soon as possible.<br>';
        $this->defaultMessage   .= 'If you have any questions, just reply to this e-mail';
    }
}
