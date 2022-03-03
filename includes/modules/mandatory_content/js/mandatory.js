function mark_as_read(event){
    var target = event.target;
	if(target.dataset.postid != undefined){
		showLoader(target);
		
		formData = new FormData();
		formData.append('action','mark_page_as_read');
		formData.append('userid',target.dataset.userid);
		formData.append('postid',target.dataset.postid);
		
        fetch(sim.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        }).then(response => response.text())
        .then(response=>{
            display_message(response, 'success', false);
            document.querySelectorAll('.mandatory_content_button, .mandatory_content_warning').forEach(el=>el.remove());
        })
        .catch(err => console.log(err));
	}
}

document.addEventListener("DOMContentLoaded",function() {
    console.log('Mandatory.js loaded');
    document.querySelectorAll('.mark_as_read').forEach(el=>el.addEventListener('click',mark_as_read));
})