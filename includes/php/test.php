<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test", function ($atts){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    global $wpdb;
    global $Modules;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    $simForms = new FORMS\SimForms();

    $elements   = $wpdb->get_results("SELECT * FROM $simForms->elTableName WHERE `conditions` IS NOT NULL");

        foreach($elements as $element){
            $conditions = maybe_unserialize($element->conditions);

            foreach($conditions as &$condition){
                foreach($condition as $index => $value){
                    if(is_array($value)){
                        foreach($value as &$rule){
                            foreach($rule as $i => $v){
                                $newIndex   = str_replace('_', '-', $i, $c);

                                if($c > 0){
                                    unset($rule[$i]);
                                    $rule[$newIndex]    = $v;
                                }
                            }


                        }
                    }
                    
                    $newIndex   = str_replace('_', '-', $index, $count);

                    if($count > 0){
                        unset($condition[$index]);
                    }

                    $condition[$newIndex]   = $value;
                }
            }

            $element->conditions    = maybe_serialize($conditions);
            $wpdb->update(
                $simForms->elTableName,
                [
                    'conditions'   => $element->conditions,
                ],
                array(
                    'id'		=> $element->id,
                )
            );
        }
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );
