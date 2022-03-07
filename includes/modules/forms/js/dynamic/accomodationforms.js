document.addEventListener("DOMContentLoaded", function() {
    console.log("Dynamic accomodation forms js loaded");

    tidy_multi_inputs();
    form = document.getElementById('sim_form_accomodation');
    
});

window.addEventListener("click", accomodation_listener);
window.addEventListener("input", accomodation_listener);

accomodation_prev_el = '';
function accomodation_listener(event) {
    var el			= event.target;
    form			= el.closest('form');
    var el_name		= el.name;
    if(el_name == '' || el_name == undefined){
        //el is a nice select
        if(el.closest('.nice-select-dropdown') != null && el.closest('.inputwrapper') != null){
            //find the select element connected to the nice-select 
            el.closest('.inputwrapper').querySelectorAll('select').forEach(select=>{
                if(el.dataset.value == select.value){
                    el	= select;
                    el_name = select.name;
                }
            });
        }else{
            return;
        }
    }

    //prevent duplicate event handling
    if(el == accomodation_prev_el){
        return;
    }
    accomodation_prev_el = el;
    //clear event prevenion after 100 ms
    setTimeout(function(){ accomodation_prev_el = ''; }, 100);

    if(el_name == 'nextBtn'){
        nextPrev(1);
    }else if(el_name == 'prevBtn'){
        nextPrev(-1);
    }

    process_accomodation_fields(el);
}

function process_accomodation_fields(el){
    var el_name = el.name;
	if(el_name == 'childcount'){
		var value_1 = get_field_value('childcount',true,0);

		if(value_1 > 0){
			form.querySelectorAll('[name="ages_of_the_children_label"], [name="childrenage"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}

		if(value_1 == 0){
			form.querySelectorAll('[name="ages_of_the_children_label"], [name="childrenage"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
	}

}
