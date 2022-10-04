<?php
namespace SIM\FANCYEMAIL;
use SIM;

class FancyEmail{
    function __construct(){
        global $wpdb;

        $this->mailTable        = $wpdb->prefix."sim_emails";
        $this->mailEventTable   = $wpdb->prefix."sim_email_events";
        $this->mailTrackerUrl   = SITEURL."/wp-json/".RESTAPIPREFIX."/mailtracker";
        $this->mailImagesFolder = wp_upload_dir()['basedir']."/email_pictures";
    }

    /**
     * Creates the tables for this module
     */
    function createDbTables(){
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
    function filterMail($args){
        global $wpdb;

        $this->subject      = &$args['subject'];

        $this->recipients   = &$args['to'];
        //Do not send an e-mail when the adres contains .empty, or is localhost or is staging
        if(
            strpos($this->recipients,'.empty') !== false        ||
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
        if(
            strpos(strtolower($this->message), 'kind regards,') === false && 
            strpos(strtolower($this->message), 'cheers,') === false
        ){
            $this->message	.= "<br><br><br>Kind regards,<br><br>".SITENAME;
        }
        
        // Mention that this is an automated message
        $footer_url     = apply_filters('sim_email_footer_url', [
            'url'   => SITEURL,
            'text'  => SITEURL
        ]);

        $url            = $footer_url['url'];
        $text           = str_replace(['https://www.', 'https://', 'http://www.', 'http://'],'', $footer_url['text']);
        $this->footer	= "<span style='font-size:10px'>This is an automated e-mail originating from <a href='$url'>$text</a></span>";
    
        // Convert message to html
        if(strpos($this->message, '<!doctype html>') === false){
            $this->htmlEmail();
        }
        
        return $args;
    }

    /**
     * Removes any obsolete email images
     */
    function cleanUpEmailMessages($emailId){
        $target   = "$this->mailImagesFolder/$emailId/";
        SIM\removeFiles($target);
    }

    /**
     * Replace any private urls to public urls, add mail logging to all links
     * 
     * @param   array   $matches    Matches from a regex
     * 
     * @return  string              Replace html
     */ 
    function checkEmailImages($matches){
        if(empty($matches)){
            return false;
        }

        // Convert to array in case of a pure url
        if(!is_array($matches)){
            $matches    = [$matches, $matches];
        }

        $html	    = $matches[0];
        $url	    = $matches[1];

        // Convert to public url
        if(strpos($url, '/private/') !== false){
            $path       = SIM\urlToPath($url);
            $name       = basename($path);
            $newPath    = "$this->mailImagesFolder/$this->emailId/";

            //create folder for this mailId
            if (!is_dir($newPath)) {
                mkdir($newPath, 0777, true);

                //Schedule a task to delete this folder in 1 month time
                wp_schedule_single_event(strtotime(time(), '+1 minute'), 'clean_up_email_messages_action', [$this->emailId]);
            }
            $newPath   = $newPath.$name;

            // Copy the private picture to the public accesible folder
            copy($path, $newPath);

            $newUrl     = SIM\pathToUrl($newPath);
            $html	    = str_replace($url, $newUrl, $html);
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
    function urlReplace($matches){
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
    function htmlEmail(){
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
                                        ?>
                                        <img src="<?php echo "$this->mailTrackerUrl?mailid=$this->emailId&ver=$this->emailId";?>" alt="." width="1px" height="1px">
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
    function getEmailStatistics(){
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
            if(!empty($_POST['s'])){
                $search  = '%'.$_POST['s'].'%';
                $query  .= " WHERE $where AND $this->mailTable.recipients LIKE '$search' OR $this->mailTable.subject LIKE '$search'";
            }else{
                if(empty($_POST['date'])){
                    if(empty($_POST['timespan'])){
                        $timespan   = '7';
                    }else{
                        $timespan   = $_POST['timespan'];
                    }
                    $maxTime   = strtotime("-$timespan days");
                }else{
                    $maxTime   = strtotime($_POST['date']);
                }
                $query  .= " WHERE $where AND $this->mailTable.time_send >= $maxTime";
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
    function clearTables(){
        global $wpdb;

        $wpdb->query("TRUNCATE $this->mailTable");
        $wpdb->query("TRUNCATE $this->mailEventTable");
    }
}
