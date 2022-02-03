

document.addEventListener("DOMContentLoaded", function() {
	console.log("Dynamic security_questions forms js loaded");
	
	tidy_multi_inputs();
	
	//show first tab
	currentTab = 0; // Current tab is set to be the first tab (0)
	form = document.getElementById('simnigeria_form_security_questions');
	showTab(currentTab,form); // Display the current tab
	form.querySelectorAll('select, input, textarea').forEach(el=>process_security_questions_fields(el));
});

window.addEventListener("click", security_questions_listener);
window.addEventListener("input", security_questions_listener);

security_questions_prev_el = '';
function security_questions_listener(event) {
	var el			= event.target;
	form			= el.closest('form');
	var name		= el.name;
	if(name == '' || name == undefined){
		if(el.closest('.nice-select-dropdown') != null){
			el.closest('.inputwrapper').querySelectorAll('select').forEach(select=>{
				if(el.dataset.value == select.value){
					el	= select;
					name = select.name;
				}
			});
		}else{
			return;
		}
	}
	
	//prevent duplicate event handling
	if(el == security_questions_prev_el){
		return;
	}
	security_questions_prev_el = el;
	//clear event prevenion after 200 ms
	setTimeout(function(){ security_questions_prev_el = ''; }, 200);
	
	if(name == 'nextBtn'){
		nextPrev(1);
	}else if(name == 'prevBtn'){
		nextPrev(-1);
	}

	process_security_questions_fields(el);
}

function process_security_questions_fields(el){
	var name = el.name;

}