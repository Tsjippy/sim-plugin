<?php
namespace SIM\LOGIN;
use SIM;
use SIM\ADMIN;

class TwoFaEmail extends ADMIN\MailSetting{

    public $user;
    public $emailCode;

    public function __construct($user, $emailCode='') {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->replaceArray['%email_code%']    = $emailCode;

        $this->moduleSlug        = 'login';
        $this->keyword           = 'email_code';

        $this->defaultSubject    = "Verification code for %site_name% login";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "Your requested a verification code for login on %site_name%.<br>";
		$this->defaultMessage   .= "Please use this code: <code>%email_code%</code>.";
    }
}

class UnsafeLogin extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug        = 'login';
        $this->keyword           = 'unsafe_login';

        $this->defaultSubject    = "Unsafe login detected on %site_name%";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "Someone just logged in onto your account without the use of a second login factor.<br>";
    	$this->defaultMessage   .= "Please let us know immidiately if this was not you.";
    }
}

class TwoFaReset extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug                   = 'login';
        $this->keyword                      = 'twofa_reset';

        $this->replaceArray['%user_login%'] = $user->user_login;

        $this->defaultSubject               = "Your account is unlocked";

        $this->defaultMessage               = 'Hi %first_name%,<br><br>';
		$this->defaultMessage              .= "I have removed all your second login factors so you can login again.<br>";
        $this->defaultMessage              .= "After logging in with your username (%user_login%) and password you have to set it up again.<br>";
        $this->defaultMessage              .= 'Find how to set it up in the <a href="%site_url%/manuals">manuals</a>';
    }
}

class EmailVerfEnabled extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug                   = 'login';
        $this->keyword                      = 'email_enabled';

        $this->replaceArray['%user_login%'] = $user->user_login;

        $this->defaultSubject               = "E-mail verification enabled";

        $this->defaultMessage               = 'Hi %first_name%,<br><br>';
		$this->defaultMessage              .= "This is to confirm that you have enabled e-mail verification for login on %site_name%.";
    }
}

class PasswordResetMail extends ADMIN\MailSetting{

    public $user;
    public $url;

    public function __construct($user, $url='') {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug        = 'login';
        $this->keyword           = 'password_reset';

        $this->replaceArray['%url%']    = $url;

        $this->defaultSubject    = "Password reset requested";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
        $this->defaultMessage    = "Someone requested a password reset for you.<br>";
        $this->defaultMessage	.= "If that was not you, please ignore this e-mail.<br>";
        $this->defaultMessage	.= "Otherwise, follow this <a href='%url%'>link</a> to reset your password.<br>";
    }
}