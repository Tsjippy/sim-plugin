var profile_picture = new function(){
	document.addEventListener('DOMContentLoaded', function() {
		console.log('Dynamic profile_picture forms js loaded');
		FormFunctions.tidyMultiInputs();
		form = document.querySelector('[data-formid="23"]');
		form.querySelectorAll('select, input, textarea').forEach(
			el=>profile_picture.processFields(el)
		);
	});
	var prevEl = '';

	var listener = function(event) {
		var el			= event.target;
		form			= el.closest('form');
		var elName		= el.name;

		if(elName == '' || elName == undefined){
			//el is a nice select
			if(el.closest('.nice-select-dropdown') != null && el.closest('.inputwrapper') != null){
				//find the select element connected to the nice-select
				el.closest('.inputwrapper').querySelectorAll('select').forEach(select=>{
					if(el.dataset.value == select.value){
						el	= select;
						elName = select.name;
					}
				});
			}else{
				return;
			}
		}

		//prevent duplicate event handling
		if(el == prevEl){
			return;
		}
		prevEl = el;

		//clear event prevenion after 100 ms
		setTimeout(function(){ prevEl = ''; }, 100);

		if(elName == 'nextBtn'){
			FormFunctions.nextPrev(1);
		}else if(elName == 'prevBtn'){
			FormFunctions.nextPrev(-1);
		}

		profile_picture.processFields(el);
	};

	window.addEventListener('click', listener);
	window.addEventListener('input', listener);

	this.processFields    = function(el){
		var elName = el.name;
		if(elName == 'profile_picture'){
			var value_1 = FormFunctions.getFieldValue('profile_picture', form, true, 'hgg', true);

			if(value_1 == 'hgg'){
			form.querySelector('[name="hidden_label"]').closest('.inputwrapper').classList.add('hidden');
			}
		}

	};
};