<?php
namespace SIM\FANCYEMAIL;
use SIM;

add_shortcode('email_stats', __NAMESPACE__.'\emailStats');
function emailStats(){
    //Load js
	wp_enqueue_script('sim_table_script');

    $fancyEmail     = new FancyEmail();

    ob_start();
    if(!empty($_POST['clear_email_stat_table'])){
        $fancyEmail->clearTables();
        ?>
        <div class='success'>
            Succesfully cleared the e-mail statistics.
        </div>
        <?php
    }

    $results        = $fancyEmail->getEmailStatistics();
    $recipients     = [];

    // create an array of unique recipient e-mail addresses
    foreach($results as $result){
        foreach(explode(',', $result->recipients) as $r){
            if(!in_array($r, $recipients)){
                $recipients[]   = $r;
            }
        }
    }
    ?>
    <script>
        function showdatefields(target){
            document.getElementById('querydates').style.display = 'none';
            document.getElementById('querydates').querySelectorAll('input').forEach(el=>el.value='');

            target.closest('div').querySelector('[name="date"]').style.display = 'none';
            target.closest('div').querySelector('[name="date"]').value      = '';

            if(target.value == 'after'){
                target.closest('div').querySelector('[name="date"]').style.display = '';
            }else if(target.value == 'custom'){
                document.getElementById('querydates').style.display = '';
            }
        }
    </script>
    <h2>E-mail statistics</h2>
    <div class='table-wrapper'>
        <form method="POST" action="">
            <input type="hidden" name="clear_email_stat_table" value="true">
            <button class="button small" id="clear_email_stat_table">Clear e-mail statistics</button>
        </form>
        <form class="tablenav top" method="POST" action="">
			<div class="alignleft">
			    <select name="timespan" class="nonice" onchange="showdatefields(this)">
				    <option value="7">Last 7 days</option>
                    <option value="14" <?php if($_POST['timespan'] == "14"){echo 'selected';}?>>Last 14 days</option>
                    <option value="30" <?php if($_POST['timespan'] == "30"){echo 'selected';}?>>Last 30 days</option>
                    <option value="after" <?php if($_POST['timespan'] == "after"){echo 'selected';}?>>After...</option>
                    <option value="custom" <?php if($_POST['timespan'] == "custom"){echo 'selected';}?>>Custom Date Range</option>
                </select>

			    <input type="date" name="date" class="" value="<?php echo $_POST['date'];?>" <?php if($_POST['timespan'] != "after"){echo 'style="display:none;"';}?>>

                <span id="querydates" <?php if($_POST['timespan'] != "custom"){echo 'style="display:none;"';}?>>
                    Between <input type="date" name="date-start" class="" value="<?php echo $_POST['date-start'];?>" >
                    and <input type="date" name="date-end" class="" value="<?php echo $_POST['date-end'];?>">

                </span>

                <select name="type" class="nonice">
				    <option value="mail-opened">Openend</option>
                    <option value="link-clicked" <?php if($_POST['type'] == "link-clicked"){echo 'selected';}?>>Clicked links</option>
                </select>

                <?php
                if(!empty($recipients)){
                    ?>
                    <select name="recipient" class='inline' placeholder='Select recipient'>
                        <option value='' $selected>---</option>
                        <?php
                        foreach($recipients as $recipient){
                            $selected   = '';
                            if(isset($_POST['recipient']) && $_POST['recipient'] == $recipient){
                                $selected   = 'selected="selected"';
                            }
                            echo "<option value='$recipient' $selected>$recipient</option>" ;
                        }
                        ?>
                    </select>
                    <?php
                }
                ?>

			    <button type="submit" class="button">Filter</button>
		    </div>
		    <p class="search-box">
                <input type="search" name="s" value="<?php echo $_POST['s'];?>">                
		        <input type="submit" id="search-submit" class="button" value="Search Emails" title="Search in subject and recipients">
            </p>
        </form>
        <?php
        if(empty($results)){
            ?>
            <p>There is nothing to show...</p>
            <?php
        }else{
        ?>
        <table class='sim-table'>
            <thead>
                <tr>
                    <th>Date send</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <?php
                    if($_POST['type'] == 'link-clicked'){
                        ?>
                        <th>Url</th>
                        <?php
                    }else{
                        ?>
                        <th>Viewcount</th>
                        <?php
                    }
                    ?>
                </tr>
            </thead>
            <?php
            foreach($results as $result){
                ?>
                <tr>
                    <td>
                        <?php echo date(DATEFORMAT.' '.TIMEFORMAT, $result->time_send);?>
                    </td>
                    <td>
                        <?php echo $result->recipients;?>
                    </td>
                    <td>
                        <?php echo $result->subject;?>
                    </td>
                    <?php
                    if($_POST['type'] == 'link-clicked'){
                        ?>
                        <td>
                            <?php echo $result->url;?>
                        </td>
                        <?php
                    }else{
                        ?>
                        <td>
                            <?php echo $result->viewcount;?>
                        </td>
                        <?php
                    }
                    ?>
                </tr>
                <?php
            }
            ?>
        </table>
        <?php
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}