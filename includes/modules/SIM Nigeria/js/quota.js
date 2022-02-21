function updateDocumentName(event){
	var target = event.target;
	if(target.classList.contains('quota_count')){
		//All characters who are not a digit, all digits, followed by all character gives the numbers
		re = new RegExp('([\D]*)[0-9]{1,}(.*)',"g");
		
		var value				= target.value
		var parent				= target.closest('.clone_div');
		parent.id				= 'quota_document_div_'+value;

		var metakey_el			= parent.querySelector('[name="fileupload[metakey]"]');
		metakey_el.value		= metakey_el.value.replace(re, '$1'+value+'$2');

		//Set the value of the hidden input
		var metakey_index_el 	= parent.querySelector('[name="fileupload[metakey_index]"]');
		metakey_index_el.value	= metakey_index_el.value.replace(re, '$1'+value+'$2');
		
		//Set the new value of the labels
		var label_el			= parent.querySelector('.quotalabel');
		label_el.textContent	= label_el.textContent.replace(re, '$1'+value+'$2');
	}
}

document.addEventListener('change',updateDocumentName);

document.addEventListener("DOMContentLoaded",function() {
	console.log('quota.js loaded');
});