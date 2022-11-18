<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;
/*     $results				= $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}sim_form_elements` WHERE `nicename`LIKE '%[]%'");

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
        $result->archivedsubs   = maybe_unserialize($result->archivedsubs);

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
            if(!is_array($result->archivedsubs)){
                $result->archivedsubs   = [];
            }
            $empty=true;
            if(!empty($sub['date']) && !empty($sub['from']) && !empty($sub['to'])){
                $empty  = false;
            }

            if($empty){
                unset($result->formresults['travel'][$index]);
                $update = true;
                continue;
            }

            if((!isset($sub['archived']) || !$sub['archived']) && !in_array($index, $result->archivedsubs)){
                $date   = strtotime($sub['date']);
                if($date < (time() - 259200)){
                    $sub['archived']    = true;
                }
            }

            if((!isset($sub['archived']) || !$sub['archived']) && !$empty && !in_array($index, $result->archivedsubs)){
                $allArchived    = false;
            }

            if(isset($sub['archived']) && $sub['archived'] && !in_array($index, $result->archivedsubs)){
                $update = true;
                unset($sub['archived']);

                $result->archivedsubs[] = $index;
            }
        }

        if($update){
            echo "Updating $result->id<br>";

            $result->formresults['travel'] = array_values($result->formresults['travel']);

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
    } */
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

