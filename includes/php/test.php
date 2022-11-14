<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;
    $results				= $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}sim_form_elements` WHERE `nicename`LIKE '%[]%'");

    foreach($results as $result){
        $wpdb->update(
            "{$wpdb->prefix}sim_form_elements",
            ['nicename' => ucfirst(str_replace('[]', '', $result->nicename))],
            array(
                'id'		=> $result->id
            ),
        );
    }

    $results				= $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}sim_form_submissions` WHERE `form_id`=8");

    foreach($results as $result){
        $result->formresults=unserialize($result->formresults);

        if(!is_array($result->formresults['travel'])){
            echo $result->id.'<br>';
        }

        $update     = false;

        if(count($result->formresults['travel']) == 7){
            $result->formresults['travel']  = array_values($result->formresults['travel']);
            unset($result->formresults['travel'][6]);
        }

        $allArchived    = true;
        foreach($result->formresults['travel'] as $index=>&$sub){
            $empty=true;
            foreach($sub as $key=>$s){
                if(empty($s) || $key =='archived'){
                    continue;
                }
                $empty = false;
                break;
            }

            if((!isset($sub['archived']) || !$sub['archived']) && !$empty){
                $allArchived    = false;
            }

            if(isset($sub['archived']) && $sub['archived']){
                $update = true;
                unset($sub['archived']);

                if(!is_array($result->archivedsubs)){
                    $result->archivedsubs   = [$index];
                }else{
                    $result->archivedsubs[] = $index;
                }
            }
        }

        if($update){
            $wpdb->update(
                "{$wpdb->prefix}sim_form_submissions",
                [
                    'formresults' => serialize($result->formresults),
                    'archivedsubs' => serialize($result->archivedsubs),
                    'archived'=>$allArchived
                ],
                array(
                    'id'		=> $result->id
                ),
                array(
                    '%s',
                    '%s',
                    '%d'
                )
            );
        }
    }
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

/* add_action('get_header', function(){
    if(!is_user_logged_in() || !is_admin() || !current_user_can('administrator')){
        wp_die('<h1>On the move</h1><br><img src="https://simnigeria.org/wp-content/uploads/OnTheMove2.jpe" alt="move" width="400" height="150" style="margin-left:auto;margin-right:auto;display:block;"><br>This website is currently on the move to the Netherlands, and will available again soon');
    }
}); */