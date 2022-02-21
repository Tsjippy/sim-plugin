<?php

namespace SIM;

//only load this if the pdf print is enabled
if(!get_module_option('PDF', 'pdf_print')) return;

function create_page_pdf(){
	global $post;
	
	$pdf = new \PDF_HTML();
	$pdf->SetFont('Arial','B',15);
	
	$pdf->skipfirstpage = false;
	
	//Set the title of the document
	$pdf->SetTitle($post->post_title);
	$pdf->AddPage();

	//If recipe
	if($post->post_type == 'recipe'){
		$pdf->print_image(get_the_post_thumbnail_url($post),-1,20,-1,-1,true,true);
		
		//Duration
		$url = plugins_url().'/custom-simnigeria-plugin/includes/pictures/time.png';
		$pdf->print_image($url,10,-1,10,10);
		$pdf->write(10,get_post_meta($post->ID,'time_needed',true).' minutes');
		
		//Serves
		$url = plugins_url().'/custom-simnigeria-plugin/includes/pictures/recipe_serves.png';
		$pdf->print_image($url,55,-1,10,10);
		
		$persons = get_post_meta(get_the_ID(),'serves',true);
		if($persons == 1){
			$person_text = 'person';
		}else{
			$person_text = 'people';
		}
		
		$pdf->write(10,"$persons $person_text");
		
		$pdf->Ln(15);
		$pdf->writeHTML('<b>Ingredients:</b>');
		$pdf->Ln(5);
		$ingredients = explode("\n", trim(get_post_meta(get_the_ID(),'ingredients',true)));
		foreach($ingredients as $ingredient){
			$pdf->write(10,chr(127).' '.$ingredient);
			$pdf->Ln(5);
		}
		
		$pdf->Ln(10);
		$pdf->writeHTML('<b>Instructions:</b>');
	}
	
	$pdf->WriteHTML($post->post_content);
	
	$pdf->printpdf();
}

// Add print to PDF button
add_filter( 'the_content', function ( $content ) {
    //Print to screen if the button is clicked
    if( isset($_POST['print_as_pdf']))	create_page_pdf();
    
    //pdf button
    if(!empty(get_post_meta(get_the_ID(), 'add_print_button',true))){
        $content .= "<div class='print_as_pdf_div' style='float:right;'>";
            $content .= "<form method='post' id='print_as_pdf_form'>";
                $content .= "<button type='submit' class='button' name='print_as_pdf'>Print this page</button>";
            $content .= "</form>";
        $content .= "</div>";
    }
	
	return $content;
});

// Save the option to have a pdf button
add_action('sim_after_post_save', function($post){
    //PDF button
    if(isset($_POST['add_print_button'])){
        //Store pdf button
        if($_POST['add_print_button'] == ''){
            $value = false;
        }else{
            $value = true;
        }
        update_post_meta($post->ID,'add_print_button', $value);
    }
});

add_action('sim_page_specific_fields', function($post_id){
    ?>
	<div id="add_print_button_div" class="frontendform">
        <h4>PDF button</h4>	
        <label>
            <input type='checkbox'  name='add_print_button' value='add_print_button' <?php if(get_post_meta($post_id,'add_print_button',true) != '') echo 'checked';?>>
            Add a 'Save as PDF' button
        </label>
    </div>
    <?php
});