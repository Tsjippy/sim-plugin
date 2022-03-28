<?php
namespace SIM\MANDATORY;
use SIM;

add_action('frontend_post_after_content', function($frontendcontend){
    $audience = get_post_meta($frontendcontend->post_id, "audience", true);

    $checked	= '';
    if(isset($audience['normal']) or !is_array($audience)){
        $checked	= 'checked';
    }

    ?>
    <div id="recipients" class="frontendform post page<?php if($frontendcontend->post_type != 'page' and $frontendcontend->post_type != 'post') echo ' hidden'; ?>">
        <h4>Audience</h4>				
        <?php
        if($frontendcontend->post_id != null){
            if(is_array($audience) and count($audience)>0){
                ?>
                <input type="checkbox" name="pagetype[normal]" value="normal" <?php echo $checked; ?>>
                <label for="normal">Normal</label><br>
                <?php
            }
        } 
        ?>
        
        <label>
            <input type="checkbox" name="pagetype[beforearrival]" value="beforearrival" <?php if(isset($audience['beforearrival'])) echo 'checked'; ?>>
            People should read this before arriving in the country (pre-field)
        </label><br>
        <label>
            <input type="checkbox" name="pagetype[afterarrival]" value="afterarrival" <?php if(isset($audience['afterarrival'])) echo 'checked'; ?>>
            People should read this after arriving in the country
        </label><br>
        <label>
            <input type="checkbox" name="pagetype[everyone]" value="everyone" <?php if(isset($audience['everyone'])) echo 'checked'; ?>>
            Everyone must read this no matter how long in the country
        </label><br>
    </div>
    <?php
});