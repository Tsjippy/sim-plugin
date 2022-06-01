console.log("Home.js loaded");
document.addEventListener("DOMContentLoaded",function() {
	// SHow message 
 	if(location['search'].includes('?message=')){
		var param	= location.search.substr(1).split("&");
		var text 	= decodeURIComponent(param[0].split("=")[1]);
		var type 	= param[1].split("=")[1];

		//remove params again
		window.history.replaceState({}, document.title, window.location.href.split('?')[0]);

		//show the message
		Swal.fire({
			icon: type.toLowerCase(),
			title: type,
			text: text,
			confirmButtonColor: "#bd2919",
		});
	}

	var scrollTop = 0;

	//Make the menu sticky after scroll
	window.onscroll = function() {
		//Small screen value
		if (window.outerWidth < 768){
			var changey = 200;
		//Other value
		}else{
			var changey = 120;
		}
		
		//Change to sticky menu
		if(scrollTop == 0 && document.documentElement.scrollTop > changey){
			scrollTop = document.documentElement.scrollTop;
			document.querySelector('body').classList.add("sticky");
			document.getElementById('page').style['padding-top']= changey+"px";
		//CHange back to normal menu
		}else if(scrollTop > 0 && document.documentElement.scrollTop < changey){
			scrollTop = 0;
			document.querySelector('body').classList.remove("sticky");
			document.getElementById('page').style['padding-top']= '0px';
		}		
	};

	var el=document.querySelector("#welcome-message-button");
	if(el != null){
		el.addEventListener("click", function(){
			//Hide the message
			document.querySelector("#welcome-message").classList.add('hidden');

			var formData = new FormData();
			formData.append('_wpnonce', sim.restNonce);
			fetch(
				sim.baseUrl+'/wp-json/sim/v1/frontpage/hide_welcome', 
				{
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				}
			).catch(err => console.error(err));
		});
	}
});