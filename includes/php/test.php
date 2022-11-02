<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    require_once( __DIR__  . '/../modules/signal/lib/vendor/autoload.php');

    $signal = new SIGNAL\Signal();

    $signal->checkDaemon();

    $output='';
    exec("bash -c 'export DISPLAY=:0.0; DBUS_SESSION_BUS_ADDRESS=unix:path=1001; dbus-send --session --dest=org.asamk.Signal --type=method_call --print-reply /org/asamk/Signal org.asamk.Signal.registerWithCaptcha string:'+2347012102841' bool: string:'signal-recaptcha-v2.6LfBXs0bAAAAAAjkDyyI1Lk5gBAUWfhI_bIyox5W.registration.03AIIukzhZQpf2Sut9by2nOrLcOvjzgOU-lOTH1xwv5xRuUquQl_agJaxvTibW93HwHg_aQLAZ9UiD_s3msz5Plq54Bz-OnSlO8OAfy8JxbI0dhRmjO_N-j1ffCLEO0KhRfXABOxgsQTweM8JTsilx6b0zthEc9P8qc59cDdkYbBG5wDiLAFy-lE2jteXTDzf2ny9jCLRIfJHgqsW8AUlw54PmItuv1HjxgwaC5W56SOAwmA_3bRJCW1F_GiTaf4rPEuw6UuiWuzk8txHP8tnqi57ZbgqQD9upEfFEsKJxMISyAllzstgtljH59t7s7uj2JGQ6h9LbiB154gicRKx9kxUa_aAoi2igeJQVPYby9F_t2xKIgxozXMDSCOTvNuXBzmuXi89A549TYjxk8zLoc2_lrLG7u-NciM68yYwOrqmJAWA-s6RSQ5CJQUvfln0eGQ7YOsM8Uielzxo3z1dN7R_1ih3JU4wYEk2RDriq2wSVlbYC_Zm0tttrfuOMo3HK2J2-LOcUrRSLVqnxD618Q2cBJDO-5dXKpmd4EKDzGKg9gBqT4o_hWgqCfBpWAlIRVVknaA-hz_il9H4KpD8neZj5yQTwAehvYcEZRjwHRMmsPygHblwSocwlsoj3FaCtWiP7sMcKSAjkYZQKV2LFL387FivRZQGrY086GMdZefkLCeZm5HRb6m5NYYISKAShyaUnNndWy4t733MNR4tiF5-elEItg9FDVIt88KHCxwBpnWccMjizqT7oKUcXHWbzzYnBNwVtjKU-Ev-38nlY5jGpAHrF1FNEfGIw7AwEmbZjf73NmHnSJEXny_epDgY0KNpC-lmWxH07bNtNS3VuJiayRjcIAQm9_oGi6niY_SQ88oUwVJZrjK5aNs8L42Qt8lonYT0JheKWsHB4-4vpd7pgh6l5hNeBiz_84oYO_d9NE2x5YqFwTYZ3z9itN-9K2RzqVTSqlf-Va1W4dV4_jn08CmboGzxIishHwRxnibKiCZe_4I49-ckdLcpzL3CiASwszJj21ZxzLmKGt9VCS8I3EmimJAeK7_p9pVuTQC6oZvvv3ZKSTQROdIhPig0V0GAr2I7fl8wrRDrejaN3kgBK68MlH610ZC4_0INL7p029La-2Hv8Nx-Nf-uoHdk-l88eVNfSv7iPi-_RsnxwDa_C9skAqg9btLvtPOSSSPkMbtON8qUwTC9galsh4YQGhm5qUdk-UvKPP-gJ_kuF1HpAMZ2eGPSSd1Qmz3hqR0lvSeuNE4T0Dp4''", $output);

    printArray($output, true);
  /*   if(!$signal->valid){
        return '<div class="error">'.$signal->error->get_error_message().'</div>';
    } */

    
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );