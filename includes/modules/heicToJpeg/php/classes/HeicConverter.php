<?php
namespace SIM\HEICTOJPEG;
use SIM;

class HeicConverter{
    public function __construct(){
        $path   = MODULE_PATH  . 'lib/vendor/maestroerror/php-heic-to-jpg/bin/heicToJpg';
        if(!is_executable($path)){
            chmod($path, 0555);
        }
    }

    /**
     * Converts heic images to jpg
     *
     * @param   string  $path       The path to the file
     * @param   string  $dest       The path to the destination file. If left empty it will echo the image to the screen
     *
     * @return  boolean|string      Whether the conversion was succesfull or if destination is empty the image blob(url)
     */
    public function convert($path, $dest=''){
        if(is_file($path) && \Maestroerror\HeicToJpg::isHeic($path)) {
            if(empty($dest)){
                $jpg    = \Maestroerror\HeicToJpg::convert($path)->get();

                $size   = getimagesizefromstring($jpg);

                // reduce size, as we do not need super big images
                if($size[0] > 1024 || $size[1] > 1024){
                    $ext        = pathinfo($path, PATHINFO_EXTENSION);
                    $checkPath  = str_replace(".$ext", '.jpeg', $path);
    
                    //store the jpeg so that we dont have to reduce again next time
                    if(!file_exists($checkPath)){
                        $img        = imagecreatefromstring($jpg);
                        $imgResized = imagescale($img , 1024);

                        imagejpeg ($imgResized, $checkPath);
                    }
                    
                    $jpg = file_get_contents($checkPath);
                }
                $base64 = base64_encode($jpg);

                return "data:image/jpeg;base64, $base64";
            }else{
                try{
                    return \Maestroerror\HeicToJpg::convert($path)->saveAs($dest);
                }catch (\Exception $e) {
                    SIM\printArray($e, true);
                    return explode(':', $e->getMessage())[0];
                }
            }
        }else{
            return false;
        }
    }
}
