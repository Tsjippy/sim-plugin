<?php
namespace SIM;

add_action( 'show_user_profile', __NAMESPACE__.'\extraUserRoles' );
add_action( 'edit_user_profile', __NAMESPACE__.'\extraUserRoles' );
function extraUserRoles( $user ) {
    ?>
    <script>
        var html = `<tr class="user-roles-wrapper">
            <th><label>Role</label><br></th>
            <td>
                <?php
                $wp_roles  = wp_roles();
                foreach($wp_roles->roles as $role=>$name){
                    if(in_array($role, $user->roles)){
                        $checked    = 'checked';
                    }else{
                        $checked    = '';
                    }
                    echo "<label>";
                        echo "<input type='checkbox' name='roles[$role]' value='$role' $checked>";
                        echo " {$name['name']}";
                        echo '   <i>'.apply_filters('sim_role_description', '', $role).'</i>';
                    echo "</label><br>";
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
function saveExtraUserRoles( $userId ) {    
    $user 		= get_userdata($userId);
    $userRoles 	= $user->roles;
    $newRoles	= (array)$_POST['roles'];

    do_action('sim_roles_changed', $user, $newRoles);
    
    //add new roles
    foreach($newRoles as $key=>$role){
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

add_filter('sim_role_description', function($description, $role){
    switch($role){
        case 'administrator':
            return 'Access to all the administration features';
        case 'editor':
            return 'Can publish and edit all posts';
        case 'author':
            return 'Can publish and edit own content';
        case 'contributor':
            return 'Can write and manage their own posts but cannot publish them';
        case 'subscriber':
            return 'Can only view';
        case 'author':
            return 'Can publish own content';
    }
    return $description;
}, 10, 2);