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
define('PLUGINPATH', plugin_dir_path(__FILE__));
define('INCLUDESPATH', PLUGINPATH.'includes/');
define('MODULESPATH', INCLUDESPATH.'modules/');
define('PICTURESPATH', INCLUDESPATH.'pictures/');