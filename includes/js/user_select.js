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