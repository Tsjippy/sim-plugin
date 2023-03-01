<?php
namespace SIM\COMMENTS;
use SIM;

add_action('sim_frontend_post_after_content', function($frontendcontend){
    $allowedPostTypes     = SIM\getModuleOption(MODULE_SLUG, 'posttypes');

    if(in_array($frontendcontend->postType, $allowedPostTypes)){
        $hidden = '';
    }else{
        $hidden = 'hidden';
    }

    if(comments_open($frontendcontend->postId)){
        $checked    = 'checked';
    }else{
        $checked    = '';
    }

    ?>
    <div id="comments" class="property frontendform <?php echo $hidden; echo implode(' ', $allowedPostTypes);?>">
        <h4>Comments</h4>
        <label>
            <input type='checkbox' name='comments' value='allow' <?php echo $checked; ?>>
            Allow comments
        </label>
    </div>
    <?php
});


// Allow comments
add_action('sim_after_post_save', function($post, $frontEndPost){
    if(isset($_POST['comments']) && $_POST['comments'] == 'allow'){
        wp_update_post(
            array(
                'ID'                => $post->ID,
                'comment_status'    => 'open',
            ),
            false,
            false
        );
    }elseif($frontEndPost->update){
        wp_update_post(
            array(
                'ID'                => $post->ID,
                'comment_status'    => 'closed'
            ),
            false,
            false
        );
    }
}, 999, 2);