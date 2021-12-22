

document.addEventListener("DOMContentLoaded", function() {
	console.log("Dynamic child_generic forms js loaded");
	
	tidy_multi_inputs();
	
	//show first tab
	currentTab = 0; // Current tab is set to be the first tab (0)
	form = document.getElementById('simnigeria_form_child_generic');
	showTab(currentTab,form); // Display the current tab
	form.querySelectorAll('select, input, textarea').forEach(el=>process_child_generic_fields(el));
});

window.addEventListener("click", child_generic_listener);
window.addEventListener("input", child_generic_listener);

child_generic_prev_el = '';
function child_generic_listener(event) {
	var el			= event.target;
	form			= el.closest('form');
	var name		= el.name;
	if(name == '' || name == undefined){
		return;
	}
	
	//prevent duplicate event handling
	if(el == child_generic_prev_el){
		return;
	}
	child_generic_prev_el = el;
	//clear event prevenion after 200 ms
	setTimeout(function(){ child_generic_prev_el = ''; }, 200);
	
	if(name == 'nextBtn'){
		nextPrev(1);
	}else if(name == 'prevBtn'){
		nextPrev(-1);
	}

	process_child_generic_fields(el);
}

function process_child_generic_fields(el){
	var name = el.name;

}