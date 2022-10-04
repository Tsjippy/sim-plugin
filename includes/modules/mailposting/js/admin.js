import {copyFormInput, fixNumbering, removeNode} from '../../forms/js/forms.js'

document.addEventListener('click', function(event) {
	let target = event.target;

	console.log(target)
	
	//add element
	if(target.matches('.add')){
		copyFormInput(target.closest(".clone_div"));

		fixNumbering(target.closest('.clone_divs_wrapper'));

		target.remove();
	}
	
	//remove element
	if(target.matches('.remove')){
		//Remove node clicked
		removeNode(target);
	}

	if(target.matches('.posttype')){
		target.closest('.posttype-wrapper').querySelector('.taxonomies-wrapper').classList.remove('hidden');
	}

	if(target.matches('.taxonomy')){
		target.closest('.taxonomy-wrapper').querySelector('.categories-wrapper').classList.remove('hidden');
	}
});