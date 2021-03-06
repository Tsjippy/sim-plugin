<?php
namespace SIM\MEDIAGALLERY;
use SIM;
use SIM\VIMEO\VimeoApi;

add_shortcode('mediagallery', function(){
    ob_start();

    $url			= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'front_end_post_pages');
    ?>
    <div class='mediabuttons'>
        <input type='hidden' id='paged' value=1>

        <?php
        if($url){
            ?>
            <a href='<?php echo $url;?>?type=attachment' class="button">Upload new media</a>
            <?php
        }
        ?>
        Show:
        <select id='media-amount' class='inline'>
            <option value='10'>10</option>
            <option value='20' selected>20</option>
            <option value='30'>30</option>
            <option value='40'>40</option>
            <option value='50'>50</option>
            <option value='60'>60</option>
            <option value='70'>70</option>
            <option value='80'>80</option>
            <option value='90'>90</option>
            <option value='100'>100</option>
        </select>
        <label>
            <input type='checkbox' name='media_type' class='media-type-selector' value='image' checked>
            Pictures
        </label>
        <label>
            <input type='checkbox' name='media_type' class='media-type-selector' value='video' checked>
            Videos
        </label>
        <label>
            <input type='checkbox' name='media_type' class='media-type-selector' value='audio' checked>
            Audio
        </label>
        <input class="searchtext" type="text" placeholder="Search..">
        <img class='search' src="<?php echo PICTURESURL.'/magnifier.png'?>">
    </div>
    <div id="medialoaderwrapper" class="hidden">
        <img src="<?php echo LOADERIMAGEURL;?>">
        <div>Loading more...</div>
    </div>

    <div class="mediawrapper">
        <?php
        echo loadMedia();
        ?>
    </div>

    <div style='text-align:center; margin-top:20px;'>
        <button id='loadmoremedia' type='button' class='button'>Load more</button>
    </div>
    <?php

    return ob_get_clean();
});

/**
 * Load more media items
 * 
 * @param   int     $amount         The amount to load. Default 20
 * @param   int     $page           The current page we should load(as in skip the first X $amount). Default 1
 * @param   int     $itemsToSkip    The amount of items to skip. Default false for none
 * @param   array   $types          The media items to load. Default image, video and audio
 * @param   int     $startIndex     The index to start loading from. Default 0
 * @param   string  $search         The search words if any.
 * 
 * @return  string                  The html
 */
function loadMedia($amount=20, $page=1, $itemsToSkip=false, $types=['image', 'video', 'audio'], $startIndex=0, $search=''){
    $canEdit            = in_array('editor', wp_get_current_user()->roles);
    $allMimes          = get_allowed_mime_types();
    $acceptedMimes     = [];
    foreach($allMimes as $mime){
        $type   = explode('/', $mime)[0];
        if(in_array($type, $types)){
            $acceptedMimes[]   = $mime;
        }
    }

    $args = array(
        'post_status'       => 'inherit',
        'post_type'         => 'attachment',
        'post_mime_type'    => $acceptedMimes,
        'posts_per_page'    => $amount, 
        'paged'             => $page,
        'meta_query'        => array(
            array(
                'key'     => 'gallery_visibility',
                'value'   => 'show',
                'compare' => '=='
            )
        )    
    );

    if(!empty($search)){
        $args['s']  = $search;
    }
    
    $postsList = new \WP_Query( $args );

    $total  = $postsList->found_posts;

    if ( ! $postsList->have_posts() ) {
        return false;
    }

    ob_start();
    $i  = ($page-1) * $amount-1;
    while ( $postsList->have_posts() ) : $postsList->the_post();
        $i++;

        //skip if needed
        if(is_numeric($itemsToSkip) && $i < $itemsToSkip){
            continue;
        }

        $id             = get_the_ID();
        $url            = wp_get_attachment_url($id);
        $iconUrl        = $url;
        $title          = get_the_title();
        $mime           = get_post_mime_type();
        $type           = explode('/', $mime)[0];
        $description    = ucfirst(get_the_content());
        $attachmentUrl  = get_attachment_link();

        /* 
        **** PREVIEW GRID ****
        */

        // Replace icon with VIMEO icon
        if($type == 'video'){
            $iconUrl       = apply_filters( 'wp_mime_type_icon', SITEURL."/wp-includes/images/media/video.png", get_post_mime_type(), $id);
        }elseif($type == 'audio'){
            $iconUrl       = SITEURL."/wp-includes/images/media/audio.png";
        }else{
            //skip if not existing, and send e-mail
            $path   = get_attached_file($id);
            if(!file_exists($path)){
                SIM\printArray("Check file with id $id");
                wp_mail(get_option('admin_email'), 'Missing file', "Hi Admin,<br><br>A file seems to be missing: $path");
                continue;
            }
        }

        $index  = $startIndex + $i;
        ?>
        <div class='cell <?php echo $type;?>' data-index='<?php echo $index;?>'>
                <img src='<?php echo $iconUrl;?>' alt='<?php echo $title;?>' class='media-item' width='150' height='120' title='<?php echo $title;?>'>
            <?php
            if(!empty($description)){
            ?>
            <div class='media-description hidden'>
                <?php echo $description;?>
            </div>
            <?php
            }
            ?>
        </div>
        <?php

        /* 
        **** FULL SCREEN VIEWS ****
        */
        ?>
        <div class="large-image <?php echo $type;?> hidden" data-index='<?php echo $index;?>'>
            <?php
            //Only show back button if not the first item
            if($i > 1){
                ?>
                <a href="#" class="prevbtn">&#8249;</a>
                <?php
            }
            ?>

            <!-- Close the image -->
            <span class="closebtn">&times;</span>

            <!-- Expanded media -->
            <div class='fullscreen-media-wrapper'>
                <?php
                // Normal media
                if($type == 'audio'){
                    // Start Audio tag
                    $mediaHtml  = "<audio loop='loop' controls='controls'>";
                        $mediaHtml  .=  "<source src='$url'/>";
                    $mediaHtml  .=  '</audio>';
                }elseif($type=='video'){
                    $mediaHtml  =  "<video width='320' height='240' controls>";
                        $mediaHtml  .=  "<source src='$url' type='$mime'>";
                    $mediaHtml  .=  '</video>';
                }else{
                    list($width, $height, $a, $attr) = getimagesize(SIM\urlToPath($url));
                    $ratio  = $height/$width;

                    //Center the image vertically
                    $mediaHtml  =  "<img src='$url' with='100%' height='100vh' style='top: max(0px, calc( 50vh - 50vw * $ratio));'>";
                }

                echo apply_filters('sim_media_gallery_item_html', $mediaHtml, $type, $id);
                ?>
            </div>
            
            <?php
           // if(empty($videoUrl)){
                //only show image text on images
                ?>
                <div id="imgtext">
                    <div class="wrapper">
                        <?php
                        echo $title;
                        ?>
                    </div>
                </div>
                <?php
            //}

            if($i != $total-1){
                ?>
                <a href="#" class="nextbtn">&#8250;</a>
                <?php
            }
            ?>

            <div class="buttonwrapper">
                <?php
                if($canEdit){
                    echo apply_filters('sim-media-edit-link', "<a href='".SITEURL."/wp-admin/upload.php?item=$id' class='button editmedia'>Edit</a>", $id);
                }

                if(!empty($description)){
                    ?>
                    <button type='button' class='button small description' data-description='<?php echo $description;?>' title='<?php echo $description;?>'>Description</button>
                    <?php
                }

                ?>
                    <a class='button small' href="<?php echo $attachmentUrl;?>">Link</a>
                <?php

                $url            = apply_filters('sim_media_gallery_download_url', $url, $id);

                if(file_exists(SIM\urlToPath($url))){
                    $fileName   = apply_filters('sim_media_gallery_download_filename', '', $type, $id);
                    ?>
                    <button type="button" class="button small download">
                        Download
                        <a href='<?php echo $url;?>' class='hidden' download="<?php echo $fileName;?>">Download</a>
                    </button>
                    <?php
                }
                ?>
            </div>
        </div>

        <?php
    endwhile;  

    wp_reset_postdata();

    return ob_get_clean();
}