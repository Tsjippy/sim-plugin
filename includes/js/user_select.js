document.addEventListener('DOMContentLoaded', () => {
	//userid to url
	document.querySelectorAll('[name="user-selection"]').forEach(el=>el.addEventListener('change', function(){
		var currentUrl = window.location.href.replace(location.hash,'');
		if (currentUrl.includes("user-id=")){
			var newUrl = currentUrl.replace(/user-id=[0-9]+/g,"user-id="+this.value);
		}else{
			var newUrl=currentUrl;
			if (currentUrl.includes("?")){
				newUrl += "&";
			}else{
				newUrl += "?";
			}
			newUrl += "user-id="+this.value;
		}
		window.location.href = newUrl+location.hash;
	}));
});