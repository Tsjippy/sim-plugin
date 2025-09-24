console.log('formsubmit loaded');

import {formReset, submitForm, markComplete, fetchRestApi} from './form_submit_functions.js';
export {formReset, submitForm, markComplete, fetchRestApi};

document.querySelectorAll('form.sim-form-wrapper').forEach(form=>form.addEventListener("submit", markComplete));

// check for unsaved formdata
window.addEventListener("beforeunload", (event) => {
	if(document.querySelector('.loader_wrapper:not(.hidden)') != null){
		return;
	}
	
	// check all pending
	document.querySelectorAll('[data-pending]').forEach(el=>{
		console.log(el);
		console.log(`${el.defaultValue} - ${el.value}`);
		event.preventDefault();
	});

	// check all inputs
	document.querySelectorAll('form.sim-form-wrapper input:not([type=radio], [type=checkbox]), form textarea').forEach(el=>{
		if(el.defaultValue != el.value){
			console.log(el);
			console.log(`${el.defaultValue} - ${el.value}`);
			event.preventDefault();
		}
	});

	// check all checkboxes and radio
	document.querySelectorAll('form.sim-form-wrapper input[type=radio], form input[type=checkbox]').forEach(el=>{
		if(el.defaultChecked != el.checked){
			console.log(el);
			console.log(`${el.defaultChecked} - ${el.checked}`);
			event.preventDefault();
		}
	});

	// check all dropdowns
	document.querySelectorAll('form.sim-form-wrapper select').forEach(el=>{
		if(el.selectedIndex != 0 && el.options[el.selectedIndex] != undefined && !el.options[el.selectedIndex].defaultSelected){
			console.log(el)
			console.log(el.options[el.selectedIndex].defaultSelected);
			event.preventDefault();
		}
	});
});

document.addEventListener('focusout', (ev)=>{
	if(ev.target.matches(`:invalid:not(select)`)){
		ev.target.reportValidity();
	}
})

document.addEventListener('input', (ev)=>{
	if(ev.target.matches(`:invalid`)){
		//ev.target.reportValidity();
	}

	if(ev.target.matches(`.datalistinput.multiple`)){
		// if the value is found in the datalist
		if(ev.target.list != null && ev.target.list.querySelector(`[value="${ev.target.value}"]`) != null){
			doneTyping(ev.target);
		}else{
			// Show the add button
			ev.target.closest(`.multi-text-input-wrapper`).querySelector(`.add-list-selection`).classList.remove('hidden');
		}
	}
});

document.addEventListener('click', (ev)=>{
	if(ev.target.matches('.selectedname')){
		ev.target.closest('.optionwrapper').querySelector(`input[type='text']`).value	= ev.target.closest(`.listselection`).querySelector(`input`).value;
		
		// Show the add button
		ev.target.closest(`.optionwrapper`).querySelector(`.add-list-selection`).classList.remove('hidden');
		
		ev.target.closest('.listselection').remove();

		ev.preventDefault();

		ev.stopPropagation();

		ev.stopImmediatePropagation();
	}
	else if(ev.target.matches(`.remove-list-selection`)){
		ev.target.closest('.listselection').remove();
	}else if(ev.target.matches(`.add-list-selection`)){
		// add button clicked
		doneTyping(ev.target.closest(`.multi-text-input-wrapper`).querySelector(`.datalistinput`));
	}
});

//Add a keyboard listener
document.addEventListener("keyup", function(event){
	if (
		['Enter', 'NumpadEnter'].includes(event.key) && 
		keysPressed.Shift == undefined && 
		document.activeElement.matches('.datalistinput')
	) {
		doneTyping(document.activeElement);
	}

	delete keysPressed[event.key];
});

// Keep track of which keys are pressed
let keysPressed = {};
document.addEventListener('keydown', (event) => {
   keysPressed[event.key] = true;
});

function doneTyping(el) {
	el.closest(`.multi-text-input-wrapper`).querySelector(`.add-list-selection`).classList.add('hidden');

	if(el.value	== ''){
		return;
	}

	let li	 		= document.createElement('li');
	li.classList.add('listselection');

	let html	= `<button type="button" class="small remove-list-selection"><span class='remove-list-selection'>×</span></button>`;

	// find the option in the datalist
	let option, value, text;

	if(el.list != null){
		option	= el.list.querySelector(`[value="${el.value}"]`);
	}

	if(option != null && option.dataset.value != null){
		value	= option.dataset.value;
		text	= el.value
	}else{
		value	= el.value;
		text	= el.value
	}

	html   += `<input type='hidden' name='${el.id}[]' value='${value}'>`;
	html   += `<span class='selectedname'>${text}</span>`

	li.innerHTML	= html;

	el.closest('.optionwrapper').querySelector('.listselectionlist').appendChild(li);

	el.value	= '';
}