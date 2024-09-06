<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_filter('sim-mailchimp-from', function($adresses){
    return array_merge($adresses, [
        'jos.personnel@sim.org'	        => 'jos.personnel',
        'jos.dirassist@sim.org'	        => 'jos.dirassist',
        'jos.director@sim.org'	        => 'jos.director',
        'jos.health@sim.org'	        => 'jos.health',
        'jos.communications@sim.org'	=> 'jos.communications',
    ]);
});

add_action('sim-mailchimp-module-extra-tags', function($settings){
    ?>
    <label>
        Mailchimp TAGs you want to add to missionaries<br>
        <input type="text" name="missionary_tags" value="<?php echo $settings["missionary_tags"]; ?>">
    </label>
    <br>
    <br>
    <label>
        Mailchimp TAGs you want to add to office staff<br>
        <input type="text" name="office_staff_tags" value="<?php echo $settings["office_staff_tags"]; ?>">
    </label>
    <br>
    <br>
    <?php
});