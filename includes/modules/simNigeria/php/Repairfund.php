<?php
namespace SIM\SIMNIGERIA;
use SIM;

//shortcode to make forms
add_shortcode( 'repairfund', function($atts){
    global $wpdb;

    $resetDate              = "10-01"; // October first
    $totalAvailable         = "600";
    $reimbursementFormId    = 21;
    $totalSpent             = 0;
    $year                   = date("Y");
    $exchangeRates          = [];
 
    // if it is before october use the last year
    if(date("m") < 10 ){
        $year--;
    }

    // get all the exchange rates
    $posts = get_posts(
		array(
			'category'  		=> get_cat_ID('Finance'),
			's'					=> "exchange rate",
			'numberposts'		=> -1,
			'search_columns'	=> ['post_title'],
			'sentence'			=> true,
            'date_query'        => array(
                array(
                    'column'    => 'post_date',
                    'after'     => "$year-$resetDate",
                ),
            ),
		)
	);

    foreach($posts as $post){

        // try to get the month from the title
        $month  = date_parse($post->post_title)['month'];
        if(!is_numeric($month)){
            $month  = date("n", strtotime($post->post_date));
        }

        $result = preg_match('/(₦|N)([0-9]{3,}|[0-9]+,[0-9]{3,})/i', $post->post_content, $matches);

        if($result && isset($matches[2])){
            $rate   = str_replace(',', '', $matches[2]);
        }

        $exchangeRates[$month]  = $rate;
    }

    
    $missing    = [];
    // check if we have all months

    if(date("m") > 9){
        $start  = 10;
        $end    = date("m");
    }else{
        $start  = 1;
        $end    = 12;
    }

    for ($i = $start; $i < $end; $i++) {
        if(($i <= date("m") || $i > 9) && !isset($exchangeRates[$i])){
            $missing[]  = $i;
        }
    }

    if(!empty($missing)){
        wp_mail(get_bloginfo('admin_email'), 'Some exchange rates are missing', 'hi,<br>please make sure to set the exchange rates for the following months: '.implode(',', $missing));
    }

    if(!empty($_REQUEST['userid'])){
        $userId = $_REQUEST['userid'];
    }else{
        $userId = get_current_user_id();
    }

    $forms  = new SIM\FORMS\SimForms();

    $forms->getForm($reimbursementFormId);

    $query          = "select * from $forms->submissionTableName where form_id = $reimbursementFormId AND timecreated > '$year-$resetDate 00:00:00'";

    $submissions    = $wpdb->get_results($query);

    foreach($submissions as $submission){
        $input  = maybe_unserialize($submission->formresults);

        if(!empty($input['user_id'])){
            $uId            = $input['user_id'];
        }else{
            $uId            = $submission->userid;
        }

        $submissionMonth    = date('n', strtotime($submission->timecreated));

        if(isset($exchangeRates[$submissionMonth])){
            $exchangeRate       = $exchangeRates[$submissionMonth];
        }elseif(isset($exchangeRates[$submissionMonth+1])){
            $exchangeRate       = $exchangeRates[$submissionMonth+1];
        }elseif(isset($exchangeRates[$submissionMonth-1])){
            $exchangeRate       = $exchangeRates[$submissionMonth-1];
        }else{
            continue;
        }

        // this submission is for the current user and the category is house repair
        if($uId == $userId && $input['category'] == 'House_repair'){
            if($input['currency'][0] == "₦"){
                $amount = $input['amount'] / $exchangeRate;
            }elseif($input['currency'][0] == "€"){
                $amount = 0.9 * $input['amount'];
            }elseif($input['currency'][0] == "$"){
                $amount = $input['amount'];
            }
            $totalSpent += $amount;
        }
    }

    return "$". number_format(round($totalAvailable - $totalSpent, 2),2);
});
