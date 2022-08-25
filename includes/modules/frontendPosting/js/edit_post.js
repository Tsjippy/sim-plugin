let editPostSwitch = async function (event){
	let afterScriptsLoaded	= function (){
		tinymce.editors.forEach(ed=>ed.remove());

		// Activate tinyMce's again
		document.querySelectorAll('.entry-content .wp-editor-area').forEach(el =>{
			window.tinyMCE.execCommand('mceAddEditor', false, el.id);
		});

		loader.remove();
	}
	
	let addScripts	= function () {
		let el	= scripts.shift();
	
		// only add if needed
		if(document.getElementById(el.id) == null && el.tagName == 'SCRIPT'){
			var s = document.createElement('script');

			if(el.type == ''){
				s.type	= 'text/javascript';
			}else{
				s.type	= el.type;
			}

			if(el.src != ''){
				s.src 	= el.src;
			}else{
				s.innerHTML	= el.innerHTML;
			}
			
			s.id	= el.id;
			
			document.head.appendChild(s);

			if(scripts.length > 0){
				if(el.src != ''){
					s.addEventListener('load', addScripts);
				}else{
					addScripts();
				}
			}else{
				if(el.src != ''){
					s.addEventListener('load', afterScriptsLoaded);
				}else{
					afterScriptsLoaded();
				}			
			}
		}else if(scripts.length > 0){
			//run again when this script is loaded
			addScripts();
		}else{
			afterScriptsLoaded();
		}
	}

	let scripts;

	let button	= event.target;
	let wrapper	= button.closest('main').querySelector('.entry-content');

	let formData    = new FormData();
    let postId      = button.dataset.id;
    formData.append('postid', postId);

	const url 		= new URL('https://localhost/simnigeria/add-content/');
	url.searchParams.set('post_id', postId);

	window.history.pushState({}, '', url);

	let loader	= Main.showLoader(button, true, 'Requesting form...');

	let response = await FormSubmit.fetchRestApi('frontend_posting/post_edit', formData);

	if(response){
		wrapper.innerHTML	= response.html;

		var temp = document.createElement('div');

		temp.innerHTML =  response.js;

		scripts	= [...temp.children];

		addScripts();

		temp.innerHTML = response.css;
		[...temp.children].forEach(el => {
			document.head.appendChild(el);
		})

		// Add nice selects
		wrapper.querySelectorAll('select').forEach(function(select){
			select._niceselect = NiceSelect.bind(select,{searchable: true});
		});
	}else{
		loader.outerHTML	= button.outerHTML;
	}
}

document.addEventListener("DOMContentLoaded",function() {
	console.log("Edit post.js loaded");
	
	document.querySelectorAll('#page-edit').forEach(el=>{
		el.classList.remove('hidden');
	});
});

document.addEventListener("click",function(ev) {	
	if(ev.target.id == 'page-edit'){
		editPostSwitch(ev);
	}
});