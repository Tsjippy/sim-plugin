<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $wpdb;
	//update all posts where this is attached
	$users = get_users();
    foreach($users as $user){
	}

	/*$theme_mods		= get_option('theme_mods_generatepress-child');

	foreach($theme_mods as $key=>$mod){
		if(strpos($key, 'custom_simnigeria') !== false){
			$theme_mods[str_replace('custom_simnigeria', 'sim', $key)]	= $mod;
			unset($theme_mods[$key]);
		}elseif(strpos($key, 'simnigeria') !== false){
			$theme_mods[str_replace('simnigeria', 'sim', $key)]	= $mod;
			unset($theme_mods[$key]);
		}
	}

	update_option('theme_mods_generatepress-child', $theme_mods); */

	//theme_mods_generatepress-child

	//DROP TABLE `{$wpdb->prefix}sim_forms`, `{$wpdb->prefix}sim_form_elements`, `{$wpdb->prefix}sim_form_submissions`
 	/* try{
		$wpdb->query("DROP TABLE `{$wpdb->prefix}sim_statistics`,`{$wpdb->prefix}sim_schedules`,`{$wpdb->prefix}sim_events`,`{$wpdb->prefix}sim_forms`, `{$wpdb->prefix}sim_form_elements`, `{$wpdb->prefix}sim_form_submissions`, {$wpdb->prefix}sim_form_shortcodes");
	}catch(Exception $e) {
		print_array($e);
	 }

	 try{
		$wpdb->query("RENAME TABLE {$wpdb->prefix}simnigeria_form_shortcodes TO {$wpdb->prefix}sim_form_shortcodes");
		$wpdb->query("RENAME TABLE {$wpdb->prefix}simnigeria_forms TO {$wpdb->prefix}sim_forms");
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}sim_forms` CHANGE `form_name` `name` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;");
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}sim_forms` CHANGE `form_version` `version` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;");
		$wpdb->query("RENAME TABLE {$wpdb->prefix}simnigeria_schedules TO {$wpdb->prefix}sim_schedules");
		$wpdb->query("RENAME TABLE {$wpdb->prefix}simnigeria_statistics TO {$wpdb->prefix}sim_statistics");
		$wpdb->query("RENAME TABLE {$wpdb->prefix}simnigeria_events TO {$wpdb->prefix}sim_events");
	 }catch(Exception $e) {
		print_array($e);
	 }

	$formbuilder = new FORMS\Formbuilder();
	$formbuilder->get_forms();
	$formbuilder->create_db_table();

	foreach($formbuilder->forms as $form){
		$form_elements		= maybe_unserialize($form->form_elements);
		$form_conditions	= maybe_unserialize($form->form_conditions);
		$form_settings		= maybe_unserialize($form->settings);
		$form_emails		= maybe_unserialize($form->emails);

		$id_mapper	= [];

		$priority=1;
		foreach($form_elements as $form_element){
			$form_element['form_id']	= $form->id;

			if(!empty($form_element['labeltext'])){
				$form_element['text']	= $form_element['labeltext'];
			}
			unset($form_element['labeltext']);
			if(!empty($form_element['infotext'])){
				$form_element['text']	= $form_element['infotext'];
			}
			unset($form_element['infotext']);

			unset($form_element['labelwrap']);

			$form_element['priority']	= $priority;
			$priority++;

			if(!empty($form_element['buttontext'])) echo $form_element['buttontext'].'<br>';
			unset($form_element['buttontext']);

			if($form_element['hidden'] == 'hidden') $form_element['hidden']=true;
			if($form_element['required'] == 'required') $form_element['required']=true;
			if($form_element['multiple'] == 'multiple') $form_element['multiple']=true;
			if($form_element['wrap'] == 'wrap') $form_element['wrap']=true;

			$old_id	=	$form_element['id'];
			unset($form_element['id']);

			$result = $wpdb->insert(
				$wpdb->prefix . 'sim_form_elements', 
				$form_element
			);

			$id_mapper[$old_id]	= $wpdb->insert_id;
		}

		//now process the conditions
		foreach($form_elements as $form_element){
			$form_element['form_id']	= $form->id;

			$conditions	= $form_conditions[$form_element['id']];
			if(!empty($conditions)){
				foreach($conditions as $index=>&$condition){
					if($index === 'copyto'){
						foreach($condition as $i=>$v){
							if(is_numeric($v)){
								unset($condition[$i]);
								$id=$id_mapper[$i];
								if(is_numeric($id)){
									$condition[$id]	= $id;
								}
							}
						}
					}else{
						if(is_numeric($condition['property_value'])){
							$id=$id_mapper[$condition['property_value']];
							if(is_numeric($id)){
								//replace with new id
								$condition['property_value']	= $id;
							}
						}

						if(!empty($condition['rules'])){
							foreach($condition['rules'] as &$rule){
								if(is_numeric($rule['conditional_field'])){
									$id=$id_mapper[$rule['conditional_field']];
									if(is_numeric($id)){
										//replace with new id
										$rule['conditional_field']	= $id;
									}
								}
								if(is_numeric($rule['conditional_field_2'])){
									$id=$id_mapper[$rule['conditional_field_2']];
									if(is_numeric($id)){
										//replace with new id
										$rule['conditional_field_2']	= $id;
									}
								}
							}
						}
					}
				}
				//change element ids
				$form_element['conditions']	= serialize($conditions);

				$result = $wpdb->update(
					$wpdb->prefix . 'sim_form_elements', 
					['conditions'	=> serialize($conditions)],
					['id'			=> $id_mapper[$form_element['id']]]
				);
			}
		}

		//Move submissions
		$query							= "SELECT * FROM {$wpdb->prefix}simnigeria_form_submissions_{$form->name} WHERE 1";
		$form_submissions					= $wpdb->get_results($query, ARRAY_A);
		foreach($form_submissions as $submission){
			$submission['form_id'] = $form->id;
			unset($submission['id']);

			$form_results = maybe_unserialize($submission['formresults']);

			if(is_numeric($form_results['id'])){
				$form_results['id']	= $wpdb->get_var( "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE (TABLE_NAME = '{$wpdb->prefix}sim_form_submissions')");
			}

			$submission['formresults']=serialize($form_results);

			$wpdb->insert(
				$wpdb->prefix . 'sim_form_submissions', 
				$submission
			);
		}

		//remap the table column settings to the new ids
		$query							= "SELECT * FROM {$wpdb->prefix}sim_form_shortcodes WHERE form_id={$form->id}";
		$form_results					= $wpdb->get_results($query, ARRAY_A);
		foreach($form_results as $result){
			$column_settings		= unserialize($result['column_settings']);

			foreach($column_settings as $index=>$column_setting){
				if($index == -1) continue;
				$column_settings[$id_mapper[$index]] = $column_setting;
				unset($column_settings[$index]);
			}

			$table_settings		= unserialize($result['table_settings']);

			foreach($table_settings as $index=>&$table_setting){
				if(is_numeric($table_setting)){
					$table_setting	= $id_mapper[$table_setting];
				}
			}

			$wpdb->update(
				$wpdb->prefix . 'sim_form_shortcodes', 
				[
					'column_settings'=>serialize($column_settings),
					'table_settings'=>serialize($table_settings),
				],
				['form_id'=>$form->id]
			);
		}

		foreach($form_settings as &$form_setting){
			if(is_numeric($form_setting)){
				$form_setting	= $id_mapper[$form_setting];
			}
		}

		foreach($form_emails as &$form_email){
			foreach($form_email as &$setting){
				if(is_numeric($setting)){
					$setting	= $id_mapper[$setting];
				}
				if(is_array($setting)){
					foreach($setting as &$set){
						if(is_numeric($set['fieldid'])){
							$set['fieldid']	= $id_mapper[$set['fieldid']];
						}
					}
				}
			}
		}

		$wpdb->update(
			$wpdb->prefix . 'sim_forms', 
			[
				'settings'=>serialize($form_settings),
				'emails'=>serialize($form_emails),
			],
			['id'=>$form->id]
		);
	} */

	return '';
});