function show_modal(modal_id){
	var modal = document.getElementById(modal_id+"_modal");
	
	if(modal != null){
		modal.classList.remove('hidden');
	}	
}

function hide_modals(){
	document.querySelectorAll('.modal:not(.hidden)').forEach(modal=>{
		modal.classList.add('hidden');
	});
}

document.addEventListener('click',function(event) {
	var target = event.target;

    //close modal if clicked outside of modal
	if(target.closest('.modal-content') == null && target.closest('.swal2-container') == null && target.tagName=='DIV'){
		hide_modals();
	}
});

//userid to url
document.querySelectorAll('[name="user_selection"]').forEach(el=>el.addEventListener('change',function(){
	var current_url = window.location.href.replace(location.hash,'');
	if (current_url.includes("userid=")){
		new_url = current_url.replace(/userid=[0-9]+/g,"userid="+this.value);
	}else{
		new_url=current_url;
		if (current_url.includes("?")){
			new_url += "&";
		}else{
			new_url += "?";
		}
		new_url += "userid="+this.value;
	}
	window.location.href = new_url+location.hash;
}));