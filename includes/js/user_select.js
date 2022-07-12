//userid to url
document.querySelectorAll('[name="user_selection"]').forEach(el=>el.addEventListener('change',function(){
	var currentUrl = window.location.href.replace(location.hash,'');
	if (currentUrl.includes("userid=")){
		var newUrl = currentUrl.replace(/userid=[0-9]+/g,"userid="+this.value);
	}else{
		var newUrl=currentUrl;
		if (currentUrl.includes("?")){
			newUrl += "&";
		}else{
			newUrl += "?";
		}
		newUrl += "userid="+this.value;
	}
	window.location.href = newUrl+location.hash;
}));