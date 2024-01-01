export async function fetchRestApi(url, formData='', showErrors=true){
	if(formData == ''){
		formData	= new FormData();
	}
	
	formData.append('_wpnonce', sim.restNonce);
	let result;
	try{
		result = await fetch(
			`${sim.baseUrl}/wp-json/sim/v2/${url}`,
			{
				method: 'POST',
				credentials: 'same-origin',
				body: formData
			}
		);
	}catch(error){
		console.error(error);
	}
	
	let response	= await result.text();
	try{
		let json		= JSON.parse(response);

		if(result.ok){
			return json;
		}else if(json.code == 'rest_cookie_invalid_nonce'){
			Main.displayMessage('Please refresh the page and try again!', 'error');
			return false;
		}else{
			console.error(json);
			Main.displayMessage(json.message, 'error');
			return false;
		}
	}catch(error){
		console.error(error);
		console.error(result);
		console.error(response);

		if(result.ok){
			if(showErrors){
				Main.displayMessage(`Problem parsing the json, refresh the page or try again.`, 'error');
			}
			console.error(`${sim.baseUrl}/wp-json${sim.restApiPrefix}/${url}` );
			console.error(response);
		}else{
			let modal	= `<div id='response-details' class="modal hidden" style='z-index:9999999999;'>`;
				modal	+= `<div class="modal-content" style='max-width: min(1000px,100%);'>`
					modal	+= `<span class="close">&times;</span>`;
					modal	+= response;
				modal	+= `</div>`;
			modal	+= `</div>`;

			document.querySelector('body').insertAdjacentHTML('afterBegin', modal);
			Main.displayMessage(`Error loading url ${sim.baseUrl}/wp-json${sim.restApiPrefix}/${url}<br><button class='button small' id='error-details' onclick='(function(){ document.getElementById("response-details").classList.remove("hidden"); })();'>Details</button><br><div class='hidden response-message'>${response}</div>`, 'error');
		}
		
		return false;
	}
}