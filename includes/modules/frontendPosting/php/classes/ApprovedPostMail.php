<?php
namespace SIM\FRONTENDPOSTING;
use SIM;
use SIM\ADMIN;

class ApprovedPostMail extends ADMIN\MailSetting{

    public $authorName;
    public $postType;
    public $url;

    public function __construct($authorName='', $postType='', $url='') {
        // call parent constructor
		parent::__construct('approved_post', MODULE_SLUG);

        $this->replaceArray['%author_name%']    = $authorName;
        $this->replaceArray['%post_type%']      = $postType;
        $this->replaceArray['%url%']            = $url;

        $this->defaultSubject    = "Your %post_type% is approved and published";

        $this->defaultMessage    = 'Hi %author_name%,<br><br>';
		$this->defaultMessage   .= "Your %post_type% is approved and published. View it <a href='%url%'>here</a>";
    }
}
