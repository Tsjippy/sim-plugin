async function markAsRead(event){
    var target = event.target;
	if(target.dataset.postid != undefined){
		main.showLoader(target);
		
		formData = new FormData();
		formData.append('userid',target.dataset.userid);
		formData.append('postid',target.dataset.postid);

        var response    = await formsubmit.fetchRestApi('mandatory_content/markasread', formData);
		
        if(response){
            main.displayMessage(response, 'success', false);
            document.querySelectorAll('.mandatory_content_button, .mandatory_content_warning').forEach(el=>el.remove());
        }
	}
}

document.addEventListener("DOMContentLoaded",function() {
    console.log('Mandatory.js loaded');
    document.querySelectorAll('.mark_as_read').forEach(el=>el.addEventListener('click',markAsRead));
})