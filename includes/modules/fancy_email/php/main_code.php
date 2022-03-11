<?php
namespace SIM\FANCYMAIL;
use SIM;

// Filter any wp_email
add_filter('wp_mail',function($args){
    global $wpdb;

    // Add e-mail to e-mails db
    $wpdb->insert(
        MAILTABLE, 
        array(
            'subject'		=> $args['subject'],
            'recipients'	=> $args['to']
        )
    );

    $email_id   = $wpdb->insert_id;

	//force html e-mail
	if(!is_array($args['headers'])) $args['headers'] = [];
	if(!in_array("Content-Type: text/html; charset=UTF-8", $args['headers'])){
		$args['headers'][]	= "Content-Type: text/html; charset=UTF-8";
	}

    // Add site greetings if not given
	if(strpos(strtolower($args['message']), 'kind regards,') === false and strpos(strtolower($args['message']), 'cheers,') === false){
		$args['message']	.= "<br><br>Kind regards,<br><br>".SITENAME;
	}
	
    // Mention that this is an automated message
    $footer = '';
	if(strpos($args['message'], 'is an automated') === false){
        $footer_url = apply_filters('sim_email_footer_url', SITEURL);
		$clean_url  = str_replace(['https://www.', 'https://'],'', $footer_url);
		$footer	    = "<span style='font-size:10px'>This is an automated e-mail originating from <a href='$footer_url'>$clean_url</a></span>";
	}

    // Convert message to html
    if(strpos($args['message'], '<!doctype html>') === false){
        $args['message']    = html_email($args['subject'], $args['message'], $footer, $email_id);
    }
    
	return $args;
}, 10,1);

// Replace any private urls to public urls
function checkEmailImages($matches){
    if(empty($matches)) return false;

    // Convert to array in case of a pure url
    if(!is_array($matches)){
        $matches    = [$matches, $matches];
    }

	$html	= $matches[0];

	if(strpos($matches[1], '/private/') !== false){
        $url	= $matches[1];
        $path	= SIM\url_to_path($matches[1]);
        $name	= basename($path);
        $new_path	= wp_upload_dir()['path']."/$name";
		copy($path, $new_path);
		$new_url= SIM\path_to_url($new_path);
		$html	= str_replace($url, $new_url, $html);
	}
	return $html;
}

function html_email($subject, $message, $footer, $emailId){
    $header_image_id    = SIM\get_module_option('fancy_email', 'picture_ids')['header_image'];

    if(!$header_image_id){
	    $header_image_id= get_theme_mod( 'custom_logo' );
    }

    // Get the logo url and make public if private
    $logo_url    = checkEmailImages(wp_get_attachment_url($header_image_id));

	// Process any images in the content
	$pattern = "/<img\s*src=[\"|']([^\"']*)[\"|']/i";
	$message = preg_replace_callback($pattern, __NAMESPACE__.'\checkEmailImages', $message);

	ob_start();
	?>
	<!doctype html>
	<html lang="en">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width">
            <title><?php echo $subject;?></title>
            <style type="text/css">@media only screen and (max-width: 599px) {table.body .container {width: 95% !important;}.header {padding: 15px 15px 12px 15px !important;}.header img {width: 200px !important;height: auto !important;}.content, .aside {padding: 30px 40px 20px 40px !important;}}</style>
        </head>
        <body style="height: 100% !important; width: 100% !important; min-width: 100%; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; -webkit-font-smoothing: antialiased !important; -moz-osx-font-smoothing: grayscale !important; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #f1f1f1; text-align: center;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%" class="body" style="border-collapse: collapse; border-spacing: 0; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; height: 100% !important; width: 100% !important; min-width: 100%; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; -webkit-font-smoothing: antialiased !important; -moz-osx-font-smoothing: grayscale !important; background-color: #f1f1f1; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%;">
                <tr style="padding: 0; vertical-align: top; text-align: left;">
                    <td align="center" valign="top" class="body-inner" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; text-align: center;">
                        <!-- Container -->
                        <table border="0" cellpadding="0" cellspacing="0" class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; width: 600px; margin: 0 auto 30px auto; Margin: 0 auto 30px auto; text-align: inherit;">
                            <!-- Header -->
                            <tr style="padding: 0; vertical-align: top; text-align: left;">
                                <td align="center" valign="middle" class="header" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; text-align: center; padding: 30px 30px 22px 30px;">
                                    <img src="<?php echo $logo_url;?>" alt="Site Logo" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; display: inline-block !important; width: 250px;">
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr style="padding: 0; vertical-align: top; text-align: left;">
                                <td align="left" valign="top" class="content" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #ffffff; padding: 60px 75px 45px 75px; border-right: 1px solid #ddd; border-bottom: 1px solid #ddd; border-left: 1px solid #ddd;">
                                    <h1 style="color:#241c15;font-family:Georgia,Times,'Times New Roman',serif;font-size:28px;font-style:normal;font-weight:400;line-height:36px;letter-spacing:normal;margin:0px 0px 20px 0px;padding:0;text-align:center">
                                        <?php echo $subject;?>
                                    </h1>
                                    <?php echo $message;?>
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr style="padding: 0; vertical-align: top; text-align: left;">
                                <td align="left" valign="top" class="content" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; padding: 20px 0px; text-align: center;">
                                    <?php
                                    echo apply_filters('sim_email_footer', $footer, $message);
                                    ?>
                                    <img src="<?php echo SITEURL."/wp-json/sim/v1/mailtracker/?mailid=$emailId";?>" alt="" width="1px" height="1px">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
	</html>

	<?php
	$message = ob_get_clean();

	return $message;
}