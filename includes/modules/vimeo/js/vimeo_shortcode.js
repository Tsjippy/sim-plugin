function showVimeoIframe(iframe){
	var loaderWrapper	= iframe.closest('.vimeo-wrapper').querySelector('.loaderwrapper');
	if(loaderWrapper != null){
		loaderWrapper.remove();
	}
	
	iframe.style.display='block';
}