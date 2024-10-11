<?php
namespace SIM;

// Define constants
define(__NAMESPACE__ .'\PLUGINNAME', 'sim-plugin');
define('SITEURL', site_url( '', 'https' ));
define('SITEURLWITHOUTSCHEME', str_replace(['https://', 'http://'], '', SITEURL));
define('SITENAME', get_bloginfo());
define(__NAMESPACE__ .'\INCLUDESURL', plugins_url('includes', __FILE__));
define(__NAMESPACE__ .'\PICTURESURL', INCLUDESURL.'/pictures');
define(__NAMESPACE__ .'\LOADERIMAGEURL', PICTURESURL.'/loading.gif');
define(__NAMESPACE__ .'\PLUGINFOLDER', plugin_dir_path(__FILE__));
define(__NAMESPACE__ .'\INCLUDESPATH', PLUGINFOLDER.'includes/');
define(__NAMESPACE__ .'\MODULESPATH', WP_CONTENT_DIR.'/sim-modules/');
define(__NAMESPACE__ .'\PICTURESPATH', INCLUDESPATH.'pictures/');
define('RESTAPIPREFIX', 'sim/v2');
define('DATEFORMAT', get_option('date_format'));
define('TIMEFORMAT', get_option('time_format'));
define(__NAMESPACE__.'\MODULELIST', [
    'banking',
    'bookings',
    'bulkChange',
    'captcha',
    'cloud',
    'comments',
    'contentFilter',
    'defaultPictures',
    'embedPage',
    'events',
    'fancyEmail',
    'forms',
    'frontendPosting',
    'heicToJpeg',
    'locations',
    'login',
    'mailchimp',
    'mailposting',
    'maintenance',
    'mandatory',
    'mediaGallery',
    'pageGallery',
    'pdf',
    'pdftoexcel',
    'prayer',
    'projects',
    'querier',
    'recipes',
    'statistics',
    'trello',
    'userManagement',
    'userPages',
    'welcomeMessage',
    'simNigeria',
    'signal',
    'vimeo',
]);