

document.addEventListener("DOMContentLoaded", function() {
	console.log("Dynamic reimbursement forms js loaded");
	
	tidy_multi_inputs();
	
	//show first tab
	currentTab = 0; // Current tab is set to be the first tab (0)
	form = document.getElementById('simnigeria_form_reimbursement');
	showTab(currentTab,form); // Display the current tab
});

window.addEventListener("click", reimbursement_listener);
window.addEventListener("input", reimbursement_listener);

reimbursement_prev_el = '';
function reimbursement_listener(event) {
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
	if(el == reimbursement_prev_el){
		return;
	}
	reimbursement_prev_el = el;
	//clear event prevenion after 200 ms
	setTimeout(function(){ reimbursement_prev_el = ''; }, 200);
	
	if(name == 'nextBtn'){
		nextPrev(1);
	}else if(name == 'prevBtn'){
		nextPrev(-1);
	}

	process_reimbursement_fields(el);
}

function process_reimbursement_fields(el){
	var name = el.name;

}