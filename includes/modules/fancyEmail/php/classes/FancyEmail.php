<?php
namespace SIM\FANCYEMAIL;
use SIM;

class FancyEmail{
    public $mailTable;
    public $mailEventTable;
    public $mailTrackerUrl;
    public $subject;
    public $recipients;
    public $headers;
    public $message;
    public $emailId;
    public $footer;
    
    public function __construct(){
        global $wpdb;

        $this->mailTable        = $wpdb->prefix."sim_emails";
        $this->mailEventTable   = $wpdb->prefix."sim_email_events";
        $this->mailTrackerUrl   = SITEURL."/wp-json/".RESTAPIPREFIX."/mailtracker";
    }

    /**
     * Creates the tables for this module
     */
    public function createDbTables(){
        if ( !function_exists( 'maybe_create_table' ) ) {
            require_once ABSPATH . '/wp-admin/install-helper.php';
        }

        //only create db if it does not exist
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        //Email overview
        $sql = "CREATE TABLE $this->mailTable (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          subject tinytext NOT NULL,
          recipients longtext NOT NULL,
          time_send text NOT NULL,
          PRIMARY KEY  (id)
        ) $charsetCollate;";

        maybe_create_table($this->mailTable, $sql );

        // Clicked links
        $sql = "CREATE TABLE $this->mailEventTable (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email_id int NOT NULL,
            type text NOT NULL,
            time text NOT NULL,
            url text NOT NULL,
            PRIMARY KEY  (id)
          ) $charsetCollate;";

        maybe_create_table($this->mailEventTable, $sql );
    }

    /**
     * Filters all WP_Mail arguments
     *
     * @param   array   $args   the array of wp_mail arguments
     *
     * @return  array           The filtered args
     */
    public function filterMail($args){
        global $wpdb;

        $this->subject      = &$args['subject'];

        $this->recipients   = &$args['to'];
        
        //Do not send an e-mail when the adres contains .empty, or is localhost or is staging
        $empty  = false;
        if(is_array($this->recipients)){
            foreach($this->recipients as $index=>$recipient){
                if(strpos($recipient, '.empty') !== false){
                    unset($this->recipients[$index]);
                }
            }

            if(empty($this->recipients)){
                $empty  = true;
            }else{
                $this->recipients   = implode(',', $this->recipients);
            }
        }elseif(strpos($this->recipients, '.empty') !== false){
            $empty  = true;
        }
        
        if(
            $empty        ||
            (
                SIM\getModuleOption(MODULE_SLUG, 'no-localhost') &&
                $_SERVER['HTTP_HOST'] == 'localhost'
            )                                                   ||
            (
                SIM\getModuleOption(MODULE_SLUG, 'no-staging') &&
                get_option("wpstg_is_staging_site") == "true"
            )
        ){
            $args['to'] = '';
            return $args;
        }

        if(!is_array($args['headers'])){
            $args['headers'] = [];
        }
        $this->headers      = &$args['headers'];

        $this->message      = &$args['message'];

        if(is_array($this->message)){
            SIM\printArray($this->message);
        }

        // max attachment size
        $totalSize  = 0;
        $maxSize    = SIM\getModuleOption(MODULE_SLUG, 'maxsize');
        $remaining  = [];
        if(!$maxSize){
            $maxSize    = 20;
        }

        // check if the total attachment size is past the limit
        foreach($args['attachments'] as $index => $attach){
            $totalSize   += filesize($attach);

            // if this is more than the limit
            if(number_format($totalSize / 1048576, 2) >= $maxSize){
                $remaining[]    = $attach;
                unset($args['attachments'][$index]);
            }
        }

        if(!empty($remaining)){
            // Send an e-mail with the remaining e-mails
            $explode    = explode(' - ', $this->subject);
            if(is_numeric(end($explode))){
                $number = end($explode) +1;

                // remove the last element
                array_pop($explode);

                // Build the subject again without the last number
                $subject    = implode(' ', $explode).' - '.$number;
            }else{
                $subject    = "$this->subject - 1";
            }

            wp_mail($this->recipients, $subject, $this->message, $args['headers'], $remaining);
        }
        
        // Add e-mail to e-mails db
        $wpdb->insert(
            $this->mailTable ,
            array(
                'subject'		=> $this->subject ,
                'recipients'	=> $this->recipients,
                'time_send'     => current_time('U')
            )
        );

        $this->emailId   = $wpdb->insert_id;

        //force html e-mail
        if(!in_array("Content-Type: text/html; charset=UTF-8", $this->headers)){
            $this->headers[]	= "Content-Type: text/html; charset=UTF-8";
        }

        // Add site greetings if not given
        $defaultGreeting    = SIM\getModuleOption(MODULE_SLUG, 'closing');
        if(!$defaultGreeting){
            $defaultGreeting    = 'Kind regards,';
        }
        if(
            strpos(strtolower($this->message), $defaultGreeting) === false &&
            strpos(strtolower($this->message), 'regards,') === false &&
            strpos(strtolower($this->message), 'cheers,') === false
        ){
            $this->message	.= "<br><br><br>$defaultGreeting<br><br>".SITENAME;
        }

        // Mention that this is an automated message
        $footerUrl     = apply_filters('sim_email_footer_url', [
            'url'   => SITEURL,
            'text'  => SITEURL
        ]);

        $url            = $footerUrl['url'];
        $text           = str_replace(['https://www.', 'https://', 'http://www.', 'http://'], '', $footerUrl['text']);
        $this->footer	= "<span style='font-size:10px'>This is an automated e-mail originating from <a href='$url'>$text</a></span>";

        // Convert message to html
        if(strpos($this->message, '<!doctype html>') === false){
            $this->htmlEmail();
        }

        return $args;
    }

    /**
     * Replace any private urls to public urls, add mail logging to all links
     *
     * @param   array   $matches    Matches from a regex
     *
     * @return  string              Replace html
     */
    public function checkEmailImages($matches){
        if(empty($matches)){
            return false;
        }

        // Convert to array in case of a pure url
        if(!is_array($matches)){
            $matches    = [$matches, $matches];
        }

        $html	    = $matches[0];
        $url	    = $matches[1];

        // add a hash so image is also readible when not logged in
        if(strpos($url, '/private/') !== false){
            // create the random string
            $str    = rand();
            $hash   = md5($str);

            // store hash in db for a month
            set_transient( $hash, basename($url), MONTH_IN_SECONDS );

            $html	    = str_replace($url, "$url?imagehash=$hash", $html);
        }

        return $html;
    }

    /**
     * Enable link tracking
     *
     * @param   array   $matches    Matches from a regex
     *
     * @return  string              Replace html
     */
    public function urlReplace($matches){
        if(empty($matches)){
            return false;
        }

        // Convert to array in case of a pure url
        if(!is_array($matches)){
            $matches    = [$matches, $matches];
        }

        $html	    = $matches[0];
        $url	    = $matches[1];

        // Change to rest-api url
        $newUrl    = "$this->mailTrackerUrl?mailid=$this->emailId&url=".urlencode($url);

        $html	    = str_replace($url, $newUrl, $html);
        return $html;
    }

    /**
     * Converts plain text e-mail message to html
     */
    public function htmlEmail(){
        // Get the logo url and make public if private
        $headerImageId    = SIM\getModuleOption(MODULE_SLUG, 'picture_ids')['header_image'];
        if(!$headerImageId){
            $headerImageId= get_theme_mod( 'custom_logo' );
        }
        $logoUrl    = wp_get_attachment_url($headerImageId);

        // Process any images in the content
        $pattern = "/<img\s*src=[\"|']([^\"']*)[\"|']/i";
        $message = preg_replace_callback($pattern, array($this, 'checkEmailImages'), $this->message);

        //Enable e-mail tracking
        $pattern = "/href=[\"|']([^\"']*)[\"|']/i";
        $message = preg_replace_callback($pattern, array($this, 'urlReplace'), $message);

        //Replace all newline characters with html new line <br>
        $message  = str_replace("\n", '<br>', $message);

        ob_start();
        ?>
        <!doctype html>
        <html lang="en">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width">
                <title><?php echo $this->subject;?></title>
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
                                        <a href="<?php echo SITEURL;?>"><img src="<?php echo $logoUrl;?>" alt="Site Logo" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; display: inline-block !important; width: 250px;"></a>
                                    </td>
                                </tr>
                                <!-- Content -->
                                <tr style="padding: 0; vertical-align: top; text-align: left;">
                                    <td align="left" valign="top" class="content" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #ffffff; padding: 60px 75px 45px 75px; border-right: 1px solid #ddd; border-bottom: 1px solid #ddd; border-left: 1px solid #ddd;">
                                        <h1 style="color:#241c15;font-family:Georgia,Times,'Times New Roman',serif;font-size:28px;font-style:normal;font-weight:400;line-height:36px;letter-spacing:normal;margin:0px 0px 20px 0px;padding:0;text-align:center">
                                            <?php echo $this->subject;?>
                                        </h1>
                                        <?php echo $message;?>
                                    </td>
                                </tr>
                                <!-- Footer -->
                                <tr style="padding: 0; vertical-align: top; text-align: left;">
                                    <td align="left" valign="top" class="content" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; padding: 20px 0px; text-align: center;">
                                        <?php
                                        echo apply_filters('sim_email_footer', $this->footer, $this->message);
                                        $url    = "$this->mailTrackerUrl?mailid=$this->emailId&ver=$this->emailId";
                                        ?>
                                        <img src="<?php echo $url;?>" alt="." width="1px" height="1px">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
        </html>

        <?php
        $this->message = ob_get_clean();
    }

    /**
     * Get all e-mail statistics from the db
     *
     * @return  object      all query results
     */
    public function getEmailStatistics(){
        global $wpdb;
        if($_POST['type'] == 'link-clicked'){
            $query      =  "SELECT ";
            $where      = "$this->mailEventTable.type = '{$_POST['type']}'";
        }else{
            $where      = "$this->mailEventTable.type = 'mail-opened'";
            $query      =  "SELECT COUNT($this->mailEventTable.email_id) AS viewcount, ";
        }
        $query  .= "$this->mailEventTable.url, $this->mailEventTable.type, $this->mailTable.recipients, $this->mailTable.time_send, $this->mailTable.subject FROM `$this->mailTable` LEFT JOIN $this->mailEventTable ON $this->mailEventTable.`email_id`=$this->mailTable.id";

        if(empty($_POST)){
            $query  .= " WHERE $where AND $this->mailTable.time_send >= ".strtotime("-7 days");
        }else{
            if(!empty($_POST['s']) || isset($_POST['recipient'])){
                if(isset($_POST['recipient'])){
                    $search  = '%'.$_POST['recipient'].'%';
                }else{
                    $search  = '%'.$_POST['s'].'%';
                }
                
                $query  .= " WHERE $where AND $this->mailTable.recipients LIKE '$search' OR $this->mailTable.subject LIKE '$search'";
            }else{
                if(!empty($_POST['date'])){
                    $maxTime   = strtotime($_POST['date']);
                }elseif(!empty($_POST['date-start'])){
                    $maxTime   = strtotime($_POST['date-start']);
                }else{
                    if(empty($_POST['timespan'])){
                        $timespan   = '7';
                    }else{
                        $timespan   = $_POST['timespan'];
                    }
                    $maxTime   = strtotime("-$timespan days");
                }
                $query  .= " WHERE $where AND $this->mailTable.time_send >= $maxTime";

                if(!empty($_POST['date-end'])){
                    $maxTime    = strtotime($_POST['date-end']);
                    $query  .= " AND $this->mailTable.time_send <= $maxTime";
                }
            }
        }

        if($_POST['type'] != 'link-clicked'){
            $query  .= " GROUP BY $this->mailTable.id";
        }
        $query  .= " ORDER BY $this->mailTable.time_send DESC";

        return $wpdb->get_results($query);
    }

    /**
     * Clear all e-mail tables
     */
    public function clearTables(){
        global $wpdb;

        $wpdb->query("TRUNCATE $this->mailTable");
        $wpdb->query("TRUNCATE $this->mailEventTable");
    }
}
