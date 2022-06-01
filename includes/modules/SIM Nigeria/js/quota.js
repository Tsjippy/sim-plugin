function updateDocumentName(target){
	//All characters who are not a digit, all digits, followed by all character gives the numbers
	re = new RegExp('([\D]*)[0-9]{1,}(.*)',"g");
	
	var value				= target.value
	var parent				= target.closest('.clone_div');
	parent.id				= 'quota_document_div_'+value;

	var metaKeyEl			= parent.querySelector('[name="fileupload[metakey]"]');
	metaKeyEl.value			= metaKeyEl.value.replace(re, '$1'+value+'$2');

	//Set the value of the hidden input
	var metaKeyIndexEl 		= parent.querySelector('[name="fileupload[metakey_index]"]');
	metaKeyIndexEl.value	= metaKeyIndexEl.value.replace(re, '$1'+value+'$2');
	
	//Set the new value of the labels
	var labelEl			= parent.querySelector('.quotalabel');
	labelEl.textContent	= labelEl.textContent.replace(re, '$1'+value+'$2');
}

document.addEventListener("DOMContentLoaded",function() {
	console.log('quota.js loaded');
});

document.addEventListener('click', async ev=>{
    var target  = ev.target;

    if(target.name == 'update_visa_documents'){
        var response    = await FormSubmit.submitForm(target, 'sim_nigeria/update_visa_documents');
        if(response){
            main.displayMessage(response, 'success');
        }
    }
});

document.addEventListener('change', ev=>{
	var target = ev.target;
	if(target.classList.contains('quota_count')){
		updateDocumentName(target);
	}
});