async function editPostSwitch(event){
	let wrapper	= event.target.closest('main').querySelector('.entry-content');

	let formData    = new FormData();
    let postId      = event.target.dataset.id;
    formData.append('postid', postId);

	const url 		= new URL('https://localhost/simnigeria/add-content/');
	url.searchParams.set('post_id', postId);

	window.history.pushState({}, '', url);

	let loader	= Main.showLoader(event.target, false, 'Requesting form...');

	let response = await FormSubmit.fetchRestApi('frontend_posting/post_edit', formData);

	if(response){
		response.vars.forEach(variable => addVar(variable));

		Object.keys(response.deps).forEach(dep => {
			getScriptUrls(response.deps[dep])
		});

		addScripts();

		wrapper.innerHTML	= response.html;

		// Add nice selects
		wrapper.querySelectorAll('select').forEach(function(select){
			select._niceselect = NiceSelect.bind(select,{searchable: true});
		});
	}

	loader.remove();
}

function afterScriptsLoaded(){
	// Activate tinyMce's again
	document.querySelectorAll('.entry-content .wp-editor-area').forEach(el =>{
		window.tinyMCE.execCommand('mceAddEditor', false, el.id);
	});
}

var urls=[];
function getScriptUrls(script) {
	// First add the dependicies
	Object.keys(script.deps).forEach(dep => {
		getScriptUrls(script.deps[dep]);
	});

	// Then the main one
	//addScript(script.src);
	urls.push(script.src);
}

function addScripts() {
	// Add the first one, wait for it to load
    var s = document.createElement('script');
    s.type = 'text/javascript';
    s.src = urls[0];
    document.getElementsByTagName('head')[0].appendChild(s);

	urls.shift();

	//run again when this script is loaded
	if(urls.length > 0){
		s.addEventListener('load', addScripts);
	}else{
		s.addEventListener('load', afterScriptsLoaded);
	}
    return s;  // to remove it later
}

function addVar(variable) {
    var s = document.createElement('script');
    s.type = 'text/javascript';
    s.textContent = variable;
    document.getElementsByTagName('head')[0].appendChild(s);
    return s;  // to remove it later
}

document.addEventListener("DOMContentLoaded",function() {
	console.log("Edit post.js loaded");
	
	document.querySelectorAll('#page-edit').forEach(el=>{
		el.addEventListener('click', editPostSwitch);
		el.classList.remove('hidden');
	});
});