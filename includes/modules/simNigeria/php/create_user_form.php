<?php
namespace SIM\SIMNIGERIA;
use SIM;

// add extra question to the new user form
add_action('sim_after_user_create_form', function(){
    ?>
    <div class="checkbox_options_group formfield">
        <h4>Check what applies to the new user</h4>
        <label>
            <input type="radio" name="visa_info[permit_type][]" class=" formfield formfieldinput" value="accompanying">
            <span class="optionlabel">Accompanying spouse </span>
        </label>
        <br>
        
        <label>
            <input type="radio" name="visa_info[permit_type][]" class=" formfield formfieldinput" value="visa">
            <span class="optionlabel">In country for less then 3 months </span>
        </label>
        <br>
        
        <label>
            <input type="radio" name="visa_info[permit_type][]" class=" formfield formfieldinput" value="greencard">
            <span class="optionlabel">In country for more than 3 months</span>
        </label>
        <br>
        
        <label>
            <input type="radio" name="visa_info[permit_type][]" class=" formfield formfieldinput" value="no">
            <span class="optionlabel">Nigerian passport </span>
        </label>
        <br>
    </div>
    <?php
});

// store the results of the form above
add_action('user_register', function($userId){
    update_user_meta($userId, 'visa_info', $_POST['visa_info']);
});