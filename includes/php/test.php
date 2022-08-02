<?php
namespace SIM;

use SIM\FORMS\Formbuilder;
use SMTPValidateEmail\Validator as SmtpEmailValidator;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $Modules;

	return '';
});