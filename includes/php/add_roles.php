<?php
namespace SIM;

add_action( 'show_user_profile', __NAMESPACE__.'\extraUserRoles' );
add_action( 'edit_user_profile', __NAMESPACE__.'\extraUserRoles' );
/**
 * Add the possibilty to select multiple roles in the /wp-admin/users.php page
 */
function extraUserRoles( $user ) {
    ?>
    <script>
        var html = `<tr class="user-roles-wrapper">
            <th><label>Role</label><br></th>
            <td>
                <?php
                $wpRoles  = wp_roles();
                foreach($wpRoles->roles as $role=>$name){
                    if(in_array($role, $user->roles)){
                        $checked    = 'checked';
                    }else{
                        $checked    = '';
                    }
                    
                    ?>
                    <label>
                        <input type='checkbox' name='roles[<?php esc_html($role);?>]' value='<?php echo esc_attr($role);?>' <?php echo esc_html($checked);?>>
                        <?php echo esc_html($name['name']);?>
                        <i>
                            <?php echo esc_html(apply_filters('sim_role_description', '', $role));?>
                        </i>
                    </label><br>
                    <?php
                }
            ?>
            </td>
        </tr>`

        document.querySelector('.user-role-wrap').outerHTML = html;
    </script>
    <?php 
}
    
add_action( 'personal_options_update', __NAMESPACE__.'\saveExtraUserRoles');
add_action( 'edit_user_profile_update', __NAMESPACE__.'\saveExtraUserRoles');

/**
 * Saves the selected user roles from the /wp-admin/users.php page
 */
function saveExtraUserRoles( $userId, $newRoles=[] ) {
    $user 		= get_userdata($userId);
    $userRoles 	= $user->roles;
    if(empty($newRoles) && !empty($_POST['roles'])){
        $newRoles = array_map('sanitize_text_field', (array)$_POST['roles']);
	}

    do_action('sim_roles_changed', $user, $newRoles);
    
    //add new roles
    foreach($newRoles as $key => $role){
        //If the role is set, and the user does not have the role currently
        if(!in_array($key, $userRoles)){
            $user->add_role( $key );
        }
    }
    
    foreach($userRoles as $role){
        //If the role is not set, but the user has the role currently
        if(!in_array($role,array_keys($newRoles))){
            $user->remove_role( $role );
        }
    }
}

add_filter('sim_role_description', __NAMESPACE__.'\roleDescriptions', 10, 2);
function roleDescriptions($description, $role){
    switch($role){
        case 'administrator':
            $description    = 'Access to all the administration features';
            break;
        case 'editor':
            $description    = 'Can publish and edit all posts';
            break;
        case 'author':
            $description    = 'Can publish and edit own content';
            break;
        case 'contributor':
            $description    = 'Can write and manage their own posts but cannot publish them';
            break;
        case 'subscriber':
            $description    = 'Can only view';
            break;
        case 'revisor':
            $description    = 'Can submit content for review <strong>-- Default role</strong>';
            break;
    }

    return $description;
}