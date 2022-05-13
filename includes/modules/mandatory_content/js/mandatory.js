async function markAsRead(event){
    var target = event.target;
	if(target.dataset.postid != undefined){
		main.showLoader(target);
		
		formData = new FormData();
		formData.append('userid',target.dataset.userid);
		formData.append('postid',target.dataset.postid);

        var response    = await formsubmit.fetchRestApi('mandatory_content/mark_as_read', formData);
		
        if(response){
            main.displayMessage(response, 'success', false);
            document.querySelectorAll('.mandatory_content_button, .mandatory_content_warning').forEach(el=>el.remove());
        }
	}
}

async function markAllAsRead(event){
    var target  = event.target;
    var loader  = main.showLoader(target);
    
    formData = new FormData();
    formData.append('userid', target.dataset.userid);

    var response    = await formsubmit.fetchRestApi('mandatory_content/mark_all_as_read', formData);
    
    if(response){
        main.displayMessage(response, 'success', false);
        document.querySelectorAll('.mark-all-as-read').forEach(el=>el.remove());
    }

    loader.remove();
}

document.addEventListener("DOMContentLoaded",function() {
    console.log('Mandatory.js loaded');
    document.querySelectorAll('.mark_as_read').forEach(el=>el.addEventListener('click',markAsRead));

    document.querySelectorAll('.mark-all-as-read').forEach(el=>el.addEventListener('click', markAllAsRead));
})