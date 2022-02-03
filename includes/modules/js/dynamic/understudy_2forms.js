

document.addEventListener("DOMContentLoaded", function() {
	console.log("Dynamic understudy_2 forms js loaded");
	
	tidy_multi_inputs();
	
	//show first tab
	currentTab = 0; // Current tab is set to be the first tab (0)
	form = document.getElementById('simnigeria_form_understudy_2');
	showTab(currentTab,form); // Display the current tab
	form.querySelectorAll('select, input, textarea').forEach(el=>process_understudy_2_fields(el));
});

window.addEventListener("click", understudy_2_listener);
window.addEventListener("input", understudy_2_listener);

understudy_2_prev_el = '';
function understudy_2_listener(event) {
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
	if(el == understudy_2_prev_el){
		return;
	}
	understudy_2_prev_el = el;
	//clear event prevenion after 200 ms
	setTimeout(function(){ understudy_2_prev_el = ''; }, 200);
	
	if(name == 'nextBtn'){
		nextPrev(1);
	}else if(name == 'prevBtn'){
		nextPrev(-1);
	}

	process_understudy_2_fields(el);
}

function process_understudy_2_fields(el){
	var name = el.name;

}