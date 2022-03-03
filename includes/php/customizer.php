<?php

//Add to the customizer
/**
 * Add 2 buttons and links to show when a user is logged in
 */
add_action( 'customize_register', function( $wp_customize ) {
	/**
	 * Control and setting for header image
	 */
	$wp_customize->add_setting(
		'sim_header_image', array(
			'default'           => '',
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'sim_header_image', 
			array(
				'label'    => esc_html( 'Header image frontpage'),
				'section'  => 'static_front_page',
				'priority' => 30,
			)
		)
	);
	
	/**
	 * Controls and settings for ministry gallery
	 */
	 
	 /**
	 * Ministry 1
	 */
	
	//Image
	$wp_customize->add_setting(
		'sim_ministry_image_1', array(
			'default'           => '',
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'sim_ministry_image_1', 
			array(
				'label'    => esc_html( 'Picture for the first ministry'),
				'section'  => 'static_front_page',
				'priority' => 40,
			)
		)
	);
	
	//Link
	$wp_customize->add_setting(
		'sim_ministry_link_1', array(
			'default'           => '#',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'sim_ministry_link_1', array(
			'label'    => esc_html( 'Link for the first ministry'),
			'section'  => 'static_front_page',
			'priority' => 40,
		)
	);
	
	//Title
	$wp_customize->add_setting(
		'sim_ministry_title_1', array(
			'default'           => 'Some title',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'sim_ministry_title_1', array(
			'label'    => esc_html( 'Title for the first ministry'),
			'section'  => 'static_front_page',
			'priority' => 40,
		)
	);
	
	//Text
	$wp_customize->add_setting(
		'sim_ministry_text_1', array(
			'default'           => 'Some text',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'sim_ministry_text_1', array(
			'type' => 'textarea',
			'label'    => esc_html( 'Text for the first ministry'),
			'section'  => 'static_front_page',
			'priority' => 40,
		)
	);
	
		 /**
	 * Ministry 2
	 */
	
	//Image
	$wp_customize->add_setting(
		'sim_ministry_image_2', array(
			'default'           => '',
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'sim_ministry_image_2', 
			array(
				'label'    => esc_html( 'Picture for the second ministry'),
				'section'  => 'static_front_page',
				'priority' => 50,
			)
		)
	);
	
	//Link
	$wp_customize->add_setting(
		'sim_ministry_link_2', array(
			'default'           => '#',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'sim_ministry_link_2', array(
			'label'    => esc_html( 'Link for the second ministry'),
			'section'  => 'static_front_page',
			'priority' => 50,
		)
	);
	
	//Title
	$wp_customize->add_setting(
		'sim_ministry_title_2', array(
			'default'           => 'Some title',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'sim_ministry_title_2', array(
			'label'    => esc_html( 'Title for the second ministry'),
			'section'  => 'static_front_page',
			'priority' => 50,
		)
	);
	
	//Text
	$wp_customize->add_setting(
		'sim_ministry_text_2', array(
			'default'           => 'Some text',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'sim_ministry_text_2', array(
			'type' => 'textarea',
			'label'    => esc_html( 'Text for the second ministry'),
			'section'  => 'static_front_page',
			'priority' => 50,
		)
	);
	
		 /**
	 * Ministry 3
	 */
	
	//Image
	$wp_customize->add_setting(
		'sim_ministry_image_3', array(
			'default'           => '',
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'sim_ministry_image_3', 
			array(
				'label'    => esc_html( 'Picture for the third ministry'),
				'section'  => 'static_front_page',
				'priority' => 60,
			)
		)
	);
	
	//Link
	$wp_customize->add_setting(
		'sim_ministry_link_3', array(
			'default'           => '#',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'sim_ministry_link_3', array(
			'label'    => esc_html( 'Link for the third ministry'),
			'section'  => 'static_front_page',
			'priority' => 60,
		)
	);
	
	//Title
	$wp_customize->add_setting(
		'sim_ministry_title_3', array(
			'default'           => 'Some title',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'sim_ministry_title_3', array(
			'label'    => esc_html( 'Title for the third ministry'),
			'section'  => 'static_front_page',
			'priority' => 60,
		)
	);
	
	//Text
	$wp_customize->add_setting(
		'sim_ministry_text_3', array(
			'default'           => 'Some text',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'sim_ministry_text_3', array(
			'type' => 'textarea',
			'label'    => esc_html( 'Text for the third ministry'),
			'section'  => 'static_front_page',
			'priority' => 60,
		)
	);
		
	//PDF Logo
	$wp_customize->add_setting(
		'pdflogo', array(
			'default'           => '',
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		)
	);
	
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'pdflogo', 
			array(
				'label'    => esc_html( 'Picture used in PDFs as logo'),
				'section'  => 'sim_nigeria',
			)
		)
	);
	
		
	//Travel coordinator signature
	$wp_customize->add_setting(
		'travelsignature', array(
			'default'           => '',
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		)
	);
	
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'travelsignature', 
			array(
				'label'    => esc_html( 'Travel coordinator signature'),
				'section'  => 'sim_nigeria',
			)
		)
	);
});