/* 
		Show vimeo in wp library
*/
//replace preview with vimeo iframe
// copied from \wp-includes\js\media-views.js
wp.media.view.Attachment.Details.prototype.render = function() {
	wp.media.view.Attachment.prototype.render.apply( this, arguments );

	wp.media.mixin.removeAllPlayers();
	this.$( 'audio, video' ).each( function (i, elem) {
		
		var el = wp.media.view.MediaDetails.prepareSrc( elem );

		new window.MediaElementPlayer( el, wp.media.mixin.mejsSettings );

		if(elem.tagName == 'VIDEO'){
			loadVimeoVideo(el);
		}
	} );
} 

//replace preview with vimeo iframe
function loadVimeoVideo(el) {
	var vimeoLink	= el.src;

	if(el.querySelector('source') != null){
		vimeoLink	= el.querySelector('source').src;
	}

	if(vimeoLink == '' || document.querySelector('.wp-media-wrapper.wp-video') == null){
		//try again after a few miliseconds till the el.src is available
		setTimeout(loadVimeoVideo, 20, el);
	//if this is a vimeo item
	}else if(vimeoLink.includes('vimeo.com/')){
		let element	= document.querySelector('.wp-media-wrapper.wp-video');

		element.innerHTML	= `
		<div class="vimeo-wrapper">
			<div class='vimeo-embed-container' style='background:url(${sim.loadingGif}) center center no-repeat;'>
				<iframe src="${vimeoLink}" style="width: 100%; height: 100%;" onload='console.log("loaded")'></iframe>
			</div>
		</div>
		`;

		// show the nice url in the url field
		document.querySelectorAll('.attachment-details-copy-link').forEach(el=>el.value	= vimeoLink.replace('player.', '').replace('/video', '').split('?')[0]);
	}else{
		var el = wp.media.view.MediaDetails.prepareSrc( elem );
		new window.MediaElementPlayer( el, wp.media.mixin.mejsSettings );
	}
}