<?php
namespace SIM\PROJECTS;
use SIM;
    
add_filter('sim_frontend_posting_modals', function($types){
    $types[]	= 'project';
    return $types;
});

add_action('sim_frontend_post_before_content', function($frontEndContent){
    $categories = get_categories( array(
        'orderby' 	=> 'name',
        'order'   	=> 'ASC',
        'taxonomy'	=> 'projects',
        'hide_empty'=> false,
    ) );
    
    $frontEndContent->showCategories('project', $categories);
});

add_action('sim_frontend_post_content_title', function ($postType){
    //Property content title
    $class = 'property project';
    if($postType != 'project'){
        $class .= ' hidden';
    }
    
    echo "<h4 class='$class' name='project_content_label'>";
        echo 'Please describe the project';
    echo "</h4>";
});

add_action('sim_after_post_save', function($post, $frontEndPost){
    if($post->post_type != 'project'){
        return;
    }
    
    //store categories
    $frontEndPost->storeCustomCategories($post, 'projects');
    
    //parent
    if(isset($_POST['parent_project'])){
        if(empty($_POST['parent_project'])){
            $parent = 0;
        }else{
            $parent = $_POST['parent_project'];
        }

        wp_update_post(
            array(
                'ID'            => $post->ID,
                'post_parent'   => $parent
            )
        );
    }

    //manager
    if(isset($_POST['manager'])){
        if(empty($_POST['manager'])){
            delete_post_meta($post->ID, 'manager');
        }else{
            //Store manager
            update_metadata( 'post', $post->ID, 'manager', json_encode($_POST['manager']));
        }
    }

    // number
    if(isset($_POST['number'])){
        if(empty($_POST['number'])){
            delete_post_meta($post->ID, 'number');
        }else{
            //Store serves
            update_metadata( 'post', $post->ID, 'number', $_POST['number']);
        }
    }
    
    //url
    if(isset($_POST['url'])){
        if(empty($_POST['url'])){
            delete_post_meta($post->ID, 'url');
        }else{
            //Store serves
            update_metadata( 'post', $post->ID, 'url', $_POST['url']);
        }
    }

    // ministry
    if(isset($_POST['ministry'])){
        if(empty($_POST['ministry'])){
            delete_post_meta($post->ID, 'ministry');
        }else{
            //Store serves
            update_metadata( 'post', $post->ID, 'ministry', $_POST['ministry']);
        }
    }
}, 10, 2);

//add meta data fields
add_action('sim_frontend_post_after_content', function ($frontendcontend){
    //Load js
    wp_enqueue_script('sim_project_script');

    $postId     = $frontendcontend->postId;
    $postName   = $frontendcontend->postName;
    
    $manager    = (array) get_post_meta($postId, 'manager', true);
    $managerId  = '';
    if(isset($manager['userid'])){
        $managerId  = $manager['userid'];
    }

    $managerName  = '';
    if(isset($manager['name'])){
        $managerName  = $manager['name'];
    }

    $managerTel  = '';
    if(isset($manager['tel'])){
        $managerTel  = $manager['tel'];
    }

    $managerEmail  = '';
    if(isset($manager['email'])){
        $managerEmail  = $manager['email'];
    }

    $url        = get_post_meta($postId, 'url', true);

    $number     = get_post_meta($postId, 'number', true);

    //Get all pages describing a ministry
	$ministries = get_posts([
		'post_type'			=> 'location',
		'posts_per_page'	=> -1,
		'post_status'		=> 'publish',
        'orderby'           => 'title',
        'order'             => 'ASC',
		'tax_query' => array(
            array(
                'taxonomy'	=> 'locations',
				'field' => 'term_id',
				'terms' => get_term_by('name', 'Ministries', 'locations')->term_id
            )
        )
	]);

    $selectedMinistry = get_post_meta($postId, 'ministry', true);
    
    ?>
    <style>
        .form-table, .form-table th, .form-table, td{
            border: none;
        }
        .form-table{
            text-align: left;
        }
    </style>
    <div id="project-attributes" class="property project<?php if($postName != 'project'){echo ' hidden';} ?>">
        <div id="parentpage" class="frontendform">
            <h4>Select a parent project</h4>
            <?php
            echo SIM\pageSelect('parent_project', $frontendcontend->postParent, '', ['project'], false);
            ?>
        </div>
        <div class="frontendform">
            <h4>Update warnings</h4>
            <label>
                <input type='checkbox' name='static_content' value='static_content' <?php if(!empty(get_post_meta($postId, 'static_content', true))){echo 'checked';}?>>
                Do not send update warnings for this project
            </label>
        </div>

        <datalist id="users">
            <?php
            foreach(SIM\getUserAccounts(false,true,true) as $user){
                echo "<option data-value='{$user->ID}' value='{$user->display_name}'></option>";
            }
            ?>
        </datalist>

        <fieldset id="project" class="frontendform">
            <legend>
                <h4>Project details</h4>
            </legend>
        
            <table class="form-table">
                <tr>
                    <th><label for="number">Project Number</label></th>
                    <td>
                        <input type='number' name='number' value='<?php echo $number; ?>'>
                    </td>
                </tr>
                <tr>
                    <th><label for="name">Manager name</label></th>
                    <td>
                        <input type='hidden' class='datalistvalue' name='manager[userid]' value='<?php echo $managerId; ?>'>
                        <input type="text" class='formbuilder' name="manager[name]" value="<?php echo $managerName; ?>" list='users'>
                    </td>
                </tr>
                <tr>
                    <th><label for="name">Manager phone number</label></th>
                    <td>
                        <input type="tel" class='formbuilder' name="manager[tel]" value="<?php echo $managerTel; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="name">Manager e-mail</label></th>
                    <td>
                        <input type="text" class='formbuilder' name="manager[email]" value="<?php echo $managerEmail; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="url">Project Url</label></th>
                    <td>
                        <input type='url' class='formbuilder' name='url' value='<?php echo $url; ?>'>
                    </td>
                </tr>
                <tr>
                    <th><label for="ministry">Ministry this project is connected to</label></th>
                    <td>
                        <select name='ministry'>
                            <?php
                            foreach($ministries as $ministry){
                                $selected   = '';
                                if($ministry->ID == $selectedMinistry){
                                    $selected   = 'selected="selected"';
                                }
                                echo "<option value='$ministry->ID' $selected>$ministry->post_title</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
        </fieldset>
    </div>
    <?php
}, 10, 2);