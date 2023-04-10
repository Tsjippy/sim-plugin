<?php
namespace SIM\MEDIAGALLERY;
use SIM;
use stdClass;

class MediaGallery{
    public $acceptedMimes;
    public $types;
    public $amount;
    public $cats;
    public $rand;
    public $page;
    public $search;
    public $wpQuery;
    public $posts;
    public $total;

    public function __construct($types=['image'], $amount=3, $cats=[], $rand=true, $page=1, $search=''){
        global $wp_query;
        
        $allMimes               = get_allowed_mime_types();
        $this->acceptedMimes    = [];
        $this->types           = $types;
        if(isset($_GET['type']) && in_array($_GET['type'], ['image', 'audio', 'video'])){
            $this->types    = [$_GET['type']];
        }
        foreach($allMimes as $mime){
            $type               = explode('/', $mime)[0];
            if(in_array($type, $this->types)){
                $this->acceptedMimes[]  = $mime;
            }
        }
        $this->amount           = $amount;
        $this->cats             = $cats;
        $this->rand             = $rand;
        $this->page             = $page;
        $this->search           = $search;
        $this->wpQuery          = $wp_query;
        $this->posts            = [];
        $this->total            = 0;

        $this->getMedia();

        wp_enqueue_style('sim_gallery_style');
		wp_enqueue_script('sim_refresh_gallery_script');
    }

    /**
     *
     * Get media from db
     */
    public function getMedia(){
        $args = array(
            'post_status'       => 'inherit',
            'post_type'         => 'attachment',
            'post_mime_type'    => $this->acceptedMimes,
            'posts_per_page'    => $this->amount,
            'meta_query'        => array(
                array(
                    'key'     => 'gallery_visibility',
                    'value'   => 'show',
                    'compare' => '=='
                )
            )
        );

        if($this->page != 1){
            $args['paged']    = $this->page;
        }

        if($this->rand){
            $args['orderby']    = 'rand';
        }


        if(!empty($this->cats) && !in_array(-1, $this->cats)){
            $args['tax_query'] = ['relation' => 'OR'];

            foreach($this->cats as $cat){

                $args['tax_query'][]    = [
                    'taxonomy'  => 'attachment_cat',
                    'field'     => 'slug',
                    'terms'     => $cat
                ];
            }
        }

        if(!empty($this->search)){
            $args['s']  = $this->search;
        }

        $this->wpQuery  = new \WP_Query( $args );

        $this->posts    = $this->wpQuery->posts;

        $this->total    = $this->wpQuery->found_posts;
    }

    /**
     * Function to show a gallery of media items
     *
     * @param	int		$speed			The speed in seconds the media should change. Default 60. -1 for never
     *
     * @return	string					The html
     */
    public function mediaGallery($title, $speed = 60){

        if(empty($this->posts)){
            return '';
        }
        ob_start();

        wp_enqueue_script('sim_page_gallery_script');

        // make sure we only try to display as many posts as available
        $amount	= min(count($this->posts), $this->amount);

        ?>
        <article class="media-gallery-article" data-types='<?php echo json_encode($this->types );?>' data-categories='<?php echo json_encode($this->cats);?>' data-speed='<?php echo $speed;?>'>
            <h3 class="media-gallery-title">
                <?php echo $title;?>
            </h3>
            <div class="row">
                <?php
                while($amount > 0) {
                    $post           = $this->posts[0];
                    unset($this->posts[0]);
                    $this->posts    = array_values($this->posts);
                    $pageUrl	    = get_permalink($post->ID);
                    $title		    = $post->post_title;
                    ?>
                    <div class="media-gallery">
                        <div class="card card-profile card-plain">
                            <div class="col-md-5">
                                <div class="card-image">
                                    <a href='<?php echo $pageUrl;?>'>
                                        <img class='img' src='<?php echo wp_get_attachment_image_url($post->ID);?>' alt='' title='<?php echo $title;?>' loading='lazy'>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="content">
                                    <a href='<?php echo $pageUrl;?>'>
                                        <h4 class='card-title'><?php echo $title;?></h4>
                                        <p class='card-description'><?php echo get_the_excerpt($post->ID);?></p>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    $amount--;
                }
                ?>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    /**
     * Shows a filterable mediagalery
     * People can load more media as they desire
     */
    public function filterableMediaGallery(){
        ob_start();

        $url			= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'front_end_post_pages');

        $categories	= get_categories( array(
            'orderby' 		=> 'name',
            'order'   		=> 'ASC',
            'taxonomy'		=> 'attachment_cat',
            'hide_empty' 	=> false,
        ) );

        $shouldSkip     = SIM\getModuleOption(MODULE_SLUG, 'categories', false);

        foreach($categories as $index=>$category){
            if(in_array($category->slug, $shouldSkip)){
                unset($categories[$index]);
            }
        }

        $categories = apply_filters('sim-media-gallery-categories', $categories);

        ?>
        <div class='mediagallery-wrapper'>
            <h4>Media gallery options</h4>
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
                    <input type='checkbox' name='media_type' class='media-type-selector' value='image'>
                    Pictures
                </label>
                <label>
                    <input type='checkbox' name='media_type' class='media-type-selector' value='video'>
                    Videos
                </label>
                <label>
                    <input type='checkbox' name='media_type' class='media-type-selector' value='audio'>
                    Audio
                </label>
                <input class="searchtext" type="text" placeholder="Search..">
                <img class='search' src="<?php echo PICTURESURL.'/magnifier.png'?>" loading='lazy' alt="magnifier">

                <button id='category-options' class='button small <?php if(!empty($this->cats)){ echo 'hidden'; }?>'>Categories</button>
            </div>

            <div class='media-categories hidden'>
                <div>Categories:<br></div>
                <?php
                foreach($categories as $cat){
                    $checked    = '';
                    if(in_array($cat->term_id, $this->cats)){
                        $checked    = 'checked';
                    }
                    ?>
                    <label>
                        <input type='checkbox' name='media-category' class='media-cat-selector' value='<?php echo $cat->slug;?>' <?php echo $checked;?>>
                        <?php echo $cat->name;?>
                    </label>
                    <?php
                }
                ?>
            </div>

            <div id="medialoaderwrapper" class="hidden">
                <img src="<?php echo LOADERIMAGEURL;?>" loading='lazy' alt=''>
                <div>Loading more...</div>
            </div>

            <div class="mediawrapper">
                <?php
                $mediaHtml  = $this->loadMediaHTML();
                if($mediaHtml){
                    echo $mediaHtml;
                }else{
                    echo "No media found";
                }
                ?>
            </div>

            <?php
            if($mediaHtml && substr_count($mediaHtml, "class='cell") == $this->amount){
                ?>
                <div style='text-align:center; margin-top:20px;'>
                    <button id='loadmoremedia' type='button' class='button'>
                        Load more
                    </button>
                </div><?php
            }
            ?>
        </div>

        <?php

        return ob_get_clean();
    }

    /**
     * Load more media items
     *
     * @param   int     $itemsToSkip    The amount of items to skip. Default false for none
     * @param   int     $startIndex     The index to start loading from. Default 0
     *
     * @return  string                  The html
     */
    public function loadMediaHTML($itemsToSkip=false, $startIndex=0){
        $canEdit           = in_array('editor', wp_get_current_user()->roles);
        $allMimes          = get_allowed_mime_types();
        $acceptedMimes     = [];
        foreach($allMimes as $mime){
            $type   = explode('/', $mime)[0];
            if(in_array($type, $this->acceptedMimes )){
                $acceptedMimes[]   = $mime;
            }
        }

        if ( empty($this->posts) ) {
            return false;
        }

        ob_start();
        $i  = ($this->page-1) * $this->amount-1;
        while ( $this->wpQuery->have_posts() ) : $this->wpQuery->the_post();
            $i++;

            //skip if needed
            if(is_numeric($itemsToSkip) && $i < $itemsToSkip){
                continue;
            }

            $id             = get_the_ID();
            $url            = wp_get_attachment_thumb_url($id);
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
                    $this->amount-=1;
                    SIM\printArray("Check file with id $id");
                    wp_mail(get_option('admin_email'), 'Missing file', "Hi Admin,<br><br>A file is registered in the media gallery but does not exist: $path");
                    continue;
                }
            }

            $index  = $startIndex + $i;
            ?>
            <div class='cell <?php echo $type;?>' data-index='<?php echo $index;?>'>
                <div class='image-wrapper'>
                    <img src='<?php echo $iconUrl;?>' alt='<?php echo $title;?>' loading='lazy' class='media-item' width='150' height='120' title='<?php echo $title;?>'>
                </div>
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
                        list($width, $height) = getimagesize(SIM\urlToPath($url));
                        $ratio  = $height/$width;

                        // Get the url to the full size image
                        $fullUrl    = wp_get_attachment_url($id);
                        //Center the image vertically
                        $mediaHtml  =  "<a href='$fullUrl' class='image'><img src='$url' loading='lazy' with='100%' height='100vh' style='top: max(0px, calc( 50vh - 50vw * $ratio));' data-full='$fullUrl'></a>";
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

                if($i != $this->total-1){
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
                        <button type='button' class='button small description' data-description='<?php echo base64_encode($description);?>' title='<?php echo strip_tags($title);?>'>Description</button>
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
}
