<?php
namespace SIM\SIMNIGERIA;
use SIM;

add_action('sim-after-download-contacts', function(){
    echo "<H4>Other Contacts Available</h4>";
	echo "For a printable list of SIM Nigeria office staff, as well as missionaries in the larger missionary community within Jos, visit the <a href='https://simnigeria.org/sim-nigeria/office-info/'>Important Contacts page</a>.";
});