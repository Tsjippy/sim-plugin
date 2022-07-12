<?php
namespace SIM\PDF;
use SIM;

//only load this if the pdf print is enabled
if(!SIM\getModuleOption(MODULE_SLUG, 'pdf_print')){
    return;
}

/**
 * Create a pdf of a post 
 * 
 */
function createPagePdf(){
	global $post;
	
	$pdf = new PdfHtml();
	$pdf->SetFont('Arial', 'B', 15);
	
	$pdf->skipFirstPage = false;
	
	//Set the title of the document
	$pdf->SetTitle($post->post_title);
	$pdf->AddPage();

	do_action('sim_before_page_print', $post, $pdf);
	
	$pdf->WriteHTML($post->post_content);
	
	$pdf->printPdf();
}

// Add print to PDF button
add_filter( 'the_content', function ( $content ) {
    //Print to screen if the button is clicked
    if( isset($_POST['print_as_pdf'])){
        createPagePdf();
    }

    if(!is_main_query()){
        return $content;
    }
    
    //pdf button
    if(!empty(get_post_meta(get_the_ID(), 'add_print_button', true))){
        $content .= "<div class='print_as_pdf_div' style='float:right;'>";
            $content .= "<form method='post' id='print_as_pdf_form'>";
                $content .= "<button type='submit' class='button' name='print_as_pdf'>Print this page</button>";
            $content .= "</form>";
        $content .= "</div>";
    }
	
	return $content;
});

// Add fields to frontend content form
add_action('sim_page_specific_fields', function($postId){
    ?>
	<div id="add_print_button_div" class="frontendform">
        <h4>PDF button</h4>	
        <label>
            <input type='checkbox'  name='add_print_button' value='add_print_button' <?php if(!empty(get_post_meta($postId, 'add_print_button', true))){echo 'checked';}?>>
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
        update_metadata( 'post', $post->ID,'add_print_button', $value);
    }
});