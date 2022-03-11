<?php
namespace SIM\FANCYMAIL;
use SIM;

add_shortcode('email_stats', __NAMESPACE__.'\email_stats');
function email_stats(){
    //Load js
	wp_enqueue_script('sim_table_script');

    global $wpdb;
    $query      =  "SELECT COUNT(`email_id`) AS viewcount, `type`, `time`, ".MAILTABLE.".subject FROM `".MAILEVENTTABLE."` INNER JOIN ".MAILTABLE." ON ".MAILEVENTTABLE.".`email_id`=wp_sim_emails.id GROUP BY `email_id` ORDER BY `time` DESC";
    $results    = $wpdb->get_results($query);

    ob_start();
    ?>
    <h2>E-mail statistics</h2>
    <table class='sim-table'>
        <thead>
            <tr>
                <th>Date send</th>
                <th>Subject</th>
                <th>Viewcount</th>
            </tr>
        </thead>
        <?php
        foreach($results as $result){
            ?>
            <tr>
                <td><?php echo date('d-m-Y', strtotime($result->time));?></td>
                <td><?php echo $result->subject;?></td>
                <td><?php echo $result->viewcount;?></td>
            </tr>
            <?php
        }
        ?>
    </table>
    <?php
    return ob_get_clean();
}