<?php
namespace SIM;

// Define constants
define('PLUGINNAME', 'sim-plugin');
define('SITEURL', site_url( '', 'https' ));
define('SITEURLWITHOUTSCHEME', str_replace(['https://', 'http://'], '', SITEURL));
define('SITENAME', get_bloginfo());
define('INCLUDESURL', plugins_url('includes',__FILE__));
define('PICTURESURL', INCLUDESURL.'/pictures');
define('LOADERIMAGEURL', PICTURESURL.'/loading.gif');
define('PLUGINFOLDER', plugin_dir_path(__FILE__));
define('INCLUDESPATH', PLUGINFOLDER.'includes/');
define('MODULESPATH', INCLUDESPATH.'modules/');
define('PICTURESPATH', INCLUDESPATH.'pictures/');
define('RESTAPIPREFIX', 'sim/v2');
define('DATEFORMAT', get_option('date_format'));
define('TIMEFORMAT', get_option('time_format'));