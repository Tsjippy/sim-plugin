<?php
namespace SIM\VIMEO;
use SIM;

// upload any local backup to OneDrive
function backupToOnedrive(){
    if(class_exists('\SIM\CLOUD\OnedriveConnector')){
        $oneDrive   = new SIM\CLOUD\OnedriveConnector();

        $vimeoApi   = new VimeoApi();
        
        $files      = glob($vimeoApi->backupDir.'*.mp4');

        foreach($files as $file){
            $vimeoId    = explode('_', basename($file))[0];

            // upload and remove local file if upload succesful
            if($oneDrive->upload( $file, 'vimeo' )){
                unlink($file);
            }
        }
    }
}

// Add any vimeo video's who are backed up to onedrive
add_filter('sim-local-vimeo-files', function($files){
    if(class_exists('\SIM\CLOUD\OnedriveConnector')){
        $oneDrive   = new SIM\CLOUD\OnedriveConnector();

        $files      = $oneDrive->client->getRoot()->getChildren();
    }

    return $files;
});