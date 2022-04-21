<?php
namespace SIM\PDF;
use SIM;

//only load this if the pdf print is enabled
if(!SIM\get_module_option('PDF', 'pdf_print')) return;

function create_page_pdf(){
	global $post;
	
	$pdf = new PDF_HTML();
	$pdf->SetFont('Arial','B',15);
	
	$pdf->skipfirstpage = false;
	
	//Set the title of the document
	$pdf->SetTitle($post->post_title);
	$pdf->AddPage();

	do_action('sim_before_page_print', $post, $pdf);
	
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

// Add fields to frontend content form
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