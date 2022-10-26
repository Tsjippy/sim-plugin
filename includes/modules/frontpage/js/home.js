console.log("Home.js loaded");
document.addEventListener("DOMContentLoaded",function() {
	// Show message 
 	if(location['search'].includes('?message=')){
		var param	= location.search.substr(1).split("&");
		var text 	= decodeURIComponent(param[0].split("=")[1]);
		var type 	= param[1].split("=")[1];

		//remove params again
		window.history.replaceState({}, document.title, window.location.href.split('?')[0]);

		let options = {
			icon: type.toLowerCase(),
			title: type,
			text: text,
			confirmButtonColor: "#bd2919",
		};

		//show the message
		if(document.fullscreenElement != null){
			options['target']	= document.fullscreenElement;
		}

		Swal.fire(options);
	}

	var scrollTop = 0;

	//Make the menu sticky after scroll
	window.onscroll = function() {
		var changeY;
		//Small screen value
		if (window.outerWidth < 768){
			changeY = 200;
		//Other value
		}else{
			changeY = 120;
		}
		
		//Change to sticky menu
		if(scrollTop == 0 && document.documentElement.scrollTop > changeY){
			scrollTop = document.documentElement.scrollTop;
			document.querySelector('body').classList.add("sticky");
			document.getElementById('page').style['padding-top']= changeY+"px";
		//CHange back to normal menu
		}else if(scrollTop > 0 && document.documentElement.scrollTop < changeY){
			scrollTop = 0;
			document.querySelector('body').classList.remove("sticky");
			document.getElementById('page').style['padding-top']= '0px';
		}		
	};

	let el=document.querySelector("#welcome-message-button");
	if(el != null){
		el.addEventListener("click", function(){
			//Hide the message
			document.querySelector("#welcome-message").classList.add('hidden');

			let formData = new FormData();
			formData.append('_wpnonce', sim.restNonce);
			fetch(
				`${sim.baseUrl}/wp-json${sim.restApiPrefix}/frontpage/hide_welcome`, 
				{
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				}
			).catch(err => console.error(err));
		});
	}
});