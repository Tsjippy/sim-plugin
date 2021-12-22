export function send_statistics(){
	var request = new XMLHttpRequest();

	request.open('POST', simnigeria.ajax_url, true);
	
	var formData = new FormData();
	formData.append('action','add_page_view');
	formData.append('url',window.location.href);
	request.send(formData);
}