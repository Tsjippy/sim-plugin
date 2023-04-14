<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header(); ?>

	<div id='primary'>
        <style>
            @media (min-width: 991px) {
                #primary:not(:only-child) {
                    width: 70%;
                }
            }

            .content-wrapper{
                margin-top: 10px;
            }
        </style>
		<main>
			<?php
				while ( have_posts() ) :

					the_post();

                    $url	= plugins_url('pictures/media.png', __DIR__);

                    $categories = wp_get_post_terms(
                        get_the_ID(),
                        'attachment_cat',
                        array(
                            'orderby'   => 'name',
                            'order'     => 'ASC',
                            'fields'    => 'id=>name'
                        )
                    );
                    
                    //First loop over the cat to see if any parent cat needs to be removed
                    foreach($categories as $id=>$category){
                        //Get the child categories of this category
                        $children = get_term_children($id, 'attachment_cat');
                        
                        //Loop over the children to see if one of them is also in the cat array
                        foreach($children as $child){
                            if(isset($categories[$child])){
                                unset($categories[$id]);
                                break;
                            }
                        }
                    }

                    $lastKey	 = array_key_last($categories);
                    ?>
                    <div class='media metas'>
                        <?php
                        if(!empty($categories)){
                            ?>
                            <div class='category media meta' style='padding-top:10px;'>
                                <img src='<?php echo $url;?>' alt='category' loading='lazy' class='media_icon'>

                                <?php
                                //now loop over the array to print the categories
                                foreach($categories as $id=>$category){
                                    //Only show the category if all of its subcats are not there
                                    $url        = get_term_link($id);
                                    $category   = ucfirst($category);
                                    echo "<a href='$url'>$category</a>";
                                    
                                    if($id != $lastKey){
                                        echo ', ';
                                    }
                                }
                                ?>
                            </div>
                            <?php
                        }

                        $vimeoId    = get_post_meta(get_the_ID(), 'vimeo_id', true);
                        if(is_numeric($vimeoId)){
                            ?>
                            <div class='vimeo media meta'>
                                <?php
                                $imageUrl   = plugins_url('pictures/vimeo.png', __DIR__);
                                $icon       = "<img src='$imageUrl' alt='vimeo' loading='lazy' class='media_icon'>";
                                echo "<a href='https://vimeo.com/$vimeoId' title='vimeo id'>$icon $vimeoId</a>";
                                ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>

                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <div class="inside-article">
                            <div class="entry-content">
                                <?php
                                the_content();
                                ?>
                            </div>

                            <div class="buttonwrapper">
                                <?php

                                if(!empty($description)){
                                    ?>
                                    <button type='button' class='button small description' data-description='<?php echo base64_encode($description);?>' title='<?php echo strip_tags($title);?>'>Description</button>
                                    <?php
                                }

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
                    </article>
                    <?php

				endwhile;
			?>
		</main>
	</div>

	<?php

    get_sidebar();

	get_footer();