//check internet
export async function hasInternet(){
	if(location.href.includes('localhost')){
		window['online'] = true;
		return window['online'];
	}

	await fetch(location.href).then((result) => {
		window['online'] = true;
	}).catch(e => {
		window['online'] = false;
	});

	return window['online'];
}

export async function waitForInternet(){
	var internet	= await hasInternet();
	if(!internet){
		displayMessage('You have no internet connection, waiting till internet is back...', 'warning', true, true, 5000);

		while(!internet){
			if(window['online']){
				break;
			}

			internet	= await hasInternet();
		}
	}
}