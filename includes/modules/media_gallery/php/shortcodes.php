<?php
namespace SIM\MEDIAGALLERY;
use SIM;

add_shortcode('mediagallery', function($atts){
    ob_start();

    $url    = get_permalink( SIM\get_module_option('frontend_posting', 'publish_post_page'));
    ?>
    <div class='mediabuttons'>
        <input type='hidden' id='paged' value=1>

        <a href='<?php echo $url;?>?type=attachment' class="button">Upload new media</a>
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

function loadMedia($amount=20, $page=1, $itemsToSkip=false, $types=['image', 'video', 'audio'], $startIndex=0, $search=''){
    $canEdit            = in_array('editor', wp_get_current_user()->roles);
    $all_mimes          = get_allowed_mime_types();
    $accepted_mimes     = [];
    foreach($all_mimes as $mime){
        $type   = explode('/', $mime)[0];
        if(in_array($type, $types)){
            $accepted_mimes[]   = $mime;
        }
    }

    $args = array(
        'post_status'       => 'inherit',
        'post_type'         => 'attachment',
        'post_mime_type'    => $accepted_mimes,
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

    if(!empty($search)) $args['s']  = $search;
    
    $postslist = new \WP_Query( $args );

    $total  = $postslist->found_posts;

    if ( ! $postslist->have_posts() ) {
        return false;
    }

    ob_start();
    $i  = ($page-1) * $amount-1;
    while ( $postslist->have_posts() ) : $postslist->the_post();
        $i++;

        //skip if needed
        if(is_numeric($itemsToSkip) and $i < $itemsToSkip) continue;

        $id             = get_the_ID();
        $url            = wp_get_attachment_url($id);
        $icon_url       = $url;
        $title          = get_the_title();
        $mime           = get_post_mime_type();
        $type           = explode('/', $mime)[0];
        $vimeo_id       = get_post_meta($id, 'vimeo_id', true);
        $description    = ucfirst(get_the_content());

        /* 
        **** PREVIEW GRID ****
        */

        // Replace icon with VIMEO icon
        if($type == 'video'){
            $icon_url       = apply_filters( 'wp_mime_type_icon', SITEURL."/wp-includes/images/media/video.png", get_post_mime_type(), $id);
        }elseif($type == 'audio'){
            $icon_url       = SITEURL."/wp-includes/images/media/audio.png";
        }else{
            //skip if not existing, and send e-mail
            $path   = get_attached_file($id);
            if(!file_exists($path)){
                SIM\print_array("Check file with id $id");
                wp_mail(get_option('admin_email'), 'Missing file', "Hi Admin,<br><br>A file seems to be missing: $path");
                continue;
            }
        }

        $index=$startIndex+$i;
        ?>
        <div class='cell <?php echo $type;?>' data-index='<?php echo $index;?>'>
                <img src='<?php echo $icon_url;?>' alt='<?php echo $title;?>' class='media-item' width='150' height='120' title='<?php echo $title;?>'>
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
                    echo "<audio loop='loop' controls='controls'>";
                        echo "<source src='$url'/>";
                    echo '</audio>';
                // Vimeo video
                }elseif(is_numeric($vimeo_id) and function_exists('SIM\VIMEO\show_vimeo_video')){
                    echo SIM\VIMEO\show_vimeo_video($vimeo_id);
                }elseif($type=='video'){
                    echo "<video width='320' height='240' controls>";
                        echo "<source src='$url' type='$mime'>";
                    echo '</video>';
                }else{
                    list($width, $height, $type, $attr) = getimagesize(SIM\url_to_path($url));
                    $ratio  = $height/$width;

                    //Center the image vertically
                    echo "<img src='$url' with='100%' height='100vh' style='top: max(0px, calc( 50vh - 50vw * $ratio));'>";
                }
                ?>
            </div>
            
            <?php
            if(empty($video_url)){
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
            }

        /*             if(
                $postslist->post_count == $amount or            // we got as many post as requested
                $i != $page*$amount+$postslist->post_count-1    // or we got less but the current index is not the last one
            ){ */
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

                $download_url = $url;
                if(is_numeric($vimeo_id)){
                    $download_url = WP_CONTENT_URL."/vimeo_files/backup/$vimeo_id.mp4";
                }
                ?>

                <button type="button" class="button small download">
                    Download
                    <a href='<?php echo $download_url;?>' class='hidden' download>Download</a>
                </button>
            </div>
        </div>

        <?php
    endwhile;  

    wp_reset_postdata();

    return ob_get_clean();
}