<?php
namespace SIM\USERMANAGEMENT;
use SIM;

// add extra question to the new user form
add_action('sim_after_user_create_form', function(){
    echo "<label>";
        echo '<h4>User roles<span class="required">*</span></h4>';
    echo "</label>";
    echo displayRoles();
});

// store the results of the form above
add_action('sim_approved_user', function($userId){
    update_user_meta($userId, 'visa_info', $_POST['visa_info']);
});