var accomodation = new function(){
	document.addEventListener('DOMContentLoaded', function() {
		console.log('Dynamic accomodation forms js loaded');
		FormFunctions.tidyMultiInputs();
		form = document.querySelector('[data-formid="19"]');
	});
	var prev_el = '';

	var listener = function(event) {
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
		if(el == prev_el){
			return;
		}
		prev_el = el;

		//clear event prevenion after 100 ms
		setTimeout(function(){ prev_el = ''; }, 100);

		if(el_name == 'nextBtn'){
			FormFunctions.nextPrev(1);
		}else if(el_name == 'prevBtn'){
			FormFunctions.nextPrev(-1);
		}

		accomodation.process_fields(el);
	};

	window.addEventListener('click', listener);
	window.addEventListener('input', listener);

	this.process_fields    = function(el){
		var el_name = el.name;
		if(el_name == 'childcount'){
			var value_1 = FormFunctions.getFieldValue('childcount',true,0,true);

			if(value_1 > 0){
			form.querySelectorAll('[name="ages_of_the_children_label"], [name="childrenage"]').forEach(el=>{
				try{
					//Make sure we only do each wrapper once by adding a temp class
					if(!el.closest('.inputwrapper').matches('.action-processed')){
						el.closest('.inputwrapper').classList.add('action-processed');
						el.closest('.inputwrapper').classList.remove('hidden');
					}
				}catch(e){
					el.classList.remove('hidden');
				}
			});
			document.querySelectorAll('.action-processed').forEach(el=>{el.classList.remove('action-processed')});
			}

			if(value_1 == 0){
			form.querySelectorAll('[name="ages_of_the_children_label"], [name="childrenage"]').forEach(el=>{
				try{
					//Make sure we only do each wrapper once by adding a temp class
					if(!el.closest('.inputwrapper').matches('.action-processed')){
						el.closest('.inputwrapper').classList.add('action-processed');
						el.closest('.inputwrapper').classList.add('hidden');
					}
				}catch(e){
					el.classList.add('hidden');
				}
			});
			document.querySelectorAll('.action-processed').forEach(el=>{el.classList.remove('action-processed')});
			}
		}

	};
};