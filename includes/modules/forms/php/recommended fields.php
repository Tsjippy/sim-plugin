<?php
//Shortcode for recommended fields
add_shortcode("recommended_fields",function ($atts){
	$UserID = get_current_user_id();
	$html	= '';
	$recommendation_html = get_recommended_fields($UserID);
	if (!empty($recommendation_html)){
		$html .=  '<div id=recommendations style="margin-top:20px;">';
            $html .=  '<h3 class="frontpage">Recommendations</h3>';
            $html .=  '<p>It would be very helpfull if you could fill in the fields below:</p>';
            $html .=  $recommendation_html;
        $html .=  '</div>';
	}
	
	return $html;
});